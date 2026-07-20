<?php

declare(strict_types=1);

namespace Doctrine\ORM\Encrypt;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Encrypt\Cipher\BatchableCipher;
use Doctrine\ORM\Encrypt\Cipher\Cipher;
use Doctrine\ORM\Encrypt\Exception\EncryptConfigurationMissing;
use Doctrine\ORM\Encrypt\Exception\UnsupportedEncryptedFieldUsage;
use Doctrine\ORM\Encrypt\KMS\KeyProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\EncryptMapping;
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\ORM\Query\EncryptedQuerySetMapping;
use Doctrine\ORM\Query\ResultSetMapping;

use function array_filter;
use function array_flip;
use function array_intersect_key;
use function array_map;
use function array_replace;
use function array_unique;
use function assert;
use function is_array;
use function is_resource;
use function stream_get_contents;

/** @internal */
final class EncryptHelper
{
    /** @var array<string, true> */
    private array $queryableFieldCache = [];

    /** @var array<class-string, bool> */
    private array $containEncryptClasses = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    /** @param class-string $class */
    public function doesClassContainEncrypt(string $class): bool
    {
        if (isset($this->containEncryptClasses[$class])) {
            return $this->containEncryptClasses[$class];
        }

        $classMetadata = $this->em->getClassMetadata($class);

        if ($classMetadata->encrypt !== null) {
            return $this->containEncryptClasses[$class] = true;
        }

        foreach ($classMetadata->fieldMappings as $fieldMapping) {
            if ($fieldMapping->encrypt !== null) {
                return $this->containEncryptClasses[$class] = true;
            }
        }

        return $this->containEncryptClasses[$class] = false;
    }

    public function doesRsmContainEncrypt(ResultSetMapping $rsm): bool
    {
        foreach (array_unique($rsm->declaringClasses) as $class) {
            if ($this->doesClassContainEncrypt($class)) {
                return true;
            }
        }

        return false;
    }

    /** @param class-string $className */
    public function assertQueryable(string $className, FieldMapping $fieldMapping): void
    {
        $cacheKey = $className . '::' . $fieldMapping->fieldName;

        if (isset($this->queryableFieldCache[$cacheKey])) {
            return;
        }

        assert($fieldMapping->encrypt !== null);

        if ($fieldMapping->encrypt->queryType === null) {
            throw UnsupportedEncryptedFieldUsage::missingQueryType($className, $fieldMapping->fieldName);
        }

        if (! $this->resolveCipher($fieldMapping->encrypt->cipher)->isDeterministic()) {
            throw UnsupportedEncryptedFieldUsage::notDeterministic($className, $fieldMapping->fieldName);
        }

        $this->queryableFieldCache[$cacheKey] = true;
    }

    /** DBAL type name used for binding and DDL: 'text' (String) or 'blob' (Binary). */
    public function getStorageTypeName(EncryptMapping $encrypt): string
    {
        return $this->resolveCipher($encrypt->cipher)->isBinary() ? 'blob' : 'text';
    }

    /**
     * Encrypts the parameters registered in the given EncryptedQuerySetMapping in place,
     * rewriting their binding types to the cipher's storage type.
     *
     * @param array<array-key, mixed> $params
     * @param array<array-key, mixed> $types
     */
    public function encryptParameters(EncryptedQuerySetMapping $mapping, array &$params, array &$types): void
    {
        $batchable        = [];
        $plainTypes       = [];
        $expansionIndexes = [];

        foreach ($mapping->encryptedParameters as $index => [$class, $field]) {
            $fieldMapping   = $this->em->getClassMetadata($class)->fieldMappings[$field];
            $encryptMapping = $fieldMapping->encrypt;
            assert($encryptMapping !== null);

            $keyProvider = $encryptMapping->keyProvider ?? '';
            $cipher      = $encryptMapping->cipher ?? '';

            $batchable[$keyProvider][$cipher][] = $index;

            $storageTypeName = $this->getStorageTypeName($encryptMapping);

            if ($types[$index] instanceof ArrayParameterType) {
                $expansionIndexes[$index] = true;
                $types[$index]            = $storageTypeName === 'blob' ? ArrayParameterType::BINARY : ArrayParameterType::STRING;
            } else {
                $types[$index] = $storageTypeName;
            }

            $plainTypes[$index] = $fieldMapping->type;
        }

        foreach ($batchable as $keyProvider => $cipherIndexes) {
            foreach ($cipherIndexes as $cipher => $indexes) {
                $keys           = array_flip($indexes);
                $filteredParams = array_intersect_key($params, $keys);
                $filteredTypes  = array_intersect_key($plainTypes, $keys);

                $params = array_replace(
                    $params,
                    $this->encryptMany($cipher, $keyProvider, $filteredParams, $filteredTypes, $expansionIndexes),
                );
            }
        }
    }

