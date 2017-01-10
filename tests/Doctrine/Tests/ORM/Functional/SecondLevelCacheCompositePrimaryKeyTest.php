<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\Cache\Flight;

/**
 * @group DDC-2183
 */
class SecondLevelCacheCompositePrimaryKeyTest extends SecondLevelCacheAbstractTest
{
    public function testPutAndLoadCompositPrimaryKeyEntities()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();

        $this->em->clear();
        $this->evictRegions();

        $leavingFromId  = $this->cities[0]->getId();
        $goingToId      = $this->cities[1]->getId();
        $leavingFrom    = $this->em->find(City::class, $leavingFromId);
        $goingTo        = $this->em->find(City::class, $goingToId);
        $flight         = new Flight($leavingFrom, $goingTo);
        $id             = [
            'leavingFrom'   => $leavingFromId,
            'goingTo'       => $goingToId,
        ];

        $flight->setDeparture(new \DateTime('tomorrow'));

        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[0]->getId()));
        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[1]->getId()));

        $this->em->persist($flight);
        $this->em->flush();
        $this->em->clear();

        self::assertTrue($this->cache->containsEntity(Flight::class, $id));
        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[0]->getId()));
        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[1]->getId()));

        $queryCount  = $this->getCurrentQueryCount();
        $flight      = $this->em->find(Flight::class, $id);
        $leavingFrom = $flight->getLeavingFrom();
        $goingTo     = $flight->getGoingTo();

        self::assertInstanceOf(Flight::class, $flight);
        self::assertInstanceOf(City::class, $goingTo);
        self::assertInstanceOf(City::class, $leavingFrom);

        self::assertEquals($goingTo->getId(), $goingToId);
        self::assertEquals($leavingFrom->getId(), $leavingFromId);
        self::assertEquals($queryCount, $this->getCurrentQueryCount());
    }

    public function testRemoveCompositPrimaryKeyEntities()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();

        $this->em->clear();
        $this->evictRegions();

        $leavingFromId  = $this->cities[0]->getId();
        $goingToId      = $this->cities[1]->getId();
        $leavingFrom    = $this->em->find(City::class, $leavingFromId);
        $goingTo        = $this->em->find(City::class, $goingToId);
        $flight         = new Flight($leavingFrom, $goingTo);
        $id             = [
            'leavingFrom'   => $leavingFromId,
            'goingTo'       => $goingToId,
        ];

        $flight->setDeparture(new \DateTime('tomorrow'));

        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[0]->getId()));
        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[1]->getId()));

        $this->em->persist($flight);
        $this->em->flush();

        self::assertTrue($this->cache->containsEntity(Flight::class, $id));
        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[0]->getId()));
        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[1]->getId()));

        $this->em->remove($flight);
        $this->em->flush();
        $this->em->clear();

        self::assertFalse($this->cache->containsEntity(Flight::class, $id));
        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[0]->getId()));
        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[1]->getId()));

        self::assertNull($this->em->find(Flight::class, $id));
    }

    public function testUpdateCompositPrimaryKeyEntities()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();

        $this->em->clear();
        $this->evictRegions();

        $now            = new \DateTime('now');
        $tomorrow       = new \DateTime('tomorrow');
        $leavingFromId  = $this->cities[0]->getId();
        $goingToId      = $this->cities[1]->getId();
        $leavingFrom    = $this->em->find(City::class, $leavingFromId);
        $goingTo        = $this->em->find(City::class, $goingToId);
        $flight         = new Flight($leavingFrom, $goingTo);
        $id             = [
            'leavingFrom'   => $leavingFromId,
            'goingTo'       => $goingToId,
        ];

        $flight->setDeparture($now);

        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[0]->getId()));
        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[1]->getId()));

        $this->em->persist($flight);
        $this->em->flush();
        $this->em->clear();

        self::assertTrue($this->cache->containsEntity(Flight::class, $id));
        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[0]->getId()));
        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[1]->getId()));

        $queryCount  = $this->getCurrentQueryCount();
        $flight      = $this->em->find(Flight::class, $id);
        $leavingFrom = $flight->getLeavingFrom();
        $goingTo     = $flight->getGoingTo();

        self::assertInstanceOf(Flight::class, $flight);
        self::assertInstanceOf(City::class, $goingTo);
        self::assertInstanceOf(City::class, $leavingFrom);

        self::assertEquals($goingTo->getId(), $goingToId);
        self::assertEquals($flight->getDeparture(), $now);
        self::assertEquals($leavingFrom->getId(), $leavingFromId);
        self::assertEquals($leavingFrom->getId(), $leavingFromId);
        self::assertEquals($queryCount, $this->getCurrentQueryCount());

        $flight->setDeparture($tomorrow);

        $this->em->persist($flight);
        $this->em->flush();
        $this->em->clear();

        self::assertTrue($this->cache->containsEntity(Flight::class, $id));
        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[0]->getId()));
        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[1]->getId()));

        $queryCount  = $this->getCurrentQueryCount();
        $flight      = $this->em->find(Flight::class, $id);
        $leavingFrom = $flight->getLeavingFrom();
        $goingTo     = $flight->getGoingTo();

        self::assertInstanceOf(Flight::class, $flight);
        self::assertInstanceOf(City::class, $goingTo);
        self::assertInstanceOf(City::class, $leavingFrom);

        self::assertEquals($goingTo->getId(), $goingToId);
        self::assertEquals($flight->getDeparture(), $tomorrow);
        self::assertEquals($leavingFrom->getId(), $leavingFromId);
        self::assertEquals($leavingFrom->getId(), $leavingFromId);
        self::assertEquals($queryCount, $this->getCurrentQueryCount());
    }
}
