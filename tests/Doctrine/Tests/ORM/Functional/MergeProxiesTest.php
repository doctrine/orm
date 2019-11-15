<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Tests\Models\Generic\DateTimeModel;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Tests\VerifyDeprecations;

class MergeProxiesTest extends OrmFunctionalTestCase
{
    use VerifyDeprecations;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->useModelSet('generic');

        parent::setUp();
    }

    /** @after */
    public function ensureTestGeneratedDeprecationMessages() : void
    {
        $this->assertHasDeprecationMessages();
    }

    /**
     * @group DDC-1392
     * @group DDC-1734
     * @group DDC-3368
     * @group #1172
     */
    public function testMergeDetachedUnInitializedProxy()
    {
        $detachedUninitialized = $this->_em->getReference(DateTimeModel::class, 123);

        $this->_em->clear();

        $managed = $this->_em->getReference(DateTimeModel::class, 123);

        $this->assertSame($managed, $this->_em->merge($detachedUninitialized));

        $this->assertFalse($managed->__isInitialized());
        $this->assertFalse($detachedUninitialized->__isInitialized());
    }

    /**
     * @group DDC-1392
     * @group DDC-1734
     * @group DDC-3368
     * @group #1172
     */
    public function testMergeUnserializedUnInitializedProxy()
    {
        $detachedUninitialized = $this->_em->getReference(DateTimeModel::class, 123);

        $this->_em->clear();

        $managed = $this->_em->getReference(DateTimeModel::class, 123);

        $this->assertSame(
            $managed,
            $this->_em->merge(unserialize(serialize($this->_em->merge($detachedUninitialized))))
        );

        $this->assertFalse($managed->__isInitialized());
        $this->assertFalse($detachedUninitialized->__isInitialized());
    }

    /**
     * @group DDC-1392
     * @group DDC-1734
     * @group DDC-3368
     * @group #1172
     */
    public function testMergeManagedProxy()
    {
        $managed = $this->_em->getReference(DateTimeModel::class, 123);

        $this->assertSame($managed, $this->_em->merge($managed));

        $this->assertFalse($managed->__isInitialized());
    }

    /**
     * @group DDC-1392
     * @group DDC-1734
     * @group DDC-3368
     * @group #1172
     *
     * Bug discovered while working on DDC-2704 - merging towards un-initialized proxies does not initialize them,
     * causing merged data to be lost when they are actually initialized
     */
    public function testMergeWithExistingUninitializedManagedProxy()
    {
        $date = new DateTimeModel();

        $this->_em->persist($date);
        $this->_em->flush($date);
        $this->_em->clear();

        $managed = $this->_em->getReference(DateTimeModel::class, $date->id);

        $this->assertInstanceOf(Proxy::class, $managed);
        $this->assertFalse($managed->__isInitialized());

        $date->date = $dateTime = new \DateTime();

        $this->assertSame($managed, $this->_em->merge($date));
        $this->assertTrue($managed->__isInitialized());
        $this->assertSame($dateTime, $managed->date, 'Data was merged into the proxy after initialization');
    }

    /**
     * @group DDC-1392
     * @group DDC-1734
     * @group DDC-3368
     * @group #1172
     */
    public function testMergingProxyFromDifferentEntityManagerWithExistingManagedInstanceDoesNotReplaceInitializer()
    {
        $em1 = $this->createEntityManager($logger1 = new DebugStack());
        $em2 = $this->createEntityManager($logger2 = new DebugStack());

        $file1 = new DateTimeModel();
        $file2 = new DateTimeModel();

        $em1->persist($file1);
        $em2->persist($file2);
        $em1->flush();
        $em2->flush();
        $em1->clear();
        $em2->clear();

        $queryCount1 = count($logger1->queries);
        $queryCount2 = count($logger2->queries);

        $proxy1  = $em1->getReference(DateTimeModel::class, $file1->id);
        $proxy2  = $em2->getReference(DateTimeModel::class, $file1->id);
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

    /**
     * @group DDC-1392
     * @group DDC-1734
     * @group DDC-3368
     * @group #1172
     */
    public function testMergingUnInitializedProxyDoesNotInitializeIt()
    {
        $em1 = $this->createEntityManager($logger1 = new DebugStack());
        $em2 = $this->createEntityManager($logger2 = new DebugStack());

        $file1 = new DateTimeModel();
        $file2 = new DateTimeModel();

        $em1->persist($file1);
        $em2->persist($file2);
        $em1->flush();
        $em2->flush();
        $em1->clear();
        $em2->clear();

        $queryCount1 = count($logger1->queries);
        $queryCount2 = count($logger1->queries);

        $unManagedProxy = $em1->getReference(DateTimeModel::class, $file1->id);
        $mergedInstance = $em2->merge($unManagedProxy);

        $this->assertNotInstanceOf(Proxy::class, $mergedInstance);
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
     * @return EntityManager
     */
    private function createEntityManager(SQLLogger $logger)
    {
        $config = new Configuration();

        $config->setProxyDir(realpath(__DIR__ . '/../../Proxies'));
        $config->setProxyNamespace('Doctrine\Tests\Proxies');
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(
            [realpath(__DIR__ . '/../../Models/Cache')],
            true
        ));
        $config->setSQLLogger($logger);

        // always runs on sqlite to prevent multi-connection race-conditions with the test suite
        // multi-connection is not relevant for the purpose of checking locking here, but merely
        // to stub out DB-level access and intercept it
        $connection = DriverManager::getConnection(
            [
                'driver' => 'pdo_sqlite',
                'memory' => true
            ],
            $config
        );


        $entityManager = EntityManager::create($connection, $config);

        (new SchemaTool($entityManager))->createSchema([$entityManager->getClassMetadata(DateTimeModel::class)]);

        return $entityManager;
    }
}
