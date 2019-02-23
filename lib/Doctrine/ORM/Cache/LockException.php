<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use Doctrine\ORM\Cache\Exception\CacheException;
use RuntimeException;

/**
 * Lock exception for cache.
 */
class LockException extends RuntimeException implements CacheException
{
}
