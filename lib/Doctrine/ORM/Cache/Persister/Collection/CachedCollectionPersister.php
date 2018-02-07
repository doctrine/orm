<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Persister\Collection;

use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Cache\Persister\CachedPersister;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Persisters\Collection\CollectionPersister;

/**
 * Interface for second level cache collection persisters.
 */
interface CachedCollectionPersister extends CachedPersister, CollectionPersister
{
    /**
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    public function getSourceEntityMetadata();

    /**
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    public function getTargetEntityMetadata();

    /**
     * Loads a collection from cache
     *
     * @return \Doctrine\ORM\PersistentCollection|mixed[]|null
     */
    public function loadCollectionCache(PersistentCollection $collection, CollectionCacheKey $key);

    /**
     * Stores a collection into cache
     *
     * @param array|\Doctrine\Common\Collections\Collection|mixed[] $elements
     *
     * @return void
     */
    public function storeCollectionCache(CollectionCacheKey $key, $elements);
}
