<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Exception;

use Doctrine\Common\Cache\Cache;

use function get_debug_type;

final class QueryCacheUsesNonPersistentCache extends CacheException
{
    public static function fromDriver(Cache $cache): self
    {
        return new self(
            'Query Cache uses a non-persistent cache driver, ' . get_debug_type($cache) . '.'
        );
    }
}
