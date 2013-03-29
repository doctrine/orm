<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\UnitOfWork;
use Doctrine\Tests\OrmTestCase;
use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\Models\Cache\City;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Cache\CollectionEntryStructure;

/**
 * @group DDC-2183
 */
class CollectionEntryStructureTest extends OrmTestCase
{
    /**
     * @var \Doctrine\ORM\Cache\CollectionEntryStructure
     */
    private $structure;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    protected function setUp()
    {
        parent::setUp();

        $this->em        = $this->_getTestEntityManager();
        $this->structure = new CollectionEntryStructure($this->em);
    }

    public function testLoadCacheCollection()
    {
        $cache = array(
            array('id'=>31),
            array('id'=>32),
        );

        $sourceClass    = $this->em->getClassMetadata(State::CLASSNAME);
        $targetClass    = $this->em->getClassMetadata(City::CLASSNAME);
        $key            = new CollectionCacheKey($sourceClass->name, 'cities', array('id'=>21));
        $collection     = new PersistentCollection($this->em, $targetClass, new ArrayCollection());
        $list           = $this->structure->loadCacheEntry($sourceClass, $key, $cache, $collection);

        $this->assertCount(2, $list);
        $this->assertCount(2, $collection);

        $this->assertInstanceOf($sourceClass->name, $list[0]);
        $this->assertInstanceOf($sourceClass->name, $list[1]);
        $this->assertInstanceOf($sourceClass->name, $collection[0]);
        $this->assertInstanceOf($sourceClass->name, $collection[1]);

        $this->assertSame($list[0], $collection[0]);
        $this->assertSame($list[1], $collection[1]);

        $this->assertEquals(31, $list[0]->getId());
        $this->assertEquals(32, $list[1]->getId());
        $this->assertEquals($list[0]->getId(), $collection[0]->getId());
        $this->assertEquals($list[1]->getId(), $collection[1]->getId());
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $this->em->getUnitOfWork()->getEntityState($collection[0]));
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $this->em->getUnitOfWork()->getEntityState($collection[1]));
    }

}