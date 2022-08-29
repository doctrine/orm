<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use function microtime;

/**
 * Timestamp cache entry
 */
class TimestampCacheEntry implements CacheEntry
{
    /** @readonly Public only for performance reasons, it should be considered immutable. */
    public float $time;

    public function __construct(float|null $time = null)
    {
        $this->time = $time ?? microtime(true);
    }

    /**
     * Creates a new TimestampCacheEntry
     *
     * This method allow Doctrine\Common\Cache\PhpFileCache compatibility
     *
     * @param array<string,float> $values array containing property values
     */
    public static function __set_state(array $values): TimestampCacheEntry
    {
        return new self($values['time']);
    }
}
