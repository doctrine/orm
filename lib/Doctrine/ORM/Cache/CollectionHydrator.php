<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;

/**
 * Hydrator cache entry for collections
 *
 */
interface CollectionHydrator
{
    /**
     * @param \Doctrine\ORM\Mapping\ClassMetadata                    $metadata   The entity metadata.
     * @param \Doctrine\ORM\Cache\CollectionCacheKey                 $key        The cached collection key.
     * @param array|\Doctrine\Common\Collections\Collection|object[] $collection The collection.
     *
     * @return \Doctrine\ORM\Cache\CollectionCacheEntry
     */
    public function buildCacheEntry(ClassMetadata $metadata, CollectionCacheKey $key, $collection);

    /**
     * @param \Doctrine\ORM\Mapping\ClassMetadata      $metadata   The owning entity metadata.
     * @param \Doctrine\ORM\Cache\CollectionCacheKey   $key        The cached collection key.
     * @param \Doctrine\ORM\Cache\CollectionCacheEntry $entry      The cached collection entry.
     * @param \Doctrine\ORM\PersistentCollection       $collection The collection to load the cache into.
     *
     * @return object[]
     */
    public function loadCacheEntry(ClassMetadata $metadata, CollectionCacheKey $key, CollectionCacheEntry $entry, PersistentCollection $collection);
}
