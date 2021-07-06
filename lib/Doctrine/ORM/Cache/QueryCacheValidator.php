<?php

namespace Doctrine\ORM\Cache;

/**
 * Cache query validator interface.
 */
interface QueryCacheValidator
{
    /**
     * Checks if the query entry is valid
     *
     * @return bool
     */
    public function isValid(QueryCacheKey $key, QueryCacheEntry $entry);
}
