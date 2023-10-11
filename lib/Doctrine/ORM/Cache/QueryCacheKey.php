<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use Doctrine\ORM\Cache;

/**
 * A cache key that identifies a particular query.
 */
class QueryCacheKey extends CacheKey
{
    /** @param Cache::MODE_* $cacheMode */
    public function __construct(
        string $cacheId,
        public readonly int $lifetime = 0,
        public readonly int $cacheMode = Cache::MODE_NORMAL,
        public readonly TimestampCacheKey|null $timestampKey = null,
    ) {
        parent::__construct($cacheId);
    }
}
