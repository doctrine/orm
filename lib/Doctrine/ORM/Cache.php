<?php

declare(strict_types=1);

namespace Doctrine\ORM;

use Doctrine\ORM\Cache\QueryCache;
use Doctrine\ORM\Cache\Region;

/**
 * Provides an API for querying/managing the second level cache regions.
 */
interface Cache
{
    public const DEFAULT_QUERY_REGION_NAME = 'query_cache_region';

    public const DEFAULT_TIMESTAMP_REGION_NAME = 'timestamp_cache_region';

    /**
     * May read items from the cache, but will not add items.
     */
    public const MODE_GET = 1;

    /**
     * Will never read items from the cache,
     * but will add items to the cache as it reads them from the database.
     */
    public const MODE_PUT = 2;

    /**
     * May read items from the cache, and add items to the cache.
     */
    public const MODE_NORMAL = 3;

    /**
     * The query will never read items from the cache,
     * but will refresh items to the cache as it reads them from the database.
     */
    public const MODE_REFRESH = 4;

    /**
     * @psalm-param class-string $className
     */
    public function getEntityCacheRegion(string $className): ?Region;

    /**
     * @psalm-param class-string $className
     */
    public function getCollectionCacheRegion(string $className, string $association): ?Region;

    /**
     * Determine whether the cache contains data for the given entity "instance".
     *
     * @psalm-param class-string $className
     */
    public function containsEntity(string $className, mixed $identifier): bool;

    /**
     * Evicts the entity data for a particular entity "instance".
     *
     * @psalm-param class-string $className
     */
    public function evictEntity(string $className, mixed $identifier): void;

    /**
     * Evicts all entity data from the given region.
     *
     * @psalm-param class-string $className
     */
    public function evictEntityRegion(string $className): void;

    /**
     * Evict data from all entity regions.
     */
    public function evictEntityRegions(): void;

    /**
     * Determine whether the cache contains data for the given collection.
     *
     * @psalm-param class-string $className
     */
    public function containsCollection(string $className, string $association, mixed $ownerIdentifier): bool;

    /**
     * Evicts the cache data for the given identified collection instance.
     *
     * @psalm-param class-string $className
     */
    public function evictCollection(string $className, string $association, mixed $ownerIdentifier): void;

    /**
     * Evicts all entity data from the given region.
     *
     * @psalm-param class-string $className
     */
    public function evictCollectionRegion(string $className, string $association): void;

    /**
     * Evict data from all collection regions.
     */
    public function evictCollectionRegions(): void;

    /**
     * Determine whether the cache contains data for the given query.
     */
    public function containsQuery(string $regionName): bool;

    /**
     * Evicts all cached query results under the given name, or default query cache if the region name is NULL.
     */
    public function evictQueryRegion(?string $regionName = null): void;

    /**
     * Evict data from all query regions.
     */
    public function evictQueryRegions(): void;

    /**
     * Get query cache by region name or create a new one if none exist.
     *
     * @param string|null $regionName Query cache region name, or default query cache if the region name is NULL.
     */
    public function getQueryCache(?string $regionName = null): QueryCache;
}
