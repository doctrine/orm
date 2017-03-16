<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\ORM\Events;

/**
 * @group DDC-2183
 */
class SecondLevelCacheTest extends SecondLevelCacheAbstractTest
{
    public function testPutOnPersist()
    {
        $this->loadFixturesCountries();
        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));
        $this->assertEquals(2, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(Country::class)));
    }

    public function testPutAndLoadEntities()
    {
        $this->loadFixturesCountries();
        $this->_em->clear();

        $this->assertEquals(2, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(Country::class)));

        $this->cache->evictEntityRegion(Country::class);

        $this->assertFalse($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        $this->assertFalse($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        $c1 = $this->_em->find(Country::class, $this->countries[0]->getId());
        $c2 = $this->_em->find(Country::class, $this->countries[1]->getId());

        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        $this->assertInstanceOf(Country::class, $c1);
        $this->assertInstanceOf(Country::class, $c2);

        $this->assertEquals($this->countries[0]->getId(), $c1->getId());
        $this->assertEquals($this->countries[0]->getName(), $c1->getName());

        $this->assertEquals($this->countries[1]->getId(), $c2->getId());
        $this->assertEquals($this->countries[1]->getName(), $c2->getName());

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();

        $c3 = $this->_em->find(Country::class, $this->countries[0]->getId());
        $c4 = $this->_em->find(Country::class, $this->countries[1]->getId());

        $this->assertEquals($queryCount, $this->getCurrentQueryCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(Country::class)));

        $this->assertInstanceOf(Country::class, $c3);
        $this->assertInstanceOf(Country::class, $c4);

        $this->assertEquals($c1->getId(), $c3->getId());
        $this->assertEquals($c1->getName(), $c3->getName());

        $this->assertEquals($c2->getId(), $c4->getId());
        $this->assertEquals($c2->getName(), $c4->getName());
    }

    public function testRemoveEntities()
    {
        $this->loadFixturesCountries();
        $this->_em->clear();

        $this->assertEquals(2, $this->secondLevelCacheLogger->getPutCount());

        $this->cache->evictEntityRegion(Country::class);
        $this->secondLevelCacheLogger->clearRegionStats($this->getEntityRegion(Country::class));

        $this->assertFalse($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        $this->assertFalse($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        $c1 = $this->_em->find(Country::class, $this->countries[0]->getId());
        $c2 = $this->_em->find(Country::class, $this->countries[1]->getId());

        $this->assertEquals(2, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getMissCount());

        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        $this->assertInstanceOf(Country::class, $c1);
        $this->assertInstanceOf(Country::class, $c2);

        $this->assertEquals($this->countries[0]->getId(), $c1->getId());
        $this->assertEquals($this->countries[0]->getName(), $c1->getName());

        $this->assertEquals($this->countries[1]->getId(), $c2->getId());
        $this->assertEquals($this->countries[1]->getName(), $c2->getName());

        $this->_em->remove($c1);
        $this->_em->remove($c2);
        $this->_em->flush();
        $this->_em->clear();

        $this->assertFalse($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        $this->assertFalse($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        $this->assertNull($this->_em->find(Country::class, $this->countries[0]->getId()));
        $this->assertNull($this->_em->find(Country::class, $this->countries[1]->getId()));

        $this->assertEquals(2, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getMissCount());
    }

    public function testUpdateEntities()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->_em->clear();

        $this->assertEquals(6, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(Country::class)));
        $this->assertEquals(4, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(State::class)));

        $this->cache->evictEntityRegion(State::class);
        $this->secondLevelCacheLogger->clearRegionStats($this->getEntityRegion(State::class));

        $this->assertFalse($this->cache->containsEntity(State::class, $this->states[0]->getId()));
        $this->assertFalse($this->cache->containsEntity(State::class, $this->states[1]->getId()));

        $s1 = $this->_em->find(State::class, $this->states[0]->getId());
        $s2 = $this->_em->find(State::class, $this->states[1]->getId());

        $this->assertEquals(4, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(Country::class)));
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(State::class)));

        $this->assertTrue($this->cache->containsEntity(State::class, $this->states[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(State::class, $this->states[1]->getId()));

        $this->assertInstanceOf(State::class, $s1);
        $this->assertInstanceOf(State::class, $s2);

        $this->assertEquals($this->states[0]->getId(), $s1->getId());
        $this->assertEquals($this->states[0]->getName(), $s1->getName());

        $this->assertEquals($this->states[1]->getId(), $s2->getId());
        $this->assertEquals($this->states[1]->getName(), $s2->getName());

        $s1->setName("NEW NAME 1");
        $s2->setName("NEW NAME 2");

        $this->_em->persist($s1);
        $this->_em->persist($s2);
        $this->_em->flush();
        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(State::class, $this->states[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(State::class, $this->states[1]->getId()));

        $this->assertEquals(6, $this->secondLevelCacheLogger->getPutCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(Country::class)));
        $this->assertEquals(4, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(State::class)));

        $queryCount = $this->getCurrentQueryCount();

        $c3 = $this->_em->find(State::class, $this->states[0]->getId());
        $c4 = $this->_em->find(State::class, $this->states[1]->getId());

        $this->assertEquals(2, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(State::class)));

        $this->assertEquals($queryCount, $this->getCurrentQueryCount());

        $this->assertTrue($this->cache->containsEntity(State::class, $this->states[0]->getId()));
        $this->assertTrue($this->cache->containsEntity(State::class, $this->states[1]->getId()));

        $this->assertInstanceOf(State::class, $c3);
        $this->assertInstanceOf(State::class, $c4);

        $this->assertEquals($s1->getId(), $c3->getId());
        $this->assertEquals("NEW NAME 1", $c3->getName());

        $this->assertEquals($s2->getId(), $c4->getId());
        $this->assertEquals("NEW NAME 2", $c4->getName());

        $this->assertEquals(2, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals(2, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(State::class)));
    }

    public function testPostFlushFailure()
    {
        $listener = new ListenerSecondLevelCacheTest(
            [
                Events::postFlush => function(){
            throw new \RuntimeException('post flush failure');
        }
            ]
        );

        $this->_em->getEventManager()
            ->addEventListener(Events::postFlush, $listener);

        $country = new Country("Brazil");

        $this->cache->evictEntityRegion(Country::class);

        try {

            $this->_em->persist($country);
            $this->_em->flush();
            $this->fail('Should throw exception');

        } catch (\RuntimeException $exc) {
            $this->assertNotNull($country->getId());
            $this->assertEquals('post flush failure', $exc->getMessage());
            $this->assertTrue($this->cache->containsEntity(Country::class, $country->getId()));
        }
    }

    public function testPostUpdateFailure()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->_em->clear();

        $listener = new ListenerSecondLevelCacheTest(
            [
                Events::postUpdate => function(){
            throw new \RuntimeException('post update failure');
        }
            ]
        );

        $this->_em->getEventManager()
            ->addEventListener(Events::postUpdate, $listener);

        $this->cache->evictEntityRegion(State::class);

        $stateId    = $this->states[0]->getId();
        $stateName  = $this->states[0]->getName();
        $state      = $this->_em->find(State::class, $stateId);

        $this->assertTrue($this->cache->containsEntity(State::class, $stateId));
        $this->assertInstanceOf(State::class, $state);
        $this->assertEquals($stateName, $state->getName());

        $state->setName($stateName . uniqid());

        $this->_em->persist($state);

        try {
            $this->_em->flush();
            $this->fail('Should throw exception');

        } catch (\Exception $exc) {
            $this->assertEquals('post update failure', $exc->getMessage());
        }

        $this->_em->clear();

        $this->assertTrue($this->cache->containsEntity(State::class, $stateId));

        $state = $this->_em->find(State::class, $stateId);

        $this->assertInstanceOf(State::class, $state);
        $this->assertEquals($stateName, $state->getName());
    }

    public function testPostRemoveFailure()
    {
        $this->loadFixturesCountries();
        $this->_em->clear();

        $listener = new ListenerSecondLevelCacheTest(
            [
                Events::postRemove => function(){
            throw new \RuntimeException('post remove failure');
        }
            ]
        );

        $this->_em->getEventManager()
            ->addEventListener(Events::postRemove, $listener);

        $this->cache->evictEntityRegion(Country::class);

        $countryId  = $this->countries[0]->getId();
        $country    = $this->_em->find(Country::class, $countryId);

        $this->assertTrue($this->cache->containsEntity(Country::class, $countryId));
        $this->assertInstanceOf(Country::class, $country);

        $this->_em->remove($country);

        try {
            $this->_em->flush();
            $this->fail('Should throw exception');

        } catch (\Exception $exc) {
            $this->assertEquals('post remove failure', $exc->getMessage());
        }

        $this->_em->clear();

        $this->assertFalse(
            $this->cache->containsEntity(Country::class, $countryId),
            'Removal attempts should clear the cache entry corresponding to the entity'
        );

        $this->assertInstanceOf(Country::class, $this->_em->find(Country::class, $countryId));
    }

    public function testCachedNewEntityExists()
    {
        $this->loadFixturesCountries();

        $persister  = $this->_em->getUnitOfWork()->getEntityPersister(Country::class);
        $queryCount = $this->getCurrentQueryCount();

        $this->assertTrue($persister->exists($this->countries[0]));

        $this->assertEquals($queryCount, $this->getCurrentQueryCount());

        $this->assertFalse($persister->exists(new Country('Foo')));
    }
}


class ListenerSecondLevelCacheTest
{
    public $callbacks;

    public function __construct(array $callbacks = [])
    {
        $this->callbacks = $callbacks;
    }

    private function dispatch($eventName, $args)
    {
        if (isset($this->callbacks[$eventName])) {
            call_user_func($this->callbacks[$eventName], $args);
        }
    }

    public function postFlush($args)
    {
        $this->dispatch(__FUNCTION__, $args);
    }

    public function postUpdate($args)
    {
        $this->dispatch(__FUNCTION__, $args);
    }

    public function postRemove($args)
    {
        $this->dispatch(__FUNCTION__, $args);
    }
}
