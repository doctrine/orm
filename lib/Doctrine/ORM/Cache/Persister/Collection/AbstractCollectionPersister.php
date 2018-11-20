<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Persister\Collection;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Cache\CollectionHydrator;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\Logging\CacheLogger;
use Doctrine\ORM\Cache\Persister\Entity\CachedEntityPersister;
use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\ToManyAssociationMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Persisters\Collection\CollectionPersister;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Utility\StaticClassNameConverter;
use function array_values;
use function count;

abstract class AbstractCollectionPersister implements CachedCollectionPersister
{
    /** @var UnitOfWork */
    protected $uow;

    /** @var ClassMetadataFactory */
    protected $metadataFactory;

    /** @var CollectionPersister */
    protected $persister;

    /** @var ClassMetadata */
    protected $sourceEntity;

    /** @var ClassMetadata */
    protected $targetEntity;

    /** @var AssociationMetadata */
    protected $association;

    /** @var mixed[][] */
    protected $queuedCache = [];

    /** @var Region */
    protected $region;

    /** @var string */
    protected $regionName;

    /** @var CollectionHydrator */
    protected $hydrator;

    /** @var CacheLogger */
    protected $cacheLogger;

    /**
     * @param CollectionPersister    $persister   The collection persister that will be cached.
     * @param Region                 $region      The collection region.
     * @param EntityManagerInterface $em          The entity manager.
     * @param AssociationMetadata    $association The association mapping.
     */
    public function __construct(
        CollectionPersister $persister,
        Region $region,
        EntityManagerInterface $em,
        AssociationMetadata $association
    ) {
        $configuration = $em->getConfiguration();
        $cacheConfig   = $configuration->getSecondLevelCacheConfiguration();
        $cacheFactory  = $cacheConfig->getCacheFactory();

        $this->region          = $region;
        $this->persister       = $persister;
        $this->association     = $association;
        $this->regionName      = $region->getName();
        $this->uow             = $em->getUnitOfWork();
        $this->metadataFactory = $em->getMetadataFactory();
        $this->cacheLogger     = $cacheConfig->getCacheLogger();
        $this->hydrator        = $cacheFactory->buildCollectionHydrator($em, $association);
        $this->sourceEntity    = $em->getClassMetadata($association->getSourceEntity());
        $this->targetEntity    = $em->getClassMetadata($association->getTargetEntity());
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheRegion()
    {
        return $this->region;
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceEntityMetadata()
    {
        return $this->sourceEntity;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetEntityMetadata()
    {
        return $this->targetEntity;
    }

    /**
     * @return PersistentCollection|null
     */
    public function loadCollectionCache(PersistentCollection $collection, CollectionCacheKey $key)
    {
        $cache = $this->region->get($key);

        if ($cache === null) {
            return null;
        }

        return $this->hydrator->loadCacheEntry($this->sourceEntity, $key, $cache, $collection);
    }

    /**
     * {@inheritdoc}
     */
    public function storeCollectionCache(CollectionCacheKey $key, $elements)
    {
        /** @var CachedEntityPersister $targetPersister */
        $association     = $this->sourceEntity->getProperty($key->association);
        $targetPersister = $this->uow->getEntityPersister($this->targetEntity->getRootClassName());
        $targetRegion    = $targetPersister->getCacheRegion();
        $targetHydrator  = $targetPersister->getEntityHydrator();

        // Only preserve ordering if association configured it
        if (! ($association instanceof ToManyAssociationMetadata && $association->getIndexedBy())) {
            // Elements may be an array or a Collection
            $elements = array_values($elements instanceof Collection ? $elements->getValues() : $elements);
        }

        $entry = $this->hydrator->buildCacheEntry($this->targetEntity, $key, $elements);

        foreach ($entry->identifiers as $index => $entityKey) {
            if ($targetRegion->contains($entityKey)) {
                continue;
            }

            $class     = $this->targetEntity;
            $className = StaticClassNameConverter::getClass($elements[$index]);

            if ($className !== $this->targetEntity->getClassName()) {
                $class = $this->metadataFactory->getMetadataFor($className);
            }

            $entity      = $elements[$index];
            $entityEntry = $targetHydrator->buildCacheEntry($class, $entityKey, $entity);

            $targetRegion->put($entityKey, $entityEntry);
        }

        $cached = $this->region->put($key, $entry);

        if ($this->cacheLogger && $cached) {
            $this->cacheLogger->collectionCachePut($this->regionName, $key);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function contains(PersistentCollection $collection, $element)
    {
        return $this->persister->contains($collection, $element);
    }

    /**
     * {@inheritdoc}
     */
    public function containsKey(PersistentCollection $collection, $key)
    {
        return $this->persister->containsKey($collection, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function count(PersistentCollection $collection)
    {
        $fieldName = $this->association->getName();
        $ownerId   = $this->uow->getEntityIdentifier($collection->getOwner());
        $key       = new CollectionCacheKey($this->sourceEntity->getRootClassName(), $fieldName, $ownerId);
        $entry     = $this->region->get($key);

        if ($entry !== null) {
            return count($entry->identifiers);
        }

        return $this->persister->count($collection);
    }

    /**
     * {@inheritdoc}
     */
    public function get(PersistentCollection $collection, $index)
    {
        return $this->persister->get($collection, $index);
    }

    /**
     * {@inheritdoc}
     */
    public function removeElement(PersistentCollection $collection, $element)
    {
        $persisterResult = $this->persister->removeElement($collection, $element);

        if ($persisterResult) {
            $this->evictCollectionCache($collection);
            $this->evictElementCache($this->sourceEntity->getRootClassName(), $collection->getOwner());
            $this->evictElementCache($this->targetEntity->getRootClassName(), $element);
        }

        return $persisterResult;
    }

    /**
     * {@inheritdoc}
     */
    public function slice(PersistentCollection $collection, $offset, $length = null)
    {
        return $this->persister->slice($collection, $offset, $length);
    }

    /**
     * {@inheritDoc}
     */
    public function loadCriteria(PersistentCollection $collection, Criteria $criteria)
    {
        return $this->persister->loadCriteria($collection, $criteria);
    }

    /**
     * Clears cache entries related to the current collection
     */
    protected function evictCollectionCache(PersistentCollection $collection)
    {
        $key = new CollectionCacheKey(
            $this->sourceEntity->getRootClassName(),
            $this->association->getName(),
            $this->uow->getEntityIdentifier($collection->getOwner())
        );

        $this->region->evict($key);

        if ($this->cacheLogger) {
            $this->cacheLogger->collectionCachePut($this->regionName, $key);
        }
    }

    /**
     * @param string $targetEntity
     * @param object $element
     */
    protected function evictElementCache($targetEntity, $element)
    {
        /** @var CachedEntityPersister $targetPersister */
        $targetPersister = $this->uow->getEntityPersister($targetEntity);
        $targetRegion    = $targetPersister->getCacheRegion();
        $key             = new EntityCacheKey($targetEntity, $this->uow->getEntityIdentifier($element));

        $targetRegion->evict($key);

        if ($this->cacheLogger) {
            $this->cacheLogger->entityCachePut($targetRegion->getName(), $key);
        }
    }
}
