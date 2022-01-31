<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Persister\Entity;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Cache\Exception\CannotUpdateReadOnlyEntity;

/**
 * Specific read-only region entity persister
 */
class ReadOnlyCachedEntityPersister extends NonStrictReadWriteCachedEntityPersister
{
    public function update(object $entity): void
    {
        throw CannotUpdateReadOnlyEntity::fromEntity(ClassUtils::getClass($entity));
    }
}
