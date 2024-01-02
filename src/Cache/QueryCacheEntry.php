<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use function microtime;

class QueryCacheEntry implements CacheEntry
{
    /**
     * Time creation of this cache entry
     */
    public readonly float $time;

    /** @param array<string, mixed> $result List of entity identifiers */
    public function __construct(
        public readonly array $result,
        float|null $time = null,
    ) {
        $this->time = $time ?: microtime(true);
    }

    /** @param array<string, mixed> $values */
    public static function __set_state(array $values): self
    {
        return new self($values['result'], $values['time']);
    }
}
