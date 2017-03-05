<?php

namespace Doctrine\Tests\Mocks;

use Doctrine\ORM\Cache\TimestampRegion;
use Doctrine\ORM\Cache\CacheKey;

/**
 * Timestamp region mock
 *
 * Used to mock a TimestampRegion
 */
class TimestampRegionMock extends CacheRegionMock implements TimestampRegion
{
    public function update(CacheKey $key)
    {
        $this->calls[__FUNCTION__][] = ['key' => $key];
    }
}
