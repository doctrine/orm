<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\EntityManager;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Tests\VerifyDeprecations;
use Exception;

use function assert;

/**
 * @group DDC-1509
 */
class DDC1509Test extends OrmFunctionalTestCase
{
    use VerifyDeprecations;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(
                [
                    $this->_em->getClassMetadata(DDC1509AbstractFile::class),
                    $this->_em->getClassMetadata(DDC1509File::class),
                    $this->_em->getClassMetadata(DDC1509Picture::class),
                ]
            );
        } catch (Exception $ignored) {
        }
    }

    public function testFailingCase(): void
    {
        $file      = new DDC1509File();
        $thumbnail = new DDC1509File();

        $picture = new DDC1509Picture();
        $picture->setFile($file);
        $picture->setThumbnail($thumbnail);

        $em = $this->_em;
        assert($em instanceof EntityManager);
        $em->persist($picture);
        $em->flush();
        $em->clear();

        $id = $picture->getPictureId();

        $pic = $em->merge($picture);
        assert($pic instanceof DDC1509Picture);

        $this->assertNotNull($pic->getThumbnail());
        $this->assertNotNull($pic->getFile());
        $this->assertHasDeprecationMessages();
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

    /** @ManyToOne(targetEntity="DDC1509AbstractFile", cascade={"persist", "remove"}) */
    private $thumbnail;

    /** @ManyToOne(targetEntity="DDC1509AbstractFile", cascade={"persist", "remove"}) */
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
    public function setFile($value = null): void
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

    public function setThumbnail($thumbnail): void
    {
        $this->thumbnail = $thumbnail;
    }
}

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"abstractFile" = "DDC1509AbstractFile", "file" = "DDC1509File"})
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
