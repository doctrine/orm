<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\Cache\City;

/**
 * @group DDC-2183
 */
class SecondLevelCacheOneToManyTest extends SecondLevelCacheAbstractTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->_em->clear();
    }

    public function testPutAndLoadOneToManyRelation()
    {
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

        $c1 = $this->_em->find(State::CLASSNAME, $this->states[0]->getId());
        $c2 = $this->_em->find(State::CLASSNAME, $this->states[1]->getId());

        //trigger lazy load
        $this->assertCount(2, $c1->getCities());
        $this->assertCount(2, $c2->getCities());

        $this->assertInstanceOf(City::CLASSNAME, $c1->getCities()->get(0));
        $this->assertInstanceOf(City::CLASSNAME, $c1->getCities()->get(1));

        $this->assertInstanceOf(City::CLASSNAME, $c2->getCities()->get(0));
        $this->assertInstanceOf(City::CLASSNAME, $c2->getCities()->get(1));

        $this->assertTrue($this->cache->containsEntity(State::CLASSNAME, $this->states[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(State::CLASSNAME, $this->states[1]->getId()));

        $this->assertTrue($this->cache->containsCollection(State::CLASSNAME, 'cities', $this->states[0]->getId()));
        $this->assertTrue($this->cache->containsCollection(State::CLASSNAME, 'cities', $this->states[1]->getId()));

        $this->assertTrue($this->cache->containsEntity(City::CLASSNAME, $this->states[0]->getCities()->get(0)->getId()));
        $this->assertTrue($this->cache->containsEntity(City::CLASSNAME, $this->states[0]->getCities()->get(1)->getId()));
        $this->assertTrue($this->cache->containsEntity(City::CLASSNAME, $this->states[1]->getCities()->get(0)->getId()));
        $this->assertTrue($this->cache->containsEntity(City::CLASSNAME, $this->states[1]->getCities()->get(1)->getId()));

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();

        $c3 = $this->_em->find(State::CLASSNAME, $this->states[0]->getId());
        $c4 = $this->_em->find(State::CLASSNAME, $this->states[1]->getId());

        //trigger lazy load from cache
        $this->assertCount(2, $c3->getCities());
        $this->assertCount(2, $c4->getCities());

        $this->assertInstanceOf(City::CLASSNAME, $c3->getCities()->get(0));
        $this->assertInstanceOf(City::CLASSNAME, $c3->getCities()->get(1));
        $this->assertInstanceOf(City::CLASSNAME, $c4->getCities()->get(0));
        $this->assertInstanceOf(City::CLASSNAME, $c4->getCities()->get(1));

        $this->assertNotSame($c1->getCities()->get(0), $c3->getCities()->get(0));
        $this->assertEquals($c1->getCities()->get(0)->getId(), $c3->getCities()->get(0)->getId());
        $this->assertEquals($c1->getCities()->get(0)->getName(), $c3->getCities()->get(0)->getName());

        $this->assertNotSame($c1->getCities()->get(1), $c3->getCities()->get(1));
        $this->assertEquals($c1->getCities()->get(1)->getId(), $c3->getCities()->get(1)->getId());
        $this->assertEquals($c1->getCities()->get(1)->getName(), $c3->getCities()->get(1)->getName());

        $this->assertNotSame($c2->getCities()->get(0), $c4->getCities()->get(0));
        $this->assertEquals($c2->getCities()->get(0)->getId(), $c4->getCities()->get(0)->getId());
        $this->assertEquals($c2->getCities()->get(0)->getName(), $c4->getCities()->get(0)->getName());

        $this->assertNotSame($c2->getCities()->get(1), $c4->getCities()->get(1));
        $this->assertEquals($c2->getCities()->get(1)->getId(), $c4->getCities()->get(1)->getId());
        $this->assertEquals($c2->getCities()->get(1)->getName(), $c4->getCities()->get(1)->getName());

        $this->assertEquals($queryCount, $this->getCurrentQueryCount());
    }
}