<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\Cache\Travel;

/** @group DDC-2183 */
class SecondLevelCacheExtraLazyCollectionTest extends SecondLevelCacheFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $sourceEntity = $this->_em->getClassMetadata(Travel::class);
        $targetEntity = $this->_em->getClassMetadata(City::class);

        $sourceEntity->associationMappings['visitedCities']['fetch'] = ClassMetadata::FETCH_EXTRA_LAZY;
        $targetEntity->associationMappings['travels']['fetch']       = ClassMetadata::FETCH_EXTRA_LAZY;
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $sourceEntity = $this->_em->getClassMetadata(Travel::class);
        $targetEntity = $this->_em->getClassMetadata(City::class);

        $sourceEntity->associationMappings['visitedCities']['fetch'] = ClassMetadata::FETCH_LAZY;
        $targetEntity->associationMappings['travels']['fetch']       = ClassMetadata::FETCH_LAZY;
    }

    public function testCacheCountAfterAddThenFlush(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesTraveler();
        $this->loadFixturesTravels();

        $this->_em->clear();

        $ownerId = $this->travels[0]->getId();
        $owner   = $this->_em->find(Travel::class, $ownerId);
        $ref     = $this->_em->find(State::class, $this->states[1]->getId());

        self::assertTrue($this->cache->containsEntity(Travel::class, $ownerId));
        self::assertTrue($this->cache->containsCollection(Travel::class, 'visitedCities', $ownerId));

        $newItem = new City('New City', $ref);
        $owner->getVisitedCities()->add($newItem);

        $this->_em->persist($newItem);
        $this->_em->persist($owner);

        $this->getQueryLog()->reset()->enable();

        self::assertFalse($owner->getVisitedCities()->isInitialized());
        self::assertEquals(4, $owner->getVisitedCities()->count());
        self::assertFalse($owner->getVisitedCities()->isInitialized());
        $this->assertQueryCount(0);

        $this->_em->flush();

        self::assertFalse($owner->getVisitedCities()->isInitialized());
        self::assertFalse($this->cache->containsCollection(Travel::class, 'visitedCities', $ownerId));

        $this->_em->clear();

        $this->getQueryLog()->reset()->enable();
        $owner = $this->_em->find(Travel::class, $ownerId);

        self::assertEquals(4, $owner->getVisitedCities()->count());
        self::assertFalse($owner->getVisitedCities()->isInitialized());
        $this->assertQueryCount(1);
    }
}
