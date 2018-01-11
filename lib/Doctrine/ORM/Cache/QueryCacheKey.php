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
     * READ-ONLY: Public only for performance reasons, it should be considered immutable.
     *
     * @var int Cache key lifetime
     */
    public $lifetime;

    /**
     * READ-ONLY: Public only for performance reasons, it should be considered immutable.
     *
     * @var int Cache mode (Doctrine\ORM\Cache::MODE_*)
     */
    public $cacheMode;

    /**
     * READ-ONLY: Public only for performance reasons, it should be considered immutable.
     *
     * @var TimestampCacheKey|null
     */
    public $timestampKey;

    /**
     * @param string $hash      Result cache id
     * @param int    $lifetime  Query lifetime
     * @param int    $cacheMode Query cache mode
     */
    public function __construct(
        $hash,
        $lifetime = 0,
        $cacheMode = Cache::MODE_NORMAL,
        ?TimestampCacheKey $timestampKey = null
    ) {
        $this->hash         = $hash;
        $this->lifetime     = $lifetime;
        $this->cacheMode    = $cacheMode;
        $this->timestampKey = $timestampKey;
    }
}
