<?php


declare(strict_types=1);

namespace Doctrine\ORM\Cache;

/**
 * Timestamp cache entry
 *
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class TimestampCacheEntry implements CacheEntry
{
    /**
     * READ-ONLY: Public only for performance reasons, it should be considered immutable.
     *
     * @var float
     */
    public $time;

    /**
     * @param float $time
     */
    public function __construct($time = null)
    {
        $this->time = $time ? (float) $time : microtime(true);
    }

    /**
     * Creates a new TimestampCacheEntry
     *
     * This method allow Doctrine\Common\Cache\PhpFileCache compatibility
     *
     * @param array $values array containing property values
     *
     * @return TimestampCacheEntry
     */
    public static function __set_state(array $values)
    {
        return new self($values['time']);
    }
}
