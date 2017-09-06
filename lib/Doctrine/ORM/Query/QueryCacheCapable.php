<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

interface QueryCacheCapable
{
    /**
     * @var string
     */
    const HINT_CACHE_ENABLED = 'doctrine.cache.enabled';

    /**
     * @var string
     */
    const HINT_CACHE_EVICT = 'doctrine.cache.evict';

    /**
     * Defines a cache driver to be used for caching queries.
     *
     * @param \Doctrine\Common\Cache\Cache|null $queryCache Cache driver.
     *
     * @return static This query instance.
     */
    public function setQueryCacheDriver($queryCache);

    /**
     * Defines whether the query should make use of a query cache, if available.
     *
     * @param boolean $bool
     *
     * @return static This query instance.
     */
    public function useQueryCache($bool);

    /**
     * Returns the cache driver used for query caching.
     *
     * @return \Doctrine\Common\Cache\Cache|null The cache driver used for query caching or NULL, if
     *                                           this Query does not use query caching.
     */
    public function getQueryCacheDriver();

    /**
     * Defines how long the query cache will be active before expire.
     *
     * @param integer $timeToLive How long the cache entry is valid.
     *
     * @return static This query instance.
     */
    public function setQueryCacheLifetime($timeToLive);

    /**
     * Retrieves the lifetime of resultset cache.
     *
     * @return int
     */
    public function getQueryCacheLifetime();

    /**
     * Defines if the query cache is active or not.
     *
     * @param boolean $expire Whether or not to force query cache expiration.
     *
     * @return static This query instance.
     */
    public function expireQueryCache($expire = true);

    /**
     * Retrieves if the query cache is active or not.
     *
     * @return bool
     */
    public function getExpireQueryCache();
}
