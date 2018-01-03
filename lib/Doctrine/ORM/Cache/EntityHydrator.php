<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Hydrator cache entry for entities
 */
interface EntityHydrator
{
    /**
     * @param \Doctrine\ORM\Mapping\ClassMetadata $metadata The entity metadata.
     * @param \Doctrine\ORM\Cache\EntityCacheKey  $key      The entity cache key.
     * @param object                              $entity   The entity.
     *
     * @return \Doctrine\ORM\Cache\EntityCacheEntry
     */
    public function buildCacheEntry(ClassMetadata $metadata, EntityCacheKey $key, $entity);

    /**
     * @param \Doctrine\ORM\Mapping\ClassMetadata  $metadata The entity metadata.
     * @param \Doctrine\ORM\Cache\EntityCacheKey   $key      The entity cache key.
     * @param \Doctrine\ORM\Cache\EntityCacheEntry $entry    The entity cache entry.
     * @param object                               $entity   The entity to load the cache into. If not specified, a new entity is created.
     */
    public function loadCacheEntry(ClassMetadata $metadata, EntityCacheKey $key, EntityCacheEntry $entry, $entity = null);
}
