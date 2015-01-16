<?php


namespace Doctrine\Tests\ORM\Functional;


use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Doctrine\Tests\TestUtil;

class MergeUninitializedProxyTest extends \Doctrine\Tests\OrmFunctionalTestCase {

    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                    $this->_em->getClassMetadata(__NAMESPACE__ . '\MUPFile'),
                    $this->_em->getClassMetadata(__NAMESPACE__ . '\MUPPicture'),
                ));
        } catch (ToolsException $ignored) {
        }
    }

    /**
     * @group DDC-1392
     * @group DDC-1734
     * @group DDC-3368
     * @group #1172
     */
    public function testMergeDetachedUnInitializedProxy()
    {
        $detachedUninitialized = $this->_em->getReference(MUPFile::CLASSNAME, 123);

        $this->_em->clear();

        $managed = $this->_em->getReference(MUPFile::CLASSNAME, 123);

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
        $detachedUninitialized = $this->_em->getReference(MUPFile::CLASSNAME, 123);

        $this->_em->clear();

        $managed = $this->_em->getReference(MUPFile::CLASSNAME, 123);

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
        $managed = $this->_em->getReference(MUPFile::CLASSNAME, 123);

        $this->assertSame($managed, $this->_em->merge($managed));

        $this->assertFalse($managed->__isInitialized());
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

        $file1 = new MUPFile();
        $file2 = new MUPFile();

        $em1->persist($file1);
        $em2->persist($file2);
        $em1->flush();
        $em2->flush();
        $em1->clear();
        $em2->clear();

        $queryCount1 = count($logger1->queries);
        $queryCount2 = count($logger2->queries);

        $proxy1  = $em1->getReference(MUPFile::CLASSNAME, $file1->fileId);
        $proxy2  = $em2->getReference(MUPFile::CLASSNAME, $file1->fileId);
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

        $file1 = new MUPFile();
        $file2 = new MUPFile();

        $em1->persist($file1);
        $em2->persist($file2);
        $em1->flush();
        $em2->flush();
        $em1->clear();
        $em2->clear();

        $queryCount1 = count($logger1->queries);
        $queryCount2 = count($logger1->queries);

        $unManagedProxy = $em1->getReference(MUPFile::CLASSNAME, $file1->fileId);
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
     * @return EntityManager
     */
    private function createEntityManager(SQLLogger $logger)
    {
        $config = new Configuration();

        $config->setProxyDir(realpath(__DIR__ . '/../../Proxies/../..'));
        $config->setProxyNamespace('Doctrine\Tests\Proxies');
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(
            array(realpath(__DIR__ . '/../../Models/Cache')),
            true
        ));

        $connection = TestUtil::getConnection();

        $connection->getConfiguration()->setSQLLogger($logger);

        $entityManager = EntityManager::create($connection, $config);

        try {
            (new SchemaTool($entityManager))
                ->createSchema([$this->_em->getClassMetadata(MUPFile::CLASSNAME)]);
        } catch (ToolsException $ignored) {
            // tables were already created
        }

        return $entityManager;
    }
}

/**
 * @Entity
 */
class MUPPicture
{
    /**
     * @Column(name="picture_id", type="integer")
     * @Id @GeneratedValue
     */
    public $pictureId;

    /**
     * @ManyToOne(targetEntity="MUPFile", cascade={"persist", "merge"})
     * @JoinColumn(name="file_id", referencedColumnName="file_id")
     */
    public $file;

}

/** @Entity */
class MUPFile
{
    const CLASSNAME = __CLASS__;

    /** @Column(name="file_id", type="integer") @Id @GeneratedValue(strategy="AUTO") */
    public $fileId;

    /** @Column(type="string", nullable=true) */
    private $contents;
}
