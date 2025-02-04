<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Persister\Entity;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Cache;
use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\EntityHydrator;
use Doctrine\ORM\Cache\Logging\CacheLogger;
use Doctrine\ORM\Cache\Persister\CachedPersister;
use Doctrine\ORM\Cache\QueryCacheKey;
use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\Cache\TimestampCacheKey;
use Doctrine\ORM\Cache\TimestampRegion;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\CriteriaOrderings;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\Proxy\DefaultProxyClassNameResolver;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\ORM\UnitOfWork;

use function array_merge;
use function func_get_args;
use function serialize;
use function sha1;

abstract class AbstractEntityPersister implements CachedEntityPersister
{
    use CriteriaOrderings;

     /** @var UnitOfWork */
    protected $uow;

    /** @var ClassMetadataFactory */
    protected $metadataFactory;

    /** @var EntityPersister */
    protected $persister;

    /** @var ClassMetadata */
    protected $class;

    /** @var mixed[] */
    protected $queuedCache = [];

    /** @var Region */
    protected $region;

    /** @var TimestampRegion */
    protected $timestampRegion;

    /** @var TimestampCacheKey */
    protected $timestampKey;

    /** @var EntityHydrator */
    protected $hydrator;

    /** @var Cache */
    protected $cache;

    /** @var FilterCollection */
    protected $filters;

    /** @var CacheLogger|null */
    protected $cacheLogger;

    /** @var string */
    protected $regionName;

    /**
     * Associations configured as FETCH_EAGER, as well as all inverse one-to-one associations.
     *
     * @var array<string>|null
     */
    protected $joinedAssociations;

    /**
     * @param EntityPersister        $persister The entity persister to cache.
     * @param Region                 $region    The entity cache region.
     * @param EntityManagerInterface $em        The entity manager.
     * @param ClassMetadata          $class     The entity metadata.
     */
    public function __construct(EntityPersister $persister, Region $region, EntityManagerInterface $em, ClassMetadata $class)
    {
        $configuration = $em->getConfiguration();
        $cacheConfig   = $configuration->getSecondLevelCacheConfiguration();
        $cacheFactory  = $cacheConfig->getCacheFactory();

        $this->class           = $class;
        $this->region          = $region;
        $this->persister       = $persister;
        $this->cache           = $em->getCache();
        $this->filters         = $em->getFilters();
        $this->regionName      = $region->getName();
        $this->uow             = $em->getUnitOfWork();
        $this->metadataFactory = $em->getMetadataFactory();
        $this->cacheLogger     = $cacheConfig->getCacheLogger();
        $this->timestampRegion = $cacheFactory->getTimestampRegion();
        $this->hydrator        = $cacheFactory->buildEntityHydrator($em, $class);
        $this->timestampKey    = new TimestampCacheKey($this->class->rootEntityName);
    }

    /**
     * {@inheritDoc}
     */
    public function addInsert($entity)
    {
        $this->persister->addInsert($entity);
    }

    /**
     * {@inheritDoc}
     */
    public function getInserts()
    {
        return $this->persister->getInserts();
    }

    /**
     * {@inheritDoc}
     */
    public function getSelectSQL($criteria, $assoc = null, $lockMode = null, $limit = null, $offset = null, ?array $orderBy = null)
    {
        return $this->persister->getSelectSQL($criteria, $assoc, $lockMode, $limit, $offset, $orderBy);
    }

    /**
     * {@inheritDoc}
     */
    public function getCountSQL($criteria = [])
    {
        return $this->persister->getCountSQL($criteria);
    }

    /**
     * {@inheritDoc}
     */
    public function getInsertSQL()
    {
        return $this->persister->getInsertSQL();
    }

    /**
     * {@inheritDoc}
     */
    public function getResultSetMapping()
    {
        return $this->persister->getResultSetMapping();
    }

    /**
     * {@inheritDoc}
     */
    public function getSelectConditionStatementSQL($field, $value, $assoc = null, $comparison = null)
    {
        return $this->persister->getSelectConditionStatementSQL($field, $value, $assoc, $comparison);
    }

    /**
     * {@inheritDoc}
     */
    public function exists($entity, ?Criteria $extraConditions = null)
    {
        if ($extraConditions === null) {
            $key = new EntityCacheKey($this->class->rootEntityName, $this->class->getIdentifierValues($entity));

            if ($this->region->contains($key)) {
                return true;
            }
        }

        return $this->persister->exists($entity, $extraConditions);
    }

