<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\UnitOfWork;

/**
 * @group DDC-1392
 */
class DDC1392Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(DDC1392File::class),
                $this->em->getClassMetadata(DDC1392Picture::class),
                ]
            );
        } catch (\Exception $ignored) {
        }
    }

    public function testFailingCase()
    {
        $file = new DDC1392File;

        $picture = new DDC1392Picture;
        $picture->setFile($file);

        $em = $this->em;
        $em->persist($picture);
        $em->flush();
        $em->clear();

        $fileId = $file->getFileId();
        $pictureId = $picture->getPictureId();

        self::assertTrue($fileId > 0);

        $picture = $em->find(DDC1392Picture::class, $pictureId);
        self::assertEquals(UnitOfWork::STATE_MANAGED, $em->getUnitOfWork()->getEntityState($picture->getFile()), "Lazy Proxy should be marked MANAGED.");

        $file = $picture->getFile();

        // With this activated there will be no problem
        //$file->__load();

        $picture->setFile(null);

        $em->clear();

        $em->merge($file);

        $em->flush();

        $q = $this->em->createQuery("SELECT COUNT(e) FROM " . __NAMESPACE__ . '\DDC1392File e');
        $result = $q->getSingleScalarResult();

        self::assertEquals(1, $result);
    }
}

/**
 * @Entity
 */
class DDC1392Picture
{
    /**
     * @Column(name="picture_id", type="integer")
     * @Id @GeneratedValue
     */
    private $pictureId;

    /**
     * @ManyToOne(targetEntity="DDC1392File", cascade={"persist", "remove"})
     * @JoinColumn(name="file_id", referencedColumnName="file_id")
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
     * Set file
     */
    public function setFile($value = null)
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
class DDC1392File
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
