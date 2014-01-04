<?php

namespace Doctrine\Tests\Mocks;

use Doctrine\ORM\Cache\CacheKey;

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
