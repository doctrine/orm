<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;
use Doctrine\ORM\UnitOfWork;

require_once __DIR__ . '/../../../TestInit.php';

class DDC353Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC353File'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC353Picture'),
            ));
        } catch(\Exception $ignored) {}
    }

    public function testWorkingCase()
    {
        $file = new DDC353File;

        $picture = new DDC353Picture;
        $picture->setFile($file);

        $em = $this->_em;
        $em->persist($picture);
        $em->flush();
        $em->clear();

        $fileId = $file->getFileId();
        $this->assertTrue($fileId > 0);

        $file = $em->getReference('Doctrine\Tests\ORM\Functional\Ticket\DDC353File', $fileId);
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $em->getUnitOfWork()->getEntityState($file), "Reference Proxy should be marked MANAGED.");

        $picture = $em->find('Doctrine\Tests\ORM\Functional\Ticket\DDC353Picture', $picture->getPictureId());
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $em->getUnitOfWork()->getEntityState($picture->getFile()), "Lazy Proxy should be marked MANAGED.");

        $em->remove($picture);
        $em->flush();
    }

    public function testFailingCase()
    {
        $file = new DDC353File;

        $picture = new DDC353Picture;
        $picture->setFile($file);

        $em = $this->_em;
        $em->persist($picture);
        $em->flush();
        $em->clear();

        $fileId = $file->getFileId();
        $pictureId = $picture->getPictureId();
        
        $this->assertTrue($fileId > 0);

        $picture = $em->find('Doctrine\Tests\ORM\Functional\Ticket\DDC353Picture', $pictureId);
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $em->getUnitOfWork()->getEntityState($picture->getFile()), "Lazy Proxy should be marked MANAGED.");

        $em->remove($picture);
        $em->flush();
    }
}

/**
 * @Entity
 */
class DDC353Picture
{
    /**
     * @Column(name="picture_id", type="integer")
     * @Id @GeneratedValue
     */
    private $pictureId;

    /**
     * @ManyToOne(targetEntity="DDC353File", cascade={"persist", "remove"})
     * @JoinColumns({
     *   @JoinColumn(name="file_id", referencedColumnName="file_id")
     * })
     */
    private $file;

    /**
     * Get pictureId
     */
    public function getPictureId()
    {
        return $this->pictureId;
    }

    /**
     * Set product
     */
    public function setProduct($value)
    {
        $this->product = $value;
    }

    /**
     * Get product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Set file
     */
    public function setFile($value)
    {
        $this->file = $value;
    }

    /**
     * Get file
     */
    public function getFile()
    {
        return $this->file;
    }
}

/**
 * @Entity
 */
class DDC353File
{
    /**
     * @Column(name="file_id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    public $fileId;

    /**
     * Get fileId
     */
    public function getFileId()
    {
        return $this->fileId;
    }
}
