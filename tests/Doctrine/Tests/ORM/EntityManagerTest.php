<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\EntityManagerClosed;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\GeoNames\Country;
use Doctrine\Tests\OrmTestCase;
use Generator;
use InvalidArgumentException;
use stdClass;
use TypeError;

use function get_class;
use function random_int;
use function sys_get_temp_dir;
use function uniqid;

class EntityManagerTest extends OrmTestCase
{
    use VerifyDeprecations;

    /** @var EntityManager */
    private $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->getTestEntityManager();
    }

    /** @group DDC-899 */
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

    /** @covers \Doctrine\ORM\EntityManager::createNamedNativeQuery */
    public function testCreateNamedNativeQuery(): void
    {
        $rsm = new ResultSetMapping();
        $this->entityManager->getConfiguration()->addNamedNativeQuery('foo', 'SELECT foo', $rsm);

        $query = $this->entityManager->createNamedNativeQuery('foo');

        self::assertInstanceOf(NativeQuery::class, $query);
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

    /** @covers Doctrine\ORM\EntityManager::createNamedQuery */
    public function testCreateNamedQuery(): void
    {
        $this->entityManager->getConfiguration()->addNamedQuery('foo', 'SELECT 1');

        $query = $this->entityManager->createNamedQuery('foo');
        self::assertInstanceOf(Query::class, $query);
        self::assertEquals('SELECT 1', $query->getDql());
    }

    /** @psalm-return list<array{string}> */
    public static function dataMethodsAffectedByNoObjectArguments(): array
    {
        return [
            ['persist'],
            ['remove'],
            ['merge'],
            ['refresh'],
            ['detach'],
        ];
    }

    /** @dataProvider dataMethodsAffectedByNoObjectArguments */
    public function testThrowsExceptionOnNonObjectValues($methodName): void
    {
        $this->expectException(ORMInvalidArgumentException::class);
        $this->expectExceptionMessage('EntityManager#' . $methodName . '() expects parameter 1 to be an entity object, NULL given.');

        $this->entityManager->$methodName(null);
    }

    /** @psalm-return list<array{string}> */
    public static function dataAffectedByErrorIfClosedException(): array
    {
        return [
            ['flush'],
            ['persist'],
            ['remove'],
            ['merge'],
            ['refresh'],
        ];
    }

    /** @dataProvider dataAffectedByErrorIfClosedException */
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

    /**
     * @param mixed $expectedValue
     *
     * @dataProvider dataToBeReturnedByWrapInTransaction
     * @group DDC-1125
     */
    public function testWrapInTransactionAcceptsReturn($expectedValue): void
    {
        $return = $this->entityManager->wrapInTransaction(
            /** @return mixed */
            static function (EntityManagerInterface $em) use ($expectedValue) {
                return $expectedValue;
            }
        );

        $this->assertSame($expectedValue, $return);
    }

    /** @group DDC-1125 */
    public function testTransactionalAcceptsReturn(): void
    {
        $return = $this->entityManager->transactional(static function ($em) {
            return 'foo';
        });

        self::assertEquals('foo', $return);
    }

    public function testTransactionalAcceptsVariousCallables(): void
    {
        self::assertSame('callback', $this->entityManager->transactional([$this, 'transactionalCallback']));
    }

    public function testTransactionalThrowsInvalidArgumentExceptionIfNonCallablePassed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected argument of type "callable", got "object"');

        $this->entityManager->transactional($this);
    }

    public function transactionalCallback($em): string
    {
        self::assertSame($this->entityManager, $em);

        return 'callback';
    }

    public function testCreateInvalidConnection(): void
    {
        $config = new Configuration();
        $config->setMetadataDriverImpl($this->createMock(MappingDriver::class));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid $connection argument of type int given: "1".');
        EntityManager::create(1, $config);
    }

    public function testNamedConstructorDeprecation(): void
    {
        $config = new Configuration();
        $config->setMetadataDriverImpl($this->createMock(MappingDriver::class));
        $config->setProxyDir(sys_get_temp_dir());
        $config->setProxyNamespace(__NAMESPACE__ . '\\MyProxies');

        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/pull/9961');

        $em = EntityManager::create(['driver' => 'pdo_sqlite', 'memory' => true], $config);

        self::assertInstanceOf(Connection::class, $em->getConnection());
    }

    /** @group #5796 */
    public function testTransactionalReThrowsThrowables(): void
    {
        try {
            $this->entityManager->transactional(static function (): void {
                (static function (array $value): void {
                    // this only serves as an IIFE that throws a `TypeError`
                })(null);
            });

            self::fail('TypeError expected to be thrown');
        } catch (TypeError $ignored) {
            self::assertFalse($this->entityManager->isOpen());
        }
    }

    /** @group #5796 */
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

    /** @group 6017 */
    public function testClearManagerWithObject(): void
    {
        $entity = new Country(456, 'United Kingdom');

        $this->expectException(ORMInvalidArgumentException::class);

        $this->entityManager->clear($entity);
    }

    /** @group 6017 */
    public function testClearManagerWithUnknownEntityName(): void
    {
        $this->expectException(MappingException::class);

        $this->entityManager->clear(uniqid('nonExisting', true));
    }

    /** @group 6017 */
    public function testClearManagerWithProxyClassName(): void
    {
        $proxy = $this->entityManager->getReference(Country::class, ['id' => random_int(457, 100000)]);

        $entity = new Country(456, 'United Kingdom');

        $this->entityManager->persist($entity);

        self::assertTrue($this->entityManager->contains($entity));

        $this->entityManager->clear(get_class($proxy));

        self::assertFalse($this->entityManager->contains($entity));
    }

    /** @group 6017 */
    public function testClearManagerWithNullValue(): void
    {
        $entity = new Country(456, 'United Kingdom');

        $this->entityManager->persist($entity);

        self::assertTrue($this->entityManager->contains($entity));

        $this->entityManager->clear(null);

        self::assertFalse($this->entityManager->contains($entity));
    }

    public function testDeprecatedClearWithArguments(): void
    {
        $entity = new Country(456, 'United Kingdom');
        $this->entityManager->persist($entity);

        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/issues/8460');

        $this->entityManager->clear(Country::class);
    }

    public function testDeprecatedFlushWithArguments(): void
    {
        $entity = new Country(456, 'United Kingdom');
        $this->entityManager->persist($entity);

        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/issues/8459');

        $this->entityManager->flush($entity);
    }
}
