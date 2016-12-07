<?php

namespace Doctrine\Tests\ORM;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\ORM\Query\ResultSetMapping;
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
        $this->assertTrue($this->_em->isOpen());
        $this->_em->close();
        $this->assertFalse($this->_em->isOpen());
    }

    public function testGetConnection()
    {
        $this->assertInstanceOf('Doctrine\DBAL\Connection', $this->_em->getConnection());
    }

    public function testGetMetadataFactory()
    {
        $this->assertInstanceOf('Doctrine\ORM\Mapping\ClassMetadataFactory', $this->_em->getMetadataFactory());
    }

    public function testGetConfiguration()
    {
        $this->assertInstanceOf('Doctrine\ORM\Configuration', $this->_em->getConfiguration());
    }

    public function testGetUnitOfWork()
    {
        $this->assertInstanceOf('Doctrine\ORM\UnitOfWork', $this->_em->getUnitOfWork());
    }

    public function testGetProxyFactory()
    {
        $this->assertInstanceOf('Doctrine\ORM\Proxy\ProxyFactory', $this->_em->getProxyFactory());
    }

    public function testGetEventManager()
    {
        $this->assertInstanceOf('Doctrine\Common\EventManager', $this->_em->getEventManager());
    }

    public function testCreateNativeQuery()
    {
        $rsm = new ResultSetMapping();
        $query = $this->_em->createNativeQuery('SELECT foo', $rsm);

        $this->assertSame('SELECT foo', $query->getSql());
    }

    /**
     * @covers \Doctrine\ORM\EntityManager::createNamedNativeQuery
     */
    public function testCreateNamedNativeQuery()
    {
        $rsm = new ResultSetMapping();
        $this->_em->getConfiguration()->addNamedNativeQuery('foo', 'SELECT foo', $rsm);

        $query = $this->_em->createNamedNativeQuery('foo');

        $this->assertInstanceOf('Doctrine\ORM\NativeQuery', $query);
    }

    public function testCreateQueryBuilder()
    {
        $this->assertInstanceOf('Doctrine\ORM\QueryBuilder', $this->_em->createQueryBuilder());
    }

    public function testCreateQueryBuilderAliasValid()
    {
        $q = $this->_em->createQueryBuilder()
             ->select('u')->from('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $q2 = clone $q;

        $this->assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $q->getQuery()->getDql());
        $this->assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $q2->getQuery()->getDql());

        $q3 = clone $q;

        $this->assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $q3->getQuery()->getDql());
    }

    public function testCreateQuery_DqlIsOptional()
    {
        $this->assertInstanceOf('Doctrine\ORM\Query', $this->_em->createQuery());
    }

    public function testGetPartialReference()
    {
        $user = $this->_em->getPartialReference('Doctrine\Tests\Models\CMS\CmsUser', 42);
        $this->assertTrue($this->_em->contains($user));
        $this->assertEquals(42, $user->id);
        $this->assertNull($user->getName());
    }

    public function testCreateQuery()
    {
        $q = $this->_em->createQuery('SELECT 1');
        $this->assertInstanceOf('Doctrine\ORM\Query', $q);
        $this->assertEquals('SELECT 1', $q->getDql());
    }

    /**
     * @covers Doctrine\ORM\EntityManager::createNamedQuery
     */
    public function testCreateNamedQuery()
    {
        $this->_em->getConfiguration()->addNamedQuery('foo', 'SELECT 1');

        $query = $this->_em->createNamedQuery('foo');
        $this->assertInstanceOf('Doctrine\ORM\Query', $query);
        $this->assertEquals('SELECT 1', $query->getDql());
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

        $this->assertEquals('foo', $return);
    }

    public function testTransactionalAcceptsVariousCallables()
    {
        $this->assertSame('callback', $this->_em->transactional([$this, 'transactionalCallback']));
    }

    public function testTransactionalThrowsInvalidArgumentExceptionIfNonCallablePassed()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected argument of type "callable", got "object"');

        $this->_em->transactional($this);
    }

    public function transactionalCallback($em)
    {
        $this->assertSame($this->_em, $em);
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
