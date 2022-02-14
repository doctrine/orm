<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;

/**
 * Hydrator cache entry for collections
 */
interface CollectionHydrator
{
    /**
     * @param array|mixed[]|Collection $collection The collection.
     *
     * @return CollectionCacheEntry
     */
    public function buildCacheEntry(ClassMetadata $metadata, CollectionCacheKey $key, $collection);

    /**
     * @return mixed[]|null
     */
    public function loadCacheEntry(ClassMetadata $metadata, CollectionCacheKey $key, CollectionCacheEntry $entry, PersistentCollection $collection);
}
