<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Persister\Entity;

use Doctrine\ORM\Cache\Exception\CannotUpdateReadOnlyEntity;
use Doctrine\ORM\Utility\StaticClassNameConverter;

/**
 * Specific read-only region entity persister
 */
class ReadOnlyCachedEntityPersister extends NonStrictReadWriteCachedEntityPersister
{
    /**
     * {@inheritdoc}
     */
    public function update($entity)
    {
        throw CannotUpdateReadOnlyEntity::fromEntity(StaticClassNameConverter::getClass($entity));
    }
}
