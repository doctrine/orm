<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache;
use Doctrine\ORM\Cache\CollectionCacheEntry;
use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Cache\DefaultCache;
use Doctrine\ORM\Cache\EntityCacheEntry;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\QueryCache;
use Doctrine\ORM\Cache\Region;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\Group;
use ReflectionMethod;
use ReflectionProperty;

use function array_merge;

#[Group('DDC-2183')]
class DefaultCacheTest extends OrmTestCase
{
    private DefaultCache $cache;
    private EntityManagerMock $em;

    protected function setUp(): void
    {
        parent::enableSecondLevelCache();

        parent::setUp();

        $this->em    = $this->getTestEntityManager();
        $this->cache = new DefaultCache($this->em);
    }

    private function putEntityCacheEntry(string $className, array $identifier, array $data): void
    {
        $metadata   = $this->em->getClassMetadata($className);
        $cacheKey   = new EntityCacheKey($metadata->name, $identifier);
        $cacheEntry = new EntityCacheEntry($metadata->name, $data);
        $persister  = $this->em->getUnitOfWork()->getEntityPersister($metadata->rootEntityName);

        $persister->getCacheRegion()->put($cacheKey, $cacheEntry);
    }

    private function putCollectionCacheEntry(string $className, string $association, array $ownerIdentifier, array $data): void
    {
        $metadata   = $this->em->getClassMetadata($className);
        $cacheKey   = new CollectionCacheKey($metadata->name, $association, $ownerIdentifier);
        $cacheEntry = new CollectionCacheEntry($data);
        $persister  = $this->em->getUnitOfWork()->getCollectionPersister($metadata->getAssociationMapping($association));

        $persister->getCacheRegion()->put($cacheKey, $cacheEntry);
    }

    public function testImplementsCache(): void
    {
        self::assertInstanceOf(Cache::class, $this->cache);
    }

    public function testGetEntityCacheRegionAccess(): void
    {
        self::assertInstanceOf(Region::class, $this->cache->getEntityCacheRegion(State::class));
        self::assertNull($this->cache->getEntityCacheRegion(CmsUser::class));
    }

    public function testGetCollectionCacheRegionAccess(): void
    {
        self::assertInstanceOf(Region::class, $this->cache->getCollectionCacheRegion(State::class, 'cities'));
        self::assertNull($this->cache->getCollectionCacheRegion(CmsUser::class, 'phonenumbers'));
    }

    public function testContainsEntity(): void
    {
        $identifier = ['id' => 1];
        $className  = Country::class;
        $cacheEntry = array_merge($identifier, ['name' => 'Brazil']);

        self::assertFalse($this->cache->containsEntity(Country::class, 1));

        $this->putEntityCacheEntry($className, $identifier, $cacheEntry);

        self::assertTrue($this->cache->containsEntity(Country::class, 1));
        self::assertFalse($this->cache->containsEntity(CmsUser::class, 1));
    }

    public function testEvictEntity(): void
    {
        $identifier = ['id' => 1];
        $className  = Country::class;
        $cacheEntry = array_merge($identifier, ['name' => 'Brazil']);

        $this->putEntityCacheEntry($className, $identifier, $cacheEntry);

        self::assertTrue($this->cache->containsEntity(Country::class, 1));

        $this->cache->evictEntity(Country::class, 1);
        $this->cache->evictEntity(CmsUser::class, 1);

        self::assertFalse($this->cache->containsEntity(Country::class, 1));
    }

    public function testEvictEntityRegion(): void
    {
        $identifier = ['id' => 1];
        $className  = Country::class;
        $cacheEntry = array_merge($identifier, ['name' => 'Brazil']);

        $this->putEntityCacheEntry($className, $identifier, $cacheEntry);

        self::assertTrue($this->cache->containsEntity(Country::class, 1));

        $this->cache->evictEntityRegion(Country::class);
        $this->cache->evictEntityRegion(CmsUser::class);

        self::assertFalse($this->cache->containsEntity(Country::class, 1));
    }

