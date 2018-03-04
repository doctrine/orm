<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Exception;

use Doctrine\ORM\ORMException;
use function sprintf;

/**
 * Exception for cache.
 */
interface CacheException extends ORMException
{
}
