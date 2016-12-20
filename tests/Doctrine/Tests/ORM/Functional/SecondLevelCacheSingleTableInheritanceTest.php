<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\PersistentCollection;
use Doctrine\Tests\Models\Cache\Attraction;
use Doctrine\Tests\Models\Cache\Bar;
use Doctrine\Tests\Models\Cache\Beach;
use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\Cache\Restaurant;

/**
 * @group DDC-2183
 */
class SecondLevelCacheSingleTableInheritanceTest extends SecondLevelCacheAbstractTest
{
    public function testUseSameRegion()
    {
        $attractionRegion   = $this->cache->getEntityCacheRegion(Attraction::class);
        $restaurantRegion   = $this->cache->getEntityCacheRegion(Restaurant::class);
        $beachRegion        = $this->cache->getEntityCacheRegion(Beach::class);
        $barRegion          = $this->cache->getEntityCacheRegion(Bar::class);

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

        $this->assertTrue($this->cache->containsEntity(Bar::class, $this->attractions[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Bar::class, $this->attractions[1]->getId()));
    }

    public function testCountaisRootClass()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();

        $this->_em->clear();

        foreach ($this->attractions as $attraction) {
            $this->assertTrue($this->cache->containsEntity(Attraction::class, $attraction->getId()));
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

        $this->cache->evictEntityRegion(Attraction::class);

        $entityId1 = $this->attractions[0]->getId();
        $entityId2 = $this->attractions[1]->getId();

        $this->assertFalse($this->cache->containsEntity(Attraction::class, $entityId1));
        $this->assertFalse($this->cache->containsEntity(Attraction::class, $entityId2));
        $this->assertFalse($this->cache->containsEntity(Bar::class, $entityId1));
        $this->assertFalse($this->cache->containsEntity(Bar::class, $entityId2));

        $entity1 = $this->_em->find(Attraction::class, $entityId1);
        $entity2 = $this->_em->find(Attraction::class, $entityId2);

        $this->assertTrue($this->cache->containsEntity(Attraction::class, $entityId1));
        $this->assertTrue($this->cache->containsEntity(Attraction::class, $entityId2));
        $this->assertTrue($this->cache->containsEntity(Bar::class, $entityId1));
        $this->assertTrue($this->cache->containsEntity(Bar::class, $entityId2));

        $this->assertInstanceOf(Attraction::class, $entity1);
        $this->assertInstanceOf(Attraction::class, $entity2);
        $this->assertInstanceOf(Bar::class, $entity1);
        $this->assertInstanceOf(Bar::class, $entity2);

        $this->assertEquals($this->attractions[0]->getId(), $entity1->getId());
        $this->assertEquals($this->attractions[0]->getName(), $entity1->getName());

        $this->assertEquals($this->attractions[1]->getId(), $entity2->getId());
        $this->assertEquals($this->attractions[1]->getName(), $entity2->getName());

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();

        $entity3 = $this->_em->find(Attraction::class, $entityId1);
        $entity4 = $this->_em->find(Attraction::class, $entityId2);

        $this->assertEquals($queryCount, $this->getCurrentQueryCount());

        $this->assertInstanceOf(Attraction::class, $entity3);
        $this->assertInstanceOf(Attraction::class, $entity4);
        $this->assertInstanceOf(Bar::class, $entity3);
        $this->assertInstanceOf(Bar::class, $entity4);

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
            $this->assertInstanceOf(Attraction::class, $entity);
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
            $this->assertTrue($this->cache->containsEntity(City::class, $city->getId()));
            $this->assertFalse($this->cache->containsCollection(City::class, 'attractions', $city->getId()));
        }

        foreach ($this->attractions as $attraction) {
            $this->assertTrue($this->cache->containsEntity(Attraction::class, $attraction->getId()));
        }
    }

    public function testOneToManyRelationSingleTable()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();

        $this->cache->evictEntityRegion(City::class);
        $this->cache->evictEntityRegion(Attraction::class);
        $this->cache->evictCollectionRegion(City::class, 'attractions');

        $this->_em->clear();

        $entity = $this->_em->find(City::class, $this->cities[0]->getId());

        $this->assertInstanceOf(City::class, $entity);
        $this->assertInstanceOf(PersistentCollection::class, $entity->getAttractions());
        $this->assertCount(2, $entity->getAttractions());

        $ownerId    = $this->cities[0]->getId();
        $queryCount = $this->getCurrentQueryCount();

        $this->assertTrue($this->cache->containsEntity(City::class, $ownerId));
        $this->assertTrue($this->cache->containsCollection(City::class, 'attractions', $ownerId));

        $this->assertInstanceOf(Bar::class, $entity->getAttractions()->get(0));
        $this->assertInstanceOf(Bar::class, $entity->getAttractions()->get(1));
        $this->assertEquals($this->attractions[0]->getName(), $entity->getAttractions()->get(0)->getName());
        $this->assertEquals($this->attractions[1]->getName(), $entity->getAttractions()->get(1)->getName());

        $this->_em->clear();

        $entity = $this->_em->find(City::class, $ownerId);

        $this->assertInstanceOf(City::class, $entity);
        $this->assertInstanceOf(PersistentCollection::class, $entity->getAttractions());
        $this->assertCount(2, $entity->getAttractions());

        $this->assertEquals($queryCount, $this->getCurrentQueryCount());

        $this->assertInstanceOf(Bar::class, $entity->getAttractions()->get(0));
        $this->assertInstanceOf(Bar::class, $entity->getAttractions()->get(1));
        $this->assertEquals($this->attractions[0]->getName(), $entity->getAttractions()->get(0)->getName());
        $this->assertEquals($this->attractions[1]->getName(), $entity->getAttractions()->get(1)->getName());
    }

    public function testQueryCacheShouldBeEvictedOnTimestampUpdate()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();
        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();
        $dql        = 'SELECT attraction FROM Doctrine\Tests\Models\Cache\Attraction attraction';

        $result1    = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        $this->assertCount(count($this->attractions), $result1);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $contact = new Beach(
            'Botafogo',
            $this->_em->find(City::class, $this->cities[1]->getId())
        );

        $this->_em->persist($contact);
        $this->_em->flush();
        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();

        $result2 = $this->_em->createQuery($dql)
            ->setCacheable(true)
            ->getResult();

        $this->assertCount(count($this->attractions) + 1, $result2);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        foreach ($result2 as $entity) {
            $this->assertInstanceOf(Attraction::class, $entity);
        }
    }
}
