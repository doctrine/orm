<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use function microtime;

/**
 * Query cache entry
 */
class QueryCacheEntry implements CacheEntry
{
    /**
     * List of entity identifiers
     *
     * @readonly Public only for performance reasons, it should be considered immutable.
     * @var array<string, mixed>
     */
    public array $result;

    /**
     * Time creation of this cache entry
     *
     * @readonly Public only for performance reasons, it should be considered immutable.
     */
    public float $time;

    /** @param array<string, mixed> $result */
    public function __construct(array $result, float|null $time = null)
    {
        $this->result = $result;
        $this->time   = $time ?: microtime(true);
    }

    /** @param array<string, mixed> $values */
    public static function __set_state(array $values): self
    {
        return new self($values['result'], $values['time']);
    }
}
