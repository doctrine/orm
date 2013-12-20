<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Cache\Attraction;
use Doctrine\Tests\Models\Cache\Restaurant;
use Doctrine\Tests\Models\Cache\Beach;
use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\Cache\Bar;

/**
 * @group DDC-2183
 */
class SecondLevelCacheSingleTableInheritanceTest extends SecondLevelCacheAbstractTest
{
    public function testUseSameRegion()
    {
        $attractionRegion   = $this->cache->getEntityCacheRegion(Attraction::CLASSNAME);
        $restaurantRegion   = $this->cache->getEntityCacheRegion(Restaurant::CLASSNAME);
        $beachRegion        = $this->cache->getEntityCacheRegion(Beach::CLASSNAME);
        $barRegion          = $this->cache->getEntityCacheRegion(Bar::CLASSNAME);

        $this->assertEquals($attractionRegion->getName(), $restaurantRegion->getName());
        $this->assertEquals($attractionRegion->getName(), $beachRegion->getName());
        $this->assertEquals($attractionRegion->getName(), $barRegion->getName());
    }

    public function testPutOnPersistSingleTableInheritance()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();

        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(Bar::CLASSNAME, $this->attractions[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Bar::CLASSNAME, $this->attractions[1]->getId()));
    }

    public function testCountaisRootClass()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();

        $this->_em->clear();

        foreach ($this->attractions as $attraction) {
            $this->assertTrue($this->cache->containsEntity(Attraction::CLASSNAME, $attraction->getId()));
            $this->assertTrue($this->cache->containsEntity(get_class($attraction), $attraction->getId()));
        }
    }

    public function testPutAndLoadEntities()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();

        $this->_em->clear();

        $this->cache->evictEntityRegion(Attraction::CLASSNAME);

        $entityId1 = $this->attractions[0]->getId();
        $entityId2 = $this->attractions[1]->getId();

        $this->assertFalse($this->cache->containsEntity(Attraction::CLASSNAME, $entityId1));
        $this->assertFalse($this->cache->containsEntity(Attraction::CLASSNAME, $entityId2));
        $this->assertFalse($this->cache->containsEntity(Bar::CLASSNAME, $entityId1));
        $this->assertFalse($this->cache->containsEntity(Bar::CLASSNAME, $entityId2));

        $entity1 = $this->_em->find(Attraction::CLASSNAME, $entityId1);
        $entity2 = $this->_em->find(Attraction::CLASSNAME, $entityId2);

        $this->assertTrue($this->cache->containsEntity(Attraction::CLASSNAME, $entityId1));
        $this->assertTrue($this->cache->containsEntity(Attraction::CLASSNAME, $entityId2));
        $this->assertTrue($this->cache->containsEntity(Bar::CLASSNAME, $entityId1));
        $this->assertTrue($this->cache->containsEntity(Bar::CLASSNAME, $entityId2));

        $this->assertInstanceOf(Attraction::CLASSNAME, $entity1);
        $this->assertInstanceOf(Attraction::CLASSNAME, $entity2);
        $this->assertInstanceOf(Bar::CLASSNAME, $entity1);
        $this->assertInstanceOf(Bar::CLASSNAME, $entity2);

        $this->assertEquals($this->attractions[0]->getId(), $entity1->getId());
        $this->assertEquals($this->attractions[0]->getName(), $entity1->getName());

        $this->assertEquals($this->attractions[1]->getId(), $entity2->getId());
        $this->assertEquals($this->attractions[1]->getName(), $entity2->getName());

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();

        $entity3 = $this->_em->find(Attraction::CLASSNAME, $entityId1);
        $entity4 = $this->_em->find(Attraction::CLASSNAME, $entityId2);

        $this->assertEquals($queryCount, $this->getCurrentQueryCount());

        $this->assertInstanceOf(Attraction::CLASSNAME, $entity3);
        $this->assertInstanceOf(Attraction::CLASSNAME, $entity4);
        $this->assertInstanceOf(Bar::CLASSNAME, $entity3);
        $this->assertInstanceOf(Bar::CLASSNAME, $entity4);

        $this->assertNotSame($entity1, $entity3);
        $this->assertEquals($entity1->getId(), $entity3->getId());
        $this->assertEquals($entity1->getName(), $entity3->getName());

        $this->assertNotSame($entity2, $entity4);
        $this->assertEquals($entity2->getId(), $entity4->getId());
        $this->assertEquals($entity2->getName(), $entity4->getName());
    }

    public function testQueryCacheFindAll()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();
        $dql        = 'SELECT a FROM Doctrine\Tests\Models\Cache\Attraction a';
        $result1    = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        $this->assertCount(count($this->attractions), $result1);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $this->_em->clear();

        $result2 = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        $this->assertCount(count($this->attractions), $result2);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        foreach ($result2 as $entity) {
            $this->assertInstanceOf(Attraction::CLASSNAME, $entity);
        }
    }

    public function testShouldNotPutOneToManyRelationOnPersist()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();

        $this->_em->clear();

        foreach ($this->cities as $city) {
            $this->assertTrue($this->cache->containsEntity(City::CLASSNAME, $city->getId()));
            $this->assertFalse($this->cache->containsCollection(City::CLASSNAME, 'attractions', $city->getId()));
        }

        foreach ($this->attractions as $attraction) {
            $this->assertTrue($this->cache->containsEntity(Attraction::CLASSNAME, $attraction->getId()));
        }
    }

    public function testOneToManyRelationSingleTable()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();

        $this->cache->evictEntityRegion(City::CLASSNAME);
        $this->cache->evictEntityRegion(Attraction::CLASSNAME);
        $this->cache->evictCollectionRegion(City::CLASSNAME, 'attractions');

        $this->_em->clear();

        $entity = $this->_em->find(City::CLASSNAME, $this->cities[0]->getId());

        $this->assertInstanceOf(City::CLASSNAME, $entity);
        $this->assertInstanceOf('Doctrine\ORM\PersistentCollection', $entity->getAttractions());
        $this->assertCount(2, $entity->getAttractions());

        $ownerId    = $this->cities[0]->getId();
        $queryCount = $this->getCurrentQueryCount();

        $this->assertTrue($this->cache->containsEntity(City::CLASSNAME, $ownerId));
        $this->assertTrue($this->cache->containsCollection(City::CLASSNAME, 'attractions', $ownerId));

        $this->assertInstanceOf(Bar::CLASSNAME, $entity->getAttractions()->get(0));
        $this->assertInstanceOf(Bar::CLASSNAME, $entity->getAttractions()->get(1));
        $this->assertEquals($this->attractions[0]->getName(), $entity->getAttractions()->get(0)->getName());
        $this->assertEquals($this->attractions[1]->getName(), $entity->getAttractions()->get(1)->getName());

        $this->_em->clear();

        $entity = $this->_em->find(City::CLASSNAME, $ownerId);

        $this->assertInstanceOf(City::CLASSNAME, $entity);
        $this->assertInstanceOf('Doctrine\ORM\PersistentCollection', $entity->getAttractions());
        $this->assertCount(2, $entity->getAttractions());

        $this->assertEquals($queryCount, $this->getCurrentQueryCount());

        $this->assertInstanceOf(Bar::CLASSNAME, $entity->getAttractions()->get(0));
        $this->assertInstanceOf(Bar::CLASSNAME, $entity->getAttractions()->get(1));
        $this->assertEquals($this->attractions[0]->getName(), $entity->getAttractions()->get(0)->getName());
        $this->assertEquals($this->attractions[1]->getName(), $entity->getAttractions()->get(1)->getName());
    }
}