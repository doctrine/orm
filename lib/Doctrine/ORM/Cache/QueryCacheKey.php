<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use Doctrine\ORM\Cache;

/**
 * A cache key that identifies a particular query.
 */
class QueryCacheKey extends CacheKey
{
    /**
     * Cache key lifetime
     *
     * @readonly Public only for performance reasons, it should be considered immutable.
     */
    public int $lifetime;

    /**
     * Cache mode
     *
     * @readonly Public only for performance reasons, it should be considered immutable.
     * @psalm-var Cache::MODE_*
     */
    public int $cacheMode;

    /** @readonly Public only for performance reasons, it should be considered immutable. */
    public TimestampCacheKey|null $timestampKey = null;

    /** @psalm-param Cache::MODE_* $cacheMode */
    public function __construct(
        string $cacheId,
        int $lifetime = 0,
        int $cacheMode = Cache::MODE_NORMAL,
        TimestampCacheKey|null $timestampKey = null,
    ) {
        $this->hash         = $cacheId;
        $this->lifetime     = $lifetime;
        $this->cacheMode    = $cacheMode;
        $this->timestampKey = $timestampKey;
    }
}
