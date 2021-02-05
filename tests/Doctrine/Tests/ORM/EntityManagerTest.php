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
    private $_em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->_em = $this->_getTestEntityManager();
    }

    /**
     * @group DDC-899
     */
    public function testIsOpen(): void
    {
        $this->assertTrue($this->_em->isOpen());
        $this->_em->close();
        $this->assertFalse($this->_em->isOpen());
    }

    public function testGetConnection(): void
    {
        $this->assertInstanceOf(Connection::class, $this->_em->getConnection());
    }

    public function testGetMetadataFactory(): void
    {
        $this->assertInstanceOf(ClassMetadataFactory::class, $this->_em->getMetadataFactory());
    }

    public function testGetConfiguration(): void
    {
        $this->assertInstanceOf(Configuration::class, $this->_em->getConfiguration());
    }

    public function testGetUnitOfWork(): void
    {
        $this->assertInstanceOf(UnitOfWork::class, $this->_em->getUnitOfWork());
    }

    public function testGetProxyFactory(): void
    {
        $this->assertInstanceOf(ProxyFactory::class, $this->_em->getProxyFactory());
    }

    public function testGetEventManager(): void
    {
        $this->assertInstanceOf(EventManager::class, $this->_em->getEventManager());
    }

    public function testCreateNativeQuery(): void
    {
        $rsm   = new ResultSetMapping();
        $query = $this->_em->createNativeQuery('SELECT foo', $rsm);

        $this->assertSame('SELECT foo', $query->getSql());
    }

    /**
     * @covers \Doctrine\ORM\EntityManager::createNamedNativeQuery
     */
    public function testCreateNamedNativeQuery(): void
    {
        $rsm = new ResultSetMapping();
        $this->_em->getConfiguration()->addNamedNativeQuery('foo', 'SELECT foo', $rsm);

        $query = $this->_em->createNamedNativeQuery('foo');

        $this->assertInstanceOf(NativeQuery::class, $query);
    }

    public function testCreateQueryBuilder(): void
    {
        $this->assertInstanceOf(QueryBuilder::class, $this->_em->createQueryBuilder());
    }

    public function testCreateQueryBuilderAliasValid(): void
    {
        $q  = $this->_em->createQueryBuilder()
             ->select('u')->from(CmsUser::class, 'u');
        $q2 = clone $q;

        $this->assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $q->getQuery()->getDql());
        $this->assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $q2->getQuery()->getDql());

        $q3 = clone $q;

        $this->assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $q3->getQuery()->getDql());
    }

    public function testCreateQuery_DqlIsOptional(): void
    {
        $this->assertInstanceOf(Query::class, $this->_em->createQuery());
    }

    public function testGetPartialReference(): void
    {
        $user = $this->_em->getPartialReference(CmsUser::class, 42);
        $this->assertTrue($this->_em->contains($user));
        $this->assertEquals(42, $user->id);
        $this->assertNull($user->getName());
    }

    public function testCreateQuery(): void
    {
        $q = $this->_em->createQuery('SELECT 1');
        $this->assertInstanceOf(Query::class, $q);
        $this->assertEquals('SELECT 1', $q->getDql());
    }

    /**
     * @covers Doctrine\ORM\EntityManager::createNamedQuery
     */
    public function testCreateNamedQuery(): void
    {
        $this->_em->getConfiguration()->addNamedQuery('foo', 'SELECT 1');

        $query = $this->_em->createNamedQuery('foo');
        $this->assertInstanceOf(Query::class, $query);
        $this->assertEquals('SELECT 1', $query->getDql());
    }

    public static function dataMethodsAffectedByNoObjectArguments()
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

        $this->_em->$methodName(null);
    }

    public static function dataAffectedByErrorIfClosedException()
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

        $this->_em->close();
        $this->_em->$methodName(new stdClass());
    }

    /**
     * @group DDC-1125
     */
    public function testTransactionalAcceptsReturn(): void
    {
        $return = $this->_em->transactional(static function ($em) {
            return 'foo';
        });

        $this->assertEquals('foo', $return);
    }

    public function testTransactionalAcceptsVariousCallables(): void
    {
        $this->assertSame('callback', $this->_em->transactional([$this, 'transactionalCallback']));
    }

    public function testTransactionalThrowsInvalidArgumentExceptionIfNonCallablePassed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected argument of type "callable", got "object"');

        $this->_em->transactional($this);
    }

    public function transactionalCallback($em)
    {
        $this->assertSame($this->_em, $em);

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
            $this->_em->transactional(static function (): void {
                (static function (array $value): void {
                    // this only serves as an IIFE that throws a `TypeError`
                })(null);
            });

            self::fail('TypeError expected to be thrown');
        } catch (TypeError $ignored) {
            self::assertFalse($this->_em->isOpen());
        }
    }

    /**
     * @group 6017
     */
    public function testClearManagerWithObject(): void
    {
        $entity = new Country(456, 'United Kingdom');

        $this->expectException(ORMInvalidArgumentException::class);

        $this->_em->clear($entity);
    }

    /**
     * @group 6017
     */
    public function testClearManagerWithUnknownEntityName(): void
    {
        $this->expectException(MappingException::class);

        $this->_em->clear(uniqid('nonExisting', true));
    }

    /**
     * @group 6017
     */
    public function testClearManagerWithProxyClassName(): void
    {
        $proxy = $this->_em->getReference(Country::class, ['id' => random_int(457, 100000)]);

        $entity = new Country(456, 'United Kingdom');

        $this->_em->persist($entity);

        $this->assertTrue($this->_em->contains($entity));

        $this->_em->clear(get_class($proxy));

        $this->assertFalse($this->_em->contains($entity));
    }

    /**
     * @group 6017
     */
    public function testClearManagerWithNullValue(): void
    {
        $entity = new Country(456, 'United Kingdom');

        $this->_em->persist($entity);

        $this->assertTrue($this->_em->contains($entity));

        $this->_em->clear(null);

        $this->assertFalse($this->_em->contains($entity));
    }

    public function testDeprecatedClearWithArguments(): void
    {
        $entity = new Country(456, 'United Kingdom');
        $this->_em->persist($entity);

        $this->expectDeprecationMessageSame('Calling Doctrine\ORM\EntityManager::clear() with any arguments to clear specific entities is deprecated and will not be supported in Doctrine ORM 3.0.');
        $this->_em->clear(Country::class);
    }

    public function testDeprecatedFlushWithArguments(): void
    {
        $entity = new Country(456, 'United Kingdom');
        $this->_em->persist($entity);

        $this->expectDeprecationMessageSame('Calling Doctrine\ORM\EntityManager::flush() with any arguments to flush specific entities is deprecated and will not be supported in Doctrine ORM 3.0.');
        $this->_em->flush($entity);
    }

    public function testDeprecatedMerge(): void
    {
        $entity = new Country(456, 'United Kingdom');
        $this->_em->persist($entity);

        $this->expectDeprecationMessageSame('Method Doctrine\ORM\EntityManager::merge() is deprecated and will be removed in Doctrine ORM 3.0.');
        $this->_em->merge($entity);
    }

    public function testDeprecatedDetach(): void
    {
        $entity = new Country(456, 'United Kingdom');
        $this->_em->persist($entity);

        $this->expectDeprecationMessageSame('Method Doctrine\ORM\EntityManager::detach() is deprecated and will be removed in Doctrine ORM 3.0.');
        $this->_em->detach($entity);
    }

    public function testDeprecatedCopy(): void
    {
        $entity = new Country(456, 'United Kingdom');
        $this->_em->persist($entity);

        try {
            $this->expectDeprecationMessageSame('Method Doctrine\ORM\EntityManager::copy() is deprecated and will be removed in Doctrine ORM 3.0.');
            $this->_em->copy($entity);
        } catch (BadMethodCallException $e) {
            // do nothing
        }
    }
}
