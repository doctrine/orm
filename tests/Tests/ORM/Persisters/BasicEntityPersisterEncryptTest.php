<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\Encrypt\Cipher\Cipher;
use Doctrine\ORM\Encrypt\Cipher\CipherRegistry;
use Doctrine\ORM\Encrypt\EncryptQuery;
use Doctrine\ORM\Encrypt\Exception\UnsupportedEncryptedFieldUsage;
use Doctrine\ORM\Encrypt\KMS\KeyProvider;
use Doctrine\ORM\Encrypt\KMS\KeyProviderRegistry;
use Doctrine\ORM\Mapping\EncryptMapping;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\Company\CompanyEmployee;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;

use function sprintf;

class BasicEntityPersisterEncryptTest extends OrmTestCase
{
    private EntityManagerMock $entityManager;
    private BasicEntityPersister $persister;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->getTestEntityManager();
        $this->persister     = new BasicEntityPersister($this->entityManager, $this->entityManager->getClassMetadata(CmsUser::class));
    }

    /** @return iterable<string, array{array<string, mixed>, array<int, mixed>, array<int, string>}> */
    public static function expandParametersProvider(): iterable
    {
        yield 'scalar' => [
            ['status' => 'active', 'username' => 'joe'],
            ['enc(active)', 'joe'],
            ['text', 'string'],
        ];

        yield 'IN list' => [
            ['status' => ['a', 'b'], 'username' => 'joe'],
            ['enc(a)', 'enc(b)', 'joe'],
            ['text', 'text', 'string'],
        ];

        yield 'all-null IN list' => [
            ['status' => [null], 'username' => 'joe'],
            ['joe'],
            ['string'],
        ];
    }

    /**
     * @param array<string, mixed> $criteria
     * @param array<int, mixed>    $expectedParams
     * @param array<int, string>   $expectedTypes
     */
    #[DataProvider('expandParametersProvider')]
    public function testExpandParameters(array $criteria, array $expectedParams, array $expectedTypes): void
    {
        $this->encryptCmsUserStatus();

        [$params, $types] = $this->persister->expandParameters($criteria);

        self::assertSame($expectedParams, $params);
        self::assertSame($expectedTypes, $types);
    }

    public function testExpandCriteriaParametersEncryptsBothOccurrencesOfADuplicatedEncryptedField(): void
    {
        $this->encryptCmsUserStatus();

        $criteria = Criteria::create()->where(
            Criteria::expr()->orX(
                Criteria::expr()->eq('status', 'a'),
                Criteria::expr()->eq('status', 'b'),
            ),
        );

        [$params] = $this->persister->expandCriteriaParameters($criteria);

        self::assertSame(['enc(a)', 'enc(b)'], $params);
    }

    public function testExpandCriteriaParametersEncryptsNotEqualComparison(): void
    {
        $this->encryptCmsUserStatus();

        $criteria = Criteria::create()->where(Criteria::expr()->neq('status', 'a'));

        [$params, $types] = $this->persister->expandCriteriaParameters($criteria);

        self::assertSame(['enc(a)'], $params);
        self::assertSame(['text'], $types);
    }

    public function testExpandCriteriaParametersEncryptsNotInList(): void
    {
        $this->encryptCmsUserStatus();

        $criteria = Criteria::create()->where(Criteria::expr()->notIn('status', ['a', 'b']));

        [$params, $types] = $this->persister->expandCriteriaParameters($criteria);

        self::assertSame(['enc(a)', 'enc(b)'], $params);
        self::assertSame(['text', 'text'], $types);
    }

    public function testExpandCriteriaParametersOnAssociationFieldDoesNotEncrypt(): void
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq('email', 3));

        [$params, $types] = $this->persister->expandCriteriaParameters($criteria);

        self::assertEquals([3], $params);

        self::assertEquals(['integer'], $types);
    }

    public function testExpandCriteriaParametersWithEmptyInListDoesNotEncryptNeighboringField(): void
    {
        $this->encryptCmsUserStatus();

        $criteria = Criteria::create()->where(
            Criteria::expr()->andX(
                Criteria::expr()->in('status', []),
                Criteria::expr()->eq('username', 'joe'),
            ),
        );

        [$params, $types] = $this->persister->expandCriteriaParameters($criteria);

        self::assertSame(['joe'], $params);
        self::assertSame(['string'], $types);
    }

    /** @return iterable<string, array{Comparison, string}> */
    public static function unsupportedOperatorProvider(): iterable
    {
        yield 'greater than' => [Criteria::expr()->gt('status', 'a'), '>'];
        yield 'contains' => [Criteria::expr()->contains('status', 'a'), 'CONTAINS'];
    }

    #[DataProvider('unsupportedOperatorProvider')]
    public function testExpandCriteriaParametersThrowsForNonEqualityOperatorOnEncryptedField(Comparison $comparison, string $operator): void
    {
        $this->encryptCmsUserStatus();

        $this->expectException(UnsupportedEncryptedFieldUsage::class);
        $this->expectExceptionMessage(sprintf(
            'Doctrine\Tests\Models\CMS\CmsUser::$status is encrypted; filtering by an encrypted field only supports equality operators (=, <>, IN, NIN), got "%s".',
            $operator,
        ));

        $this->persister->expandCriteriaParameters(Criteria::create()->where($comparison));
    }

    /** @return iterable<string, array{bool, EncryptQuery|null, string}> */
    public static function nonQueryableFieldProvider(): iterable
    {
        yield 'missing queryType' => [
            true,
            null,
            'Doctrine\Tests\Models\CMS\CmsUser::$status is encrypted; filtering by an encrypted field without queryType set is not supported.',
        ];

        yield 'non deterministic cipher' => [
            false,
            EncryptQuery::Equality,
            'Doctrine\Tests\Models\CMS\CmsUser::$status is encrypted; filtering by an encrypted field using non deterministic cipher is not supported.',
        ];
    }

    #[DataProvider('nonQueryableFieldProvider')]
    public function testExpandParametersThrowsWhenEncryptedFieldIsNotQueryable(bool $deterministic, EncryptQuery|null $queryType, string $expectedMessage): void
    {
        $this->encryptCmsUserStatus($deterministic, $queryType);

        $this->expectException(UnsupportedEncryptedFieldUsage::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->persister->expandParameters(['status' => 'active']);
    }

    public function testGetSelectSQLThrowsWhenOrderingByEncryptedField(): void
    {
        $this->encryptCmsUserStatus();

        $this->expectException(UnsupportedEncryptedFieldUsage::class);
        $this->expectExceptionMessage('Doctrine\Tests\Models\CMS\CmsUser::$status is encrypted; ordering by an encrypted field is not supported.');

        $this->persister->getSelectSQL([], null, null, null, null, ['status' => 'ASC']);
    }

    public function testPrepareInsertDataEncryptsFieldAndLeavesOtherColumnsUntouched(): void
    {
        $this->encryptCmsUserStatus();

        $user           = new CmsUser();
        $user->status   = 'active';
        $user->username = 'joe';

        $this->entityManager->getUnitOfWork()->registerManaged($user, ['id' => 1], ['status' => null, 'username' => null]);
        $this->entityManager->getUnitOfWork()->propertyChanged($user, 'status', null, 'active');
        $this->entityManager->getUnitOfWork()->propertyChanged($user, 'username', null, 'joe');

        $data = $this->invokeMethod($this->persister, 'prepareInsertData', $user);

        self::assertSame('enc(active)', $data['cms_users']['status']);
        self::assertSame('joe', $data['cms_users']['username']);
    }

    public function testPrepareInsertDataEncryptsFieldUnderItsOwningTableForJoinedInheritance(): void
    {
        $this->configureEncryption();
        $this->encryptField(CompanyEmployee::class, 'name');

        $employee = new CompanyEmployee();
        $employee->setName('secret-name');

        $this->entityManager->getUnitOfWork()->registerManaged($employee, ['id' => 1], ['name' => null]);
        $this->entityManager->getUnitOfWork()->propertyChanged($employee, 'name', null, 'secret-name');

        $persister = $this->entityManager->getUnitOfWork()->getEntityPersister(CompanyEmployee::class);
        $data      = $this->invokeMethod($persister, 'prepareInsertData', $employee);

        self::assertSame('enc(secret-name)', $data['company_persons']['name']);
    }

    public function testExpandToManyParametersUsesTheCriterionsOwnClassForQueryabilityChecks(): void
    {
        $this->configureEncryption();
        $this->encryptField(CmsAddress::class, 'city', null);

        $this->expectException(UnsupportedEncryptedFieldUsage::class);
        $this->expectExceptionMessage('Doctrine\Tests\Models\CMS\CmsAddress::$city is encrypted; filtering by an encrypted field without queryType set is not supported.');

        $this->invokeMethod($this->persister, 'expandToManyParameters', [
            ['field' => 'city', 'value' => 'Paris', 'class' => $this->entityManager->getClassMetadata(CmsAddress::class)],
        ]);
    }

    private function createCipher(bool $deterministic): Cipher
    {
        $cipher = $this->createStub(Cipher::class);
        $cipher->method('isBinary')->willReturn(false);
        $cipher->method('isDeterministic')->willReturn($deterministic);
        $cipher->method('encrypt')->willReturnCallback(static fn (string $plaintext): string => 'enc(' . $plaintext . ')');

        return $cipher;
    }

    private function configureEncryption(bool $deterministic = true): void
    {
        $cipherRegistry = $this->createStub(CipherRegistry::class);
        $cipherRegistry->method('getCipher')->willReturn($this->createCipher($deterministic));

        $keyProviderRegistry = $this->createStub(KeyProviderRegistry::class);
        $keyProviderRegistry->method('getKeyProvider')->willReturn($this->createStub(KeyProvider::class));

        $this->entityManager->getConfiguration()->setCipherRegistry($cipherRegistry);
        $this->entityManager->getConfiguration()->setKeyProviderRegistry($keyProviderRegistry);
    }

    /** @param class-string $className */
    private function encryptField(string $className, string $fieldName, EncryptQuery|null $queryType = EncryptQuery::Equality): void
    {
        $encrypt            = new EncryptMapping();
        $encrypt->queryType = $queryType;

        $this->entityManager->getClassMetadata($className)->fieldMappings[$fieldName]->encrypt = $encrypt;
    }

    private function encryptCmsUserStatus(bool $deterministic = true, EncryptQuery|null $queryType = EncryptQuery::Equality): void
    {
        $this->configureEncryption($deterministic);
        $this->encryptField(CmsUser::class, 'status', $queryType);
    }

    private function invokeMethod(object $object, string $method, mixed ...$args): mixed
    {
        return (new ReflectionMethod($object, $method))->invoke($object, ...$args);
    }
}
