<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\EntityManagerClosed;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmTestCase;
use Exception;
use Generator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use ReflectionProperty;
use stdClass;
use Symfony\Component\VarExporter\LazyGhostTrait;
use TypeError;

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

    public function testCreateQuery(): void
    {
        $q = $this->entityManager->createQuery('SELECT 1');
        self::assertInstanceOf(Query::class, $q);
        self::assertEquals('SELECT 1', $q->getDql());
    }

    /** @phpstan-return list<array{string}> */
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

    /** Resetting the EntityManager relies on lazy objects until https://github.com/doctrine/orm/issues/5933 is resolved */
    public function testLazyGhostEntityManager(): void
    {
        $em = new class () extends EntityManager {
            use LazyGhostTrait;

            public function __construct()
            {
            }
        };

        $em = $em::createLazyGhost(static function ($em): void {
            $r = new ReflectionProperty(EntityManager::class, 'unitOfWork');
            $r->setValue($em, new class () extends UnitOfWork {
                public function __construct()
                {
                }

                public function clear(): void
                {
                }
            });
        });

        $this->assertTrue($em->isOpen());
        $em->close();
        $this->assertFalse($em->isOpen());

        $em->resetLazyObject();
        $this->assertTrue($em->isOpen());
    }

    public function testItPreservesTheOriginalExceptionOnRollbackFailure(): void
    {
        $driver = $this->createMock(Driver::class);
        $driver->method('connect')
            ->willReturn($this->createMock(Driver\Connection::class));

        $entityManager = new EntityManagerMock(new class ([], $driver) extends Connection {
            public function rollBack(): void
            {
                throw new Exception('Rollback exception');
            }
        });

        try {
            $entityManager->wrapInTransaction(static function (): void {
                throw new Exception('Original exception');
            });
            self::fail('Exception expected');
        } catch (Exception $e) {
            self::assertSame('Rollback exception', $e->getMessage());
            self::assertNotNull($e->getPrevious());
            self::assertSame('Original exception', $e->getPrevious()->getMessage());
        }
    }

    public function testItDoesNotAttemptToRollbackIfNoTransactionIsActive(): void
    {
        $driver = $this->createMock(Driver::class);
        $driver->method('connect')
            ->willReturn($this->createMock(Driver\Connection::class));

        $entityManager = new EntityManagerMock(
            new class ([], $driver) extends Connection {
                public function commit(): void
                {
                    throw new Exception('Commit exception that happens after doing the actual commit');
                }

                public function rollBack(): void
                {
                    Assert::fail('Should not attempt to rollback if no transaction is active');
                }

                public function isTransactionActive(): bool
                {
                    return false;
                }
            },
        );

        $this->expectExceptionMessage('Commit exception');
        $entityManager->wrapInTransaction(static function (): void {
        });
    }
}
