<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Exception;

use LogicException;

final class QueryCacheNotConfigured extends CacheException
{
    public static function create(): self
    {
        return new self('Query Cache is not configured.');
    }
}
