<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\ORM\Cache\DefaultCache;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\CollectionCacheKey;

/**
 * @group DDC-2183
 */
class DefaultCacheTest extends OrmFunctionalTestCase
{
    /**
     * @var \Doctrine\ORM\Cache
     */
    private $cache;

    protected function setUp()
    {
        parent::enableSecondLevelCache();
        parent::setUp();

        $this->cache = new DefaultCache($this->_em);
    }

    private function putEntityCache($className, array $identifier, array $cacheEntry)
    {
        $metadata   = $this->_em->getClassMetadata($className);
        $cacheKey   = new EntityCacheKey($metadata->rootEntityName, $identifier);

        $this->cache->getEntityCacheRegionAccess($metadata->rootEntityName)
            ->put($cacheKey, $cacheEntry);
    }

    private function putCollectionCache($className, $association, array $ownerIdentifier, array $cacheEntry)
    {
        $metadata   = $this->_em->getClassMetadata($className);
        $cacheKey   = new CollectionCacheKey($metadata->rootEntityName, $association, $ownerIdentifier);

        $this->cache->getCollectionCacheRegionAccess($className, $association)
            ->put($cacheKey, $cacheEntry);
    }

    public function testImplementsCache()
    {
        $this->assertInstanceOf('Doctrine\ORM\Cache', $this->cache);
    }
    
    public function testGetEntityCacheRegionAccess()
    {
        $this->assertInstanceOf('Doctrine\ORM\Cache\RegionAccess', $this->cache->getEntityCacheRegionAccess(State::CLASSNAME));
        $this->assertNull($this->cache->getEntityCacheRegionAccess('Doctrine\Tests\Models\CMS\CmsUser'));
    }

    public function testGetCollectionCacheRegionAccess()
    {
        $this->assertInstanceOf('Doctrine\ORM\Cache\RegionAccess', $this->cache->getCollectionCacheRegionAccess(State::CLASSNAME, 'cities'));
        $this->assertNull($this->cache->getCollectionCacheRegionAccess('Doctrine\Tests\Models\CMS\CmsUser', 'phonenumbers'));
    }

    public function testContainsEntity()
    {
        $identifier = array('id'=>1);
        $className  = Country::CLASSNAME;
        $cacheEntry = array_merge($identifier, array('name' => 'Brazil'));

        $this->assertFalse($this->cache->containsEntity(Country::CLASSNAME, 1));

        $this->putEntityCache($className, $identifier, $cacheEntry);

        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, 1));
    }

    public function testEvictEntity()
    {
        $identifier = array('id'=>1);
        $className  = Country::CLASSNAME;
        $cacheEntry = array_merge($identifier, array('name' => 'Brazil'));

        $this->putEntityCache($className, $identifier, $cacheEntry);

        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, 1));

        $this->cache->evictEntity(Country::CLASSNAME, 1);

        $this->assertFalse($this->cache->containsEntity(Country::CLASSNAME, 1));
    }

    public function testEvictEntityRegion()
    {
        $identifier = array('id'=>1);
        $className  = Country::CLASSNAME;
        $cacheEntry = array_merge($identifier, array('name' => 'Brazil'));

        $this->putEntityCache($className, $identifier, $cacheEntry);

        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, 1));

        $this->cache->evictEntityRegion(Country::CLASSNAME);

        $this->assertFalse($this->cache->containsEntity(Country::CLASSNAME, 1));
    }

    public function testEvictEntityRegions()
    {
        $identifier = array('id'=>1);
        $className  = Country::CLASSNAME;
        $cacheEntry = array_merge($identifier, array('name' => 'Brazil'));

        $this->putEntityCache($className, $identifier, $cacheEntry);

        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, 1));

        $this->cache->evictEntityRegions();

        $this->assertFalse($this->cache->containsEntity(Country::CLASSNAME, 1));
    }

    public function testContainsCollection()
    {
        $ownerId        = array('id'=>1);
        $className      = State::CLASSNAME;
        $association    = 'cities';
        $cacheEntry     = array(
            array('id' => 11),
            array('id' => 12),
        );

        $this->assertFalse($this->cache->containsCollection(State::CLASSNAME, $association, 1));

        $this->putCollectionCache($className, $association, $ownerId, $cacheEntry);

        $this->assertTrue($this->cache->containsCollection(State::CLASSNAME, $association, 1));
    }

    public function testEvictCollection()
    {
        $ownerId        = array('id'=>1);
        $className      = State::CLASSNAME;
        $association    = 'cities';
        $cacheEntry     = array(
            array('id' => 11),
            array('id' => 12),
        );

        $this->putCollectionCache($className, $association, $ownerId, $cacheEntry);

        $this->assertTrue($this->cache->containsCollection(State::CLASSNAME, $association, 1));

        $this->cache->evictCollection($className, $association, $ownerId);

        $this->assertFalse($this->cache->containsCollection(State::CLASSNAME, $association, 1));
    }

    public function testEvictCollectionRegion()
    {
        $ownerId        = array('id'=>1);
        $className      = State::CLASSNAME;
        $association    = 'cities';
        $cacheEntry     = array(
            array('id' => 11),
            array('id' => 12),
        );

        $this->putCollectionCache($className, $association, $ownerId, $cacheEntry);

        $this->assertTrue($this->cache->containsCollection(State::CLASSNAME, $association, 1));

        $this->cache->evictCollectionRegion($className, $association);

        $this->assertFalse($this->cache->containsCollection(State::CLASSNAME, $association, 1));
    }

    public function testEvictCollectionRegions()
    {
        $ownerId        = array('id'=>1);
        $className      = State::CLASSNAME;
        $association    = 'cities';
        $cacheEntry     = array(
            array('id' => 11),
            array('id' => 12),
        );

        $this->putCollectionCache($className, $association, $ownerId, $cacheEntry);

        $this->assertTrue($this->cache->containsCollection(State::CLASSNAME, $association, 1));

        $this->cache->evictCollectionRegions();

        $this->assertFalse($this->cache->containsCollection(State::CLASSNAME, $association, 1));
    }

}