<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Encrypt;

use DateTimeImmutable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Encrypt\Cipher\Cipher;
use Doctrine\ORM\Encrypt\Cipher\CipherRegistry;
use Doctrine\ORM\Encrypt\EncryptHelper;
use Doctrine\ORM\Encrypt\EncryptQuery;
use Doctrine\ORM\Encrypt\Exception\EncryptConfigurationMissing;
use Doctrine\ORM\Encrypt\Exception\UnsupportedEncryptedFieldUsage;
use Doctrine\ORM\Encrypt\KMS\KeyProvider;
use Doctrine\ORM\Encrypt\KMS\KeyProviderRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\EncryptMapping;
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\ORM\Query\EncryptedQuerySetMapping;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\Models\CMS\CmsUser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(EncryptHelper::class)]
#[CoversClass(EncryptedQuerySetMapping::class)]
final class EncryptHelperTest extends TestCase
{
    /** @return iterable<string, array{bool, string}> */
    public static function storageTypeNameProvider(): iterable
    {
        yield 'binary cipher' => [true, 'blob'];
        yield 'non-binary cipher' => [false, 'text'];
    }

    #[DataProvider('storageTypeNameProvider')]
    public function testGetStorageTypeName(bool $isBinary, string $expected): void
    {
        $fieldEncryptor = new EncryptHelper($this->createEntityManager($this->createCipherRegistry(isBinary: $isBinary)));

        self::assertSame($expected, $fieldEncryptor->getStorageTypeName(new EncryptMapping()));
    }

    public function testGetStorageTypeNameResolvesCipherByMappingCipherName(): void
    {
        $cipher = $this->createStub(Cipher::class);
        $cipher->method('isBinary')->willReturn(true);

        $cipherRegistry = $this->createMock(CipherRegistry::class);
        $cipherRegistry->expects(self::once())
            ->method('getCipher')
            ->with('my-cipher')
            ->willReturn($cipher);

        $fieldEncryptor = new EncryptHelper($this->createEntityManager($cipherRegistry));

        $encryptMapping         = new EncryptMapping();
        $encryptMapping->cipher = 'my-cipher';

        self::assertSame('blob', $fieldEncryptor->getStorageTypeName($encryptMapping));
    }

    public function testGetStorageTypeNameCipherRegistryNotConfigured(): void
    {
        $fieldEncryptor = new EncryptHelper($this->createEntityManager(null));

        $this->expectException(EncryptConfigurationMissing::class);
        $this->expectExceptionMessage('Cipher registry is not configured. Call Configuration::setCipherRegistry() to set it.');

        $fieldEncryptor->getStorageTypeName(new EncryptMapping());
    }

    public function testAssertQueryableThrowsWhenQueryTypeMissing(): void
    {
        $fieldEncryptor = new EncryptHelper($this->createEntityManager($this->createCipherRegistry(isBinary: false)));

        $fieldMapping          = new FieldMapping('string', 'status', 'status');
        $fieldMapping->encrypt = new EncryptMapping();

        $this->expectException(UnsupportedEncryptedFieldUsage::class);
        $this->expectExceptionMessage('CmsUser::$status is encrypted; filtering by an encrypted field without queryType set is not supported.');

        $fieldEncryptor->assertQueryable('CmsUser', $fieldMapping);
    }

    public function testAssertQueryableCipherNotDeterministic(): void
    {
        $cipher = $this->createStub(Cipher::class);
        $cipher->method('isDeterministic')->willReturn(false);

        $cipherRegistry = $this->createStub(CipherRegistry::class);
        $cipherRegistry->method('getCipher')->willReturn($cipher);

        $fieldEncryptor = new EncryptHelper($this->createEntityManager($cipherRegistry));

        $fieldMapping                     = new FieldMapping('string', 'status', 'status');
        $fieldMapping->encrypt            = new EncryptMapping();
        $fieldMapping->encrypt->queryType = EncryptQuery::Equality;

        $this->expectException(UnsupportedEncryptedFieldUsage::class);
        $this->expectExceptionMessage('CmsUser::$status is encrypted; filtering by an encrypted field using non deterministic cipher is not supported.');

        $fieldEncryptor->assertQueryable('CmsUser', $fieldMapping);
    }

