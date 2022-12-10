<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use DateTime;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\Proxy;
use Doctrine\Tests\DbalExtensions\Connection;
use Doctrine\Tests\DbalExtensions\QueryLog;
use Doctrine\Tests\Models\Generic\DateTimeModel;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Tests\TestUtil;

use function assert;
use function realpath;
use function serialize;
use function unserialize;

class MergeProxiesTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('generic');

        parent::setUp();
    }

    /**
     * @group DDC-1392
     * @group DDC-1734
     * @group DDC-3368
     * @group #1172
     */
    public function testMergeDetachedUnInitializedProxy(): void
    {
        $detachedUninitialized = $this->_em->getReference(DateTimeModel::class, 123);

        $this->_em->clear();

        $managed = $this->_em->getReference(DateTimeModel::class, 123);

        self::assertSame($managed, $this->_em->merge($detachedUninitialized));

        self::assertFalse($managed->__isInitialized());
        self::assertFalse($detachedUninitialized->__isInitialized());
    }

    /**
     * @group DDC-1392
     * @group DDC-1734
     * @group DDC-3368
     * @group #1172
     */
    public function testMergeUnserializedUnInitializedProxy(): void
    {
        $detachedUninitialized = $this->_em->getReference(DateTimeModel::class, 123);

        $this->_em->clear();

        $managed = $this->_em->getReference(DateTimeModel::class, 123);

        self::assertSame(
            $managed,
            $this->_em->merge(unserialize(serialize($this->_em->merge($detachedUninitialized))))
        );

        self::assertFalse($managed->__isInitialized());
        self::assertFalse($detachedUninitialized->__isInitialized());
    }

    /**
     * @group DDC-1392
     * @group DDC-1734
     * @group DDC-3368
     * @group #1172
     */
    public function testMergeManagedProxy(): void
    {
        $managed = $this->_em->getReference(DateTimeModel::class, 123);

        self::assertSame($managed, $this->_em->merge($managed));

        self::assertFalse($managed->__isInitialized());
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
    public function testMergeWithExistingUninitializedManagedProxy(): void
    {
        $date = new DateTimeModel();

        $this->_em->persist($date);
        $this->_em->flush($date);
        $this->_em->clear();

        $managed = $this->_em->getReference(DateTimeModel::class, $date->id);

        self::assertInstanceOf(Proxy::class, $managed);
        self::assertFalse($managed->__isInitialized());

        $date->date = $dateTime = new DateTime();

        self::assertSame($managed, $this->_em->merge($date));
        self::assertTrue($managed->__isInitialized());
        self::assertSame($dateTime, $managed->date, 'Data was merged into the proxy after initialization');
    }

    /**
     * @group DDC-1392
     * @group DDC-1734
     * @group DDC-3368
     * @group #1172
     */
    public function testMergingProxyFromDifferentEntityManagerWithExistingManagedInstanceDoesNotReplaceInitializer(): void
    {
        $em1 = $this->createEntityManager();
        $em2 = $this->createEntityManager();

        $file1 = new DateTimeModel();
        $file2 = new DateTimeModel();

        $em1->persist($file1);
        $em2->persist($file2);
        $em1->flush();
        $em2->flush();
        $em1->clear();
        $em2->clear();

        $logger1 = $this->getResetQueryLogFromEntityManager($em1);
        $logger2 = $this->getResetQueryLogFromEntityManager($em2);

        $proxy1  = $em1->getReference(DateTimeModel::class, $file1->id);
        $proxy2  = $em2->getReference(DateTimeModel::class, $file1->id);
        $merged2 = $em2->merge($proxy1);

        self::assertNotSame($proxy1, $merged2);
        self::assertSame($proxy2, $merged2);

        self::assertFalse($proxy1->__isInitialized());
        self::assertFalse($proxy2->__isInitialized());

        $proxy1->__load();

        self::assertCount(
            1,
            $logger1->queries,
            'Loading the first proxy was done through the first entity manager'
        );
        self::assertCount(
            0,
            $logger2->queries,
            'No queries were executed on the second entity manager, as it is unrelated with the first proxy'
        );

        $proxy2->__load();

        self::assertCount(
            1,
            $logger1->queries,
            'Loading the second proxy does not affect the first entity manager'
        );
        self::assertCount(
            1,
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
    public function testMergingUnInitializedProxyDoesNotInitializeIt(): void
    {
        $em1 = $this->createEntityManager();
        $em2 = $this->createEntityManager();

        $file1 = new DateTimeModel();
        $file2 = new DateTimeModel();

        $em1->persist($file1);
        $em2->persist($file2);
        $em1->flush();
        $em2->flush();
        $em1->clear();
        $em2->clear();

        $logger1 = $this->getResetQueryLogFromEntityManager($em1);
        $logger2 = $this->getResetQueryLogFromEntityManager($em2);

        $unManagedProxy = $em1->getReference(DateTimeModel::class, $file1->id);
        $mergedInstance = $em2->merge($unManagedProxy);

        self::assertNotInstanceOf(Proxy::class, $mergedInstance);
        self::assertNotSame($unManagedProxy, $mergedInstance);
        self::assertFalse($unManagedProxy->__isInitialized());

        self::assertCount(
            0,
            $logger1->queries,
            'Loading the merged instance affected only the first entity manager'
        );
        self::assertCount(
            1,
            $logger2->queries,
            'Loading the merged instance was done via the second entity manager'
        );

        $unManagedProxy->__load();

        self::assertCount(
            1,
            $logger1->queries,
            'Loading the first proxy was done through the first entity manager'
        );
        self::assertCount(
            1,
            $logger2->queries,
            'No queries were executed on the second entity manager, as it is unrelated with the first proxy'
        );
    }

    private function createEntityManager(): EntityManagerInterface
    {
        $config = new Configuration();

        TestUtil::configureProxies($config);
        $config->setMetadataDriverImpl(ORMSetup::createDefaultAnnotationDriver(
            [realpath(__DIR__ . '/../../Models/Cache')]
        ));

        // always runs on sqlite to prevent multi-connection race-conditions with the test suite
        // multi-connection is not relevant for the purpose of checking locking here, but merely
        // to stub out DB-level access and intercept it
        $connection = DriverManager::getConnection(
            [
                'driver' => 'pdo_sqlite',
                'memory' => true,
                'wrapperClass' => Connection::class,
            ],
            $config
        );

        $entityManager = new EntityManager($connection, $config);

        (new SchemaTool($entityManager))->createSchema([$entityManager->getClassMetadata(DateTimeModel::class)]);

        return $entityManager;
    }

    private function getResetQueryLogFromEntityManager(EntityManagerInterface $entityManager): QueryLog
    {
        $connection = $entityManager->getConnection();
        assert($connection instanceof Connection);

        return $connection->queryLog->reset()->enable();
    }
}
