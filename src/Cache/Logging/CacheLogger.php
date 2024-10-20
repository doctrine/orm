<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Logging;

use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\QueryCacheKey;

/**
 * Interface for logging.
 */
interface CacheLogger
{
    /**
     * Log an entity put into second level cache.
     */
    public function entityCachePut(string $regionName, EntityCacheKey $key): void;

    /**
     * Log an entity get from second level cache resulted in a hit.
     */
    public function entityCacheHit(string $regionName, EntityCacheKey $key): void;

    /**
     * Log an entity get from second level cache resulted in a miss.
     */
    public function entityCacheMiss(string $regionName, EntityCacheKey $key): void;

    /**
     * Log an entity put into second level cache.
     */
    public function collectionCachePut(string $regionName, CollectionCacheKey $key): void;

    /**
     * Log an entity get from second level cache resulted in a hit.
     */
    public function collectionCacheHit(string $regionName, CollectionCacheKey $key): void;

    /**
     * Log an entity get from second level cache resulted in a miss.
     */
    public function collectionCacheMiss(string $regionName, CollectionCacheKey $key): void;

    /**
     * Log a query put into the query cache.
     */
    public function queryCachePut(string $regionName, QueryCacheKey $key): void;

    /**
     * Log a query get from the query cache resulted in a hit.
     */
    public function queryCacheHit(string $regionName, QueryCacheKey $key): void;

    /**
     * Log a query get from the query cache resulted in a miss.
     */
    public function queryCacheMiss(string $regionName, QueryCacheKey $key): void;
}
