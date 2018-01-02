<?php


declare(strict_types=1);

namespace Doctrine\ORM\Cache;

/**
 * A key that identifies a timestamped space.
 *
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class TimestampCacheKey extends CacheKey
{
    /**
     * @param string $space Result cache id
     */
    public function __construct($space)
    {
        $this->hash = (string) $space;
    }
}
