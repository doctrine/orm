<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

use Doctrine\DBAL\Cache\QueryCacheProfile;

interface ResultCacheCapable
{
    /**
     * Set the result cache id to use to store the result set cache entry.
     * If this is not explicitly set by the developer then a hash is automatically
     * generated for you.
     *
     * @param string $id
     *
     * @return static This query instance.
     */
    public function setResultCacheId($id);

    /**
     * Get the result cache id to use to store the result set cache entry if set.
     *
     * @deprecated
     *
     * @return string
     */
    public function getResultCacheId();

    /**
     * Set a cache profile for the result cache.
     *
     * If no result cache driver is set in the QueryCacheProfile, the default
     * result cache driver is used from the configuration.
     *
     * @param \Doctrine\DBAL\Cache\QueryCacheProfile $profile
     *
     * @return static This query instance.
     */
    public function setResultCacheProfile(QueryCacheProfile $profile = null);

    /**
     * Defines a cache driver to be used for caching result sets and implicitly enables caching.
     *
     * @param \Doctrine\Common\Cache\Cache|null $resultCacheDriver Cache driver
     *
     * @return static This query instance.
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function setResultCacheDriver($resultCacheDriver = null);

    /**
     * Returns the cache driver used for caching result sets.
     *
     * @deprecated
     *
     * @return \Doctrine\Common\Cache\Cache Cache driver
     */
    public function getResultCacheDriver();

    /**
     * Defines how long the result cache will be active before expire.
     *
     * @param integer $lifetime How long the cache entry is valid.
     *
     * @return static This query instance.
     */
    public function setResultCacheLifetime($lifetime);

    /**
     * Retrieves the lifetime of resultset cache.
     *
     * @deprecated
     *
     * @return integer
     */
    public function getResultCacheLifetime();

    /**
     * Defines if the result cache is active or not.
     *
     * @param boolean $expire Whether or not to force resultset cache expiration.
     *
     * @return static This query instance.
     */
    public function expireResultCache($expire = true);

    /**
     * Retrieves if the resultset cache is active or not.
     *
     * @return boolean
     */
    public function getExpireResultCache();

    /**
     * @return QueryCacheProfile
     */
    public function getQueryCacheProfile();

}
