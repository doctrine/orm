<?php

namespace Doctrine\Tests\ORM;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\MappingException;
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
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\GeoNames\Country;
use Doctrine\Tests\OrmTestCase;

class EntityManagerTest extends OrmTestCase
{
    /**
     * @var EntityManager
     */
    private $_em;

    function setUp()
    {
        parent::setUp();
        $this->_em = $this->_getTestEntityManager();
    }

    /**
     * @group DDC-899
     */
    public function testIsOpen()
    {
        self::assertTrue($this->_em->isOpen());
        $this->_em->close();
        self::assertFalse($this->_em->isOpen());
    }

    public function testGetConnection()
    {
        self::assertInstanceOf(Connection::class, $this->_em->getConnection());
    }

    public function testGetMetadataFactory()
    {
        self::assertInstanceOf(ClassMetadataFactory::class, $this->_em->getMetadataFactory());
    }

    public function testGetConfiguration()
    {
        self::assertInstanceOf(Configuration::class, $this->_em->getConfiguration());
    }

    public function testGetUnitOfWork()
    {
        self::assertInstanceOf(UnitOfWork::class, $this->_em->getUnitOfWork());
    }

    public function testGetProxyFactory()
    {
        self::assertInstanceOf(ProxyFactory::class, $this->_em->getProxyFactory());
    }

    public function testGetEventManager()
    {
        self::assertInstanceOf(EventManager::class, $this->_em->getEventManager());
    }

    public function testCreateNativeQuery()
    {
        $rsm = new ResultSetMapping();
        $query = $this->_em->createNativeQuery('SELECT foo', $rsm);

        self::assertSame('SELECT foo', $query->getSql());
    }

    /**
     * @covers \Doctrine\ORM\EntityManager::createNamedNativeQuery
     */
    public function testCreateNamedNativeQuery()
    {
        $rsm = new ResultSetMapping();
        $this->_em->getConfiguration()->addNamedNativeQuery('foo', 'SELECT foo', $rsm);

        $query = $this->_em->createNamedNativeQuery('foo');

        self::assertInstanceOf(NativeQuery::class, $query);
    }

    public function testCreateQueryBuilder()
    {
        self::assertInstanceOf(QueryBuilder::class, $this->_em->createQueryBuilder());
    }

    public function testCreateQueryBuilderAliasValid()
    {
        $q = $this->_em->createQueryBuilder()
             ->select('u')->from(CmsUser::class, 'u');
        $q2 = clone $q;

        self::assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $q->getQuery()->getDql());
        self::assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $q2->getQuery()->getDql());

        $q3 = clone $q;

        self::assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $q3->getQuery()->getDql());
    }

    public function testCreateQuery_DqlIsOptional()
    {
        self::assertInstanceOf(Query::class, $this->_em->createQuery());
    }

    public function testGetPartialReference()
    {
        $user = $this->_em->getPartialReference(CmsUser::class, 42);
        self::assertTrue($this->_em->contains($user));
        self::assertEquals(42, $user->id);
        self::assertNull($user->getName());
    }

    public function testCreateQuery()
    {
        $q = $this->_em->createQuery('SELECT 1');
        self::assertInstanceOf(Query::class, $q);
        self::assertEquals('SELECT 1', $q->getDql());
    }

    /**
     * @covers Doctrine\ORM\EntityManager::createNamedQuery
     */
    public function testCreateNamedQuery()
    {
        $this->_em->getConfiguration()->addNamedQuery('foo', 'SELECT 1');

        $query = $this->_em->createNamedQuery('foo');
        self::assertInstanceOf(Query::class, $query);
        self::assertEquals('SELECT 1', $query->getDql());
    }

    static public function dataMethodsAffectedByNoObjectArguments()
    {
        return [
            ['persist'],
            ['remove'],
            ['merge'],
            ['refresh'],
            ['detach']
        ];
    }

    /**
     * @dataProvider dataMethodsAffectedByNoObjectArguments
     */
    public function testThrowsExceptionOnNonObjectValues($methodName) {
        $this->expectException(ORMInvalidArgumentException::class);
        $this->expectExceptionMessage('EntityManager#' . $methodName . '() expects parameter 1 to be an entity object, NULL given.');

        $this->_em->$methodName(null);
    }

    static public function dataAffectedByErrorIfClosedException()
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
     * @param string $methodName
     */
    public function testAffectedByErrorIfClosedException($methodName)
    {
        $this->expectException(ORMException::class);
        $this->expectExceptionMessage('closed');

        $this->_em->close();
        $this->_em->$methodName(new \stdClass());
    }

    /**
     * @group DDC-1125
     */
    public function testTransactionalAcceptsReturn()
    {
        $return = $this->_em->transactional(function ($em) {
            return 'foo';
        });

        self::assertEquals('foo', $return);
    }

    public function testTransactionalAcceptsVariousCallables()
    {
        self::assertSame('callback', $this->_em->transactional([$this, 'transactionalCallback']));
    }

    public function testTransactionalAcceptsReturnFalse()
    {
        $return = $this->_em->transactional(function ($em) {
            return false;
        });

        self::assertEquals(false, $return);
    }

    public function transactionalCallback($em)
    {
        self::assertSame($this->_em, $em);
        return 'callback';
    }

    public function testCreateInvalidConnection()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid $connection argument of type integer given: "1".');

        $config = new Configuration();
        $config->setMetadataDriverImpl($this->createMock(MappingDriver::class));
        EntityManager::create(1, $config);
    }

    /**
     * @group 6017
     */
    public function testClearManagerWithObject()
    {
        $entity = new Country(456, 'United Kingdom');

        $this->expectException(ORMInvalidArgumentException::class);

        $this->_em->clear($entity);
    }

    /**
     * @group 6017
     */
    public function testClearManagerWithUnknownEntityName()
    {
        $this->expectException(MappingException::class);

        $this->_em->clear(uniqid('nonExisting', true));
    }

    /**
     * @group 6017
     */
    public function testClearManagerWithProxyClassName()
    {
        $proxy = $this->_em->getReference(Country::class, ['id' => rand(457, 100000)]);

        $entity = new Country(456, 'United Kingdom');

        $this->_em->persist($entity);

        $this->assertTrue($this->_em->contains($entity));

        $this->_em->clear(get_class($proxy));

        $this->assertFalse($this->_em->contains($entity));
    }

    /**
     * @group 6017
     */
    public function testClearManagerWithNullValue()
    {
        $entity = new Country(456, 'United Kingdom');

        $this->_em->persist($entity);

        $this->assertTrue($this->_em->contains($entity));

        $this->_em->clear(null);

        $this->assertFalse($this->_em->contains($entity));
    }
}
