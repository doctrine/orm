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
     * @var int
     */
    public $lifetime;

    /**
     * Cache mode
     *
     * @readonly Public only for performance reasons, it should be considered immutable.
     * @var int
     * @psalm-var Cache::MODE_*
     */
    public $cacheMode;

    /**
     * @readonly Public only for performance reasons, it should be considered immutable.
     * @var TimestampCacheKey|null
     */
    public $timestampKey;

    /** @psalm-param Cache::MODE_* $cacheMode */
    public function __construct(
        string $cacheId,
        int $lifetime = 0,
        int $cacheMode = Cache::MODE_NORMAL,
        ?TimestampCacheKey $timestampKey = null
    ) {
        $this->lifetime     = $lifetime;
        $this->cacheMode    = $cacheMode;
        $this->timestampKey = $timestampKey;

        parent::__construct($cacheId);
    }
}
