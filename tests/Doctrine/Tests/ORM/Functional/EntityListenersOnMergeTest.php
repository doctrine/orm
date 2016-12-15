<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\DDC3597\DDC3597Image;
use Doctrine\Tests\Models\DDC3597\DDC3597Media;
use Doctrine\Tests\Models\DDC3597\DDC3597Root;

/**
 */
class EntityListenersOnMergeTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(
            [
                $this->_em->getClassMetadata(DDC3597Root::class),
                $this->_em->getClassMetadata(DDC3597Media::class),
                $this->_em->getClassMetadata(DDC3597Image::class),
            ]
        );
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->_schemaTool->dropSchema(
            [
                $this->_em->getClassMetadata(DDC3597Root::class),
                $this->_em->getClassMetadata(DDC3597Media::class),
                $this->_em->getClassMetadata(DDC3597Image::class),
            ]
        );
    }

    public function testMergeNewEntityLifecyleEventsModificationsShouldBeKept()
    {
        $imageEntity = new DDC3597Image('foobar');
        $imageEntity->setFormat('JPG');
        $imageEntity->setSize(123);
        $imageEntity->getDimension()->setWidth(300);
        $imageEntity->getDimension()->setHeight(500);

        $imageEntity = $this->_em->merge($imageEntity);

        $this->assertNotNull($imageEntity->getCreatedAt());
        $this->assertNotNull($imageEntity->getUpdatedAt());
    }
}