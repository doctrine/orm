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

    const NON_CACHEABLE_ENTITY = 'Doctrine\Tests\Models\CMS\CmsUser';

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

        $persister->getCacheRegion()->put($cacheKey, $cacheEntry);
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

        $persister->getCacheRegion()->put($cacheKey, $cacheEntry);
    }

    public function testImplementsCache()
    {
        $this->assertInstanceOf('Doctrine\ORM\Cache', $this->cache);
    }
    
    public function testGetEntityCacheRegionAccess()
    {
        $this->assertInstanceOf('Doctrine\ORM\Cache\Region', $this->cache->getEntityCacheRegion(State::CLASSNAME));
        $this->assertNull($this->cache->getEntityCacheRegion(self::NON_CACHEABLE_ENTITY));
    }

    public function testGetCollectionCacheRegionAccess()
    {
        $this->assertInstanceOf('Doctrine\ORM\Cache\Region', $this->cache->getCollectionCacheRegion(State::CLASSNAME, 'cities'));
        $this->assertNull($this->cache->getCollectionCacheRegion(self::NON_CACHEABLE_ENTITY, 'phonenumbers'));
    }

    public function testContainsEntity()
    {
        $identifier = array('id'=>1);
        $className  = Country::CLASSNAME;
        $cacheEntry = array_merge($identifier, array('name' => 'Brazil'));

        $this->assertFalse($this->cache->containsEntity(Country::CLASSNAME, 1));

        $this->putEntityCacheEntry($className, $identifier, $cacheEntry);

        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, 1));
        $this->assertFalse($this->cache->containsEntity(self::NON_CACHEABLE_ENTITY, 1));
    }

    public function testEvictEntity()
    {
        $identifier = array('id'=>1);
        $className  = Country::CLASSNAME;
        $cacheEntry = array_merge($identifier, array('name' => 'Brazil'));

        $this->putEntityCacheEntry($className, $identifier, $cacheEntry);

        $this->assertTrue($this->cache->containsEntity(Country::CLASSNAME, 1));

        $this->cache->evictEntity(Country::CLASSNAME, 1);
        $this->cache->evictEntity(self::NON_CACHEABLE_ENTITY, 1);

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
        $this->cache->evictEntityRegion(self::NON_CACHEABLE_ENTITY);

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
        $this->assertFalse($this->cache->containsCollection(self::NON_CACHEABLE_ENTITY, 'phonenumbers', 1));
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
        $this->cache->evictCollection(self::NON_CACHEABLE_ENTITY, 'phonenumbers', 1);

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
        $this->cache->evictCollectionRegion(self::NON_CACHEABLE_ENTITY, 'phonenumbers');

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

    public function testQueryCache()
    {
        $this->assertFalse($this->cache->containsQuery('foo'));

        $defaultQueryCache = $this->cache->getQueryCache();
        $fooQueryCache     = $this->cache->getQueryCache('foo');

        $this->assertInstanceOf('Doctrine\ORM\Cache\QueryCache', $defaultQueryCache);
        $this->assertInstanceOf('Doctrine\ORM\Cache\QueryCache', $fooQueryCache);
        $this->assertSame($defaultQueryCache, $this->cache->getQueryCache());
        $this->assertSame($fooQueryCache, $this->cache->getQueryCache('foo'));

        $this->cache->evictQueryRegion();
        $this->cache->evictQueryRegion('foo');
        $this->cache->evictQueryRegions();

        $this->assertTrue($this->cache->containsQuery('foo'));

        $this->assertSame($defaultQueryCache, $this->cache->getQueryCache());
        $this->assertSame($fooQueryCache, $this->cache->getQueryCache('foo'));
    }

    public function testToIdentifierArrayShouldLookupForEntityIdentifier()
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