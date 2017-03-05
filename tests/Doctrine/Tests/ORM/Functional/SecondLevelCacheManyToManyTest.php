<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\Cache\Travel;
use Doctrine\Tests\Models\Cache\Traveler;

/**
 * @group DDC-2183
 */
class SecondLevelCacheManyToManyTest extends SecondLevelCacheAbstractTest
{
    public function testShouldPutManyToManyCollectionOwningSideOnPersist()
    {
        $this->evictRegions();

        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesTraveler();
        $this->loadFixturesTravels();
        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(Travel::class, $this->travels[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Travel::class, $this->travels[1]->getId()));

        $this->assertTrue($this->cache->containsCollection(Travel::class, 'visitedCities', $this->travels[0]->getId()));
        $this->assertTrue($this->cache->containsCollection(Travel::class, 'visitedCities', $this->travels[1]->getId()));

        $this->assertTrue($this->cache->containsEntity(City::class, $this->cities[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(City::class, $this->cities[1]->getId()));
        $this->assertTrue($this->cache->containsEntity(City::class, $this->cities[2]->getId()));
        $this->assertTrue($this->cache->containsEntity(City::class, $this->cities[3]->getId()));
    }

    public function testPutAndLoadManyToManyRelation()
    {
        $this->evictRegions();

        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesTraveler();
        $this->loadFixturesTravels();

        $this->_em->clear();
        $this->cache->evictEntityRegion(City::class);
        $this->cache->evictEntityRegion(Travel::class);
        $this->cache->evictCollectionRegion(Travel::class, 'visitedCities');

        $this->secondLevelCacheLogger->clearStats();

        $this->assertFalse($this->cache->containsEntity(Travel::class, $this->travels[0]->getId()));
        $this->assertFalse($this->cache->containsEntity(Travel::class, $this->travels[1]->getId()));

        $this->assertFalse($this->cache->containsCollection(Travel::class, 'visitedCities', $this->travels[0]->getId()));
        $this->assertFalse($this->cache->containsCollection(Travel::class, 'visitedCities', $this->travels[1]->getId()));

        $this->assertFalse($this->cache->containsEntity(City::class, $this->cities[0]->getId()));
        $this->assertFalse($this->cache->containsEntity(City::class, $this->cities[1]->getId()));
        $this->assertFalse($this->cache->containsEntity(City::class, $this->cities[2]->getId()));
        $this->assertFalse($this->cache->containsEntity(City::class, $this->cities[3]->getId()));

        $t1 = $this->_em->find(Travel::class, $this->travels[0]->getId());
        $t2 = $this->_em->find(Travel::class, $this->travels[1]->getId());

        $this->assertEquals(2, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getMissCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(Travel::class)));
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionMissCount($this->getEntityRegion(Travel::class)));

        //trigger lazy load
        $this->assertCount(3, $t1->getVisitedCities());
        $this->assertCount(2, $t2->getVisitedCities());

        $this->assertEquals(4, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(4, $this->secondLevelCacheLogger->getMissCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getCollectionRegion(Travel::class, 'visitedCities')));
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionMissCount($this->getCollectionRegion(Travel::class, 'visitedCities')));

        $this->assertInstanceOf(City::class, $t1->getVisitedCities()->get(0));
        $this->assertInstanceOf(City::class, $t1->getVisitedCities()->get(1));
        $this->assertInstanceOf(City::class, $t1->getVisitedCities()->get(2));

        $this->assertInstanceOf(City::class, $t2->getVisitedCities()->get(0));
        $this->assertInstanceOf(City::class, $t2->getVisitedCities()->get(1));

