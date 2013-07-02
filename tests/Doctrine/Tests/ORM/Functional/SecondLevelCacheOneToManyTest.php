<?php

namespace Doctrine\Tests\ORM\Functional;


use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\Cache\State;

/**
 * @group DDC-2183
 */
class SecondLevelCacheOneToManyTest extends SecondLevelCacheAbstractTest
{
    public function testShouldNotPutCollectionInverseSideOnPersist()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(State::CLASSNAME, $this->states[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(State::CLASSNAME, $this->states[1]->getId()));

        $this->assertFalse($this->cache->containsCollection(State::CLASSNAME, 'cities', $this->states[0]->getId()));
        $this->assertFalse($this->cache->containsCollection(State::CLASSNAME, 'cities', $this->states[1]->getId()));
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

    public function testLoadOnoToManyCollectionFromDatabaseWhenEntityMissing()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->_em->clear();

        //trigger lazy load from database
        $this->assertCount(2, $this->_em->find(State::CLASSNAME, $this->states[0]->getId())->getCities());
        
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


    public function testShoudNotPutOneToManyRelationOnPersist()
    {
        $this->loadFixturesCountries();
        $this->cache->evictEntityRegion(State::CLASSNAME);

        $state = new State("State Foo", $this->countries[0]);

        $this->_em->persist($state);
        $this->_em->flush();
        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(State::CLASSNAME, $state->getId()));
        $this->assertFalse($this->cache->containsCollection(State::CLASSNAME, 'cities', $state->getId()));
    }
}