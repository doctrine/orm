<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Mapping\CacheUsage;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\Mocks\ConcurrentRegionMock;
use Doctrine\ORM\Cache\Region\DefaultRegion;
use Doctrine\Tests\Mocks\TimestampRegionMock;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Cache\Lock;

/**
 * @group DDC-2183
 */
class SecondLevelCacheConcurrentTest extends SecondLevelCacheAbstractTest
{
    /**
     * @var \Doctrine\Tests\ORM\Functional\CacheFactorySecondLevelCacheConcurrentTest
     */
    private $cacheFactory;

    private $countryMetadata;

    protected function setUp()
    {
        $this->enableSecondLevelCache();
        parent::setUp();

        $this->cacheFactory = new CacheFactorySecondLevelCacheConcurrentTest($this->getSharedSecondLevelCacheDriverImpl());

        $this->_em->getConfiguration()
            ->getSecondLevelCacheConfiguration()
            ->setCacheFactory($this->cacheFactory);

        $this->countryMetadata = $this->_em->getClassMetadata(Country::class);
        $countryMetadata       = clone $this->countryMetadata;

        $countryMetadata->cache['usage'] = CacheUsage::NONSTRICT_READ_WRITE;

        $this->_em->getMetadataFactory()->setMetadataFor(Country::class, $countryMetadata);
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->_em->getMetadataFactory()->setMetadataFor(Country::class, $this->countryMetadata);
    }

    public function testBasicConcurrentEntityReadLock()
    {
        $this->loadFixturesCountries();
        $this->_em->clear();

        $countryId = $this->countries[0]->getId();
        $cacheId   = new EntityCacheKey(Country::class, ['id'=>$countryId]);
        $region    = $this->_em->getCache()->getEntityCacheRegion(Country::class);

        self::assertTrue($this->cache->containsEntity(Country::class, $countryId));

        /** @var \Doctrine\Tests\Mocks\ConcurrentRegionMock */
        $region->setLock($cacheId, Lock::createLockRead()); // another proc lock the entity cache

        self::assertFalse($this->cache->containsEntity(Country::class, $countryId));

        $queryCount = $this->getCurrentQueryCount();
        $country    = $this->_em->find(Country::class, $countryId);

        self::assertInstanceOf(Country::class, $country);
        self::assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        self::assertFalse($this->cache->containsEntity(Country::class, $countryId));
    }

    public function testBasicConcurrentCollectionReadLock()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();

        $this->_em->clear();
        $this->evictRegions();

        $stateId    = $this->states[0]->getId();
        $state      = $this->_em->find(State::class, $stateId);

        self::assertInstanceOf(State::class, $state);
        self::assertInstanceOf(Country::class, $state->getCountry());
        self::assertNotNull($state->getCountry()->getName());
        self::assertCount(2, $state->getCities());

        $this->_em->clear();
        $this->secondLevelCacheLogger->clearStats();

        $stateId   = $this->states[0]->getId();
        $cacheId   = new CollectionCacheKey(State::class, 'cities', ['id'=>$stateId]);
        $region    = $this->_em->getCache()->getCollectionCacheRegion(State::class, 'cities');

        self::assertTrue($this->cache->containsCollection(State::class, 'cities', $stateId));

        /* @var $region \Doctrine\Tests\Mocks\ConcurrentRegionMock */
        $region->setLock($cacheId, Lock::createLockRead()); // another proc lock the entity cache

        self::assertFalse($this->cache->containsCollection(State::class, 'cities', $stateId));

        $queryCount = $this->getCurrentQueryCount();
        $state      = $this->_em->find(State::class, $stateId);

        self::assertEquals(0, $this->secondLevelCacheLogger->getMissCount());
        self::assertEquals(1, $this->secondLevelCacheLogger->getHitCount());

        self::assertEquals(0, $this->secondLevelCacheLogger->getRegionMissCount($this->getEntityRegion(State::class)));
        self::assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(State::class)));

        self::assertInstanceOf(State::class, $state);
        self::assertCount(2, $state->getCities());

        self::assertEquals(1, $this->secondLevelCacheLogger->getMissCount());
        self::assertEquals(1, $this->secondLevelCacheLogger->getHitCount());
        self::assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount($this->getCollectionRegion(State::class, 'cities')));

        self::assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        self::assertFalse($this->cache->containsCollection(State::class, 'cities', $stateId));
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
        $mock   = new ConcurrentRegionMock($region);

        return $mock;
    }

    public function getTimestampRegion()
    {
        return new TimestampRegionMock();
    }
}
