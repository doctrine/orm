<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Exception;

use function sprintf;

final class NonCacheableEntity extends \LogicException implements CacheException
{
    public static function fromEntity(string $entityName) : self
    {
        return new self(sprintf(
            'Entity "%s" not configured as part of the second-level cache.',
            $entityName
        ));
    }
}
