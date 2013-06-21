<?php

namespace Doctrine\Tests\ORM\Functional;


use Doctrine\ORM\Cache\Access\ConcurrentRegionAccessStrategy;
use Doctrine\ORM\Cache\DefaultCollectionEntryStructure;
use Doctrine\ORM\Cache\DefaultEntityEntryStructure;
use Doctrine\Tests\Mocks\ConcurrentRegionMock;
use Doctrine\ORM\Cache\Region\DefaultRegion;
use Doctrine\ORM\Cache\DefaultQueryCache;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\CacheFactory;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Cache\Lock;


/**
 * @group DDC-2183
 */
class SecondLevelCacheConcurrentTest extends SecondLevelCacheAbstractTest
{
    /**
     * @var \Doctrine\ORM\Cache\Access\ConcurrentRegionAccessStrategy\CacheFactorySecondLevelCacheConcurrentTest
     */
    private $cacheFactory;

    protected function setUp()
    {
        $this->enableSecondLevelCache();
        parent::setUp();

        $this->cacheFactory = new CacheFactorySecondLevelCacheConcurrentTest(self::getSharedSecondLevelCacheDriverImpl());

        $this->_em->getConfiguration()->setSecondLevelCacheFactory($this->cacheFactory);
    }

    public function testBasicConcurrentReadLock()
    {
        $this->loadFixturesCountries();
        $this->_em->clear();

        $countryId = $this->countries[0]->getId();
        $cacheId   = new EntityCacheKey(Country::CLASSNAME, array('id'=>$countryId));
        $region    = $this->_em->getCache()->getEntityCacheRegionAccess(Country::CLASSNAME)->getRegion();

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

    public function testBasicConcurrentWriteLock()
    {
        $this->loadFixturesCountries();
        $this->_em->clear();

        $lock      = Lock::createLockWrite();
        $countryId = $this->countries[0]->getId();
        $cacheKey  = new EntityCacheKey(Country::CLASSNAME, array('id'=>$countryId));
        $region    = $this->cache->getEntityCacheRegionAccess(Country::CLASSNAME)->getRegion();

        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $countryId));

        /** @var \Doctrine\Tests\Mocks\ConcurrentRegionMock */
        $region->setLock($cacheKey, $lock); // another proc lock the entity cache

        $queryCount = $this->getCurrentQueryCount();
        $country    = $this->_em->find(Country::CLASSNAME, $countryId); // Cache locked, goes straight to the database

        $this->assertInstanceOf(Country::CLASSNAME, $country);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertFalse($this->cache->containsEntity(Country::CLASSNAME, $countryId));

        $country->setName('Foo 1');
        $this->_em->persist($country);
        $this->_em->flush();
        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();
        $country    = $this->_em->find(Country::CLASSNAME, $countryId); // Cache locked, goes straight to the database

        $this->assertInstanceOf(Country::CLASSNAME, $country);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertEquals('Foo 1', $country->getName());
        $this->assertFalse($this->cache->containsEntity(Country::CLASSNAME, $countryId));

        /** @var \Doctrine\Tests\Mocks\ConcurrentRegionMock */
        $region->writeUnlock($cacheKey, $lock); // another proc unlock
        $region->evict($cacheKey); // and clear the cache.

        $this->assertFalse($this->cache->containsEntity(Country::CLASSNAME, $countryId));

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();
        $country    = $this->_em->find(Country::CLASSNAME, $countryId); // No cache

        $this->assertInstanceOf(Country::CLASSNAME, $country);
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
        $this->assertEquals('Foo 1', $country->getName());
        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, $countryId));
    }
}

class CacheFactorySecondLevelCacheConcurrentTest implements CacheFactory
{
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function buildEntityRegionAccessStrategy(ClassMetadata $metadata)
    {
        $regionName = $metadata->cache['region'];
        $region     = $this->createRegion($regionName);
        $access     = new ConcurrentRegionAccessStrategy($region);

        return $access;
    }

    public function buildCollectionRegionAccessStrategy(ClassMetadata $metadata, $fieldName)
    {
        $mapping    = $metadata->getAssociationMapping($fieldName);
        $regionName = $mapping['cache']['region'];
        $region     = $this->createRegion($regionName);
        $access     = new ConcurrentRegionAccessStrategy($region);

        return $access;
    }

    public function buildQueryCache(EntityManagerInterface $em, $regionName = null)
    {
        return new DefaultQueryCache($em, $this->createRegion($regionName ?: Cache::DEFAULT_QUERY_REGION_NAME));
    }

    public function buildCollectionEntryStructure(EntityManagerInterface $em)
    {
        return new DefaultCollectionEntryStructure($em);
    }

    public function buildEntityEntryStructure(EntityManagerInterface $em)
    {
        return new DefaultEntityEntryStructure($em);
    }

    private function createRegion($regionName)
    {
        $region = new DefaultRegion($regionName, $this->cache);
        $mock   = new ConcurrentRegionMock($region);

        return $mock;
    }
}