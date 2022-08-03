<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM;

use BadMethodCallException;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\ORMException;
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
use Doctrine\Tests\VerifyDeprecations;
use InvalidArgumentException;
use stdClass;
use TypeError;

use function get_class;
use function random_int;
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

    /**
     * @group DDC-899
     */
    public function testIsOpen(): void
    {
        $this->assertTrue($this->entityManager->isOpen());
        $this->entityManager->close();
        $this->assertFalse($this->entityManager->isOpen());
    }

    public function testGetConnection(): void
    {
        $this->assertInstanceOf(Connection::class, $this->entityManager->getConnection());
    }

    public function testGetMetadataFactory(): void
    {
        $this->assertInstanceOf(ClassMetadataFactory::class, $this->entityManager->getMetadataFactory());
    }

    public function testGetConfiguration(): void
    {
        $this->assertInstanceOf(Configuration::class, $this->entityManager->getConfiguration());
    }

    public function testGetUnitOfWork(): void
    {
        $this->assertInstanceOf(UnitOfWork::class, $this->entityManager->getUnitOfWork());
    }

    public function testGetProxyFactory(): void
    {
        $this->assertInstanceOf(ProxyFactory::class, $this->entityManager->getProxyFactory());
    }

    public function testGetEventManager(): void
    {
        $this->assertInstanceOf(EventManager::class, $this->entityManager->getEventManager());
    }

    public function testCreateNativeQuery(): void
    {
        $rsm   = new ResultSetMapping();
        $query = $this->entityManager->createNativeQuery('SELECT foo', $rsm);

        $this->assertSame('SELECT foo', $query->getSql());
    }

    /**
     * @covers \Doctrine\ORM\EntityManager::createNamedNativeQuery
     */
    public function testCreateNamedNativeQuery(): void
    {
        $rsm = new ResultSetMapping();
        $this->entityManager->getConfiguration()->addNamedNativeQuery('foo', 'SELECT foo', $rsm);

        $query = $this->entityManager->createNamedNativeQuery('foo');

        $this->assertInstanceOf(NativeQuery::class, $query);
    }

    public function testCreateQueryBuilder(): void
    {
        $this->assertInstanceOf(QueryBuilder::class, $this->entityManager->createQueryBuilder());
    }

    public function testCreateQueryBuilderAliasValid(): void
    {
        $q  = $this->entityManager->createQueryBuilder()
             ->select('u')->from(CmsUser::class, 'u');
        $q2 = clone $q;

        $this->assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $q->getQuery()->getDql());
        $this->assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $q2->getQuery()->getDql());

        $q3 = clone $q;

        $this->assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $q3->getQuery()->getDql());
    }

    public function testCreateQueryDqlIsOptional(): void
    {
        $this->assertInstanceOf(Query::class, $this->entityManager->createQuery());
    }

    public function testGetPartialReference(): void
    {
        $user = $this->entityManager->getPartialReference(CmsUser::class, 42);
        $this->assertTrue($this->entityManager->contains($user));
        $this->assertEquals(42, $user->id);
        $this->assertNull($user->getName());
    }

    public function testCreateQuery(): void
    {
        $q = $this->entityManager->createQuery('SELECT 1');
        $this->assertInstanceOf(Query::class, $q);
        $this->assertEquals('SELECT 1', $q->getDql());
    }

    /**
     * @covers Doctrine\ORM\EntityManager::createNamedQuery
     */
    public function testCreateNamedQuery(): void
    {
        $this->entityManager->getConfiguration()->addNamedQuery('foo', 'SELECT 1');

        $query = $this->entityManager->createNamedQuery('foo');
        $this->assertInstanceOf(Query::class, $query);
        $this->assertEquals('SELECT 1', $query->getDql());
    }

    /**
     * @psalm-return list<array{string}>
     */
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
            ['merge'],
            ['refresh'],
        ];
    }

    /**
     * @dataProvider dataAffectedByErrorIfClosedException
     */
    public function testAffectedByErrorIfClosedException(string $methodName): void
    {
        $this->expectException(ORMException::class);
        $this->expectExceptionMessage('closed');

        $this->entityManager->close();
        $this->entityManager->$methodName(new stdClass());
    }

    /**
     * @group DDC-1125
     */
    public function testTransactionalAcceptsReturn(): void
    {
        $return = $this->entityManager->transactional(static function ($em) {
            return 'foo';
        });

        $this->assertEquals('foo', $return);
    }

    public function testTransactionalAcceptsVariousCallables(): void
    {
        $this->assertSame('callback', $this->entityManager->transactional([$this, 'transactionalCallback']));
    }

    public function testTransactionalThrowsInvalidArgumentExceptionIfNonCallablePassed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected argument of type "callable", got "object"');

        $this->entityManager->transactional($this);
    }

    public function transactionalCallback($em): string
    {
        $this->assertSame($this->entityManager, $em);

        return 'callback';
    }

    public function testCreateInvalidConnection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid $connection argument of type integer given: "1".');

        $config = new Configuration();
        $config->setMetadataDriverImpl($this->createMock(MappingDriver::class));
        EntityManager::create(1, $config);
    }

    /**
     * @group #5796
     */
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

    /**
     * @group 6017
     */
    public function testClearManagerWithObject(): void
    {
        $entity = new Country(456, 'United Kingdom');

        $this->expectException(ORMInvalidArgumentException::class);

        $this->entityManager->clear($entity);
    }

    /**
     * @group 6017
     */
    public function testClearManagerWithUnknownEntityName(): void
    {
        $this->expectException(MappingException::class);

        $this->entityManager->clear(uniqid('nonExisting', true));
    }

    /**
     * @group 6017
     */
    public function testClearManagerWithProxyClassName(): void
    {
        $proxy = $this->entityManager->getReference(Country::class, ['id' => random_int(457, 100000)]);

        $entity = new Country(456, 'United Kingdom');

        $this->entityManager->persist($entity);

        $this->assertTrue($this->entityManager->contains($entity));

        $this->entityManager->clear(get_class($proxy));

        $this->assertFalse($this->entityManager->contains($entity));
    }

    /**
     * @group 6017
     */
    public function testClearManagerWithNullValue(): void
    {
        $entity = new Country(456, 'United Kingdom');

        $this->entityManager->persist($entity);

        $this->assertTrue($this->entityManager->contains($entity));

        $this->entityManager->clear(null);

        $this->assertFalse($this->entityManager->contains($entity));
    }

    public function testDeprecatedClearWithArguments(): void
    {
        $entity = new Country(456, 'United Kingdom');
        $this->entityManager->persist($entity);

        $this->expectDeprecationMessageSame('Calling Doctrine\ORM\EntityManager::clear() with any arguments to clear specific entities is deprecated and will not be supported in Doctrine ORM 3.0.');
        $this->entityManager->clear(Country::class);
    }

    public function testDeprecatedFlushWithArguments(): void
    {
        $entity = new Country(456, 'United Kingdom');
        $this->entityManager->persist($entity);

        $this->expectDeprecationMessageSame('Calling Doctrine\ORM\EntityManager::flush() with any arguments to flush specific entities is deprecated and will not be supported in Doctrine ORM 3.0.');
        $this->entityManager->flush($entity);
    }

    public function testDeprecatedMerge(): void
    {
        $entity = new Country(456, 'United Kingdom');
        $this->entityManager->persist($entity);

        $this->expectDeprecationMessageSame('Method Doctrine\ORM\EntityManager::merge() is deprecated and will be removed in Doctrine ORM 3.0.');
        $this->entityManager->merge($entity);
    }

    public function testDeprecatedDetach(): void
    {
        $entity = new Country(456, 'United Kingdom');
        $this->entityManager->persist($entity);

        $this->expectDeprecationMessageSame('Method Doctrine\ORM\EntityManager::detach() is deprecated and will be removed in Doctrine ORM 3.0.');
        $this->entityManager->detach($entity);
    }

    public function testDeprecatedCopy(): void
    {
        $entity = new Country(456, 'United Kingdom');
        $this->entityManager->persist($entity);

        try {
            $this->expectDeprecationMessageSame('Method Doctrine\ORM\EntityManager::copy() is deprecated and will be removed in Doctrine ORM 3.0.');
            $this->entityManager->copy($entity);
        } catch (BadMethodCallException $e) {
            // do nothing
        }
    }
}
