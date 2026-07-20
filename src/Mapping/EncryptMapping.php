<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use ArrayAccess;
use Doctrine\ORM\Encrypt\EncryptQuery;

/** @template-implements ArrayAccess<string, mixed> */
final class EncryptMapping implements ArrayAccess
{
    use ArrayAccessImplementation;

    public string|null $cipher          = null;
    public string|null $keyProvider     = null;
    public EncryptQuery|null $queryType = null;

    public static function fromAttribute(Encrypt $attribute): self
    {
        $mapping = new self();

        $mapping->cipher      = $attribute->cipher;
        $mapping->keyProvider = $attribute->keyProvider;
        $mapping->queryType   = $attribute->queryType;

        return $mapping;
    }

    /**
     * @param array<string, mixed> $mappingArray
     * @phpstan-param array{
     *     cipher?: string|null,
     *     keyProvider?: string|null,
     *     queryType?: EncryptQuery|string|null,
     * } $mappingArray
     */
    public static function fromMappingArray(array $mappingArray): self
    {
        $encryptMapping = new self();

        $encryptMapping->cipher      = $mappingArray['cipher'] ?? null;
        $encryptMapping->keyProvider = $mappingArray['keyProvider'] ?? null;

        $queryType = $mappingArray['queryType'] ?? null;

        if ($queryType !== null && ! $queryType instanceof EncryptQuery) {
            $queryType = EncryptQuery::from($queryType);
        }

        $encryptMapping->queryType = $queryType;

        return $encryptMapping;
    }

    /** @return list<string> */
    public function __sleep(): array
    {
        return ['cipher', 'keyProvider', 'queryType'];
    }
}
