<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\Lock;
use Doctrine\ORM\Cache\Region\DefaultRegion;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\Mocks\ConcurrentRegionMock;
use Doctrine\Tests\Mocks\TimestampRegionMock;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\Models\Cache\State;

/**
 * @group DDC-2183
 */
class SecondLevelCacheConcurrentTest extends SecondLevelCacheAbstractTest
{
    /** @var CacheFactorySecondLevelCacheConcurrentTest */
    private $cacheFactory;

    private $countryMetadata;

    protected function setUp(): void
    {
        $this->enableSecondLevelCache();
        parent::setUp();

        $this->cacheFactory = new CacheFactorySecondLevelCacheConcurrentTest($this->getSharedSecondLevelCacheDriverImpl());

        $this->_em->getConfiguration()
            ->getSecondLevelCacheConfiguration()
            ->setCacheFactory($this->cacheFactory);

        $this->countryMetadata = $this->_em->getClassMetadata(Country::class);
        $countryMetadata       = clone $this->countryMetadata;

        $countryMetadata->cache['usage'] = ClassMetadata::CACHE_USAGE_NONSTRICT_READ_WRITE;

        $this->_em->getMetadataFactory()->setMetadataFor(Country::class, $countryMetadata);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->_em->getMetadataFactory()->setMetadataFor(Country::class, $this->countryMetadata);
    }

    public function testBasicConcurrentEntityReadLock(): void
    {
        $this->loadFixturesCountries();
        $this->_em->clear();

        $countryId = $this->countries[0]->getId();
        $cacheId   = new EntityCacheKey(Country::class, ['id' => $countryId]);
        $region    = $this->_em->getCache()->getEntityCacheRegion(Country::class);

        $this->assertTrue($this->cache->containsEntity(Country::class, $countryId));

        /** @var ConcurrentRegionMock $region */
        $region->setLock($cacheId, Lock::createLockRead()); // another proc lock the entity cache

        $this->assertFalse($this->cache->containsEntity(Country::class, $countryId));

        $queryCount = $this->getCurrentQueryCount();
        $country    = $this->_em->find(Country::class, $countryId);

        $this->assertInstanceOf(Country::class, $country);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertFalse($this->cache->containsEntity(Country::class, $countryId));
    }

    public function testBasicConcurrentCollectionReadLock(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();

        $this->_em->clear();
        $this->evictRegions();

        $stateId = $this->states[0]->getId();
        $state   = $this->_em->find(State::class, $stateId);

        $this->assertInstanceOf(State::class, $state);
        $this->assertInstanceOf(Country::class, $state->getCountry());
        $this->assertNotNull($state->getCountry()->getName());
        $this->assertCount(2, $state->getCities());

        $this->_em->clear();
        $this->secondLevelCacheLogger->clearStats();

        $stateId = $this->states[0]->getId();
        $cacheId = new CollectionCacheKey(State::class, 'cities', ['id' => $stateId]);
        $region  = $this->_em->getCache()->getCollectionCacheRegion(State::class, 'cities');

        $this->assertTrue($this->cache->containsCollection(State::class, 'cities', $stateId));

        /** @var ConcurrentRegionMock $region */
        $region->setLock($cacheId, Lock::createLockRead()); // another proc lock the entity cache

        $this->assertFalse($this->cache->containsCollection(State::class, 'cities', $stateId));

        $queryCount = $this->getCurrentQueryCount();
        $state      = $this->_em->find(State::class, $stateId);

        $this->assertEquals(0, $this->secondLevelCacheLogger->getMissCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getHitCount());

        $this->assertEquals(0, $this->secondLevelCacheLogger->getRegionMissCount($this->getEntityRegion(State::class)));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(State::class)));

        $this->assertInstanceOf(State::class, $state);
        $this->assertCount(2, $state->getCities());

        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount($this->getCollectionRegion(State::class, 'cities')));

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertFalse($this->cache->containsCollection(State::class, 'cities', $stateId));
    }
}

class CacheFactorySecondLevelCacheConcurrentTest extends DefaultCacheFactory
{
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function getRegion(array $cache)
    {
        $region = new DefaultRegion($cache['region'], $this->cache);

        return new ConcurrentRegionMock($region);
    }

    public function getTimestampRegion()
    {
        return new TimestampRegionMock();
    }
}
