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
    private $em;

    function setUp()
    {
        parent::setUp();
        $this->em = $this->getTestEntityManager();
    }

    /**
     * @group DDC-899
     */
    public function testIsOpen()
    {
        self::assertTrue($this->em->isOpen());
        $this->em->close();
        self::assertFalse($this->em->isOpen());
    }

    public function testGetConnection()
    {
        self::assertInstanceOf(Connection::class, $this->em->getConnection());
    }

    public function testGetMetadataFactory()
    {
        self::assertInstanceOf(ClassMetadataFactory::class, $this->em->getMetadataFactory());
    }

    public function testGetConfiguration()
    {
        self::assertInstanceOf(Configuration::class, $this->em->getConfiguration());
    }

    public function testGetUnitOfWork()
    {
        self::assertInstanceOf(UnitOfWork::class, $this->em->getUnitOfWork());
    }

    public function testGetProxyFactory()
    {
        self::assertInstanceOf(ProxyFactory::class, $this->em->getProxyFactory());
    }

    public function testGetEventManager()
    {
        self::assertInstanceOf(EventManager::class, $this->em->getEventManager());
    }

    public function testCreateNativeQuery()
    {
        $rsm = new ResultSetMapping();
        $query = $this->em->createNativeQuery('SELECT foo', $rsm);

        self::assertSame('SELECT foo', $query->getSql());
    }

    /**
     * @covers \Doctrine\ORM\EntityManager::createNamedNativeQuery
     */
    public function testCreateNamedNativeQuery()
    {
        $rsm = new ResultSetMapping();
        $this->em->getConfiguration()->addNamedNativeQuery('foo', 'SELECT foo', $rsm);

        $query = $this->em->createNamedNativeQuery('foo');

        self::assertInstanceOf(NativeQuery::class, $query);
    }

    public function testCreateQueryBuilder()
    {
        self::assertInstanceOf(QueryBuilder::class, $this->em->createQueryBuilder());
    }

    public function testCreateQueryBuilderAliasValid()
    {
        $q = $this->em->createQueryBuilder()
             ->select('u')->from(CmsUser::class, 'u');
        $q2 = clone $q;

        self::assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $q->getQuery()->getDql());
        self::assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $q2->getQuery()->getDql());

        $q3 = clone $q;

        self::assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $q3->getQuery()->getDql());
    }

    public function testCreateQuery_DqlIsOptional()
    {
        self::assertInstanceOf(Query::class, $this->em->createQuery());
    }

    public function testGetPartialReference()
    {
        $user = $this->em->getPartialReference(CmsUser::class, 42);
        self::assertTrue($this->em->contains($user));
        self::assertEquals(42, $user->id);
        self::assertNull($user->getName());
    }

    public function testCreateQuery()
    {
        $q = $this->em->createQuery('SELECT 1');
        self::assertInstanceOf(Query::class, $q);
        self::assertEquals('SELECT 1', $q->getDql());
    }

    /**
     * @covers Doctrine\ORM\EntityManager::createNamedQuery
     */
    public function testCreateNamedQuery()
    {
        $this->em->getConfiguration()->addNamedQuery('foo', 'SELECT 1');

        $query = $this->em->createNamedQuery('foo');
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

        $this->em->$methodName(null);
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

        $this->em->close();
        $this->em->$methodName(new \stdClass());
    }

    public function dataToBeReturnedByTransactional()
    {
        return [
            [null],
            [false],
            ['foo'],
        ];
    }

    /**
     * @dataProvider dataToBeReturnedByTransactional
     */
    public function testTransactionalAcceptsReturn($value)
    {
        self::assertSame(
            $value,
            $this->em->transactional(function ($em) use ($value) {
                return $value;
            })
        );
    }

    public function testTransactionalAcceptsVariousCallables()
    {
        self::assertSame('callback', $this->em->transactional([$this, 'transactionalCallback']));
    }

    public function transactionalCallback($em)
    {
        self::assertSame($this->em, $em);
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

        $this->em->clear($entity);
    }

    /**
     * @group 6017
     */
    public function testClearManagerWithUnknownEntityName()
    {
        $this->expectException(MappingException::class);

        $this->em->clear(uniqid('nonExisting', true));
    }

    /**
     * @group 6017
     */
    public function testClearManagerWithProxyClassName()
    {
        $proxy = $this->em->getReference(Country::class, ['id' => rand(457, 100000)]);

        $entity = new Country(456, 'United Kingdom');

        $this->em->persist($entity);

        $this->assertTrue($this->em->contains($entity));

        $this->em->clear(get_class($proxy));

        $this->assertFalse($this->em->contains($entity));
    }

    /**
     * @group 6017
     */
    public function testClearManagerWithNullValue()
    {
        $entity = new Country(456, 'United Kingdom');

        $this->em->persist($entity);

        $this->assertTrue($this->em->contains($entity));

        $this->em->clear(null);

        $this->assertFalse($this->em->contains($entity));
    }
}