    /**
     * Decrypts the given row's encrypted columns in place.
     *
     * @param array<string, EncryptMapping> $encryptedFieldMappings encrypted columns, mapped to their EncryptMapping
     * @param array<string, mixed>          $row
     */
    public function decryptRow(array $encryptedFieldMappings, array &$row): void
    {
        $batchable = [];

        foreach ($encryptedFieldMappings as $column => $encryptMapping) {
            $keyProvider = $encryptMapping->keyProvider ?? '';
            $cipher      = $encryptMapping->cipher ?? '';

            $batchable[$keyProvider][$cipher][$column] = $this->getStorageTypeName($encryptMapping);
        }

        foreach ($batchable as $keyProvider => $cipherColumns) {
            foreach ($cipherColumns as $cipher => $columnAndStorageTypes) {
                $filteredRow = array_intersect_key($row, $columnAndStorageTypes);

                $row = array_replace(
                    $row,
                    $this->decryptMany($cipher, $keyProvider, $filteredRow, $columnAndStorageTypes),
                );
            }
        }
    }

    /**
     * @param array<array-key, mixed>  $params
     * @param array<array-key, string> $types
     * @param array<array-key, true>   $expansionIndexes indexes holding array-expansion parameters
     *
     * @return array<array-key, string|array<array-key, string|null>|null>
     */
    private function encryptMany(string $cipherName, string $keyProviderName, array $params, array $types, array $expansionIndexes): array
    {
        $cipher      = $this->resolveCipher($cipherName === '' ? null : $cipherName);
        $keyProvider = $this->resolveKeyProvider($keyProviderName === '' ? null : $keyProviderName);

        $platform      = $this->em->getConnection()->getDatabasePlatform();
        $plainDbValues = [];

        foreach ($params as $index => $param) {
            if (isset($expansionIndexes[$index]) && is_array($param)) {
                $plainDbValues[$index] = array_map(
                    fn (mixed $value): string|null => $this->convertToPlainDbValue($value, $types[$index], $platform),
                    $param,
                );

                continue;
            }

            $plainDbValues[$index] = $this->convertToPlainDbValue($param, $types[$index], $platform);
        }

        if ($cipher instanceof BatchableCipher) {
            return $this->batchEncrypt($cipher, $keyProvider, $plainDbValues);
        }

        $encrypt = static fn (string|null $plainDbValue): string|null => $plainDbValue === null
            ? null
            : $cipher->encrypt($plainDbValue, $keyProvider);

        return array_map(
            static fn (array|string|null $plainDbValue): array|string|null => is_array($plainDbValue)
                ? array_map($encrypt, $plainDbValue)
                : $encrypt($plainDbValue),
            $plainDbValues,
        );
    }

