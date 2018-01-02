<?php


declare(strict_types=1);

namespace Doctrine\ORM\Cache\Persister\Collection;

use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Cache\Persister\CachedPersister;
use Doctrine\ORM\Persisters\Collection\CollectionPersister;
use Doctrine\ORM\PersistentCollection;

/**
 * Interface for second level cache collection persisters.
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 * @since 2.5
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
     * @param \Doctrine\ORM\PersistentCollection     $collection
     * @param \Doctrine\ORM\Cache\CollectionCacheKey $key
     *
     * @return \Doctrine\ORM\PersistentCollection|null
     */
    public function loadCollectionCache(PersistentCollection $collection, CollectionCacheKey $key);

    /**
     * Stores a collection into cache
     *
     * @param \Doctrine\ORM\Cache\CollectionCacheKey        $key
     * @param array|\Doctrine\Common\Collections\Collection $elements
     *
     * @return void
     */
    public function storeCollectionCache(CollectionCacheKey $key, $elements);
}