    public function testAssertQueryable(): void
    {
        $cipher = $this->createStub(Cipher::class);
        $cipher->method('isDeterministic')->willReturn(true);

        $cipherRegistry = $this->createStub(CipherRegistry::class);
        $cipherRegistry->method('getCipher')->willReturn($cipher);

        $fieldEncryptor = new EncryptHelper($this->createEntityManager($cipherRegistry));

        $fieldMapping                     = new FieldMapping('string', 'status', 'status');
        $fieldMapping->encrypt            = new EncryptMapping();
        $fieldMapping->encrypt->queryType = EncryptQuery::Equality;

        $fieldEncryptor->assertQueryable('CmsUser', $fieldMapping);

        $this->expectNotToPerformAssertions();
    }

    public function testDoesClassContainEncrypt(): void
    {
        $statusMapping          = new FieldMapping('string', 'status', 'status');
        $statusMapping->encrypt = new EncryptMapping();

        $classMetadata                          = new ClassMetadata(CmsUser::class);
        $classMetadata->fieldMappings['status'] = $statusMapping;

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getClassMetadata')->willReturn($classMetadata);

        $encryptHelper = new EncryptHelper($em);

        self::assertTrue($encryptHelper->doesClassContainEncrypt(CmsUser::class));
    }

    public function testDoesClassContainEncryptFalse(): void
    {
        $classMetadata                            = new ClassMetadata(CmsUser::class);
        $classMetadata->fieldMappings['username'] = new FieldMapping('string', 'username', 'username');

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getClassMetadata')->willReturn($classMetadata);

        $encryptHelper = new EncryptHelper($em);

        self::assertFalse($encryptHelper->doesClassContainEncrypt(CmsUser::class));
    }

    public function testDoesRsmContainEncrypt(): void
    {
        $classMetadata          = new ClassMetadata(CmsUser::class);
        $classMetadata->encrypt = new EncryptMapping();

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getClassMetadata')->willReturn($classMetadata);

        $rsm                             = new ResultSetMapping();
        $rsm->declaringClasses['status'] = CmsUser::class;

        $encryptHelper = new EncryptHelper($em);

        self::assertTrue($encryptHelper->doesRsmContainEncrypt($rsm));
    }

    public function testDoesRsmContainEncryptFalse(): void
    {
        $classMetadata = new ClassMetadata(CmsUser::class);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getClassMetadata')->willReturn($classMetadata);

        $rsm                               = new ResultSetMapping();
        $rsm->declaringClasses['username'] = CmsUser::class;

        $encryptHelper = new EncryptHelper($em);

        self::assertFalse($encryptHelper->doesRsmContainEncrypt($rsm));
    }

    public function testEncryptedQuerySetMappingIsEmptyIsTrueUntilFirstParameter(): void
    {
        $querySetMapping = new EncryptedQuerySetMapping();

        self::assertTrue($querySetMapping->isEmpty());

        $querySetMapping->addEncryptedParameter(0, CmsUser::class, 'status');

        self::assertFalse($querySetMapping->isEmpty());
    }

    public function testEncryptedQuerySetMappingAddEncryptedParametersRegistersEachPosition(): void
    {
        $querySetMapping = new EncryptedQuerySetMapping();
        $querySetMapping->addEncryptedParameters([0, 2, 'named'], CmsUser::class, 'status');

        self::assertSame([
            0 => [CmsUser::class, 'status'],
            2 => [CmsUser::class, 'status'],
            'named' => [CmsUser::class, 'status'],
        ], $querySetMapping->getEncryptedParameters());
    }

    public function testEncryptParametersEncryptsOnlyMappedIndexesAndSetsStorageType(): void
    {
        $encryptHelper = new EncryptHelper($this->createEntityManager(
            $this->createEncryptCipherRegistry(),
            $this->createKeyProviderRegistry(),
            $this->createClassMetadata(),
        ));

        $querySetMapping = new EncryptedQuerySetMapping();
        $querySetMapping->addEncryptedParameter(0, CmsUser::class, 'status');

        $params = ['active', 'joe'];
        $types  = ['string', 'string'];

        $encryptHelper->encryptParameters($querySetMapping, $params, $types);

        self::assertSame(['enc(active)', 'joe'], $params);
        self::assertSame(['text', 'string'], $types);
    }

