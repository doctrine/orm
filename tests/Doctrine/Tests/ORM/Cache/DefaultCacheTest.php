<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\Tests\OrmTestCase;
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
class DefaultCacheTest extends OrmTestCase
{
    /**
     * @var \Doctrine\ORM\Cache
     */
    private $cache;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    protected function setUp()
    {
        parent::enableSecondLevelCache();
        parent::setUp();

        $this->em    = $this->_getTestEntityManager();
        $this->cache = new DefaultCache($this->em);
    }

    /**
     * @param string $className
     * @param array $identifier
     * @param array $data
     */
    private function putEntityCacheEntry($className, array $identifier, array $data)
    {
        $metadata   = $this->em->getClassMetadata($className);
        $cacheKey   = new EntityCacheKey($metadata->name, $identifier);
        $cacheEntry = new EntityCacheEntry($metadata->name, $data);
        $persister  = $this->em->getUnitOfWork()->getEntityPersister($metadata->rootEntityName);

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
        $metadata   = $this->em->getClassMetadata($className);
        $cacheKey   = new CollectionCacheKey($metadata->name, $association, $ownerIdentifier);
        $cacheEntry = new CollectionCacheEntry($data);
        $persister  = $this->em->getUnitOfWork()->getCollectionPersister($metadata->getAssociationMapping($association));

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

    public function testToIdentifierArrayShoudLookupForEntityIdentifier()
    {
        $identifier = 123;
        $entity     = new Country('Foo');
        $metadata   = $this->em->getClassMetadata(Country::CLASSNAME);
        $method     = new \ReflectionMethod($this->cache, 'toIdentifierArray');
        $property   = new \ReflectionProperty($entity, 'id');

        $property->setAccessible(true);
        $method->setAccessible(true);
        $property->setValue($entity, $identifier);

        $this->assertEquals(array('id'=>$identifier), $method->invoke($this->cache, $metadata, $identifier));
    }

}