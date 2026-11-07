<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Persister\Collection;

use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\PersistentCollection;

use function spl_object_id;

class NonStrictReadWriteCachedCollectionPersister extends AbstractCollectionPersister
{
    public function afterTransactionComplete(): void
    {
        if (isset($this->queuedCache['update'])) {
            foreach ($this->queuedCache['update'] as $item) {
                $this->evictCollectionCache($item['key']);
                $this->storeCollectionCache($item['key'], $item['list']);
            }
        }

        if (isset($this->queuedCache['delete'])) {
            foreach ($this->queuedCache['delete'] as $key) {
                $this->evictCollectionCache($key);
            }
        }

        $this->queuedCache = [];
    }

    public function afterTransactionRolledBack(): void
    {
        $this->queuedCache = [];
    }

    public function delete(PersistentCollection $collection): void
    {
        $ownerId = $this->uow->getEntityIdentifier($collection->getOwner());
        $key     = new CollectionCacheKey($this->sourceEntity->rootEntityName, $this->association->fieldName, $ownerId, $this->filters->getHash());

        $this->persister->delete($collection);

        $this->queuedCache['delete'][spl_object_id($collection)] = $key;
    }

    public function update(PersistentCollection $collection): void
    {
        $isInitialized = $collection->isInitialized();
        $isDirty       = $collection->isDirty();

        if (! $isInitialized && ! $isDirty) {
            return;
        }

        $ownerId = $this->uow->getEntityIdentifier($collection->getOwner());
        $key     = new CollectionCacheKey($this->sourceEntity->rootEntityName, $this->association->fieldName, $ownerId, $this->filters->getHash());

        // Filtered collections must be reloaded: new elements may not match the SQL filter.
        if (
            $isDirty && ! $isInitialized ||
            $this->association->isOrdered() ||
            $key->filterHash !== '' ||
            ($this->collectionFilterHashes[$collection] ?? '') !== ''
        ) {
            $this->persister->update($collection);

            $this->queuedCache['delete'][spl_object_id($collection)] = $key;

            return;
        }

        $this->persister->update($collection);

        $this->queuedCache['update'][spl_object_id($collection)] = [
            'key'   => $key,
            'list'  => $collection,
        ];
    }
}