    public function testEncryptParametersSupportsColumnNameKeys(): void
    {
        $encryptHelper = new EncryptHelper($this->createEntityManager(
            $this->createEncryptCipherRegistry(),
            $this->createKeyProviderRegistry(),
            $this->createClassMetadata(),
        ));

        $querySetMapping = new EncryptedQuerySetMapping();
        $querySetMapping->addEncryptedParameter('status', CmsUser::class, 'status');

        $params = ['status' => 'active', 'username' => 'joe'];
        $types  = ['status' => 'string', 'username' => 'string'];

        $encryptHelper->encryptParameters($querySetMapping, $params, $types);

        self::assertSame(['status' => 'enc(active)', 'username' => 'joe'], $params);
        self::assertSame(['status' => 'text', 'username' => 'string'], $types);
    }

    public function testEncryptParametersEncryptsArrayParameterElements(): void
    {
        $encryptHelper = new EncryptHelper($this->createEntityManager(
            $this->createEncryptCipherRegistry(),
            $this->createKeyProviderRegistry(),
            $this->createClassMetadata(),
        ));

        $querySetMapping = new EncryptedQuerySetMapping();
        $querySetMapping->addEncryptedParameter(0, CmsUser::class, 'status');

        $params = [['active', 'inactive'], 'joe'];
        $types  = [ArrayParameterType::STRING, 'string'];

        $encryptHelper->encryptParameters($querySetMapping, $params, $types);

        self::assertSame([['enc(active)', 'enc(inactive)'], 'joe'], $params);
        self::assertSame([ArrayParameterType::STRING, 'string'], $types);
    }

    public function testEncryptParametersSetsArrayParameterTypeToBinaryForBinaryCipher(): void
    {
        $cipher = $this->createStub(Cipher::class);
        $cipher->method('isBinary')->willReturn(true);
        $cipher->method('encrypt')->willReturnCallback(static fn (string $plaintext): string => 'enc(' . $plaintext . ')');

        $cipherRegistry = $this->createStub(CipherRegistry::class);
        $cipherRegistry->method('getCipher')->willReturn($cipher);

        $encryptHelper = new EncryptHelper($this->createEntityManager(
            $cipherRegistry,
            $this->createKeyProviderRegistry(),
            $this->createClassMetadata(),
        ));

        $querySetMapping = new EncryptedQuerySetMapping();
        $querySetMapping->addEncryptedParameter(0, CmsUser::class, 'status');

        $params = [['active', 'inactive'], 'joe'];
        $types  = [ArrayParameterType::STRING, 'string'];

        $encryptHelper->encryptParameters($querySetMapping, $params, $types);

        self::assertSame([ArrayParameterType::BINARY, 'string'], $types);
    }

    public function testEncryptParametersThrowsWhenKeyProviderRegistryNotConfigured(): void
    {
        $encryptHelper = new EncryptHelper($this->createEntityManager(
            $this->createEncryptCipherRegistry(),
            null,
            $this->createClassMetadata(),
        ));

        $querySetMapping = new EncryptedQuerySetMapping();
        $querySetMapping->addEncryptedParameter(0, CmsUser::class, 'status');

        $params = ['active'];
        $types  = ['string'];

        $this->expectException(EncryptConfigurationMissing::class);
        $this->expectExceptionMessage('Key provider registry is not configured. Call Configuration::setKeyProviderRegistry() to set it.');

        $encryptHelper->encryptParameters($querySetMapping, $params, $types);
    }

