<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Persister\Collection;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Cache\CollectionCacheVersionEntry;
use Doctrine\ORM\Cache\CollectionHydrator;
use Doctrine\ORM\Cache\Logging\CacheLogger;
use Doctrine\ORM\Cache\Persister\Entity\CachedEntityPersister;
use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Persisters\Collection\CollectionPersister;
use Doctrine\ORM\Proxy\DefaultProxyClassNameResolver;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\ORM\UnitOfWork;
use WeakMap;

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
    protected FilterCollection $filters;
    protected CollectionHydrator $hydrator;
    protected CacheLogger|null $cacheLogger;

    /** @var WeakMap<object, string> Filter contexts indexed by collection identity. */
    protected WeakMap $collectionFilterHashes;

    public function __construct(
        protected CollectionPersister $persister,
        protected Region $region,
        EntityManagerInterface $em,
        protected AssociationMapping $association,
    ) {
        $configuration = $em->getConfiguration();
        $cacheConfig   = $configuration->getSecondLevelCacheConfiguration();
        $cacheFactory  = $cacheConfig->getCacheFactory();

        $this->region                 = $region;
        $this->persister              = $persister;
        $this->association            = $association;
        $this->filters                = $em->getFilters();
        $this->regionName             = $region->getName();
        $this->uow                    = $em->getUnitOfWork();
        $this->metadataFactory        = $em->getMetadataFactory();
        $this->cacheLogger            = $cacheConfig->getCacheLogger();
        $this->hydrator               = $cacheFactory->buildCollectionHydrator($em, $association);
        $this->sourceEntity           = $em->getClassMetadata($association->sourceEntity);
        $this->targetEntity           = $em->getClassMetadata($association->targetEntity);
        $this->collectionFilterHashes = new WeakMap();
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
        $this->collectionFilterHashes[$collection] = $key->filterHash;
        $versionedKey                              = $this->getVersionedCacheKey($key);
        if ($versionedKey === null) {
            return null;
        }

        $cache = $this->region->get($versionedKey);

        if ($cache === null) {
            return null;
        }

        return $this->hydrator->loadCacheEntry($this->sourceEntity, $key, $cache, $collection);
    }

    public function storeCollectionCache(CollectionCacheKey $key, Collection|array $elements): void
    {
        $versionedKey = $this->getVersionedCacheKey($key);
        if ($versionedKey === null) {
            return;
        }

        $associationMapping = $this->sourceEntity->associationMappings[$key->association];
        $targetPersister    = $this->uow->getEntityPersister($this->targetEntity->rootEntityName);
        assert($targetPersister instanceof CachedEntityPersister);
        $targetRegion   = $targetPersister->getCacheRegion();
        $targetHydrator = $targetPersister->getEntityHydrator();

        // Only preserve ordering if association configured it
        if (! $associationMapping->isIndexed()) {
            // Elements may be an array or a Collection
            $elements = array_values($elements instanceof Collection ? $elements->getValues() : $elements);
        }

        $entry = $this->hydrator->buildCacheEntry($this->targetEntity, $key, $elements);

        foreach ($entry->identifiers as $index => $entityKey) {
            if ($targetRegion->contains($entityKey)) {
                continue;
            }

            $class     = $this->targetEntity;
            $className = DefaultProxyClassNameResolver::getClass($elements[$index]);

            if ($className !== $this->targetEntity->name) {
                $class = $this->metadataFactory->getMetadataFor($className);
            }

            $entity      = $elements[$index];
            $entityEntry = $targetHydrator->buildCacheEntry($class, $entityKey, $entity);

            $targetRegion->put($entityKey, $entityEntry);
        }

        if ($this->region->put($versionedKey, $entry)) {
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
        $ownerId      = $this->uow->getEntityIdentifier($collection->getOwner());
        $key          = new CollectionCacheKey($this->sourceEntity->rootEntityName, $this->association->fieldName, $ownerId, $this->filters->getHash());
        $versionedKey = $this->getVersionedCacheKey($key);
        $entry        = $versionedKey === null ? null : $this->region->get($versionedKey);

        if ($entry !== null) {
            return count($entry->identifiers);
        }

        return $this->persister->count($collection);
    }

    protected function evictCollectionCache(CollectionCacheKey $key): void
    {
        $this->region->evict(new CollectionCacheKey($key->entityClass, $key->association, $key->ownerIdentifier));
    }

    private function getVersionedCacheKey(CollectionCacheKey $key): CollectionCacheKey|null
    {
        $versionKey = new CollectionCacheKey($key->entityClass, $key->association, $key->ownerIdentifier);
        $version    = $this->region->get($versionKey);
        if (! $version instanceof CollectionCacheVersionEntry) {
            $version = new CollectionCacheVersionEntry();
            if (! $this->region->put($versionKey, $version)) {
                return null;
            }
        }

        return new CollectionCacheKey(
            $key->entityClass,
            $key->association,
            $key->ownerIdentifier,
            $key->filterHash,
            $version->version,
        );
    }

    public function get(PersistentCollection $collection, mixed $index): mixed
    {
        return $this->persister->get($collection, $index);
    }

    /**
     * {@inheritDoc}
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
