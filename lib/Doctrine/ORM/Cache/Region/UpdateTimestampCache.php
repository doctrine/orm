<?php


declare(strict_types=1);

namespace Doctrine\ORM\Cache\Region;

use Doctrine\ORM\Cache\TimestampCacheEntry;
use Doctrine\ORM\Cache\TimestampRegion;
use Doctrine\ORM\Cache\CacheKey;

/**
 * Tracks the timestamps of the most recent updates to particular keys.
 *
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class UpdateTimestampCache extends DefaultRegion implements TimestampRegion
{
    /**
     * {@inheritdoc}
     */
    public function update(CacheKey $key)
    {
        $this->put($key, new TimestampCacheEntry);
    }
}
