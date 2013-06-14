<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\ORM\Cache\DefaultCache;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\EntityCacheEntry;
use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Cache\CollectionCacheEntry;

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

    /**
     * @param string $className
     * @param array $identifier
     * @param array $data
     */
    private function putEntityCacheEntry($className, array $identifier, array $data)
    {
        $metadata   = $this->_em->getClassMetadata($className);
        $cacheKey   = new EntityCacheKey($metadata->rootEntityName, $identifier);
        $cacheEntry = new EntityCacheEntry($data);
        $persister  = $this->_em->getUnitOfWork()->getEntityPersister($metadata->rootEntityName);

        $persister->getCacheRegionAcess()->put($cacheKey, $cacheEntry);
    }

    /**
     * @param string $className
     * @param string $association
     * @param array $ownerIdentifier
     * @param array $data
     */
    private function putCollectionCacheEntry($className, $association, array $ownerIdentifier, array $data)
    {
        $metadata   = $this->_em->getClassMetadata($className);
        $cacheKey   = new CollectionCacheKey($metadata->rootEntityName, $association, $ownerIdentifier);
        $cacheEntry = new CollectionCacheEntry($data);
        $persister  = $this->_em->getUnitOfWork()->getCollectionPersister($metadata->getAssociationMapping($association));

        $persister->getCacheRegionAcess()->put($cacheKey, $cacheEntry);
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

        $this->putEntityCacheEntry($className, $identifier, $cacheEntry);

        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, 1));
    }

    public function testEvictEntity()
    {
        $identifier = array('id'=>1);
        $className  = Country::CLASSNAME;
        $cacheEntry = array_merge($identifier, array('name' => 'Brazil'));

        $this->putEntityCacheEntry($className, $identifier, $cacheEntry);

        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, 1));

        $this->cache->evictEntity(Country::CLASSNAME, 1);

        $this->assertFalse($this->cache->containsEntity(Country::CLASSNAME, 1));
    }

    public function testEvictEntityRegion()
    {
        $identifier = array('id'=>1);
        $className  = Country::CLASSNAME;
        $cacheEntry = array_merge($identifier, array('name' => 'Brazil'));

        $this->putEntityCacheEntry($className, $identifier, $cacheEntry);

        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, 1));

        $this->cache->evictEntityRegion(Country::CLASSNAME);

        $this->assertFalse($this->cache->containsEntity(Country::CLASSNAME, 1));
    }

    public function testEvictEntityRegions()
    {
        $identifier = array('id'=>1);
        $className  = Country::CLASSNAME;
        $cacheEntry = array_merge($identifier, array('name' => 'Brazil'));

        $this->putEntityCacheEntry($className, $identifier, $cacheEntry);

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

        $this->putCollectionCacheEntry($className, $association, $ownerId, $cacheEntry);

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

        $this->putCollectionCacheEntry($className, $association, $ownerId, $cacheEntry);

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

        $this->putCollectionCacheEntry($className, $association, $ownerId, $cacheEntry);

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

        $this->putCollectionCacheEntry($className, $association, $ownerId, $cacheEntry);

        $this->assertTrue($this->cache->containsCollection(State::CLASSNAME, $association, 1));

        $this->cache->evictCollectionRegions();

        $this->assertFalse($this->cache->containsCollection(State::CLASSNAME, $association, 1));
    }

}