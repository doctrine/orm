<?php

namespace Doctrine\Tests\Mocks;

use Doctrine\ORM\Cache\TimestampRegion;
use Doctrine\ORM\Cache\CacheKey;

class TimestampRegionMock extends CacheRegionMock implements TimestampRegion
{
    public function update(CacheKey $key)
    {
        $this->calls[__FUNCTION__][] = array('key' => $key);
    }
}
