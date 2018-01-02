<?php


declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use Doctrine\ORM\Cache;

/**
 * A cache key that identifies a particular query.
 *
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class QueryCacheKey extends CacheKey
{
    /**
     * READ-ONLY: Public only for performance reasons, it should be considered immutable.
     *
     * @var integer Cache key lifetime
     */
    public $lifetime;

    /**
     * READ-ONLY: Public only for performance reasons, it should be considered immutable.
     *
     * @var integer Cache mode (Doctrine\ORM\Cache::MODE_*)
     */
    public $cacheMode;

    /**
     * READ-ONLY: Public only for performance reasons, it should be considered immutable.
     *
     * @var TimestampCacheKey|null
     */
    public $timestampKey;

    /**
     * @param string $hash Result cache id
     * @param integer $lifetime Query lifetime
     * @param int $cacheMode Query cache mode
     * @param TimestampCacheKey|null $timestampKey
     */
    public function __construct(
        $hash,
        $lifetime = 0,
        $cacheMode = Cache::MODE_NORMAL,
        TimestampCacheKey $timestampKey = null
    ) {
        $this->hash         = $hash;
        $this->lifetime     = $lifetime;
        $this->cacheMode    = $cacheMode;
        $this->timestampKey = $timestampKey;
    }
}
