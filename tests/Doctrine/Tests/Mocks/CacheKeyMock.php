<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\ORM\Cache\CacheKey;

/**
 * Cache key mock
 *
 * Used to store/retrieve entries from a cache region
 */
class CacheKeyMock extends CacheKey
{
    /** @param string $hash The string hash that represents this cache key */
    public function __construct(string $hash)
    {
        parent::__construct($hash);
    }
}
