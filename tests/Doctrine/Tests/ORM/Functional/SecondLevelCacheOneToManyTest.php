<?php

namespace Doctrine\Tests\ORM\Functional;


use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\Cache\Travel;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\Models\Cache\Traveler;

/**
 * @group DDC-2183
 */
class SecondLevelCacheOneToManyTest extends SecondLevelCacheAbstractTest
{
    public function testPutOnPersist()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(State::CLASSNAME, $this->states[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(State::CLASSNAME, $this->states[1]->getId()));

        $this->assertTrue($this->cache->containsCollection(State::CLASSNAME, 'cities', $this->states[0]->getId()));
        $this->assertTrue($this->cache->containsCollection(State::CLASSNAME, 'cities', $this->states[1]->getId()));

        $this->assertTrue($this->cache->containsEntity(City::CLASSNAME, $this->states[0]->getCities()->get(0)->getId()));
        $this->assertTrue($this->cache->containsEntity(City::CLASSNAME, $this->states[0]->getCities()->get(1)->getId()));
        $this->assertTrue($this->cache->containsEntity(City::CLASSNAME, $this->states[1]->getCities()->get(0)->getId()));
        $this->assertTrue($this->cache->containsEntity(City::CLASSNAME, $this->states[1]->getCities()->get(1)->getId()));

        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getCollectionRegion(State::CLASSNAME, 'cities')));
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(Country::CLASSNAME)));
        $this->assertEquals(4, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(State::CLASSNAME)));
        $this->assertEquals(4, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(City::CLASSNAME)));
        $this->assertEquals(12, $this->secondLevelCacheLogger->getPutCount());
    }

    public function testPutAndLoadOneToManyRelation()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->_em->clear();
        $this->secondLevelCacheLogger->clearStats();

        $this->cache->evictEntityRegion(State::CLASSNAME);
        $this->cache->evictEntityRegion(City::CLASSNAME);
        $this->cache->evictCollectionRegion(State::CLASSNAME, 'cities');

        $this->assertFalse($this->cache->containsEntity(State::CLASSNAME, $this->states[0]->getId()));
        $this->assertFalse($this->cache->containsEntity(State::CLASSNAME, $this->states[1]->getId()));

        $this->assertFalse($this->cache->containsCollection(State::CLASSNAME, 'cities', $this->states[0]->getId()));
        $this->assertFalse($this->cache->containsCollection(State::CLASSNAME, 'cities', $this->states[1]->getId()));

        $this->assertFalse($this->cache->containsEntity(City::CLASSNAME, $this->states[0]->getCities()->get(0)->getId()));
        $this->assertFalse($this->cache->containsEntity(City::CLASSNAME, $this->states[0]->getCities()->get(1)->getId()));
        $this->assertFalse($this->cache->containsEntity(City::CLASSNAME, $this->states[1]->getCities()->get(0)->getId()));
        $this->assertFalse($this->cache->containsEntity(City::CLASSNAME, $this->states[1]->getCities()->get(1)->getId()));

        $s1 = $this->_em->find(State::CLASSNAME, $this->states[0]->getId());
        $s2 = $this->_em->find(State::CLASSNAME, $this->states[1]->getId());

        $this->assertEquals(2, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getMissCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(State::CLASSNAME)));
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionMissCount($this->getEntityRegion(State::CLASSNAME)));

        //trigger lazy load
        $this->assertCount(2, $s1->getCities());
        $this->assertCount(2, $s2->getCities());

        $this->assertEquals(4, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(4, $this->secondLevelCacheLogger->getMissCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getCollectionRegion(State::CLASSNAME, 'cities')));
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionMissCount($this->getCollectionRegion(State::CLASSNAME, 'cities')));

        $this->assertInstanceOf(City::CLASSNAME, $s1->getCities()->get(0));
        $this->assertInstanceOf(City::CLASSNAME, $s1->getCities()->get(1));

        $this->assertInstanceOf(City::CLASSNAME, $s2->getCities()->get(0));
        $this->assertInstanceOf(City::CLASSNAME, $s2->getCities()->get(1));

        $this->assertTrue($this->cache->containsEntity(State::CLASSNAME, $this->states[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(State::CLASSNAME, $this->states[1]->getId()));

        $this->assertTrue($this->cache->containsCollection(State::CLASSNAME, 'cities', $this->states[0]->getId()));
        $this->assertTrue($this->cache->containsCollection(State::CLASSNAME, 'cities', $this->states[1]->getId()));

        $this->assertTrue($this->cache->containsEntity(City::CLASSNAME, $this->states[0]->getCities()->get(0)->getId()));
        $this->assertTrue($this->cache->containsEntity(City::CLASSNAME, $this->states[0]->getCities()->get(1)->getId()));
        $this->assertTrue($this->cache->containsEntity(City::CLASSNAME, $this->states[1]->getCities()->get(0)->getId()));
        $this->assertTrue($this->cache->containsEntity(City::CLASSNAME, $this->states[1]->getCities()->get(1)->getId()));

        $this->_em->clear();
        $this->secondLevelCacheLogger->clearStats();

        $queryCount = $this->getCurrentQueryCount();

        $s3 = $this->_em->find(State::CLASSNAME, $this->states[0]->getId());
        $s4 = $this->_em->find(State::CLASSNAME, $this->states[1]->getId());
       
        //trigger lazy load from cache
        $this->assertCount(2, $s3->getCities());
        $this->assertCount(2, $s4->getCities());

        $this->assertEquals(4, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(State::CLASSNAME)));
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionHitCount($this->getCollectionRegion(State::CLASSNAME, 'cities')));

        $this->assertInstanceOf(City::CLASSNAME, $s3->getCities()->get(0));
        $this->assertInstanceOf(City::CLASSNAME, $s3->getCities()->get(1));
        $this->assertInstanceOf(City::CLASSNAME, $s4->getCities()->get(0));
        $this->assertInstanceOf(City::CLASSNAME, $s4->getCities()->get(1));

        $this->assertNotSame($s1->getCities()->get(0), $s3->getCities()->get(0));
        $this->assertEquals($s1->getCities()->get(0)->getId(), $s3->getCities()->get(0)->getId());
        $this->assertEquals($s1->getCities()->get(0)->getName(), $s3->getCities()->get(0)->getName());

        $this->assertNotSame($s1->getCities()->get(1), $s3->getCities()->get(1));
        $this->assertEquals($s1->getCities()->get(1)->getId(), $s3->getCities()->get(1)->getId());
        $this->assertEquals($s1->getCities()->get(1)->getName(), $s3->getCities()->get(1)->getName());

        $this->assertNotSame($s2->getCities()->get(0), $s4->getCities()->get(0));
        $this->assertEquals($s2->getCities()->get(0)->getId(), $s4->getCities()->get(0)->getId());
        $this->assertEquals($s2->getCities()->get(0)->getName(), $s4->getCities()->get(0)->getName());

        $this->assertNotSame($s2->getCities()->get(1), $s4->getCities()->get(1));
        $this->assertEquals($s2->getCities()->get(1)->getId(), $s4->getCities()->get(1)->getId());
        $this->assertEquals($s2->getCities()->get(1)->getName(), $s4->getCities()->get(1)->getName());

        $this->assertEquals(4, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals($queryCount, $this->getCurrentQueryCount());
    }

    public function testStoreOneToManyAssociationWhitCascade()
    {
        $this->cache->evictCollectionRegion(Traveler::CLASSNAME, 'travels');
        $this->cache->evictEntityRegion(Traveler::CLASSNAME);
        $this->cache->evictEntityRegion(Travel::CLASSNAME);

        $traveler = new Traveler('Doctrine Bot');

        $traveler->addTravel(new Travel($traveler));
        $traveler->addTravel(new Travel($traveler));
        $traveler->addTravel(new Travel($traveler));

        $this->_em->persist($traveler);
        $this->_em->flush();
        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(Travel::CLASSNAME, $traveler->getId()));
        $this->assertTrue($this->cache->containsCollection(Traveler::CLASSNAME, 'travels', $traveler->getId()));
        $this->assertTrue($this->cache->containsEntity(Travel::CLASSNAME, $traveler->getTravels()->get(0)->getId()));
        $this->assertTrue($this->cache->containsEntity(Travel::CLASSNAME, $traveler->getTravels()->get(1)->getId()));
        $this->assertTrue($this->cache->containsEntity(Travel::CLASSNAME, $traveler->getTravels()->get(2)->getId()));

        $queryCount1    = $this->getCurrentQueryCount();
        $t1             = $this->_em->find(Traveler::CLASSNAME, $traveler->getId());

        $this->assertInstanceOf(Traveler::CLASSNAME, $t1);
        $this->assertInstanceOf(Travel::CLASSNAME, $t1->getTravels()->get(0));
        $this->assertInstanceOf(Travel::CLASSNAME, $t1->getTravels()->get(1));
        $this->assertInstanceOf(Travel::CLASSNAME, $t1->getTravels()->get(2));
        $this->assertCount(3, $t1->getTravels());
        $this->assertNotSame($traveler, $t1);

        $this->assertEquals($queryCount1, $this->getCurrentQueryCount());

        $t1->removeTravel($t1->getTravels()->get(1));

        $this->_em->persist($t1);
        $this->_em->flush($t1);
        $this->_em->clear();

        $queryCount2 = $this->getCurrentQueryCount();
        $t2          = $this->_em->find(Traveler::CLASSNAME, $traveler->getId());

        $this->assertInstanceOf(Traveler::CLASSNAME, $t2);
        $this->assertInstanceOf(Travel::CLASSNAME, $t2->getTravels()->get(0));
        $this->assertInstanceOf(Travel::CLASSNAME, $t2->getTravels()->get(2));
        $this->assertCount(2, $t1->getTravels());
        $this->assertNotSame($traveler, $t1);

        $this->assertEquals($queryCount2, $this->getCurrentQueryCount());

        $this->assertCount(2, $t1->getTravels());
        $this->assertTrue($this->cache->containsEntity(Travel::CLASSNAME, $traveler->getId()));
        $this->assertTrue($this->cache->containsCollection(Traveler::CLASSNAME, 'travels', $traveler->getId()));
        $this->assertTrue($this->cache->containsEntity(Travel::CLASSNAME, $traveler->getTravels()->get(0)->getId()));
        $this->assertTrue($this->cache->containsEntity(Travel::CLASSNAME, $traveler->getTravels()->get(2)->getId()));

        $this->assertFalse($this->cache->containsEntity(Travel::CLASSNAME, $traveler->getTravels()->get(1)->getId()));
        $this->assertNull($this->_em->find(Travel::CLASSNAME, $traveler->getTravels()->get(1)->getId()));

        $this->assertEquals($queryCount2 + 1, $this->getCurrentQueryCount());
    }

    public function testLoadOnoToManyCollectionFromDatabaseWhenEntityMissing()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->_em->clear();
        
        $this->assertTrue($this->cache->containsEntity(State::CLASSNAME, $this->states[0]->getId()));
        $this->assertTrue($this->cache->containsCollection(State::CLASSNAME, 'cities', $this->states[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(City::CLASSNAME, $this->states[0]->getCities()->get(0)->getId()));
        $this->assertTrue($this->cache->containsEntity(City::CLASSNAME, $this->states[0]->getCities()->get(1)->getId()));
       
        $queryCount = $this->getCurrentQueryCount();
        $stateId    = $this->states[0]->getId();
        $state      = $this->_em->find(State::CLASSNAME, $stateId);
        $cityId     = $this->states[0]->getCities()->get(1)->getId();

        //trigger lazy load from cache
        $this->assertCount(2, $state->getCities());
        $this->assertEquals($queryCount, $this->getCurrentQueryCount());
        $this->assertTrue($this->cache->containsEntity(City::CLASSNAME, $cityId));

        $this->cache->evictEntity(City::CLASSNAME, $cityId);

        $this->assertFalse($this->cache->containsEntity(City::CLASSNAME, $cityId));
        $this->assertTrue($this->cache->containsEntity(State::CLASSNAME, $stateId));
        $this->assertTrue($this->cache->containsCollection(State::CLASSNAME, 'cities', $stateId));

        $this->_em->clear();

        $state = $this->_em->find(State::CLASSNAME, $stateId);

        //trigger lazy load from database
        $this->assertCount(2, $state->getCities());
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
    }


    public function testShoudNotPutOneToManyRelationOnPersistIfTheCollectionIsEmpty()
    {
        $this->loadFixturesCountries();
        $this->cache->evictEntityRegion(State::CLASSNAME);

        $state = new State("State Foo", $this->countries[0]);

        $this->_em->persist($state);
        $this->_em->flush();
        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(State::CLASSNAME, $state->getId()));
        $this->assertFalse($this->cache->containsCollection(State::CLASSNAME, 'cities', $state->getId()));

        $state = $this->_em->find(State::CLASSNAME, $state);
        $this->assertInstanceOf(State::CLASSNAME, $state);

        $city = new City("City Bar", $state);

        $state->addCity($city);

        $this->_em->persist($city);
        $this->_em->persist($state);
        $this->_em->flush();

        $this->assertTrue($this->cache->containsEntity(State::CLASSNAME, $state->getId()));
        $this->assertTrue($this->cache->containsCollection(State::CLASSNAME, 'cities', $state->getId()));
        $this->assertTrue($this->cache->containsEntity(City::CLASSNAME, $city->getId()));

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();
        $state      = $this->_em->find(State::CLASSNAME, $state);

        $this->assertInstanceOf(State::CLASSNAME, $state);
        $this->assertCount(1, $state->getCities());
        $this->assertInstanceOf(City::CLASSNAME, $state->getCities()->get(0));
        $this->assertEquals($city->getName(), $state->getCities()->get(0)->getName());
        $this->assertEquals($queryCount, $this->getCurrentQueryCount());
    }
}