        $this->assertTrue($this->cache->containsEntity(Travel::class, $this->travels[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Travel::class, $this->travels[1]->getId()));

        $this->assertTrue($this->cache->containsCollection(Travel::class, 'visitedCities', $this->travels[0]->getId()));
        $this->assertTrue($this->cache->containsCollection(Travel::class, 'visitedCities', $this->travels[1]->getId()));

        $this->assertTrue($this->cache->containsEntity(City::class, $this->cities[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(City::class, $this->cities[1]->getId()));
        $this->assertTrue($this->cache->containsEntity(City::class, $this->cities[2]->getId()));
        $this->assertTrue($this->cache->containsEntity(City::class, $this->cities[3]->getId()));

        $this->_em->clear();
        $this->secondLevelCacheLogger->clearStats();

        $queryCount = $this->getCurrentQueryCount();

        $t3 = $this->_em->find(Travel::class, $this->travels[0]->getId());
        $t4 = $this->_em->find(Travel::class, $this->travels[1]->getId());

        //trigger lazy load from cache
        $this->assertCount(3, $t3->getVisitedCities());
        $this->assertCount(2, $t4->getVisitedCities());

        $this->assertInstanceOf(City::class, $t3->getVisitedCities()->get(0));
        $this->assertInstanceOf(City::class, $t3->getVisitedCities()->get(1));
        $this->assertInstanceOf(City::class, $t3->getVisitedCities()->get(2));

        $this->assertInstanceOf(City::class, $t4->getVisitedCities()->get(0));
        $this->assertInstanceOf(City::class, $t4->getVisitedCities()->get(1));

        $this->assertEquals(4, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(Travel::class)));
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionHitCount($this->getCollectionRegion(Travel::class, 'visitedCities')));

        $this->assertNotSame($t1->getVisitedCities()->get(0), $t3->getVisitedCities()->get(0));
        $this->assertEquals($t1->getVisitedCities()->get(0)->getId(), $t3->getVisitedCities()->get(0)->getId());
        $this->assertEquals($t1->getVisitedCities()->get(0)->getName(), $t3->getVisitedCities()->get(0)->getName());

        $this->assertNotSame($t1->getVisitedCities()->get(1), $t3->getVisitedCities()->get(1));
        $this->assertEquals($t1->getVisitedCities()->get(1)->getId(), $t3->getVisitedCities()->get(1)->getId());
        $this->assertEquals($t1->getVisitedCities()->get(1)->getName(), $t3->getVisitedCities()->get(1)->getName());

        $this->assertNotSame($t1->getVisitedCities()->get(2), $t3->getVisitedCities()->get(2));
        $this->assertEquals($t1->getVisitedCities()->get(2)->getId(), $t3->getVisitedCities()->get(2)->getId());
        $this->assertEquals($t1->getVisitedCities()->get(2)->getName(), $t3->getVisitedCities()->get(2)->getName());

        $this->assertNotSame($t2->getVisitedCities()->get(0), $t4->getVisitedCities()->get(0));
        $this->assertEquals($t2->getVisitedCities()->get(0)->getId(), $t4->getVisitedCities()->get(0)->getId());
        $this->assertEquals($t2->getVisitedCities()->get(0)->getName(), $t4->getVisitedCities()->get(0)->getName());

        $this->assertNotSame($t2->getVisitedCities()->get(1), $t4->getVisitedCities()->get(1));
        $this->assertEquals($t2->getVisitedCities()->get(1)->getId(), $t4->getVisitedCities()->get(1)->getId());
        $this->assertEquals($t2->getVisitedCities()->get(1)->getName(), $t4->getVisitedCities()->get(1)->getName());

        $this->assertEquals(4, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals($queryCount, $this->getCurrentQueryCount());
    }

    public function testStoreManyToManyAssociationWhitCascade()
    {
        $this->evictRegions();

        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();

        $this->cache->evictEntityRegion(City::class);
        $this->cache->evictEntityRegion(Traveler::class);
        $this->cache->evictEntityRegion(Travel::class);
        $this->cache->evictCollectionRegion(State::class, 'cities');
        $this->cache->evictCollectionRegion(Traveler::class, 'travels');

        $traveler   = new Traveler('Doctrine Bot');
        $travel     = new Travel($traveler);

        $travel->addVisitedCity($this->cities[0]);
        $travel->addVisitedCity($this->cities[1]);
        $travel->addVisitedCity($this->cities[3]);

        $this->_em->persist($traveler);
        $this->_em->persist($travel);
        $this->_em->flush();
        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(Travel::class, $travel->getId()));
        $this->assertTrue($this->cache->containsEntity(Traveler::class, $traveler->getId()));
        $this->assertTrue($this->cache->containsEntity(City::class, $this->cities[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(City::class, $this->cities[1]->getId()));
        $this->assertTrue($this->cache->containsEntity(City::class, $this->cities[3]->getId()));
        $this->assertTrue($this->cache->containsCollection(Travel::class, 'visitedCities', $travel->getId()));

        $queryCount1 = $this->getCurrentQueryCount();
        $t1          = $this->_em->find(Travel::class, $travel->getId());

        $this->assertInstanceOf(Travel::class, $t1);
        $this->assertCount(3, $t1->getVisitedCities());
        $this->assertEquals($queryCount1, $this->getCurrentQueryCount());
    }

    /**
     * @expectedException \Doctrine\ORM\Cache\CacheException
     * @expectedExceptionMessage Cannot update a readonly collection "Doctrine\Tests\Models\Cache\Travel#visitedCities
     */
    public function testReadOnlyCollection()
    {
        $this->evictRegions();

        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesTraveler();
        $this->loadFixturesTravels();

        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(Travel::class, $this->travels[0]->getId()));
        $this->assertTrue($this->cache->containsCollection(Travel::class, 'visitedCities', $this->travels[0]->getId()));

        $travel = $this->_em->find(Travel::class, $this->travels[0]->getId());

        $this->assertCount(3, $travel->getVisitedCities());

        $travel->getVisitedCities()->remove(0);

        $this->_em->persist($travel);
        $this->_em->flush();
    }

    public function testManyToManyWithEmptyRelation()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesTraveler();
        $this->loadFixturesTravels();
        $this->_em->clear();

        $this->evictRegions();

        $queryCount = $this->getCurrentQueryCount();

        $entitiId   = $this->travels[2]->getId(); //empty travel
        $entity     = $this->_em->find(Travel::class, $entitiId);

        $this->assertEquals(0, $entity->getVisitedCities()->count());
        $this->assertEquals($queryCount+2, $this->getCurrentQueryCount());

        $this->_em->clear();

        $entity     = $this->_em->find(Travel::class, $entitiId);

        $queryCount = $this->getCurrentQueryCount();
        $this->assertEquals(0, $entity->getVisitedCities()->count());
        $this->assertEquals($queryCount, $this->getCurrentQueryCount());

    }
}
