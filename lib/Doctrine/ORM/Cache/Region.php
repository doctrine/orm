<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

/**
 * Defines a contract for accessing a particular named region.
 */
interface Region extends MultiGetRegion
{
    /**
     * Retrieve the name of this region.
     *
     * @return string The region name
     */
    public function getName();

    /**
     * Determine whether this region contains data for the given key.
     *
     * @param \Doctrine\ORM\Cache\CacheKey $key The cache key
     *
     * @return bool TRUE if the underlying cache contains corresponding data; FALSE otherwise.
     */
    public function contains(CacheKey $key);

    /**
     * Get an item from the cache.
     *
     * @param \Doctrine\ORM\Cache\CacheKey $key The key of the item to be retrieved.
     *
     * @return \Doctrine\ORM\Cache\CacheEntry|null The cached entry or NULL
     *
     * @throws \Doctrine\ORM\Cache\CacheException Indicates a problem accessing the item or region.
     */
    public function get(CacheKey $key);

    /**
     * Put an item into the cache.
     *
     * @param \Doctrine\ORM\Cache\CacheKey   $key   The key under which to cache the item.
     * @param \Doctrine\ORM\Cache\CacheEntry $entry The entry to cache.
     * @param \Doctrine\ORM\Cache\Lock       $lock  The lock previously obtained.
     *
     * @throws \Doctrine\ORM\Cache\CacheException Indicates a problem accessing the region.
     */
    public function put(CacheKey $key, CacheEntry $entry, ?Lock $lock = null);

    /**
     * Remove an item from the cache.
     *
     * @param \Doctrine\ORM\Cache\CacheKey $key The key under which to cache the item.
     *
     * @throws \Doctrine\ORM\Cache\CacheException Indicates a problem accessing the region.
     */
    public function evict(CacheKey $key);

    /**
     * Remove all contents of this particular cache region.
     *
     * @throws \Doctrine\ORM\Cache\CacheException Indicates problem accessing the region.
     */
    public function evictAll();
}
