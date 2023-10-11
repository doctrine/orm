<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Region;

use Doctrine\ORM\Cache\CacheKey;
use Doctrine\ORM\Cache\TimestampCacheEntry;
use Doctrine\ORM\Cache\TimestampRegion;

/**
 * Tracks the timestamps of the most recent updates to particular keys.
 */
class UpdateTimestampCache extends DefaultRegion implements TimestampRegion
{
    public function update(CacheKey $key): void
    {
        $this->put($key, new TimestampCacheEntry());
    }
}
