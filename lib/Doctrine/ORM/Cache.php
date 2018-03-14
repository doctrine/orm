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
     * @param string $className The entity class.
     */
    public function getEntityCacheRegion(string $className) : ?Region;

    /**
     * @param string $className   The entity class.
     * @param string $association The field name that represents the association.
     */
    public function getCollectionCacheRegion(string $className, string $association) : ?Region;

    /**
     * Determine whether the cache contains data for the given entity "instance".
     *
     * @param string $className  The entity class.
     * @param mixed  $identifier The entity identifier
     *
     * @return bool true if the underlying cache contains corresponding data; false otherwise.
     */
    public function containsEntity(string $className, $identifier) : bool;

    /**
     * Evicts the entity data for a particular entity "instance".
     *
     * @param string $className  The entity class.
     * @param mixed  $identifier The entity identifier.
     */
    public function evictEntity(string $className, $identifier) : void;

    /**
     * Evicts all entity data from the given region.
     *
     * @param string $className The entity metadata.
     */
    public function evictEntityRegion(string $className) : void;

    /**
     * Evict data from all entity regions.
     */
    public function evictEntityRegions() : void;

    /**
     * Determine whether the cache contains data for the given collection.
     *
     * @param string $className       The entity class.
     * @param string $association     The field name that represents the association.
     * @param mixed  $ownerIdentifier The identifier of the owning entity.
     *
     * @return bool true if the underlying cache contains corresponding data; false otherwise.
     */
    public function containsCollection(string $className, string $association, $ownerIdentifier) : bool;

    /**
     * Evicts the cache data for the given identified collection instance.
     *
     * @param string $className       The entity class.
     * @param string $association     The field name that represents the association.
     * @param mixed  $ownerIdentifier The identifier of the owning entity.
     */
    public function evictCollection(string $className, string $association, $ownerIdentifier) : void;

    /**
     * Evicts all entity data from the given region.
     *
     * @param string $className   The entity class.
     * @param string $association The field name that represents the association.
     */
    public function evictCollectionRegion(string $className, string $association) : void;

    /**
     * Evict data from all collection regions.
     */
    public function evictCollectionRegions() : void;

    /**
     * Determine whether the cache contains data for the given query.
     *
     * @param string $regionName The cache name given to the query.
     *
     * @return bool true if the underlying cache contains corresponding data; false otherwise.
     */
    public function containsQuery(string $regionName) : bool;

    /**
     * Evicts all cached query results under the given name, or default query cache if the region name is NULL.
     *
     * @param string|null $regionName The cache name associated to the queries being cached.
     */
    public function evictQueryRegion(?string $regionName = null) : void;

    /**
     * Evict data from all query regions.
     */
    public function evictQueryRegions() : void;

    /**
     * Get query cache by region name or create a new one if none exist.
     *
     * @param string|null $regionName Query cache region name, or default query cache if the region name is NULL.
     *
     * @return QueryCache The Query Cache associated with the region name.
     */
    public function getQueryCache(?string $regionName = null) : QueryCache;
}
