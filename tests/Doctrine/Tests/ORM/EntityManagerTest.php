<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\EntityManagerClosed;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\GeoNames\Country;
use Doctrine\Tests\OrmTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use stdClass;
use TypeError;

use function get_class;
use function random_int;
use function uniqid;

class EntityManagerTest extends OrmTestCase
{
    private EntityManagerMock $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->getTestEntityManager();
    }

    #[Group('DDC-899')]
    public function testIsOpen(): void
    {
        self::assertTrue($this->entityManager->isOpen());
        $this->entityManager->close();
        self::assertFalse($this->entityManager->isOpen());
    }

    public function testGetConnection(): void
    {
        self::assertInstanceOf(Connection::class, $this->entityManager->getConnection());
    }

    public function testGetMetadataFactory(): void
    {
        self::assertInstanceOf(ClassMetadataFactory::class, $this->entityManager->getMetadataFactory());
    }

    public function testGetConfiguration(): void
    {
        self::assertInstanceOf(Configuration::class, $this->entityManager->getConfiguration());
    }

    public function testGetUnitOfWork(): void
    {
        self::assertInstanceOf(UnitOfWork::class, $this->entityManager->getUnitOfWork());
    }

    public function testGetProxyFactory(): void
    {
        self::assertInstanceOf(ProxyFactory::class, $this->entityManager->getProxyFactory());
    }

    public function testGetEventManager(): void
    {
        self::assertInstanceOf(EventManager::class, $this->entityManager->getEventManager());
    }

    public function testCreateNativeQuery(): void
    {
        $rsm   = new ResultSetMapping();
        $query = $this->entityManager->createNativeQuery('SELECT foo', $rsm);

        self::assertSame('SELECT foo', $query->getSql());
    }

    public function testCreateQueryBuilder(): void
    {
        self::assertInstanceOf(QueryBuilder::class, $this->entityManager->createQueryBuilder());
    }

    public function testCreateQueryBuilderAliasValid(): void
    {
        $q  = $this->entityManager->createQueryBuilder()
             ->select('u')->from(CmsUser::class, 'u');
        $q2 = clone $q;

        self::assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $q->getQuery()->getDql());
        self::assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $q2->getQuery()->getDql());

        $q3 = clone $q;

        self::assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $q3->getQuery()->getDql());
    }

    public function testCreateQueryDqlIsOptional(): void
    {
        self::assertInstanceOf(Query::class, $this->entityManager->createQuery());
    }

    public function testGetPartialReference(): void
    {
        $user = $this->entityManager->getPartialReference(CmsUser::class, 42);
        self::assertTrue($this->entityManager->contains($user));
        self::assertEquals(42, $user->id);
        self::assertNull($user->getName());
    }

    public function testCreateQuery(): void
    {
        $q = $this->entityManager->createQuery('SELECT 1');
        self::assertInstanceOf(Query::class, $q);
        self::assertEquals('SELECT 1', $q->getDql());
    }

    /** @psalm-return list<array{string}> */
    public static function dataAffectedByErrorIfClosedException(): array
    {
        return [
            ['flush'],
            ['persist'],
            ['remove'],
            ['refresh'],
        ];
    }

    #[DataProvider('dataAffectedByErrorIfClosedException')]
    public function testAffectedByErrorIfClosedException(string $methodName): void
    {
        $this->expectException(EntityManagerClosed::class);
        $this->expectExceptionMessage('closed');

        $this->entityManager->close();
        $this->entityManager->$methodName(new stdClass());
    }

    /** @return Generator<array{mixed}> */
    public static function dataToBeReturnedByWrapInTransaction(): Generator
    {
        yield [[]];
        yield [[1]];
        yield [0];
        yield [100.5];
        yield [null];
        yield [true];
        yield [false];
        yield ['foo'];
    }

    #[DataProvider('dataToBeReturnedByWrapInTransaction')]
    #[Group('DDC-1125')]
    public function testWrapInTransactionAcceptsReturn(mixed $expectedValue): void
    {
        $return = $this->entityManager->wrapInTransaction(
            static fn (EntityManagerInterface $em): mixed => $expectedValue,
        );

        $this->assertSame($expectedValue, $return);
    }

    #[Group('#5796')]
    public function testWrapInTransactionReThrowsThrowables(): void
    {
        try {
            $this->entityManager->wrapInTransaction(static function (): void {
                (static function (array $value): void {
                    // this only serves as an IIFE that throws a `TypeError`
                })(null);
            });

            self::fail('TypeError expected to be thrown');
        } catch (TypeError) {
            self::assertFalse($this->entityManager->isOpen());
        }
    }

    #[Group('6017')]
    public function testClearManagerWithObject(): void
    {
        $entity = new Country('456', 'United Kingdom');

        $this->expectException(ORMInvalidArgumentException::class);

        $this->entityManager->clear($entity);
    }

    #[Group('6017')]
    public function testClearManagerWithUnknownEntityName(): void
    {
        $this->expectException(MappingException::class);

        $this->entityManager->clear(uniqid('nonExisting', true));
    }

    #[Group('6017')]
    public function testClearManagerWithProxyClassName(): void
    {
        $proxy = $this->entityManager->getReference(Country::class, ['id' => random_int(457, 100000)]);

        $entity = new Country('456', 'United Kingdom');

        $this->entityManager->persist($entity);

        self::assertTrue($this->entityManager->contains($entity));

        $this->entityManager->clear(get_class($proxy));

        self::assertFalse($this->entityManager->contains($entity));
    }

    #[Group('6017')]
    public function testClearManagerWithNullValue(): void
    {
        $entity = new Country('456', 'United Kingdom');

        $this->entityManager->persist($entity);

        self::assertTrue($this->entityManager->contains($entity));

        $this->entityManager->clear(null);

        self::assertFalse($this->entityManager->contains($entity));
    }
}
