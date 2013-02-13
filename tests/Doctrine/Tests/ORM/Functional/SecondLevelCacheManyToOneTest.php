<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\Models\Cache\State;

/**
 * @group DDC-2183
 */
class SecondLevelCacheManyToOneTest extends SecondLevelCacheAbstractTest
{
    public function testPutOnPersist()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->states[0]->getCountry()->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->states[1]->getCountry()->getId()));
        $this->assertTrue($this->cache->containsEntity(State::CLASSNAME, $this->states[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(State::CLASSNAME, $this->states[1]->getId()));
    }

    public function testPutAndLoadManyToOneRelation()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->_em->clear();

        $this->cache->evictEntityRegion(State::CLASSNAME);
        $this->cache->evictEntityRegion(Country::CLASSNAME);

        $this->assertFalse($this->cache->containsEntity(State::CLASSNAME, $this->states[0]->getId()));
        $this->assertFalse($this->cache->containsEntity(State::CLASSNAME, $this->states[1]->getId()));
        $this->assertFalse($this->cache->containsEntity(Country::CLASSNAME, $this->states[0]->getCountry()->getId()));
        $this->assertFalse($this->cache->containsEntity(Country::CLASSNAME, $this->states[1]->getCountry()->getId()));

        $c1 = $this->_em->find(State::CLASSNAME, $this->states[0]->getId());
        $c2 = $this->_em->find(State::CLASSNAME, $this->states[1]->getId());

        //trigger lazy load
        $this->assertNotNull($c1->getCountry()->getName());
        $this->assertNotNull($c2->getCountry()->getName());

        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->states[0]->getCountry()->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->states[1]->getCountry()->getId()));
        $this->assertTrue($this->cache->containsEntity(State::CLASSNAME, $this->states[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(State::CLASSNAME, $this->states[1]->getId()));

        $this->assertInstanceOf(State::CLASSNAME, $c1);
        $this->assertInstanceOf(State::CLASSNAME, $c2);
        $this->assertInstanceOf(Country::CLASSNAME, $c1->getCountry());
        $this->assertInstanceOf(Country::CLASSNAME, $c2->getCountry());

        $this->assertEquals($this->states[0]->getId(), $c1->getId());
        $this->assertEquals($this->states[0]->getName(), $c1->getName());
        $this->assertEquals($this->states[0]->getCountry()->getId(), $c1->getCountry()->getId());
        $this->assertEquals($this->states[0]->getCountry()->getName(), $c1->getCountry()->getName());

        $this->assertEquals($this->states[1]->getId(), $c2->getId());
        $this->assertEquals($this->states[1]->getName(), $c2->getName());
        $this->assertEquals($this->states[1]->getCountry()->getId(), $c2->getCountry()->getId());
        $this->assertEquals($this->states[1]->getCountry()->getName(), $c2->getCountry()->getName());

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();

        $c3 = $this->_em->find(State::CLASSNAME, $this->states[0]->getId());
        $c4 = $this->_em->find(State::CLASSNAME, $this->states[1]->getId());

        $this->assertEquals($queryCount, $this->getCurrentQueryCount());

        //trigger lazy load from cache
        $this->assertNotNull($c3->getCountry()->getName());
        $this->assertNotNull($c4->getCountry()->getName());

        $this->assertInstanceOf(State::CLASSNAME, $c3);
        $this->assertInstanceOf(State::CLASSNAME, $c4);
        $this->assertInstanceOf(Country::CLASSNAME, $c3->getCountry());
        $this->assertInstanceOf(Country::CLASSNAME, $c4->getCountry());

        $this->assertEquals($c1->getId(), $c3->getId());
        $this->assertEquals($c1->getName(), $c3->getName());

        $this->assertEquals($c2->getId(), $c4->getId());
        $this->assertEquals($c2->getName(), $c4->getName());

        $this->assertEquals($this->states[0]->getCountry()->getId(), $c3->getCountry()->getId());
        $this->assertEquals($this->states[0]->getCountry()->getName(), $c3->getCountry()->getName());

        $this->assertEquals($this->states[1]->getCountry()->getId(), $c4->getCountry()->getId());
        $this->assertEquals($this->states[1]->getCountry()->getName(), $c4->getCountry()->getName());
    }

    public function testLoadFromDatabaseWhenAssociationIsMissing()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->states[0]->getCountry()->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $this->states[1]->getCountry()->getId()));
        $this->assertTrue($this->cache->containsEntity(State::CLASSNAME, $this->states[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(State::CLASSNAME, $this->states[1]->getId()));

        $this->cache->evictEntityRegion(Country::CLASSNAME);
        $this->assertFalse($this->cache->containsEntity(Country::CLASSNAME, $this->states[0]->getCountry()->getId()));
        $this->assertFalse($this->cache->containsEntity(Country::CLASSNAME, $this->states[1]->getCountry()->getId()));

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();

        $state1 = $this->_em->find(State::CLASSNAME, $this->states[0]->getId());
        $state2 = $this->_em->find(State::CLASSNAME, $this->states[1]->getId());

        $this->assertEquals($queryCount + 2, $this->getCurrentQueryCount());
    }
}