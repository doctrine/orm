<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use Doctrine\ORM\Query\ResultSetMapping;

/**
 * Defines the contract for caches capable of storing query results.
 * These caches should only concern themselves with storing the matching result ids.
 */
interface QueryCache
{
    public function clear(): bool;

    /** @param mixed[] $hints */
    public function put(QueryCacheKey $key, ResultSetMapping $rsm, mixed $result, array $hints = []): bool;

    /**
     * @param mixed[] $hints
     *
     * @return mixed[]|null
     */
    public function get(QueryCacheKey $key, ResultSetMapping $rsm, array $hints = []): array|null;

    public function getRegion(): Region;
}
