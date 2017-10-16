<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache;
use Doctrine\Tests\Models\CMS\CmsUser;
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
        $this->assertInstanceOf(Cache::class, $this->cache);
    }

    public function testGetEntityCacheRegionAccess()
    {
        $this->assertInstanceOf(Cache\Region::class, $this->cache->getEntityCacheRegion(State::class));
        $this->assertNull($this->cache->getEntityCacheRegion(CmsUser::class));
    }

    public function testGetCollectionCacheRegionAccess()
    {
        $this->assertInstanceOf(Cache\Region::class, $this->cache->getCollectionCacheRegion(State::class, 'cities'));
        $this->assertNull($this->cache->getCollectionCacheRegion(CmsUser::class, 'phonenumbers'));
    }

    public function testContainsEntity()
    {
        $identifier = ['id'=>1];
        $className  = Country::class;
        $cacheEntry = array_merge($identifier, ['name' => 'Brazil']);

        $this->assertFalse($this->cache->containsEntity(Country::class, 1));

        $this->putEntityCacheEntry($className, $identifier, $cacheEntry);

        $this->assertTrue($this->cache->containsEntity(Country::class, 1));
        $this->assertFalse($this->cache->containsEntity(CmsUser::class, 1));
    }

    public function testEvictEntity()
    {
        $identifier = ['id'=>1];
        $className  = Country::class;
        $cacheEntry = array_merge($identifier, ['name' => 'Brazil']);

        $this->putEntityCacheEntry($className, $identifier, $cacheEntry);

        $this->assertTrue($this->cache->containsEntity(Country::class, 1));

        $this->cache->evictEntity(Country::class, 1);
        $this->cache->evictEntity(CmsUser::class, 1);

        $this->assertFalse($this->cache->containsEntity(Country::class, 1));
    }

    public function testEvictEntityRegion()
    {
        $identifier = ['id'=>1];
        $className  = Country::class;
        $cacheEntry = array_merge($identifier, ['name' => 'Brazil']);

        $this->putEntityCacheEntry($className, $identifier, $cacheEntry);

        $this->assertTrue($this->cache->containsEntity(Country::class, 1));

        $this->cache->evictEntityRegion(Country::class);
        $this->cache->evictEntityRegion(CmsUser::class);

        $this->assertFalse($this->cache->containsEntity(Country::class, 1));
    }

    public function testEvictEntityRegions()
    {
        $identifier = ['id'=>1];
        $className  = Country::class;
        $cacheEntry = array_merge($identifier, ['name' => 'Brazil']);

        $this->putEntityCacheEntry($className, $identifier, $cacheEntry);

        $this->assertTrue($this->cache->containsEntity(Country::class, 1));

        $this->cache->evictEntityRegions();

        $this->assertFalse($this->cache->containsEntity(Country::class, 1));
    }

    public function testContainsCollection()
    {
        $ownerId        = ['id'=>1];
        $className      = State::class;
        $association    = 'cities';
        $cacheEntry     = [
            ['id' => 11],
            ['id' => 12],
        ];

        $this->assertFalse($this->cache->containsCollection(State::class, $association, 1));

        $this->putCollectionCacheEntry($className, $association, $ownerId, $cacheEntry);

        $this->assertTrue($this->cache->containsCollection(State::class, $association, 1));
        $this->assertFalse($this->cache->containsCollection(CmsUser::class, 'phonenumbers', 1));
    }

    public function testEvictCollection()
    {
        $ownerId        = ['id'=>1];
        $className      = State::class;
        $association    = 'cities';
        $cacheEntry     = [
            ['id' => 11],
            ['id' => 12],
        ];

        $this->putCollectionCacheEntry($className, $association, $ownerId, $cacheEntry);

        $this->assertTrue($this->cache->containsCollection(State::class, $association, 1));

        $this->cache->evictCollection($className, $association, $ownerId);
        $this->cache->evictCollection(CmsUser::class, 'phonenumbers', 1);

        $this->assertFalse($this->cache->containsCollection(State::class, $association, 1));
    }

    public function testEvictCollectionRegion()
    {
        $ownerId        = ['id'=>1];
        $className      = State::class;
        $association    = 'cities';
        $cacheEntry     = [
            ['id' => 11],
            ['id' => 12],
        ];

        $this->putCollectionCacheEntry($className, $association, $ownerId, $cacheEntry);

        $this->assertTrue($this->cache->containsCollection(State::class, $association, 1));

        $this->cache->evictCollectionRegion($className, $association);
        $this->cache->evictCollectionRegion(CmsUser::class, 'phonenumbers');

        $this->assertFalse($this->cache->containsCollection(State::class, $association, 1));
    }

    public function testEvictCollectionRegions()
    {
        $ownerId        = ['id'=>1];
        $className      = State::class;
        $association    = 'cities';
        $cacheEntry     = [
            ['id' => 11],
            ['id' => 12],
        ];

        $this->putCollectionCacheEntry($className, $association, $ownerId, $cacheEntry);

        $this->assertTrue($this->cache->containsCollection(State::class, $association, 1));

        $this->cache->evictCollectionRegions();

        $this->assertFalse($this->cache->containsCollection(State::class, $association, 1));
    }

    public function testQueryCache()
    {
        $this->assertFalse($this->cache->containsQuery('foo'));

        $defaultQueryCache = $this->cache->getQueryCache();
        $fooQueryCache     = $this->cache->getQueryCache('foo');

        $this->assertInstanceOf(Cache\QueryCache::class, $defaultQueryCache);
        $this->assertInstanceOf(Cache\QueryCache::class, $fooQueryCache);
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
        $metadata   = $this->em->getClassMetadata(Country::class);
        $method     = new \ReflectionMethod($this->cache, 'toIdentifierArray');
        $property   = new \ReflectionProperty($entity, 'id');

        $property->setAccessible(true);
        $method->setAccessible(true);
        $property->setValue($entity, $identifier);

        $this->assertEquals(['id'=>$identifier], $method->invoke($this->cache, $metadata, $identifier));
    }

}
