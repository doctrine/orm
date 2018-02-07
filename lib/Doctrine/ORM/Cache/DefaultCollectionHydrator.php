<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Query;

/**
 * Default hydrator cache for collections
 */
class DefaultCollectionHydrator implements CollectionHydrator
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \Doctrine\ORM\UnitOfWork
     */
    private $uow;

    /**
     * @var mixed[]
     */
    private static $hints = [Query::HINT_CACHE_ENABLED => true];

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em The entity manager.
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em  = $em;
        $this->uow = $em->getUnitOfWork();
    }

    /**
     * {@inheritdoc}
     */
    public function buildCacheEntry(ClassMetadata $metadata, CollectionCacheKey $key, $collection)
    {
        $data = [];

        foreach ($collection as $index => $entity) {
            $data[$index] = new EntityCacheKey($metadata->getRootClassName(), $this->uow->getEntityIdentifier($entity));
        }

        return new CollectionCacheEntry($data);
    }

    /**
     * {@inheritdoc}
     */
    public function loadCacheEntry(
        ClassMetadata $metadata,
        CollectionCacheKey $key,
        CollectionCacheEntry $entry,
        PersistentCollection $collection
    ) {
        /* @var $targetPersister \Doctrine\ORM\Cache\Persister\CachedPersister */
        $association     = $metadata->getProperty($key->association);
        $targetPersister = $this->uow->getEntityPersister($association->getTargetEntity());
        $targetRegion    = $targetPersister->getCacheRegion();
        $list            = [];

        $entityEntries = $targetRegion->getMultiple($entry);

        if ($entityEntries === null) {
            return null;
        }

        /* @var $entityEntries \Doctrine\ORM\Cache\EntityCacheEntry[] */
        foreach ($entityEntries as $index => $entityEntry) {
            $data = $entityEntry->resolveAssociationEntries($this->em);

            $entity = $this->uow->createEntity($entityEntry->class, $data, self::$hints);

            $collection->hydrateSet($index, $entity);

            $list[$index] = $entity;
        }

        $this->uow->hydrationComplete();

        return $list;
    }
}
