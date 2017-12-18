<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use Doctrine\Common\Cache\Cache;

final class QueryCacheUsesNonPersistentCache extends \Exception implements CacheException
{
    public static function fromDriver(Cache $cache) : self
    {
        return new self(
            'Query Cache uses a non-persistent cache driver, ' . get_class($cache) . '.'
        );
    }
}