    /**
     * {@inheritDoc}
     */
    public function getCacheRegion()
    {
        return $this->region;
    }

    /** @return EntityHydrator */
    public function getEntityHydrator()
    {
        return $this->hydrator;
    }

    /**
     * {@inheritDoc}
     */
    public function storeEntityCache($entity, EntityCacheKey $key)
    {
        $class     = $this->class;
        $className = DefaultProxyClassNameResolver::getClass($entity);

        if ($className !== $this->class->name) {
            $class = $this->metadataFactory->getMetadataFor($className);
        }

        $entry  = $this->hydrator->buildCacheEntry($class, $key, $entity);
        $cached = $this->region->put($key, $entry);

        if ($this->cacheLogger && $cached) {
            $this->cacheLogger->entityCachePut($this->regionName, $key);
        }

        return $cached;
    }

    /** @param object $entity */
    private function storeJoinedAssociations($entity): void
    {
        if ($this->joinedAssociations === null) {
            $associations = [];

            foreach ($this->class->associationMappings as $name => $assoc) {
                if (
                    isset($assoc['cache']) &&
                    ($assoc['type'] & ClassMetadata::TO_ONE) &&
                    ($assoc['fetch'] === ClassMetadata::FETCH_EAGER || ! $assoc['isOwningSide'])
                ) {
                    $associations[] = $name;
                }
            }

            $this->joinedAssociations = $associations;
        }

        foreach ($this->joinedAssociations as $name) {
            $assoc       = $this->class->associationMappings[$name];
            $assocEntity = $this->class->getFieldValue($entity, $name);

            if ($assocEntity === null) {
                continue;
            }

            $assocId        = $this->uow->getEntityIdentifier($assocEntity);
            $assocMetadata  = $this->metadataFactory->getMetadataFor($assoc['targetEntity']);
            $assocKey       = new EntityCacheKey($assocMetadata->rootEntityName, $assocId);
            $assocPersister = $this->uow->getEntityPersister($assoc['targetEntity']);

            $assocPersister->storeEntityCache($assocEntity, $assocKey);
        }
    }

    /**
     * Generates a string of currently query
     *
     * @param string            $query
     * @param string[]|Criteria $criteria
     * @param string[]|null     $orderBy
     * @param int|null          $limit
     * @param int|null          $offset
     *
     * @return string
     */
    protected function getHash($query, $criteria, ?array $orderBy = null, $limit = null, $offset = null)
    {
        [$params] = $criteria instanceof Criteria
            ? $this->persister->expandCriteriaParameters($criteria)
            : $this->persister->expandParameters($criteria);

        return sha1($query . serialize($params) . serialize($orderBy) . $limit . $offset . $this->filters->getHash());
    }

    /**
     * {@inheritDoc}
     */
    public function expandParameters($criteria)
    {
        return $this->persister->expandParameters($criteria);
    }

    /**
     * {@inheritDoc}
     */
    public function expandCriteriaParameters(Criteria $criteria)
    {
        return $this->persister->expandCriteriaParameters($criteria);
    }

    /**
     * {@inheritDoc}
     */
    public function getClassMetadata()
    {
        return $this->persister->getClassMetadata();
    }

    /**
     * {@inheritDoc}
     */
    public function getManyToManyCollection(array $assoc, $sourceEntity, $offset = null, $limit = null)
    {
        return $this->persister->getManyToManyCollection($assoc, $sourceEntity, $offset, $limit);
    }

    /**
     * {@inheritDoc}
     */
    public function getOneToManyCollection(array $assoc, $sourceEntity, $offset = null, $limit = null)
    {
        return $this->persister->getOneToManyCollection($assoc, $sourceEntity, $offset, $limit);
    }

    /**
     * {@inheritDoc}
     */
    public function getOwningTable($fieldName)
    {
        return $this->persister->getOwningTable($fieldName);
    }

    /**
     * {@inheritDoc}
     */
    public function executeInserts()
    {
        // The commit order/foreign key relationships may make it necessary that multiple calls to executeInsert()
        // are performed, so collect all the new entities.
        $newInserts = $this->persister->getInserts();

        if ($newInserts) {
            $this->queuedCache['insert'] = array_merge($this->queuedCache['insert'] ?? [], $newInserts);
        }

        return $this->persister->executeInserts();
    }

