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

    public function testMergeDetachedUnInitializedProxy()
    {
        $detachedUninitialized = $this->_em->getReference(MUPFile::CLASSNAME, 123);

        $this->_em->clear();

        $managed = $this->_em->getReference(MUPFile::CLASSNAME, 123);

        $this->assertSame($managed, $this->_em->merge($detachedUninitialized));

        $this->assertFalse($managed->__isInitialized());
        $this->assertFalse($detachedUninitialized->__isInitialized());
    }

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

    public function testMergeManagedProxy()
    {
        $managed = $this->_em->getReference(MUPFile::CLASSNAME, 123);

        $this->assertSame($managed, $this->_em->merge($managed));

        $this->assertFalse($managed->__isInitialized());
    }

    public function testMergingProxyFromDifferentEntityManagerDoesNotReplaceInitializer()
    {
        $em1 = $this->createEntityManager($logger1 = new DebugStack());
        $em2 = $this->createEntityManager($logger2 = new DebugStack());

        $file1 = new MUPFile();

        $em1->persist($file1);
        $em1->flush();
        $em1->clear();

        $queryCount1 = count($logger1->queries);
        $queryCount2 = count($logger1->queries);

        $proxy1 = $em1->getReference(MUPFile::CLASSNAME, $file1->fileId);
        $proxy2 = $em2->getReference(MUPFile::CLASSNAME, $file1->fileId);
        $merged2  = $em2->merge($proxy1);

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

    public function testMergeDetachedIntoEntity() {

        $file = new MUPFile;

        $picture = new MUPPicture;
        $picture->file = $file;

        $em = $this->_em;
        $em->persist($picture);
        $em->flush();
        $em->clear();

        $fileId = $file->fileId;
        $pictureId = $picture->pictureId;

        $picture = $em->find(__NAMESPACE__ . '\MUPPicture', $pictureId);

        $em->clear();

        $file = $em->find(__NAMESPACE__ . '\MUPFile', $fileId);

        $picture = $em->merge($picture);

        $this->assertEquals($file, $picture->file, "Detached proxy was not merged into managed entity");
    }

    public function testMergeUnserializedIntoProxy() {

        $file = new MUPFile;

        $picture = new MUPPicture;
        $picture->file = $file;

        $picture2 = new MUPPicture;
        $picture2->file = $file;

        $em = $this->_em;
        $em->persist($picture);
        $em->persist($picture2);
        $em->flush();
        $em->clear();

        $pictureId = $picture->pictureId;
        $picture2Id = $picture2->pictureId;

        $picture = $em->find(__NAMESPACE__ . '\MUPPicture', $pictureId);
        $serializedPicture = serialize($picture);

        $em->clear();

        $picture2 = $em->find(__NAMESPACE__ . '\MUPPicture', $picture2Id);
        $this->assertFalse($picture->file->__isInitialized());
        $picture = unserialize($serializedPicture);

        $this->assertTrue($picture->file instanceof Proxy);
        $this->assertFalse($picture->file->__isInitialized());

        $picture = $em->merge($picture);

        $this->assertTrue($picture->file instanceof Proxy);
        $this->assertFalse($picture->file->__isInitialized(), 'Proxy has been initialized during merge.');

        $this->assertEquals($picture2->file, $picture->file, "Unserialized proxy was not merged into managed proxy");
    }

    public function testMergeDetachedIntoProxy() {

        $file = new MUPFile;

        $picture = new MUPPicture;
        $picture->file = $file;

        $picture2 = new MUPPicture;
        $picture2->file = $file;

        $em = $this->_em;
        $em->persist($picture);
        $em->persist($picture2);
        $em->flush();
        $em->clear();

        $pictureId = $picture->pictureId;
        $picture2Id = $picture2->pictureId;

        $picture = $em->find(__NAMESPACE__ . '\MUPPicture', $pictureId);

        $em->clear();

        $picture2 = $em->find(__NAMESPACE__ . '\MUPPicture', $picture2Id);

        $this->assertTrue($picture->file instanceof Proxy);
        $this->assertFalse($picture->file->__isInitialized());

        $picture = $em->merge($picture);

        $this->assertTrue($picture->file instanceof Proxy);
        $this->assertFalse($picture->file->__isInitialized(), 'Proxy has been initialized during merge.');

        $this->assertEquals($picture2->file, $picture->file, "Detached proxy was not merged into managed proxy");
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
