<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Persister\Collection;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Cache\CollectionHydrator;
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

abstract class AbstractCollectionPersister implements CachedCollectionPersister
{
    protected UnitOfWork $uow;
    protected ClassMetadataFactory $metadataFactory;
    protected ClassMetadata $sourceEntity;
    protected ClassMetadata $targetEntity;

    /** @var mixed[] */
    protected array $queuedCache = [];

    protected string $regionName;
    protected CollectionHydrator $hydrator;
    protected CacheLogger|null $cacheLogger;

    /** @param mixed[] $association The association mapping. */
    public function __construct(
        protected CollectionPersister $persister,
        protected Region $region,
        EntityManagerInterface $em,
        protected array $association,
    ) {
        $configuration = $em->getConfiguration();
        $cacheConfig   = $configuration->getSecondLevelCacheConfiguration();
        $cacheFactory  = $cacheConfig->getCacheFactory();

        $this->regionName      = $region->getName();
        $this->uow             = $em->getUnitOfWork();
        $this->metadataFactory = $em->getMetadataFactory();
        $this->cacheLogger     = $cacheConfig->getCacheLogger();
        $this->hydrator        = $cacheFactory->buildCollectionHydrator($em, $association);
        $this->sourceEntity    = $em->getClassMetadata($association['sourceEntity']);
        $this->targetEntity    = $em->getClassMetadata($association['targetEntity']);
    }

    public function getCacheRegion(): Region
    {
        return $this->region;
    }

    public function getSourceEntityMetadata(): ClassMetadata
    {
        return $this->sourceEntity;
    }

    public function getTargetEntityMetadata(): ClassMetadata
    {
        return $this->targetEntity;
    }

    public function loadCollectionCache(PersistentCollection $collection, CollectionCacheKey $key): array|null
    {
        $cache = $this->region->get($key);

        if ($cache === null) {
            return null;
        }

        return $this->hydrator->loadCacheEntry($this->sourceEntity, $key, $cache, $collection);
    }

    public function storeCollectionCache(CollectionCacheKey $key, Collection|array $elements): void
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

        if ($this->region->put($key, $entry)) {
            $this->cacheLogger?->collectionCachePut($this->regionName, $key);
        }
    }

    public function contains(PersistentCollection $collection, object $element): bool
    {
        return $this->persister->contains($collection, $element);
    }

    public function containsKey(PersistentCollection $collection, mixed $key): bool
    {
        return $this->persister->containsKey($collection, $key);
    }

    public function count(PersistentCollection $collection): int
    {
        $ownerId = $this->uow->getEntityIdentifier($collection->getOwner());
        $key     = new CollectionCacheKey($this->sourceEntity->rootEntityName, $this->association['fieldName'], $ownerId);
        $entry   = $this->region->get($key);

        if ($entry !== null) {
            return count($entry->identifiers);
        }

        return $this->persister->count($collection);
    }

    public function get(PersistentCollection $collection, mixed $index): mixed
    {
        return $this->persister->get($collection, $index);
    }

    /**
     * {@inheritdoc}
     */
    public function slice(PersistentCollection $collection, int $offset, int|null $length = null): array
    {
        return $this->persister->slice($collection, $offset, $length);
    }

    /**
     * {@inheritDoc}
     */
    public function loadCriteria(PersistentCollection $collection, Criteria $criteria): array
    {
        return $this->persister->loadCriteria($collection, $criteria);
    }
}
