<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use Doctrine\ORM\Exception\ORMException;
use LogicException;

use function sprintf;

/**
 * Exception for cache.
 */
class CacheException extends LogicException implements ORMException
{
    public static function updateReadOnlyCollection(string $sourceEntity, string $fieldName): self
    {
        return new self(sprintf('Cannot update a readonly collection "%s#%s"', $sourceEntity, $fieldName));
    }

    public static function nonCacheableEntity(string $entityName): self
    {
        return new self(sprintf('Entity "%s" not configured as part of the second-level cache.', $entityName));
    }
}
