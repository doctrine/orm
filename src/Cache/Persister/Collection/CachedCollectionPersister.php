<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Persister\Collection;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Cache\Persister\CachedPersister;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Persisters\Collection\CollectionPersister;

/**
 * Interface for second level cache collection persisters.
 */
interface CachedCollectionPersister extends CachedPersister, CollectionPersister
{
    public function getSourceEntityMetadata(): ClassMetadata;

    public function getTargetEntityMetadata(): ClassMetadata;

    /**
     * Loads a collection from cache
     *
     * @return mixed[]|null
     */
    public function loadCollectionCache(PersistentCollection $collection, CollectionCacheKey $key): array|null;

    /**
     * Stores a collection into cache
     *
     * @param mixed[]|Collection $elements
     */
    public function storeCollectionCache(CollectionCacheKey $key, Collection|array $elements): void;
}
