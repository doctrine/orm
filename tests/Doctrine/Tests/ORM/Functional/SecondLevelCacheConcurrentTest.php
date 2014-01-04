<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\Mocks\ConcurrentRegionMock;
use Doctrine\ORM\Cache\Region\DefaultRegion;
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

        $this->countryMetadata = $this->_em->getClassMetadata(Country::CLASSNAME);
        $countryMetadata       = clone $this->countryMetadata;

        $countryMetadata->cache['usage'] = ClassMetadata::CACHE_USAGE_NONSTRICT_READ_WRITE;

        $this->_em->getMetadataFactory()->setMetadataFor(Country::CLASSNAME, $countryMetadata);
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->_em->getMetadataFactory()->setMetadataFor(Country::CLASSNAME, $this->countryMetadata);
    }

    public function testBasicConcurrentEntityReadLock()
    {
        $this->loadFixturesCountries();
        $this->_em->clear();

        $countryId = $this->countries[0]->getId();
        $cacheId   = new EntityCacheKey(Country::CLASSNAME, array('id'=>$countryId));
        $region    = $this->_em->getCache()->getEntityCacheRegion(Country::CLASSNAME);

        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $countryId));

        /** @var \Doctrine\Tests\Mocks\ConcurrentRegionMock */
        $region->setLock($cacheId, Lock::createLockRead()); // another proc lock the entity cache

        $this->assertFalse($this->cache->containsEntity(Country::CLASSNAME, $countryId));

        $queryCount = $this->getCurrentQueryCount();
        $country    = $this->_em->find(Country::CLASSNAME, $countryId);

        $this->assertInstanceOf(Country::CLASSNAME, $country);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertFalse($this->cache->containsEntity(Country::CLASSNAME, $countryId));
    }

    public function testBasicConcurrentCollectionReadLock()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();

        $this->_em->clear();
        $this->evictRegions();

        $stateId    = $this->states[0]->getId();
        $state      = $this->_em->find(State::CLASSNAME, $stateId);

        $this->assertInstanceOf(State::CLASSNAME, $state);
        $this->assertInstanceOf(Country::CLASSNAME, $state->getCountry());
        $this->assertNotNull($state->getCountry()->getName());
        $this->assertCount(2, $state->getCities());

        $this->_em->clear();
        $this->secondLevelCacheLogger->clearStats();

        $stateId   = $this->states[0]->getId();
        $cacheId   = new CollectionCacheKey(State::CLASSNAME, 'cities', array('id'=>$stateId));
        $region    = $this->_em->getCache()->getCollectionCacheRegion(State::CLASSNAME, 'cities');

        $this->assertTrue($this->cache->containsCollection(State::CLASSNAME, 'cities', $stateId));

        /* @var $region \Doctrine\Tests\Mocks\ConcurrentRegionMock */
        $region->setLock($cacheId, Lock::createLockRead()); // another proc lock the entity cache

        $this->assertFalse($this->cache->containsCollection(State::CLASSNAME, 'cities', $stateId));

        $queryCount = $this->getCurrentQueryCount();
        $state      = $this->_em->find(State::CLASSNAME, $stateId);

        $this->assertEquals(0, $this->secondLevelCacheLogger->getMissCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getHitCount());

        $this->assertEquals(0, $this->secondLevelCacheLogger->getRegionMissCount($this->getEntityRegion(State::CLASSNAME)));
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionHitCount($this->getEntityRegion(State::CLASSNAME)));

        $this->assertInstanceOf(State::CLASSNAME, $state);
        $this->assertCount(2, $state->getCities());

        $this->assertEquals(1, $this->secondLevelCacheLogger->getMissCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getHitCount());
        $this->assertEquals(1, $this->secondLevelCacheLogger->getRegionMissCount($this->getCollectionRegion(State::CLASSNAME, 'cities')));

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertFalse($this->cache->containsCollection(State::CLASSNAME, 'cities', $stateId));
    }
}

class CacheFactorySecondLevelCacheConcurrentTest extends \Doctrine\ORM\Cache\DefaultCacheFactory
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
        return new \Doctrine\Tests\Mocks\TimestampRegionMock();
    }
}