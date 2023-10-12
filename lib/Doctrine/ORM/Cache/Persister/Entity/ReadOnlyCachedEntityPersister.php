<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Persister\Entity;

use Doctrine\ORM\Cache\Exception\CannotUpdateReadOnlyEntity;

use function get_class;

/**
 * Specific read-only region entity persister
 */
class ReadOnlyCachedEntityPersister extends NonStrictReadWriteCachedEntityPersister
{
    /**
     * {@inheritDoc}
     */
    public function update($entity)
    {
        throw CannotUpdateReadOnlyEntity::fromEntity($this->proxyClassNameResolver->resolveClassName(get_class($entity)));
    }
}
