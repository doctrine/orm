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
    /** @param mixed[]|Collection $collection The collection. */
    public function buildCacheEntry(ClassMetadata $metadata, CollectionCacheKey $key, array|Collection $collection): CollectionCacheEntry;

    /** @return mixed[]|null */
    public function loadCacheEntry(ClassMetadata $metadata, CollectionCacheKey $key, CollectionCacheEntry $entry, PersistentCollection $collection): array|null;
}