    public function testEncryptParametersStringifiesNonStringDatabaseValues(): void
    {
        $cipher = $this->createMock(Cipher::class);
        $cipher->method('isBinary')->willReturn(false);
        $cipher->expects(self::exactly(2))
            ->method('encrypt')
            ->with(self::callback(static fn (string $plaintext): bool => $plaintext === '42' || $plaintext === '1'))
            ->willReturnCallback(static fn (string $plaintext): string => 'enc(' . $plaintext . ')');

        $cipherRegistry = $this->createStub(CipherRegistry::class);
        $cipherRegistry->method('getCipher')->willReturn($cipher);

        $classMetadata = new ClassMetadata(CmsUser::class);

        $integerMapping          = new FieldMapping('integer', 'age', 'age');
        $integerMapping->encrypt = new EncryptMapping();

        $booleanMapping          = new FieldMapping('boolean', 'active', 'active');
        $booleanMapping->encrypt = new EncryptMapping();

        $classMetadata->fieldMappings['age']    = $integerMapping;
        $classMetadata->fieldMappings['active'] = $booleanMapping;

        $encryptHelper = new EncryptHelper($this->createEntityManager(
            $cipherRegistry,
            $this->createKeyProviderRegistry(),
            $classMetadata,
        ));

        $querySetMapping = new EncryptedQuerySetMapping();
        $querySetMapping->addEncryptedParameter(0, CmsUser::class, 'age');
        $querySetMapping->addEncryptedParameter(1, CmsUser::class, 'active');

        $params = [42, true];
        $types  = ['integer', 'boolean'];

        $encryptHelper->encryptParameters($querySetMapping, $params, $types);

        self::assertSame(['enc(42)', 'enc(1)'], $params);
    }

    public function testEncryptParametersPassesNullValuesThroughUnencrypted(): void
    {
        $encryptHelper = new EncryptHelper($this->createEntityManager(
            $this->createEncryptCipherRegistry(),
            $this->createKeyProviderRegistry(),
            $this->createClassMetadata(),
        ));

        $querySetMapping = new EncryptedQuerySetMapping();
        $querySetMapping->addEncryptedParameter(0, CmsUser::class, 'status');
        $querySetMapping->addEncryptedParameter(1, CmsUser::class, 'status');

        $params = ['active', null];
        $types  = ['string', 'string'];

        $encryptHelper->encryptParameters($querySetMapping, $params, $types);

        self::assertSame(['enc(active)', null], $params);
        self::assertSame(['text', 'text'], $types);
    }

    public function testEncryptParametersConvertsThroughDbalTypeBeforeEncrypting(): void
    {
        $cipher = $this->createMock(Cipher::class);
        $cipher->method('isBinary')->willReturn(false);
        $cipher->expects(self::once())
            ->method('encrypt')
            ->with('2024-01-15 10:30:00', self::anything())
            ->willReturn('encrypted');

        $cipherRegistry = $this->createStub(CipherRegistry::class);
        $cipherRegistry->method('getCipher')->willReturn($cipher);

        $classMetadata = new ClassMetadata(CmsUser::class);

        $fieldMapping          = new FieldMapping('datetime_immutable', 'createdAt', 'created_at');
        $fieldMapping->encrypt = new EncryptMapping();

        $classMetadata->fieldMappings['createdAt'] = $fieldMapping;

        $encryptHelper = new EncryptHelper($this->createEntityManager(
            $cipherRegistry,
            $this->createKeyProviderRegistry(),
            $classMetadata,
        ));

        $querySetMapping = new EncryptedQuerySetMapping();
        $querySetMapping->addEncryptedParameter(0, CmsUser::class, 'createdAt');

        $params = [new DateTimeImmutable('2024-01-15 10:30:00')];
        $types  = ['datetime_immutable'];

        $encryptHelper->encryptParameters($querySetMapping, $params, $types);

        self::assertSame(['encrypted'], $params);
    }

    public function testEncryptParametersResolvesNullCipherAndKeyProviderNamesToNull(): void
    {
        $cipherRegistry = $this->createMock(CipherRegistry::class);
        $cipherRegistry->expects(self::atLeastOnce())
            ->method('getCipher')
            ->with(null)
            ->willReturn($this->createEncryptCipher());

        $keyProviderRegistry = $this->createMock(KeyProviderRegistry::class);
        $keyProviderRegistry->expects(self::once())
            ->method('getKeyProvider')
            ->with(null)
            ->willReturn($this->createStub(KeyProvider::class));

        $encryptHelper = new EncryptHelper($this->createEntityManager(
            $cipherRegistry,
            $keyProviderRegistry,
            $this->createClassMetadata(),
        ));

        $querySetMapping = new EncryptedQuerySetMapping();
        $querySetMapping->addEncryptedParameter(0, CmsUser::class, 'status');

        $params = ['active'];
        $types  = ['string'];

        $encryptHelper->encryptParameters($querySetMapping, $params, $types);
    }

