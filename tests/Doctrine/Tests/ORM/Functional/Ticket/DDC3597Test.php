<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\DDC3597\DDC3597Image;
use Doctrine\Tests\Models\DDC3597\DDC3597Media;
use Doctrine\Tests\Models\DDC3597\DDC3597Root;

/**
 * @group DDC-117
 */
class DDC3597Test extends \Doctrine\Tests\OrmFunctionalTestCase {

    protected function setUp() {
        parent::setUp();
        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(DDC3597Root::class),
            $this->em->getClassMetadata(DDC3597Media::class),
            $this->em->getClassMetadata(DDC3597Image::class)
            ]
        );
    }

    /**
     * @group DDC-3597
     */
    public function testSaveImageEntity() {
        $imageEntity = new DDC3597Image('foobar');
        $imageEntity->setFormat('JPG');
        $imageEntity->setSize(123);
        $imageEntity->getDimension()->setWidth(300);
        $imageEntity->getDimension()->setHeight(500);

        $this->em->persist($imageEntity);
        $this->em->flush(); //before this fix, it will fail with a exception

        $this->em->clear();

        //request entity
        $imageEntity = $this->em->find(DDC3597Image::class, $imageEntity->getId());
        self::assertInstanceOf(DDC3597Image::class, $imageEntity);

        //cleanup
        $this->em->remove($imageEntity);
        $this->em->flush();
        $this->em->clear();

        //check delete
        $imageEntity = $this->em->find(DDC3597Image::class, $imageEntity->getId());
        self::assertNull($imageEntity);
    }
}
