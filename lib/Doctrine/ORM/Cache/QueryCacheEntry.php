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
    public $result;

    /**
     * Time creation of this cache entry
     *
     * @readonly Public only for performance reasons, it should be considered immutable.
     * @var float
     */
    public $time;

    /**
     * @param array<string, mixed> $result
     * @param float|null           $time
     */
    public function __construct($result, $time = null)
    {
        $this->result = $result;
        $this->time   = $time ?: microtime(true);
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return QueryCacheEntry
     */
    public static function __set_state(array $values)
    {
        return new self($values['result'], $values['time']);
    }
}
