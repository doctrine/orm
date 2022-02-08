<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\EntityManagerClosed;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmTestCase;
use Generator;
use stdClass;
use TypeError;

class EntityManagerTest extends OrmTestCase
{
    /** @var EntityManager */
    private $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = $this->getTestEntityManager();
    }

    /**
     * @group DDC-899
     */
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

    /**
     * @psalm-return list<array{string}>
     */
    public static function dataMethodsAffectedByNoObjectArguments(): array
    {
        return [
            ['persist'],
            ['remove'],
            ['refresh'],
            ['detach'],
        ];
    }

    /**
     * @dataProvider dataMethodsAffectedByNoObjectArguments
     */
    public function testThrowsExceptionOnNonObjectValues($methodName): void
    {
        $this->expectException(ORMInvalidArgumentException::class);
        $this->expectExceptionMessage('EntityManager#' . $methodName . '() expects parameter 1 to be an entity object, NULL given.');

        $this->entityManager->$methodName(null);
    }

    /**
     * @psalm-return list<array{string}>
     */
    public static function dataAffectedByErrorIfClosedException(): array
    {
        return [
            ['flush'],
            ['persist'],
            ['remove'],
            ['refresh'],
        ];
    }

    /**
     * @dataProvider dataAffectedByErrorIfClosedException
     */
    public function testAffectedByErrorIfClosedException(string $methodName): void
    {
        $this->expectException(EntityManagerClosed::class);
        $this->expectExceptionMessage('closed');

        $this->entityManager->close();
        $this->entityManager->$methodName(new stdClass());
    }

    /** @return Generator<array{mixed}> */
    public function dataToBeReturnedByWrapInTransaction(): Generator
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

    /**
     * @dataProvider dataToBeReturnedByWrapInTransaction
     * @group DDC-1125
     */
    public function testWrapInTransactionAcceptsReturn(mixed $expectedValue): void
    {
        $return = $this->entityManager->wrapInTransaction(
            /** @return mixed */
            static function (EntityManagerInterface $em) use ($expectedValue) {
                return $expectedValue;
            }
        );

        $this->assertSame($expectedValue, $return);
    }

    /**
     * @group #5796
     */
    public function testWrapInTransactionReThrowsThrowables(): void
    {
        try {
            $this->entityManager->wrapInTransaction(static function (): void {
                (static function (array $value): void {
                    // this only serves as an IIFE that throws a `TypeError`
                })(null);
            });

            self::fail('TypeError expected to be thrown');
        } catch (TypeError $ignored) {
            self::assertFalse($this->entityManager->isOpen());
        }
    }
}
