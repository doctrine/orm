<?php

namespace Doctrine\Tests\ORM;

use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Doctrine\Tests\Models\Generic\DateTimeModel;
use Doctrine\Tests\TestUtil;

class EntityManagerTest extends \Doctrine\Tests\OrmTestCase
{
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
        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
        $query = $this->_em->createNativeQuery('SELECT foo', $rsm);

        $this->assertSame('SELECT foo', $query->getSql());
    }

    /**
     * @covers Doctrine\ORM\EntityManager::createNamedNativeQuery
     */
    public function testCreateNamedNativeQuery()
    {
        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
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
        return array(
            array('persist'),
            array('remove'),
            array('merge'),
            array('refresh'),
            array('detach')
        );
    }

    /**
     * @dataProvider dataMethodsAffectedByNoObjectArguments
     */
    public function testThrowsExceptionOnNonObjectValues($methodName) {
        $this->setExpectedException('Doctrine\ORM\ORMInvalidArgumentException',
            'EntityManager#'.$methodName.'() expects parameter 1 to be an entity object, NULL given.');
        $this->_em->$methodName(null);
    }

    static public function dataAffectedByErrorIfClosedException()
    {
        return array(
            array('flush'),
            array('persist'),
            array('remove'),
            array('merge'),
            array('refresh'),
        );
    }

    /**
     * @dataProvider dataAffectedByErrorIfClosedException
     * @param string $methodName
     */
    public function testAffectedByErrorIfClosedException($methodName)
    {
        $this->setExpectedException('Doctrine\ORM\ORMException', 'closed');

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
        $this->assertSame('callback', $this->_em->transactional(array($this, 'transactionalCallback')));
    }

    public function testTransactionalThrowsInvalidArgumentExceptionIfNonCallablePassed()
    {
        $this->setExpectedException('InvalidArgumentException', 'Expected argument of type "callable", got "object"');
        $this->_em->transactional($this);
    }

    public function transactionalCallback($em)
    {
        $this->assertSame($this->_em, $em);
        return 'callback';
    }

    public function testMergeDetachedUnInitializedProxy()
    {
        $em = $this->createEntityManager();

        $detachedUninitialized = $em->getReference(DateTimeModel::CLASSNAME, 123);

        $em->clear();

        $managed = $em->getReference(DateTimeModel::CLASSNAME, 123);

        $this->assertSame($managed, $em->merge($detachedUninitialized));

        $this->assertFalse($managed->__isInitialized());
        $this->assertFalse($detachedUninitialized->__isInitialized());
    }

    public function testMergeUnserializedUnInitializedProxy()
    {
        $em = $this->createEntityManager();

        $detachedUninitialized = $em->getReference(DateTimeModel::CLASSNAME, 123);

        $em->clear();

        $managed = $em->getReference(DateTimeModel::CLASSNAME, 123);

        $this->assertSame(
            $managed,
            $em->merge(unserialize(serialize($em->merge($detachedUninitialized))))
        );

        $this->assertFalse($managed->__isInitialized());
        $this->assertFalse($detachedUninitialized->__isInitialized());
    }

    public function testMergeManagedProxy()
    {
        $em = $this->createEntityManager();

        $managed = $em->getReference(DateTimeModel::CLASSNAME, 123);

        $this->assertSame($managed, $em->merge($managed));

        $this->assertFalse($managed->__isInitialized());
    }

    public function testMergingProxyFromDifferentEntityManagerWithExistingManagedInstanceDoesNotReplaceInitializer()
    {
        $em1 = $this->createEntityManager($logger1 = new DebugStack());
        $em2 = $this->createEntityManager($logger2 = new DebugStack());

        $entity1 = new DateTimeModel();
        $entity2 = new DateTimeModel();

        $em1->persist($entity1);
        $em2->persist($entity2);
        $em1->flush();
        $em2->flush();
        $em1->clear();
        $em2->clear();

        $queryCount1 = count($logger1->queries);
        $queryCount2 = count($logger2->queries);

        $proxy1  = $em1->getReference(DateTimeModel::CLASSNAME, $entity1->id);
        $proxy2  = $em2->getReference(DateTimeModel::CLASSNAME, $entity1->id);
        $merged2 = $em2->merge($proxy1);

        $this->assertNotSame($proxy1, $merged2);
        $this->assertSame($proxy2, $merged2);

        $this->assertFalse($proxy1->__isInitialized());
        $this->assertFalse($proxy2->__isInitialized());

        $proxy1->__load();

        $this->assertCount(
            $queryCount1 + 1,
            $logger1->queries,
            'Loading the first proxy was done through the first entity manager'
        );
        $this->assertCount(
            $queryCount2,
            $logger2->queries,
            'No queries were executed on the second entity manager, as it is unrelated with the first proxy'
        );

        $proxy2->__load();

        $this->assertCount(
            $queryCount1 + 1,
            $logger1->queries,
            'Loading the second proxy does not affect the first entity manager'
        );
        $this->assertCount(
            $queryCount2 + 1,
            $logger2->queries,
            'Loading of the second proxy instance was done through the second entity manager'
        );
    }

    public function testMergingUnInitializedProxyDoesNotInitializeIt()
    {
        $em1 = $this->createEntityManager($logger1 = new DebugStack());
        $em2 = $this->createEntityManager($logger2 = new DebugStack());

        $entity1 = new DateTimeModel();
        $entity2 = new DateTimeModel();

        $em1->persist($entity1);
        $em2->persist($entity2);
        $em1->flush();
        $em2->flush();
        $em1->clear();
        $em2->clear();

        $queryCount1 = count($logger1->queries);
        $queryCount2 = count($logger1->queries);

        $unManagedProxy = $em1->getReference(DateTimeModel::CLASSNAME, $entity1->id);
        $mergedInstance = $em2->merge($unManagedProxy);

        $this->assertNotInstanceOf('Doctrine\Common\Proxy\Proxy', $mergedInstance);
        $this->assertNotSame($unManagedProxy, $mergedInstance);
        $this->assertFalse($unManagedProxy->__isInitialized());

        $this->assertCount(
            $queryCount1,
            $logger1->queries,
            'Loading the merged instance affected only the first entity manager'
        );
        $this->assertCount(
            $queryCount1 + 1,
            $logger2->queries,
            'Loading the merged instance was done via the second entity manager'
        );

        $unManagedProxy->__load();

        $this->assertCount(
            $queryCount1 + 1,
            $logger1->queries,
            'Loading the first proxy was done through the first entity manager'
        );
        $this->assertCount(
            $queryCount2 + 1,
            $logger2->queries,
            'No queries were executed on the second entity manager, as it is unrelated with the first proxy'
        );
    }

    /**
     * @param SQLLogger $logger
     *
     * @return \Doctrine\ORM\EntityManager
     */
    private function createEntityManager(SQLLogger $logger = null)
    {
        $config = new Configuration();

        $config->setProxyDir(realpath(__DIR__ . '/../Proxies/../..'));
        $config->setProxyNamespace('Doctrine\Tests\Proxies');
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(
            array(realpath(__DIR__ . '/../Models/Cache')),
            true
        ));

        $connection = TestUtil::getConnection();

        $connection->getConfiguration()->setSQLLogger($logger);

        $entityManager = EntityManager::create($connection, $config);

        try {
            (new SchemaTool($entityManager))
                ->createSchema([$entityManager->getClassMetadata(DateTimeModel::CLASSNAME)]);
        } catch (ToolsException $ignored) {
            // tables were already created
        }

        return $entityManager;
    }
}
