<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

interface SLCacheCapable
{
    /**
     * Enable/disable second level query (result) caching for this query.
     *
     * @param boolean $cacheable
     *
     * @return static This query instance.
     */
    public function setCacheable($cacheable);

    /**
     * @return boolean TRUE if the query results are enable for second level cache, FALSE otherwise.
     */
    public function isCacheable();

    /**
     * @param string $cacheRegion
     *
     * @return static This query instance.
     */
    public function setCacheRegion($cacheRegion);

    /**
     * Obtain the name of the second level query cache region in which query results will be stored
     *
     * @return string|null The cache region name; NULL indicates the default region.
     */
    public function getCacheRegion();

    /**
     * @return integer
     */
    public function getLifetime();

    /**
     * Sets the life-time for this query into second level cache.
     *
     * @param integer $lifetime
     *
     * @return \Doctrine\ORM\AbstractQuery This query instance.
     */
    public function setLifetime($lifetime);

    /**
     * @return integer
     */
    public function getCacheMode();

    /**
     * @param integer $cacheMode
     *
     * @return \Doctrine\ORM\AbstractQuery This query instance.
     */
    public function setCacheMode($cacheMode);
}
