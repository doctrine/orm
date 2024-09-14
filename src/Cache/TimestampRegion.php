<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

/**
 * Defines the contract for a cache region which will specifically be used to store entity "update timestamps".
 */
interface TimestampRegion extends Region
{
    /**
     * Update a specific key into the cache region.
     *
     * @return void
     *
     * @throws LockException Indicates a problem accessing the region.
     */
    public function update(CacheKey $key);
}
