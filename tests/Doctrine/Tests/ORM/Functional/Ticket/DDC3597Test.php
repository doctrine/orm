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
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(DDC3597Root::CLASSNAME),
            $this->_em->getClassMetadata(DDC3597Media::CLASSNAME),
            $this->_em->getClassMetadata(DDC3597Image::CLASSNAME)
        ));
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

        $this->_em->persist($imageEntity);
        $this->_em->flush(); //before this fix, it will fail with a exception

        $this->_em->clear();

        //request entity
        $imageEntity = $this->_em->find(DDC3597Image::CLASSNAME, $imageEntity->getId());
        $this->assertInstanceOf(DDC3597Image::CLASSNAME, $imageEntity);

        //cleanup
        $this->_em->remove($imageEntity);
        $this->_em->flush();
        $this->_em->clear();

        //check delete
        $imageEntity = $this->_em->find(DDC3597Image::CLASSNAME, $imageEntity->getId());
        $this->assertNull($imageEntity);
    }
}
