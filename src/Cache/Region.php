<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use Doctrine\ORM\Cache\Exception\CacheException;

/**
 * Defines a contract for accessing a particular named region.
 */
interface Region
{
    /**
     * Retrieve the name of this region.
     */
    public function getName(): string;

    /**
     * Determine whether this region contains data for the given key.
     *
     * @param CacheKey $key The cache key
     */
    public function contains(CacheKey $key): bool;

    /**
     * Get an item from the cache.
     *
     * @param CacheKey $key The key of the item to be retrieved.
     *
     * @return CacheEntry|null The cached entry or NULL
     *
     * @throws CacheException Indicates a problem accessing the item or region.
     */
    public function get(CacheKey $key): CacheEntry|null;

    /**
     * Get all items from the cache identified by $keys.
     * It returns NULL if some elements can not be found.
     *
     * @param CollectionCacheEntry $collection The collection of the items to be retrieved.
     *
     * @return CacheEntry[]|null The cached entries or NULL if one or more entries can not be found
     */
    public function getMultiple(CollectionCacheEntry $collection): array|null;

    /**
     * Put an item into the cache.
     *
     * @param CacheKey   $key   The key under which to cache the item.
     * @param CacheEntry $entry The entry to cache.
     * @param Lock|null  $lock  The lock previously obtained.
     *
     * @throws CacheException Indicates a problem accessing the region.
     */
    public function put(CacheKey $key, CacheEntry $entry, Lock|null $lock = null): bool;

    /**
     * Remove an item from the cache.
     *
     * @param CacheKey $key The key under which to cache the item.
     *
     * @throws CacheException Indicates a problem accessing the region.
     */
    public function evict(CacheKey $key): bool;

    /**
     * Remove all contents of this particular cache region.
     *
     * @throws CacheException Indicates problem accessing the region.
     */
    public function evictAll(): bool;
}
