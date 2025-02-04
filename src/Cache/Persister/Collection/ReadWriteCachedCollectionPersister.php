<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Persister\Collection;

use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Cache\ConcurrentRegion;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Persisters\Collection\CollectionPersister;

use function spl_object_id;

/** @phpstan-import-type AssociationMapping from ClassMetadata */
class ReadWriteCachedCollectionPersister extends AbstractCollectionPersister
{
    /** @param AssociationMapping $association The association mapping. */
    public function __construct(CollectionPersister $persister, ConcurrentRegion $region, EntityManagerInterface $em, array $association)
    {
        parent::__construct($persister, $region, $em, $association);
    }

    /**
     * {@inheritDoc}
     */
    public function afterTransactionComplete()
    {
        if (isset($this->queuedCache['update'])) {
            foreach ($this->queuedCache['update'] as $item) {
                $this->region->evict($item['key']);
            }
        }

        if (isset($this->queuedCache['delete'])) {
            foreach ($this->queuedCache['delete'] as $item) {
                $this->region->evict($item['key']);
            }
        }

        $this->queuedCache = [];
    }

    /**
     * {@inheritDoc}
     */
    public function afterTransactionRolledBack()
    {
        if (isset($this->queuedCache['update'])) {
            foreach ($this->queuedCache['update'] as $item) {
                $this->region->evict($item['key']);
            }
        }

        if (isset($this->queuedCache['delete'])) {
            foreach ($this->queuedCache['delete'] as $item) {
                $this->region->evict($item['key']);
            }
        }

        $this->queuedCache = [];
    }

    /**
     * {@inheritDoc}
     */
    public function delete(PersistentCollection $collection)
    {
        $ownerId = $this->uow->getEntityIdentifier($collection->getOwner());
        $key     = new CollectionCacheKey($this->sourceEntity->rootEntityName, $this->association['fieldName'], $ownerId, $this->filters->getHash());
        $lock    = $this->region->lock($key);

        $this->persister->delete($collection);

        if ($lock === null) {
            return;
        }

        $this->queuedCache['delete'][spl_object_id($collection)] = [
            'key'   => $key,
            'lock'  => $lock,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function update(PersistentCollection $collection)
    {
        $isInitialized = $collection->isInitialized();
        $isDirty       = $collection->isDirty();

        if (! $isInitialized && ! $isDirty) {
            return;
        }

        $this->persister->update($collection);

        $ownerId = $this->uow->getEntityIdentifier($collection->getOwner());
        $key     = new CollectionCacheKey($this->sourceEntity->rootEntityName, $this->association['fieldName'], $ownerId, $this->filters->getHash());
        $lock    = $this->region->lock($key);

        if ($lock === null) {
            return;
        }

        $this->queuedCache['update'][spl_object_id($collection)] = [
            'key'   => $key,
            'lock'  => $lock,
        ];
    }
}
