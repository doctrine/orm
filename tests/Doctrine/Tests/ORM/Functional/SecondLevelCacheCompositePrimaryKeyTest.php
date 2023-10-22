<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use DateTime;
use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\Cache\Flight;

/** @group DDC-2183 */
class SecondLevelCacheCompositePrimaryKeyTest extends SecondLevelCacheFunctionalTestCase
{
    public function testPutAndLoadCompositPrimaryKeyEntities(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();

        $this->_em->clear();
        $this->evictRegions();

        $leavingFromId = $this->cities[0]->getId();
        $goingToId     = $this->cities[1]->getId();
        $leavingFrom   = $this->_em->find(City::class, $leavingFromId);
        $goingTo       = $this->_em->find(City::class, $goingToId);
        $flight        = new Flight($leavingFrom, $goingTo);
        $id            = [
            'leavingFrom'   => $leavingFromId,
            'goingTo'       => $goingToId,
        ];

        $flight->setDeparture(new DateTime('tomorrow'));

        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[0]->getId()));
        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[1]->getId()));

        $this->_em->persist($flight);
        $this->_em->flush();
        $this->_em->clear();

        self::assertTrue($this->cache->containsEntity(Flight::class, $id));
        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[0]->getId()));
        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[1]->getId()));

        $this->getQueryLog()->reset()->enable();
        $flight      = $this->_em->find(Flight::class, $id);
        $leavingFrom = $flight->getLeavingFrom();
        $goingTo     = $flight->getGoingTo();

        self::assertInstanceOf(Flight::class, $flight);
        self::assertInstanceOf(City::class, $goingTo);
        self::assertInstanceOf(City::class, $leavingFrom);

        self::assertEquals($goingTo->getId(), $goingToId);
        self::assertEquals($leavingFrom->getId(), $leavingFromId);
        $this->assertQueryCount(0);
    }

    public function testRemoveCompositPrimaryKeyEntities(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();

        $this->_em->clear();
        $this->evictRegions();

        $leavingFromId = $this->cities[0]->getId();
        $goingToId     = $this->cities[1]->getId();
        $leavingFrom   = $this->_em->find(City::class, $leavingFromId);
        $goingTo       = $this->_em->find(City::class, $goingToId);
        $flight        = new Flight($leavingFrom, $goingTo);
        $id            = [
            'leavingFrom'   => $leavingFromId,
            'goingTo'       => $goingToId,
        ];

        $flight->setDeparture(new DateTime('tomorrow'));

        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[0]->getId()));
        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[1]->getId()));

        $this->_em->persist($flight);
        $this->_em->flush();

        self::assertTrue($this->cache->containsEntity(Flight::class, $id));
        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[0]->getId()));
        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[1]->getId()));

        $this->_em->remove($flight);
        $this->_em->flush();
        $this->_em->clear();

        self::assertFalse($this->cache->containsEntity(Flight::class, $id));
        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[0]->getId()));
        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[1]->getId()));

        self::assertNull($this->_em->find(Flight::class, $id));
    }

    public function testUpdateCompositPrimaryKeyEntities(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();

        $this->_em->clear();
        $this->evictRegions();

        $now           = new DateTime('now');
        $tomorrow      = new DateTime('tomorrow');
        $leavingFromId = $this->cities[0]->getId();
        $goingToId     = $this->cities[1]->getId();
        $leavingFrom   = $this->_em->find(City::class, $leavingFromId);
        $goingTo       = $this->_em->find(City::class, $goingToId);
        $flight        = new Flight($leavingFrom, $goingTo);
        $id            = [
            'leavingFrom'   => $leavingFromId,
            'goingTo'       => $goingToId,
        ];

        $flight->setDeparture($now);

        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[0]->getId()));
        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[1]->getId()));

        $this->_em->persist($flight);
        $this->_em->flush();
        $this->_em->clear();

        self::assertTrue($this->cache->containsEntity(Flight::class, $id));
        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[0]->getId()));
        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[1]->getId()));

        $this->getQueryLog()->reset()->enable();
        $flight      = $this->_em->find(Flight::class, $id);
        $leavingFrom = $flight->getLeavingFrom();
        $goingTo     = $flight->getGoingTo();

        self::assertInstanceOf(Flight::class, $flight);
        self::assertInstanceOf(City::class, $goingTo);
        self::assertInstanceOf(City::class, $leavingFrom);

        self::assertEquals($goingTo->getId(), $goingToId);
        self::assertEquals($flight->getDeparture(), $now);
        self::assertEquals($leavingFrom->getId(), $leavingFromId);
        self::assertEquals($leavingFrom->getId(), $leavingFromId);
        $this->assertQueryCount(0);

        $flight->setDeparture($tomorrow);

        $this->_em->persist($flight);
        $this->_em->flush();
        $this->_em->clear();

        self::assertTrue($this->cache->containsEntity(Flight::class, $id));
        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[0]->getId()));
        self::assertTrue($this->cache->containsEntity(City::class, $this->cities[1]->getId()));

        $this->getQueryLog()->reset()->enable();
        $flight      = $this->_em->find(Flight::class, $id);
        $leavingFrom = $flight->getLeavingFrom();
        $goingTo     = $flight->getGoingTo();

        self::assertInstanceOf(Flight::class, $flight);
        self::assertInstanceOf(City::class, $goingTo);
        self::assertInstanceOf(City::class, $leavingFrom);

        self::assertEquals($goingTo->getId(), $goingToId);
        self::assertEquals($flight->getDeparture(), $tomorrow);
        self::assertEquals($leavingFrom->getId(), $leavingFromId);
        self::assertEquals($leavingFrom->getId(), $leavingFromId);
        $this->assertQueryCount(0);
    }
}
