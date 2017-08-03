<?php


declare(strict_types=1);

namespace Doctrine\ORM\Cache\Persister\Entity;

use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\Persister\CachedPersister;
use Doctrine\ORM\Persisters\Entity\EntityPersister;

/**
 * Interface for second level cache entity persisters.
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 * @since 2.5
 */
interface CachedEntityPersister extends CachedPersister, EntityPersister
{
    /**
     * @return \Doctrine\ORM\Cache\EntityHydrator
     */
    public function getEntityHydrator();

    /**
     * @param object                             $entity
     * @param \Doctrine\ORM\Cache\EntityCacheKey $key
     *
     * @return boolean
     */
    public function storeEntityCache($entity, EntityCacheKey $key);
}
