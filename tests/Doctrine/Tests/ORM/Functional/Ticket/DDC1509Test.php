<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\UnitOfWork;

/**
 * @group DDC-1509
 */
class DDC1509Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1509AbstractFile'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1509File'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1509Picture'),
            ));
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
        $em = $this->_em;
        $em->persist($picture);
        $em->flush();
        $em->clear();

        $id = $picture->getPictureId();

        $pic = $em->merge($picture);
        /* @var $pic DDC1509Picture */

        $this->assertNotNull($pic->getThumbnail());
        $this->assertNotNull($pic->getFile());
    }

}

/**
 * @Entity
 */
class DDC1509Picture
{

    /**
     * @Column(type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="DDC1509AbstractFile", cascade={"persist", "remove"})
     */
    private $thumbnail;

    /**
     * @ManyToOne(targetEntity="DDC1509AbstractFile", cascade={"persist", "remove"})
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
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"file" = "DDC1509File"})
 */
class DDC1509AbstractFile
{

    /**
     * @Column(type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
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
 * @Entity
 */
class DDC1509File extends DDC1509AbstractFile
{
    
}
