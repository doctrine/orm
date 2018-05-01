<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Exception;

use function sprintf;

final class CannotUpdateReadOnlyEntity extends \LogicException implements CacheException
{
    public static function fromEntity(string $entityName) : self
    {
        return new self(sprintf('Cannot update a readonly entity "%s"', $entityName));
    }
}
