<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\DDC3597\DDC3597Image;
use Doctrine\Tests\Models\DDC3597\DDC3597Media;
use Doctrine\Tests\Models\DDC3597\DDC3597Root;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('DDC-117')]
class DDC3597Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            DDC3597Root::class,
            DDC3597Media::class,
            DDC3597Image::class,
        );
    }

    #[Group('DDC-3597')]
    public function testSaveImageEntity(): void
    {
        $imageEntity = new DDC3597Image('foobar');
        $imageEntity->setFormat('JPG');
        $imageEntity->setSize(123);
        $imageEntity->getDimension()->setWidth(300);
        $imageEntity->getDimension()->setHeight(500);

        $this->_em->persist($imageEntity);
        $this->_em->flush(); //before this fix, it will fail with a exception

        $this->_em->clear();

        //request entity
        $imageEntity = $this->_em->find(DDC3597Image::class, $imageEntity->getId());
        self::assertInstanceOf(DDC3597Image::class, $imageEntity);

        //cleanup
        $this->_em->remove($imageEntity);
        $this->_em->flush();
        $this->_em->clear();

        //check delete
        $imageEntity = $this->_em->find(DDC3597Image::class, $imageEntity->getId());
        self::assertNull($imageEntity);
    }
}
