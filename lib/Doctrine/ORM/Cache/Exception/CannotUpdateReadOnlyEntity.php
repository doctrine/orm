<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Exception;

class CannotUpdateReadOnlyEntity extends \Exception implements CacheException
{
    public static function fromEntity(string $entityName) : self
    {
        return new self(sprintf('Cannot update a readonly entity "%s"', $entityName));
    }
}