    public function testEvictEntityRegions(): void
    {
        $identifier = ['id' => 1];
        $className  = Country::class;
        $cacheEntry = array_merge($identifier, ['name' => 'Brazil']);

        $this->putEntityCacheEntry($className, $identifier, $cacheEntry);

        self::assertTrue($this->cache->containsEntity(Country::class, 1));

        $this->cache->evictEntityRegions();

        self::assertFalse($this->cache->containsEntity(Country::class, 1));
    }

    public function testContainsCollection(): void
    {
        $ownerId     = ['id' => 1];
        $className   = State::class;
        $association = 'cities';
        $cacheEntry  = [
            ['id' => 11],
            ['id' => 12],
        ];

        self::assertFalse($this->cache->containsCollection(State::class, $association, 1));

        $this->putCollectionCacheEntry($className, $association, $ownerId, $cacheEntry);

        self::assertTrue($this->cache->containsCollection(State::class, $association, 1));
        self::assertFalse($this->cache->containsCollection(CmsUser::class, 'phonenumbers', 1));
    }

    public function testEvictCollection(): void
    {
        $ownerId     = ['id' => 1];
        $className   = State::class;
        $association = 'cities';
        $cacheEntry  = [
            ['id' => 11],
            ['id' => 12],
        ];

        $this->putCollectionCacheEntry($className, $association, $ownerId, $cacheEntry);

        self::assertTrue($this->cache->containsCollection(State::class, $association, 1));

        $this->cache->evictCollection($className, $association, $ownerId);
        $this->cache->evictCollection(CmsUser::class, 'phonenumbers', 1);

        self::assertFalse($this->cache->containsCollection(State::class, $association, 1));
    }

    public function testEvictCollectionRegion(): void
    {
        $ownerId     = ['id' => 1];
        $className   = State::class;
        $association = 'cities';
        $cacheEntry  = [
            ['id' => 11],
            ['id' => 12],
        ];

        $this->putCollectionCacheEntry($className, $association, $ownerId, $cacheEntry);

        self::assertTrue($this->cache->containsCollection(State::class, $association, 1));

        $this->cache->evictCollectionRegion($className, $association);
        $this->cache->evictCollectionRegion(CmsUser::class, 'phonenumbers');

        self::assertFalse($this->cache->containsCollection(State::class, $association, 1));
    }

    public function testEvictCollectionRegions(): void
    {
        $ownerId     = ['id' => 1];
        $className   = State::class;
        $association = 'cities';
        $cacheEntry  = [
            ['id' => 11],
            ['id' => 12],
        ];

        $this->putCollectionCacheEntry($className, $association, $ownerId, $cacheEntry);

        self::assertTrue($this->cache->containsCollection(State::class, $association, 1));

        $this->cache->evictCollectionRegions();

        self::assertFalse($this->cache->containsCollection(State::class, $association, 1));
    }

    public function testQueryCache(): void
    {
        self::assertFalse($this->cache->containsQuery('foo'));

        $defaultQueryCache = $this->cache->getQueryCache();
        $fooQueryCache     = $this->cache->getQueryCache('foo');

        self::assertInstanceOf(QueryCache::class, $defaultQueryCache);
        self::assertInstanceOf(QueryCache::class, $fooQueryCache);
        self::assertSame($defaultQueryCache, $this->cache->getQueryCache());
        self::assertSame($fooQueryCache, $this->cache->getQueryCache('foo'));

        $this->cache->evictQueryRegion();
        $this->cache->evictQueryRegion('foo');
        $this->cache->evictQueryRegions();

        self::assertTrue($this->cache->containsQuery('foo'));

        self::assertSame($defaultQueryCache, $this->cache->getQueryCache());
        self::assertSame($fooQueryCache, $this->cache->getQueryCache('foo'));
    }

    public function testToIdentifierArrayShouldLookupForEntityIdentifier(): void
    {
        $identifier = 123;
        $entity     = new Country('Foo');
        $metadata   = $this->em->getClassMetadata(Country::class);
        $method     = new ReflectionMethod($this->cache, 'toIdentifierArray');
        $property   = new ReflectionProperty($entity, 'id');

        $property->setValue($entity, $identifier);

        self::assertEquals(['id' => $identifier], $method->invoke($this->cache, $metadata, $identifier));
    }
}
