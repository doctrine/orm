<?php

namespace Shitty\Tests\Mocks;

use Shitty\ORM\Cache\CacheKey;

/**
 * Cache key mock
 *
 * Used to store/retrieve entries from a cache region
 */
class CacheKeyMock extends CacheKey
{
    /**
     * @param string $hash The string hash that represend this cache key
     */
    function __construct($hash)
    {
        $this->hash = $hash;
    }
}
