<?php


declare(strict_types=1);

namespace Doctrine\ORM\Cache;

/**
 * Cache query validator interface.
 *
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
interface QueryCacheValidator
{
    /**
     * Checks if the query entry is valid
     *
     * @param \Doctrine\ORM\Cache\QueryCacheKey   $key
     * @param \Doctrine\ORM\Cache\QueryCacheEntry $entry
     *
     * @return boolean
     */
    public function isValid(QueryCacheKey $key, QueryCacheEntry $entry);
}