    /**
     * {@inheritDoc}
     */
    public function load(array $criteria, $entity = null, $assoc = null, array $hints = [], $lockMode = null, $limit = null, ?array $orderBy = null)
    {
        if ($entity !== null || $assoc !== null || $hints !== [] || $lockMode !== null) {
            return $this->persister->load($criteria, $entity, $assoc, $hints, $lockMode, $limit, $orderBy);
        }

        //handle only EntityRepository#findOneBy
        $query      = $this->persister->getSelectSQL($criteria, null, null, $limit, null, $orderBy);
        $hash       = $this->getHash($query, $criteria, null, null, null);
        $rsm        = $this->getResultSetMapping();
        $queryKey   = new QueryCacheKey($hash, 0, Cache::MODE_NORMAL, $this->timestampKey);
        $queryCache = $this->cache->getQueryCache($this->regionName);
        $result     = $queryCache->get($queryKey, $rsm);

        if ($result !== null) {
            if ($this->cacheLogger) {
                $this->cacheLogger->queryCacheHit($this->regionName, $queryKey);
            }

            return $result[0];
        }

        $result = $this->persister->load($criteria, $entity, $assoc, $hints, $lockMode, $limit, $orderBy);

        if ($result === null) {
            return null;
        }

        $cached = $queryCache->put($queryKey, $rsm, [$result]);

        if ($this->cacheLogger) {
            $this->cacheLogger->queryCacheMiss($this->regionName, $queryKey);

            if ($cached) {
                $this->cacheLogger->queryCachePut($this->regionName, $queryKey);
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function loadAll(array $criteria = [], ?array $orderBy = null, $limit = null, $offset = null)
    {
        $query      = $this->persister->getSelectSQL($criteria, null, null, $limit, $offset, $orderBy);
        $hash       = $this->getHash($query, $criteria, null, null, null);
        $rsm        = $this->getResultSetMapping();
        $queryKey   = new QueryCacheKey($hash, 0, Cache::MODE_NORMAL, $this->timestampKey);
        $queryCache = $this->cache->getQueryCache($this->regionName);
        $result     = $queryCache->get($queryKey, $rsm);

        if ($result !== null) {
            if ($this->cacheLogger) {
                $this->cacheLogger->queryCacheHit($this->regionName, $queryKey);
            }

            return $result;
        }

        $result = $this->persister->loadAll($criteria, $orderBy, $limit, $offset);
        $cached = $queryCache->put($queryKey, $rsm, $result);

        if ($this->cacheLogger) {
            if ($result) {
                $this->cacheLogger->queryCacheMiss($this->regionName, $queryKey);
            }

            if ($cached) {
                $this->cacheLogger->queryCachePut($this->regionName, $queryKey);
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function loadById(array $identifier, $entity = null)
    {
        $cacheKey   = new EntityCacheKey($this->class->rootEntityName, $identifier);
        $cacheEntry = $this->region->get($cacheKey);
        $class      = $this->class;

        if ($cacheEntry !== null) {
            if ($cacheEntry->class !== $this->class->name) {
                $class = $this->metadataFactory->getMetadataFor($cacheEntry->class);
            }

            $cachedEntity = $this->hydrator->loadCacheEntry($class, $cacheKey, $cacheEntry, $entity);

            if ($cachedEntity !== null) {
                if ($this->cacheLogger) {
                    $this->cacheLogger->entityCacheHit($this->regionName, $cacheKey);
                }

                return $cachedEntity;
            }
        }

        $entity = $this->persister->loadById($identifier, $entity);

        if ($entity === null) {
            return null;
        }

        $class     = $this->class;
        $className = DefaultProxyClassNameResolver::getClass($entity);

        if ($className !== $this->class->name) {
            $class = $this->metadataFactory->getMetadataFor($className);
        }

        $cacheEntry = $this->hydrator->buildCacheEntry($class, $cacheKey, $entity);
        $cached     = $this->region->put($cacheKey, $cacheEntry);

        if ($cached && ($this->joinedAssociations === null || $this->joinedAssociations)) {
            $this->storeJoinedAssociations($entity);
        }

        if ($this->cacheLogger) {
            if ($cached) {
                $this->cacheLogger->entityCachePut($this->regionName, $cacheKey);
            }

            $this->cacheLogger->entityCacheMiss($this->regionName, $cacheKey);
        }

        return $entity;
    }

    /**
     * {@inheritDoc}
     */
    public function count($criteria = [])
    {
        return $this->persister->count($criteria);
    }

    /**
     * {@inheritDoc}
     */
    public function loadCriteria(Criteria $criteria)
    {
        $orderBy     = self::getCriteriaOrderings($criteria);
        $limit       = $criteria->getMaxResults();
        $offset      = $criteria->getFirstResult();
        $query       = $this->persister->getSelectSQL($criteria);
        $hash        = $this->getHash($query, $criteria, $orderBy, $limit, $offset);
        $rsm         = $this->getResultSetMapping();
        $queryKey    = new QueryCacheKey($hash, 0, Cache::MODE_NORMAL, $this->timestampKey);
        $queryCache  = $this->cache->getQueryCache($this->regionName);
        $cacheResult = $queryCache->get($queryKey, $rsm);

        if ($cacheResult !== null) {
            if ($this->cacheLogger) {
                $this->cacheLogger->queryCacheHit($this->regionName, $queryKey);
            }

            return $cacheResult;
        }

        $result = $this->persister->loadCriteria($criteria);
        $cached = $queryCache->put($queryKey, $rsm, $result);

        if ($this->cacheLogger) {
            if ($result) {
                $this->cacheLogger->queryCacheMiss($this->regionName, $queryKey);
            }

            if ($cached) {
                $this->cacheLogger->queryCachePut($this->regionName, $queryKey);
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function loadManyToManyCollection(array $assoc, $sourceEntity, PersistentCollection $collection)
    {
        $persister = $this->uow->getCollectionPersister($assoc);
        $hasCache  = ($persister instanceof CachedPersister);

        if (! $hasCache) {
            return $this->persister->loadManyToManyCollection($assoc, $sourceEntity, $collection);
        }

        $ownerId = $this->uow->getEntityIdentifier($collection->getOwner());
        $key     = $this->buildCollectionCacheKey($assoc, $ownerId, $this->filters->getHash());
        $list    = $persister->loadCollectionCache($collection, $key);

        if ($list !== null) {
            if ($this->cacheLogger) {
                $this->cacheLogger->collectionCacheHit($persister->getCacheRegion()->getName(), $key);
            }

            return $list;
        }

        $list = $this->persister->loadManyToManyCollection($assoc, $sourceEntity, $collection);

        $persister->storeCollectionCache($key, $list);

        if ($this->cacheLogger) {
            $this->cacheLogger->collectionCacheMiss($persister->getCacheRegion()->getName(), $key);
        }

        return $list;
    }

    /**
     * {@inheritDoc}
     */
    public function loadOneToManyCollection(array $assoc, $sourceEntity, PersistentCollection $collection)
    {
        $persister = $this->uow->getCollectionPersister($assoc);
        $hasCache  = ($persister instanceof CachedPersister);

        if (! $hasCache) {
            return $this->persister->loadOneToManyCollection($assoc, $sourceEntity, $collection);
        }

        $ownerId = $this->uow->getEntityIdentifier($collection->getOwner());
        $key     = $this->buildCollectionCacheKey($assoc, $ownerId, $this->filters->getHash());
        $list    = $persister->loadCollectionCache($collection, $key);

        if ($list !== null) {
            if ($this->cacheLogger) {
                $this->cacheLogger->collectionCacheHit($persister->getCacheRegion()->getName(), $key);
            }

            return $list;
        }

        $list = $this->persister->loadOneToManyCollection($assoc, $sourceEntity, $collection);

        $persister->storeCollectionCache($key, $list);

        if ($this->cacheLogger) {
            $this->cacheLogger->collectionCacheMiss($persister->getCacheRegion()->getName(), $key);
        }

        return $list;
    }

    /**
     * {@inheritDoc}
     */
    public function loadOneToOneEntity(array $assoc, $sourceEntity, array $identifier = [])
    {
        return $this->persister->loadOneToOneEntity($assoc, $sourceEntity, $identifier);
    }

    /**
     * {@inheritDoc}
     */
    public function lock(array $criteria, $lockMode)
    {
        $this->persister->lock($criteria, $lockMode);
    }

    /**
     * {@inheritDoc}
     */
    public function refresh(array $id, $entity, $lockMode = null)
    {
        $this->persister->refresh($id, $entity, $lockMode);
    }

    /**
     * @param array<string, mixed> $association
     * @param array<string, mixed> $ownerId
     *
     * @return CollectionCacheKey
     */
    protected function buildCollectionCacheKey(array $association, $ownerId/*, string $filterHash */)
    {
        $filterHash = (string) (func_get_args()[2] ?? ''); // todo: move to argument in next major release

        return new CollectionCacheKey(
            $this->metadataFactory->getMetadataFor($association['sourceEntity'])->rootEntityName,
            $association['fieldName'],
            $ownerId,
            $filterHash
        );
    }
}
