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
        $this->em->clear();

        self::assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        self::assertTrue($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));
        self::assertEquals(2, $this->secondLevelCacheLogger->getPutCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(Country::class)));
    }

    public function testPutAndLoadEntities()
    {
        $this->loadFixturesCountries();
        $this->em->clear();

        self::assertEquals(2, $this->secondLevelCacheLogger->getPutCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(Country::class)));

        $this->cache->evictEntityRegion(Country::class);

        self::assertFalse($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        self::assertFalse($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        $c1 = $this->em->find(Country::class, $this->countries[0]->getId());
        $c2 = $this->em->find(Country::class, $this->countries[1]->getId());

        self::assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        self::assertTrue($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        self::assertInstanceOf(Country::class, $c1);
        self::assertInstanceOf(Country::class, $c2);

        self::assertEquals($this->countries[0]->getId(), $c1->getId());
        self::assertEquals($this->countries[0]->getName(), $c1->getName());

        self::assertEquals($this->countries[1]->getId(), $c2->getId());
        self::assertEquals($this->countries[1]->getName(), $c2->getName());

        $this->em->clear();

        $queryCount = $this->getCurrentQueryCount();

        $c3 = $this->em->find(Country::class, $this->countries[0]->getId());
        $c4 = $this->em->find(Country::class, $this->countries[1]->getId());

        self::assertEquals($queryCount, $this->getCurrentQueryCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getHitCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(Country::class)));

        self::assertInstanceOf(Country::class, $c3);
        self::assertInstanceOf(Country::class, $c4);

        self::assertEquals($c1->getId(), $c3->getId());
        self::assertEquals($c1->getName(), $c3->getName());

        self::assertEquals($c2->getId(), $c4->getId());
        self::assertEquals($c2->getName(), $c4->getName());
    }

    public function testRemoveEntities()
    {
        $this->loadFixturesCountries();
        $this->em->clear();

        self::assertEquals(2, $this->secondLevelCacheLogger->getPutCount());

        $this->cache->evictEntityRegion(Country::class);
        $this->secondLevelCacheLogger->clearRegionStats($this->getEntityRegion(Country::class));

        self::assertFalse($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        self::assertFalse($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        $c1 = $this->em->find(Country::class, $this->countries[0]->getId());
        $c2 = $this->em->find(Country::class, $this->countries[1]->getId());

        self::assertEquals(2, $this->secondLevelCacheLogger->getPutCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getMissCount());

        self::assertTrue($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        self::assertTrue($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        self::assertInstanceOf(Country::class, $c1);
        self::assertInstanceOf(Country::class, $c2);

        self::assertEquals($this->countries[0]->getId(), $c1->getId());
        self::assertEquals($this->countries[0]->getName(), $c1->getName());

        self::assertEquals($this->countries[1]->getId(), $c2->getId());
        self::assertEquals($this->countries[1]->getName(), $c2->getName());

        $this->em->remove($c1);
        $this->em->remove($c2);
        $this->em->flush();
        $this->em->clear();

        self::assertFalse($this->cache->containsEntity(Country::class, $this->countries[0]->getId()));
        self::assertFalse($this->cache->containsEntity(Country::class, $this->countries[1]->getId()));

        self::assertNull($this->em->find(Country::class, $this->countries[0]->getId()));
        self::assertNull($this->em->find(Country::class, $this->countries[1]->getId()));

        self::assertEquals(2, $this->secondLevelCacheLogger->getPutCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getMissCount());
    }

    public function testUpdateEntities()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->em->clear();

        self::assertEquals(6, $this->secondLevelCacheLogger->getPutCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(Country::class)));
        self::assertEquals(4, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(State::class)));

        $this->cache->evictEntityRegion(State::class);
        $this->secondLevelCacheLogger->clearRegionStats($this->getEntityRegion(State::class));

        self::assertFalse($this->cache->containsEntity(State::class, $this->states[0]->getId()));
        self::assertFalse($this->cache->containsEntity(State::class, $this->states[1]->getId()));

        $s1 = $this->em->find(State::class, $this->states[0]->getId());
        $s2 = $this->em->find(State::class, $this->states[1]->getId());

        self::assertEquals(4, $this->secondLevelCacheLogger->getPutCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(Country::class)));
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(State::class)));

        self::assertTrue($this->cache->containsEntity(State::class, $this->states[0]->getId()));
        self::assertTrue($this->cache->containsEntity(State::class, $this->states[1]->getId()));

        self::assertInstanceOf(State::class, $s1);
        self::assertInstanceOf(State::class, $s2);

        self::assertEquals($this->states[0]->getId(), $s1->getId());
        self::assertEquals($this->states[0]->getName(), $s1->getName());

        self::assertEquals($this->states[1]->getId(), $s2->getId());
        self::assertEquals($this->states[1]->getName(), $s2->getName());

        $s1->setName("NEW NAME 1");
        $s2->setName("NEW NAME 2");

        $this->em->persist($s1);
        $this->em->persist($s2);
        $this->em->flush();
        $this->em->clear();

        self::assertTrue($this->cache->containsEntity(State::class, $this->states[0]->getId()));
        self::assertTrue($this->cache->containsEntity(State::class, $this->states[1]->getId()));

        self::assertEquals(6, $this->secondLevelCacheLogger->getPutCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(Country::class)));
        self::assertEquals(4, $this->secondLevelCacheLogger->getRegionPutCount($this->getEntityRegion(State::class)));

        $queryCount = $this->getCurrentQueryCount();

        $c3 = $this->em->find(State::class, $this->states[0]->getId());
        $c4 = $this->em->find(State::class, $this->states[1]->getId());

        self::assertEquals(2, $this->secondLevelCacheLogger->getHitCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(State::class)));

        self::assertEquals($queryCount, $this->getCurrentQueryCount());

        self::assertTrue($this->cache->containsEntity(State::class, $this->states[0]->getId()));
        self::assertTrue($this->cache->containsEntity(State::class, $this->states[1]->getId()));

        self::assertInstanceOf(State::class, $c3);
        self::assertInstanceOf(State::class, $c4);

        self::assertEquals($s1->getId(), $c3->getId());
        self::assertEquals("NEW NAME 1", $c3->getName());

        self::assertEquals($s2->getId(), $c4->getId());
        self::assertEquals("NEW NAME 2", $c4->getName());

        self::assertEquals(2, $this->secondLevelCacheLogger->getHitCount());
        self::assertEquals(2, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(State::class)));
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

        $this->em->getEventManager()
            ->addEventListener(Events::postFlush, $listener);

        $country = new Country("Brazil");

        $this->cache->evictEntityRegion(Country::class);

        try {

            $this->em->persist($country);
            $this->em->flush();
            $this->fail('Should throw exception');

        } catch (\RuntimeException $exc) {
            self::assertNotNull($country->getId());
            self::assertEquals('post flush failure', $exc->getMessage());
            self::assertTrue($this->cache->containsEntity(Country::class, $country->getId()));
        }
    }

    public function testPostUpdateFailure()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->em->clear();

        $listener = new ListenerSecondLevelCacheTest(
            [
                Events::postUpdate => function(){
            throw new \RuntimeException('post update failure');
        }
            ]
        );

        $this->em->getEventManager()
            ->addEventListener(Events::postUpdate, $listener);

        $this->cache->evictEntityRegion(State::class);

        $stateId    = $this->states[0]->getId();
        $stateName  = $this->states[0]->getName();
        $state      = $this->em->find(State::class, $stateId);

        self::assertTrue($this->cache->containsEntity(State::class, $stateId));
        self::assertInstanceOf(State::class, $state);
        self::assertEquals($stateName, $state->getName());

        $state->setName($stateName . uniqid());

        $this->em->persist($state);

        try {
            $this->em->flush();
            $this->fail('Should throw exception');

        } catch (\Exception $exc) {
            self::assertEquals('post update failure', $exc->getMessage());
        }

        $this->em->clear();

        self::assertTrue($this->cache->containsEntity(State::class, $stateId));

        $state = $this->em->find(State::class, $stateId);

        self::assertInstanceOf(State::class, $state);
        self::assertEquals($stateName, $state->getName());
    }

    public function testPostRemoveFailure()
    {
        $this->loadFixturesCountries();
        $this->em->clear();

        $listener = new ListenerSecondLevelCacheTest(
            [
                Events::postRemove => function(){
            throw new \RuntimeException('post remove failure');
        }
            ]
        );

        $this->em->getEventManager()
            ->addEventListener(Events::postRemove, $listener);

        $this->cache->evictEntityRegion(Country::class);

        $countryId  = $this->countries[0]->getId();
        $country    = $this->em->find(Country::class, $countryId);

        self::assertTrue($this->cache->containsEntity(Country::class, $countryId));
        self::assertInstanceOf(Country::class, $country);

        $this->em->remove($country);

        try {
            $this->em->flush();
            $this->fail('Should throw exception');

        } catch (\Exception $exc) {
            self::assertEquals('post remove failure', $exc->getMessage());
        }

        $this->em->clear();

        self::assertFalse(
            $this->cache->containsEntity(Country::class, $countryId),
            'Removal attempts should clear the cache entry corresponding to the entity'
        );

        self::assertInstanceOf(Country::class, $this->em->find(Country::class, $countryId));
    }

    public function testCachedNewEntityExists()
    {
        $this->loadFixturesCountries();

        $persister  = $this->em->getUnitOfWork()->getEntityPersister(Country::class);
        $queryCount = $this->getCurrentQueryCount();

        self::assertTrue($persister->exists($this->countries[0]));

        self::assertEquals($queryCount, $this->getCurrentQueryCount());

        self::assertFalse($persister->exists(new Country('Foo')));
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