    public function testEncryptParametersBatchesFieldsSharingCipherAndKeyProvider(): void
    {
        $keyProviderRegistry = $this->createMock(KeyProviderRegistry::class);
        $keyProviderRegistry->expects(self::once())
            ->method('getKeyProvider')
            ->willReturn($this->createStub(KeyProvider::class));

        $classMetadata = $this->createClassMetadata();

        $nameMapping          = new FieldMapping('string', 'name', 'name');
        $nameMapping->encrypt = new EncryptMapping();

        $classMetadata->fieldMappings['name'] = $nameMapping;

        $encryptHelper = new EncryptHelper($this->createEntityManager(
            $this->createEncryptCipherRegistry(),
            $keyProviderRegistry,
            $classMetadata,
        ));

        $querySetMapping = new EncryptedQuerySetMapping();
        $querySetMapping->addEncryptedParameter(0, CmsUser::class, 'status');
        $querySetMapping->addEncryptedParameter(1, CmsUser::class, 'name');

        $params = ['active', 'joe'];
        $types  = ['string', 'string'];

        $encryptHelper->encryptParameters($querySetMapping, $params, $types);

        self::assertSame(['enc(active)', 'enc(joe)'], $params);
    }

    /** @return iterable<string, array{array<string, mixed>, array<string, mixed>}> */
    public static function decryptRowProvider(): iterable
    {
        yield 'decrypts only mapped columns' => [
            ['status_1' => 'enc(active)', 'username_1' => 'joe'],
            ['status_1' => 'dec(enc(active))', 'username_1' => 'joe'],
        ];

        yield 'passes null values through undecrypted' => [
            ['status_1' => null, 'username_1' => 'joe'],
            ['status_1' => null, 'username_1' => 'joe'],
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $expected
     */
    #[DataProvider('decryptRowProvider')]
    public function testDecryptRow(array $row, array $expected): void
    {
        $encryptHelper = new EncryptHelper($this->createEntityManager(
            $this->createDecryptCipherRegistry(),
            $this->createKeyProviderRegistry(),
        ));

        $encryptHelper->decryptRow(['status_1' => new EncryptMapping()], $row);

        self::assertSame($expected, $row);
    }

    public function testDecryptRowNormalizesBlobStreamsBeforeDecrypting(): void
    {
        $cipher = $this->createStub(Cipher::class);
        $cipher->method('isBinary')->willReturn(true);
        $cipher->method('decrypt')->willReturnCallback(static fn (string $envelope): string => 'dec(' . $envelope . ')');

        $cipherRegistry = $this->createStub(CipherRegistry::class);
        $cipherRegistry->method('getCipher')->willReturn($cipher);

        $encryptHelper = new EncryptHelper($this->createEntityManager(
            $cipherRegistry,
            $this->createKeyProviderRegistry(),
        ));

        $row = ['status_1' => 'binary-envelope'];

        $encryptHelper->decryptRow(['status_1' => new EncryptMapping()], $row);

        self::assertSame(['status_1' => 'dec(binary-envelope)'], $row);
    }

    public function testDecryptRowResolvesNullCipherAndKeyProviderNamesToNull(): void
    {
        $cipherRegistry = $this->createMock(CipherRegistry::class);
        $cipherRegistry->expects(self::atLeastOnce())
            ->method('getCipher')
            ->with(null)
            ->willReturn($this->createDecryptCipher());

        $keyProviderRegistry = $this->createMock(KeyProviderRegistry::class);
        $keyProviderRegistry->expects(self::once())
            ->method('getKeyProvider')
            ->with(null)
            ->willReturn($this->createStub(KeyProvider::class));

        $encryptHelper = new EncryptHelper($this->createEntityManager($cipherRegistry, $keyProviderRegistry));

        $row = ['status_1' => 'enc(active)'];

        $encryptHelper->decryptRow(['status_1' => new EncryptMapping()], $row);
    }

    public function testDecryptRowBatchesColumnsSharingCipherAndKeyProvider(): void
    {
        $keyProviderRegistry = $this->createMock(KeyProviderRegistry::class);
        $keyProviderRegistry->expects(self::once())
            ->method('getKeyProvider')
            ->willReturn($this->createStub(KeyProvider::class));

        $encryptHelper = new EncryptHelper($this->createEntityManager(
            $this->createDecryptCipherRegistry(),
            $keyProviderRegistry,
        ));

        $row = ['status_1' => 'enc(active)', 'name_2' => 'enc(joe)'];

        $encryptHelper->decryptRow(
            ['status_1' => new EncryptMapping(), 'name_2' => new EncryptMapping()],
            $row,
        );

        self::assertSame(['status_1' => 'dec(enc(active))', 'name_2' => 'dec(enc(joe))'], $row);
    }

    public function testDecryptRowThrowsWhenKeyProviderRegistryNotConfigured(): void
    {
        $encryptHelper = new EncryptHelper($this->createEntityManager($this->createDecryptCipherRegistry()));

        $row = ['status_1' => 'enc(active)'];

        $this->expectException(EncryptConfigurationMissing::class);
        $this->expectExceptionMessage('Key provider registry is not configured. Call Configuration::setKeyProviderRegistry() to set it.');

        $encryptHelper->decryptRow(['status_1' => new EncryptMapping()], $row);
    }

    private function createCipherRegistry(bool $isBinary): CipherRegistry
    {
        $cipher = $this->createStub(Cipher::class);
        $cipher->method('isBinary')->willReturn($isBinary);

        $cipherRegistry = $this->createStub(CipherRegistry::class);
        $cipherRegistry->method('getCipher')->willReturn($cipher);

        return $cipherRegistry;
    }

    private function createEncryptCipher(): Cipher
    {
        $cipher = $this->createStub(Cipher::class);
        $cipher->method('isBinary')->willReturn(false);
        $cipher->method('encrypt')->willReturnCallback(static fn (string $plaintext): string => 'enc(' . $plaintext . ')');

        return $cipher;
    }

    private function createEncryptCipherRegistry(): CipherRegistry
    {
        $cipherRegistry = $this->createStub(CipherRegistry::class);
        $cipherRegistry->method('getCipher')->willReturn($this->createEncryptCipher());

        return $cipherRegistry;
    }

    private function createDecryptCipher(): Cipher
    {
        $cipher = $this->createStub(Cipher::class);
        $cipher->method('isBinary')->willReturn(false);
        $cipher->method('decrypt')->willReturnCallback(static fn (string $envelope): string => 'dec(' . $envelope . ')');

        return $cipher;
    }

    private function createDecryptCipherRegistry(): CipherRegistry
    {
        $cipherRegistry = $this->createStub(CipherRegistry::class);
        $cipherRegistry->method('getCipher')->willReturn($this->createDecryptCipher());

        return $cipherRegistry;
    }

    private function createKeyProviderRegistry(): KeyProviderRegistry
    {
        $keyProviderRegistry = $this->createStub(KeyProviderRegistry::class);
        $keyProviderRegistry->method('getKeyProvider')->willReturn($this->createStub(KeyProvider::class));

        return $keyProviderRegistry;
    }

    /** @return ClassMetadata<CmsUser> */
    private function createClassMetadata(): ClassMetadata
    {
        $classMetadata = new ClassMetadata(CmsUser::class);

        $statusMapping          = new FieldMapping('string', 'status', 'status');
        $statusMapping->encrypt = new EncryptMapping();

        $classMetadata->fieldMappings['status']   = $statusMapping;
        $classMetadata->fieldMappings['username'] = new FieldMapping('string', 'username', 'username');

        return $classMetadata;
    }

    /** @param ClassMetadata<CmsUser>|null $classMetadata */
    private function createEntityManager(
        CipherRegistry|null $cipherRegistry,
        KeyProviderRegistry|null $keyProviderRegistry = null,
        ClassMetadata|null $classMetadata = null,
    ): EntityManagerInterface {
        $configuration = new Configuration();
        $configuration->setCipherRegistry($cipherRegistry);
        $configuration->setKeyProviderRegistry($keyProviderRegistry);

        $connection = $this->createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn(new SQLitePlatform());

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getConfiguration')->willReturn($configuration);
        $em->method('getConnection')->willReturn($connection);

        if ($classMetadata !== null) {
            $em->method('getClassMetadata')->willReturn($classMetadata);
        }

        return $em;
    }
}