    /**
     * @param array<array-key, string|array<array-key, string|null>|null> $plainDbValues
     *
     * @return array<array-key, string|array<array-key, string|null>|null>
     */
    private function batchEncrypt(BatchableCipher $cipher, KeyProvider $keyProvider, array $plainDbValues): array
    {
        $flatPlaintexts = [];

        foreach ($plainDbValues as $index => $plainDbValue) {
            if (is_array($plainDbValue)) {
                foreach ($plainDbValue as $subIndex => $subValue) {
                    if ($subValue !== null) {
                        $flatPlaintexts[$index . ':' . $subIndex] = $subValue;
                    }
                }

                continue;
            }

            if ($plainDbValue !== null) {
                $flatPlaintexts[$index] = $plainDbValue;
            }
        }

        $flatCiphertexts = $flatPlaintexts === [] ? [] : $cipher->encryptMany($flatPlaintexts, $keyProvider);

        $ciphertexts = [];

        foreach ($plainDbValues as $index => $plainDbValue) {
            if (is_array($plainDbValue)) {
                $ciphertexts[$index] = [];

                foreach ($plainDbValue as $subIndex => $subValue) {
                    $ciphertexts[$index][$subIndex] = $subValue === null ? null : $flatCiphertexts[$index . ':' . $subIndex];
                }

                continue;
            }

            $ciphertexts[$index] = $plainDbValue === null ? null : $flatCiphertexts[$index];
        }

        return $ciphertexts;
    }

    /**
     * @param array<string, mixed>  $row
     * @param array<string, string> $types
     *
     * @return array<string, string|null>
     */
    private function decryptMany(string $cipherName, string $keyProviderName, array $row, array $types): array
    {
        $cipher      = $this->resolveCipher($cipherName === '' ? null : $cipherName);
        $keyProvider = $this->resolveKeyProvider($keyProviderName === '' ? null : $keyProviderName);

        $platform        = $this->em->getConnection()->getDatabasePlatform();
        $encryptedValues = [];

        foreach ($row as $column => $encryptedDbValue) {
            if ($encryptedDbValue === null) {
                $encryptedValues[$column] = null;

                continue;
            }

            $encryptedValue = Type::getType($types[$column])->convertToPHPValue($encryptedDbValue, $platform);

            if (is_resource($encryptedValue)) {
                $encryptedValue = stream_get_contents($encryptedValue);
            }

            $encryptedValues[$column] = $encryptedValue;
        }

        if ($cipher instanceof BatchableCipher) {
            return $this->batchDecrypt($cipher, $keyProvider, $encryptedValues);
        }

        return array_map(
            static fn (string|null $encryptedValue): string|null => $encryptedValue === null
                ? null
                : $cipher->decrypt($encryptedValue, $keyProvider),
            $encryptedValues,
        );
    }

    /**
     * @param array<string, string|null> $encryptedValues
     *
     * @return array<string, string|null>
     */
    private function batchDecrypt(BatchableCipher $cipher, KeyProvider $keyProvider, array $encryptedValues): array
    {
        $flatCiphertexts = array_filter($encryptedValues, static fn (string|null $value): bool => $value !== null);

        $flatPlaintexts = $flatCiphertexts === [] ? [] : $cipher->decryptMany($flatCiphertexts, $keyProvider);

        $plaintexts = [];

        foreach ($encryptedValues as $column => $encryptedValue) {
            $plaintexts[$column] = $encryptedValue === null ? null : $flatPlaintexts[$column];
        }

        return $plaintexts;
    }

    private function convertToPlainDbValue(mixed $value, string $typeName, AbstractPlatform $platform): string|null
    {
        if ($value === null) {
            return null;
        }

        $plainDbValue = Type::getType($typeName)->convertToDatabaseValue($value, $platform);

        if (is_resource($plainDbValue)) {
            $plainDbValue = stream_get_contents($plainDbValue);
        }

        return $plainDbValue === null ? null : (string) $plainDbValue;
    }

    /** @throws EncryptConfigurationMissing */
    private function resolveCipher(string|null $name): Cipher
    {
        $registry = $this->em->getConfiguration()->getCipherRegistry();

        if ($registry === null) {
            throw EncryptConfigurationMissing::forCipherRegistry();
        }

        return $registry->getCipher($name);
    }

    /** @throws EncryptConfigurationMissing */
    private function resolveKeyProvider(string|null $name): KeyProvider
    {
        $registry = $this->em->getConfiguration()->getKeyProviderRegistry();

        if ($registry === null) {
            throw EncryptConfigurationMissing::forKeyProviderRegistry();
        }

        return $registry->getKeyProvider($name);
    }
}
