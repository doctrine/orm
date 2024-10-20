<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC353Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(DDC353File::class, DDC353Picture::class);
    }

    public function testWorkingCase(): void
    {
        $file = new DDC353File();

        $picture = new DDC353Picture();
        $picture->setFile($file);

        $em = $this->_em;
        $em->persist($picture);
        $em->flush();
        $em->clear();

        $fileId = $file->getFileId();
        self::assertGreaterThan(0, $fileId);

        $file = $em->getReference(DDC353File::class, $fileId);
        self::assertEquals(UnitOfWork::STATE_MANAGED, $em->getUnitOfWork()->getEntityState($file), 'Reference Proxy should be marked MANAGED.');

        $picture = $em->find(DDC353Picture::class, $picture->getPictureId());
        self::assertEquals(UnitOfWork::STATE_MANAGED, $em->getUnitOfWork()->getEntityState($picture->getFile()), 'Lazy Proxy should be marked MANAGED.');

        $em->remove($picture);
        $em->flush();
    }

    public function testFailingCase(): void
    {
        $file = new DDC353File();

        $picture = new DDC353Picture();
        $picture->setFile($file);

        $em = $this->_em;
        $em->persist($picture);
        $em->flush();
        $em->clear();

        $fileId    = $file->getFileId();
        $pictureId = $picture->getPictureId();

        self::assertGreaterThan(0, $fileId);

        $picture = $em->find(DDC353Picture::class, $pictureId);
        self::assertEquals(UnitOfWork::STATE_MANAGED, $em->getUnitOfWork()->getEntityState($picture->getFile()), 'Lazy Proxy should be marked MANAGED.');

        $em->remove($picture);
        $em->flush();
    }
}

#[Entity]
class DDC353Picture
{
    #[Column(name: 'picture_id', type: 'integer')]
    #[Id]
    #[GeneratedValue]
    private int $pictureId;

    #[JoinColumn(name: 'file_id', referencedColumnName: 'file_id')]
    #[ManyToOne(targetEntity: 'DDC353File', cascade: ['persist', 'remove'])]
    private DDC353File|null $file = null;

    public function getPictureId(): int
    {
        return $this->pictureId;
    }

    public function setFile(DDC353File $value): void
    {
        $this->file = $value;
    }

    public function getFile(): DDC353File
    {
        return $this->file;
    }
}

#[Entity]
class DDC353File
{
    /** @var int */
    #[Column(name: 'file_id', type: 'integer')]
    #[Id]
    #[GeneratedValue(strategy: 'AUTO')]
    public $fileId;

    /**
     * Get fileId
     */
    public function getFileId(): int
    {
        return $this->fileId;
    }
}
