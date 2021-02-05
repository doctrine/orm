<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\ORM\Cache\CacheKey;
use Doctrine\ORM\Cache\TimestampRegion;

/**
 * Timestamp region mock
 *
 * Used to mock a TimestampRegion
 */
class TimestampRegionMock extends CacheRegionMock implements TimestampRegion
{
    public function update(CacheKey $key): void
    {
        $this->calls[__FUNCTION__][] = ['key' => $key];
    }
}
