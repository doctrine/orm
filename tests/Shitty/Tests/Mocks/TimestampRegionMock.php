<?php

namespace Shitty\Tests\Mocks;

use Shitty\ORM\Cache\TimestampRegion;
use Shitty\ORM\Cache\CacheKey;

/**
 * Timestamp region mock
 *
 * Used to mock a TimestampRegion
 */
class TimestampRegionMock extends CacheRegionMock implements TimestampRegion
{
    public function update(CacheKey $key)
    {
        $this->calls[__FUNCTION__][] = array('key' => $key);
    }
}
