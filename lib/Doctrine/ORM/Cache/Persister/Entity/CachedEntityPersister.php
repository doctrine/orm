<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Persister\Entity;

use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\EntityHydrator;
use Doctrine\ORM\Cache\Persister\CachedPersister;
use Doctrine\ORM\Persisters\Entity\EntityPersister;

/**
 * Interface for second level cache entity persisters.
 */
interface CachedEntityPersister extends CachedPersister, EntityPersister
{
    public function getEntityHydrator(): EntityHydrator;

    public function storeEntityCache(object $entity, EntityCacheKey $key): bool;
}
