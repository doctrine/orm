<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Exception;

use function sprintf;

class CannotUpdateReadOnlyCollection extends CacheException
{
    public static function fromEntityAndField(string $sourceEntity, string $fieldName): self
    {
        return new self(sprintf(
            'Cannot update a readonly collection "%s#%s"',
            $sourceEntity,
            $fieldName,
        ));
    }
}
