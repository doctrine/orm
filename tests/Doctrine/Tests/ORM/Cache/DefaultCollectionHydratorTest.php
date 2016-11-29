<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\EntityCacheEntry;
use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Cache\CollectionCacheEntry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Cache\DefaultCollectionHydrator;

/**
 * @group DDC-2183
 */
class DefaultCollectionHydratorTest extends OrmFunctionalTestCase
{
    /**
     * @var \Doctrine\ORM\Cache\CollectionHydrator
     */
    private $structure;

    protected function setUp()
    {
        $this->enableSecondLevelCache();
        parent::setUp();

        $targetPersister = $this->_em->getUnitOfWork()->getEntityPersister(City::CLASSNAME);
        $this->structure = new DefaultCollectionHydrator($this->_em, $targetPersister);
    }

    public function testImplementsCollectionEntryStructure()
    {
        $this->assertInstanceOf('Doctrine\ORM\Cache\DefaultCollectionHydrator', $this->structure);
    }

    public function testLoadCacheCollection()
    {
        $targetRegion   = $this->_em->getCache()->getEntityCacheRegion(City::CLASSNAME);
        $entry          = new CollectionCacheEntry(array(
            new EntityCacheKey(City::CLASSNAME, array('id'=>31)),
            new EntityCacheKey(City::CLASSNAME, array('id'=>32)),
        ));

        $targetRegion->put(new EntityCacheKey(City::CLASSNAME, array('id'=>31)), new EntityCacheEntry(City::CLASSNAME, array('id'=>31, 'name'=>'Foo')));
        $targetRegion->put(new EntityCacheKey(City::CLASSNAME, array('id'=>32)), new EntityCacheEntry(City::CLASSNAME, array('id'=>32, 'name'=>'Bar')));

        $sourceClass    = $this->_em->getClassMetadata(State::CLASSNAME);
        $targetClass    = $this->_em->getClassMetadata(City::CLASSNAME);
        $key            = new CollectionCacheKey($sourceClass->name, 'cities', array('id'=>21));
        $collection     = new PersistentCollection($this->_em, $targetClass, new ArrayCollection());
        $list           = $this->structure->loadCacheEntry($sourceClass, $key, $entry, $collection);

        $this->assertNotNull($list);
        $this->assertCount(2, $list);
        $this->assertCount(2, $collection);

        $this->assertInstanceOf($targetClass->name, $list[0]);
        $this->assertInstanceOf($targetClass->name, $list[1]);
        $this->assertInstanceOf($targetClass->name, $collection[0]);
        $this->assertInstanceOf($targetClass->name, $collection[1]);

        $this->assertSame($list[0], $collection[0]);
        $this->assertSame($list[1], $collection[1]);

        $this->assertEquals(31, $list[0]->getId());
        $this->assertEquals(32, $list[1]->getId());
        $this->assertEquals($list[0]->getId(), $collection[0]->getId());
        $this->assertEquals($list[1]->getId(), $collection[1]->getId());
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $this->_em->getUnitOfWork()->getEntityState($collection[0]));
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $this->_em->getUnitOfWork()->getEntityState($collection[1]));
    }

}