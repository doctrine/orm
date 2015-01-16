<?php


namespace Doctrine\Tests\ORM\Functional;


use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\Tools\ToolsException;

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

    public function testMergeUnserializedIntoEntity() {

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
        $serializedPicture = serialize($picture);

        $em->clear();

        $file = $em->find(__NAMESPACE__ . '\MUPFile', $fileId);
        $picture = unserialize($serializedPicture);

        $picture = $em->merge($picture);

        $this->assertEquals($file, $picture->file, "Unserialized proxy was not merged into managed entity");
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

/**
 * @Entity
 */
class MUPFile
{
    const CLASSNAME = __CLASS__;

    /**
     * @Column(name="file_id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    public $fileId;

}
