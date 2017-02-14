<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;

/**
 * @group DDC-1509
 */
class DDC1509Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    protected function setUp()
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(DDC1509AbstractFile::class),
                $this->em->getClassMetadata(DDC1509File::class),
                $this->em->getClassMetadata(DDC1509Picture::class),
                ]
            );
        } catch (\Exception $ignored) {

        }
    }

    public function testFailingCase()
    {
        $file = new DDC1509File;
        $thumbnail = new DDC1509File;

        $picture = new DDC1509Picture;
        $picture->setFile($file);
        $picture->setThumbnail($thumbnail);


        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->em;
        $em->persist($picture);
        $em->flush();
        $em->clear();

        $id = $picture->getPictureId();

        $pic = $em->merge($picture);
        /* @var $pic DDC1509Picture */

        self::assertNotNull($pic->getThumbnail());
        self::assertNotNull($pic->getFile());
    }

}

/**
 * @ORM\Entity
 */
class DDC1509Picture
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="DDC1509AbstractFile", cascade={"persist", "remove"})
     */
    private $thumbnail;

    /**
     * @ORM\ManyToOne(targetEntity="DDC1509AbstractFile", cascade={"persist", "remove"})
     */
    private $file;

    /**
     * Get pictureId
     */
    public function getPictureId()
    {
        return $this->id;
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

    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    public function setThumbnail($thumbnail)
    {
        $this->thumbnail = $thumbnail;
    }

}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"abstractFile" = "DDC1509AbstractFile", "file" = "DDC1509File"})
 */
class DDC1509AbstractFile
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * Get fileId
     */
    public function getFileId()
    {
        return $this->id;
    }

}

/**
 * @ORM\Entity
 */
class DDC1509File extends DDC1509AbstractFile
{

}
