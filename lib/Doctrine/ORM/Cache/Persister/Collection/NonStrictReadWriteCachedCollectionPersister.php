<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Persister\Collection;

use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Mapping\ToManyAssociationMetadata;
use Doctrine\ORM\PersistentCollection;
use function spl_object_id;

class NonStrictReadWriteCachedCollectionPersister extends AbstractCollectionPersister
{
    /**
     * {@inheritdoc}
     */
    public function afterTransactionComplete()
    {
        if (isset($this->queuedCache['update'])) {
            foreach ($this->queuedCache['update'] as $item) {
                $this->storeCollectionCache($item['key'], $item['list']);
            }
        }

        if (isset($this->queuedCache['delete'])) {
            foreach ($this->queuedCache['delete'] as $key) {
                $this->region->evict($key);
            }
        }

        $this->queuedCache = [];
    }

    /**
     * {@inheritdoc}
     */
    public function afterTransactionRolledBack()
    {
        $this->queuedCache = [];
    }

    /**
     * {@inheritdoc}
     */
    public function delete(PersistentCollection $collection)
    {
        $fieldName = $this->association->getName();
        $ownerId   = $this->uow->getEntityIdentifier($collection->getOwner());
        $key       = new CollectionCacheKey($this->sourceEntity->getRootClassName(), $fieldName, $ownerId);

        $this->persister->delete($collection);

        $this->queuedCache['delete'][spl_object_id($collection)] = $key;
    }

    /**
     * {@inheritdoc}
     */
    public function update(PersistentCollection $collection)
    {
        $isInitialized = $collection->isInitialized();
        $isDirty       = $collection->isDirty();

        if (! $isInitialized && ! $isDirty) {
            return;
        }

        $fieldName = $this->association->getName();
        $ownerId   = $this->uow->getEntityIdentifier($collection->getOwner());
        $key       = new CollectionCacheKey($this->sourceEntity->getRootClassName(), $fieldName, $ownerId);

        // Invalidate non initialized collections OR ordered collection
        if (($isDirty && ! $isInitialized) ||
            ($this->association instanceof ToManyAssociationMetadata && $this->association->getOrderBy())) {
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
