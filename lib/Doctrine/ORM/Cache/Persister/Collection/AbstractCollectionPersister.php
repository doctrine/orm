<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Persister\Collection;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Cache\CollectionHydrator;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\Logging\CacheLogger;
use Doctrine\ORM\Cache\Persister\Entity\CachedEntityPersister;
use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Persisters\Collection\CollectionPersister;
use Doctrine\ORM\UnitOfWork;

use function array_values;
use function assert;
use function count;

/** @psalm-import-type AssociationMapping from ClassMetadata */
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

    /** @var mixed[] */
    protected $association;

    /** @var mixed[] */
    protected $queuedCache = [];

    /** @var Region */
    protected $region;

    /** @var string */
    protected $regionName;

    /** @var CollectionHydrator */
    protected $hydrator;

    /** @var CacheLogger|null */
    protected $cacheLogger;

    /**
     * @param CollectionPersister    $persister   The collection persister that will be cached.
     * @param Region                 $region      The collection region.
     * @param EntityManagerInterface $em          The entity manager.
     * @param AssociationMapping     $association The association mapping.
     */
    public function __construct(CollectionPersister $persister, Region $region, EntityManagerInterface $em, array $association)
    {
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
        $this->sourceEntity    = $em->getClassMetadata($association['sourceEntity']);
        $this->targetEntity    = $em->getClassMetadata($association['targetEntity']);
    }

    /**
     * {@inheritDoc}
     */
    public function getCacheRegion()
    {
        return $this->region;
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceEntityMetadata()
    {
        return $this->sourceEntity;
    }

    /**
     * {@inheritDoc}
     */
    public function getTargetEntityMetadata()
    {
        return $this->targetEntity;
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function storeCollectionCache(CollectionCacheKey $key, $elements)
    {
        $associationMapping = $this->sourceEntity->associationMappings[$key->association];
        $targetPersister    = $this->uow->getEntityPersister($this->targetEntity->rootEntityName);
        assert($targetPersister instanceof CachedEntityPersister);
        $targetRegion   = $targetPersister->getCacheRegion();
        $targetHydrator = $targetPersister->getEntityHydrator();

        // Only preserve ordering if association configured it
        if (! (isset($associationMapping['indexBy']) && $associationMapping['indexBy'])) {
            // Elements may be an array or a Collection
            $elements = array_values($elements instanceof Collection ? $elements->getValues() : $elements);
        }

        $entry = $this->hydrator->buildCacheEntry($this->targetEntity, $key, $elements);

        foreach ($entry->identifiers as $index => $entityKey) {
            if ($targetRegion->contains($entityKey)) {
                continue;
            }

            $class     = $this->targetEntity;
            $className = ClassUtils::getClass($elements[$index]);

            if ($className !== $this->targetEntity->name) {
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
     * {@inheritDoc}
     */
    public function contains(PersistentCollection $collection, $element)
    {
        return $this->persister->contains($collection, $element);
    }

    /**
     * {@inheritDoc}
     */
    public function containsKey(PersistentCollection $collection, $key)
    {
        return $this->persister->containsKey($collection, $key);
    }

    /**
     * {@inheritDoc}
     */
    public function count(PersistentCollection $collection)
    {
        $ownerId = $this->uow->getEntityIdentifier($collection->getOwner());
        $key     = new CollectionCacheKey($this->sourceEntity->rootEntityName, $this->association['fieldName'], $ownerId);
        $entry   = $this->region->get($key);

        if ($entry !== null) {
            return count($entry->identifiers);
        }

        return $this->persister->count($collection);
    }

    /**
     * {@inheritDoc}
     */
    public function get(PersistentCollection $collection, $index)
    {
        return $this->persister->get($collection, $index);
    }

    /**
     * {@inheritDoc}
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
     *
     * @deprecated This method is not used anymore.
     *
     * @return void
     */
    protected function evictCollectionCache(PersistentCollection $collection)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9512',
            'The method %s() is deprecated and will be removed without replacement.'
        );

        $key = new CollectionCacheKey(
            $this->sourceEntity->rootEntityName,
            $this->association['fieldName'],
            $this->uow->getEntityIdentifier($collection->getOwner())
        );

        $this->region->evict($key);

        if ($this->cacheLogger) {
            $this->cacheLogger->collectionCachePut($this->regionName, $key);
        }
    }

    /**
     * @deprecated This method is not used anymore.
     *
     * @param string $targetEntity
     * @param object $element
     * @psalm-param class-string $targetEntity
     *
     * @return void
     */
    protected function evictElementCache($targetEntity, $element)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9512',
            'The method %s() is deprecated and will be removed without replacement.'
        );

        $targetPersister = $this->uow->getEntityPersister($targetEntity);
        assert($targetPersister instanceof CachedEntityPersister);
        $targetRegion = $targetPersister->getCacheRegion();
        $key          = new EntityCacheKey($targetEntity, $this->uow->getEntityIdentifier($element));

        $targetRegion->evict($key);

        if ($this->cacheLogger) {
            $this->cacheLogger->entityCachePut($targetRegion->getName(), $key);
        }
    }
}
