<?php


declare(strict_types=1);

namespace Doctrine\ORM\Cache\Persister\Entity;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Cache;
use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\Persister\CachedPersister;
use Doctrine\ORM\Cache\QueryCacheKey;
use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\Cache\TimestampCacheKey;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FetchMode;
use Doctrine\ORM\Mapping\ManyToManyAssociationMetadata;
use Doctrine\ORM\Mapping\OneToManyAssociationMetadata;
use Doctrine\ORM\Mapping\ToOneAssociationMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\Utility\StaticClassNameConverter;

/**
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 * @since 2.5
 */
abstract class AbstractEntityPersister implements CachedEntityPersister
{
     /**
     * @var \Doctrine\ORM\UnitOfWork
     */
    protected $uow;

    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadataFactory
     */
    protected $metadataFactory;

    /**
     * @var \Doctrine\ORM\Persisters\Entity\EntityPersister
     */
    protected $persister;

    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadata
     */
    protected $class;

     /**
     * @var array
     */
    protected $queuedCache = [];

    /**
     * @var \Doctrine\ORM\Cache\Region
     */
    protected $region;

    /**
     * @var \Doctrine\ORM\Cache\TimestampRegion
     */
    protected $timestampRegion;

    /**
     * @var \Doctrine\ORM\Cache\TimestampCacheKey
     */
    protected $timestampKey;

    /**
     * @var \Doctrine\ORM\Cache\EntityHydrator
     */
    protected $hydrator;

    /**
     * @var \Doctrine\ORM\Cache
     */
    protected $cache;

    /**
     * @var \Doctrine\ORM\Cache\Logging\CacheLogger
     */
    protected $cacheLogger;

    /**
     * @var string
     */
    protected $regionName;

    /**
     * Associations configured as FetchMode::EAGER, as well as all inverse one-to-one associations.
     *
     * @var array|null
     */
    protected $joinedAssociations;

    /**
     * @param \Doctrine\ORM\Persisters\Entity\EntityPersister $persister The entity persister to cache.
     * @param \Doctrine\ORM\Cache\Region                      $region    The entity cache region.
     * @param \Doctrine\ORM\EntityManagerInterface            $em        The entity manager.
     * @param \Doctrine\ORM\Mapping\ClassMetadata             $class     The entity metadata.
     */
    public function __construct(EntityPersister $persister, Region $region, EntityManagerInterface $em, ClassMetadata $class)
    {
        $configuration  = $em->getConfiguration();
        $cacheConfig    = $configuration->getSecondLevelCacheConfiguration();
        $cacheFactory   = $cacheConfig->getCacheFactory();

        $this->class            = $class;
        $this->region           = $region;
        $this->persister        = $persister;
        $this->cache            = $em->getCache();
        $this->regionName       = $region->getName();
        $this->uow              = $em->getUnitOfWork();
        $this->metadataFactory  = $em->getMetadataFactory();
        $this->cacheLogger      = $cacheConfig->getCacheLogger();
        $this->timestampRegion  = $cacheFactory->getTimestampRegion();
        $this->hydrator         = $cacheFactory->buildEntityHydrator($em, $class);
        $this->timestampKey     = new TimestampCacheKey($this->class->getRootClassName());
    }

    /**
     * {@inheritdoc}
     */
    public function getSelectSQL(
        $criteria,
        AssociationMetadata $association = null,
        $lockMode = null,
        $limit = null,
        $offset = null,
        array $orderBy = []
    ) {
        return $this->persister->getSelectSQL($criteria, $association, $lockMode, $limit, $offset, $orderBy);
    }

    /**
     * {@inheritDoc}
     */
    public function getCountSQL($criteria = [])
    {
        return $this->persister->getCountSQL($criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function getInsertSQL()
    {
        return $this->persister->getInsertSQL();
    }

    /**
     * {@inheritdoc}
     */
    public function getResultSetMapping()
    {
        return $this->persister->getResultSetMapping();
    }

    /**
     * {@inheritdoc}
     */
    public function getSelectConditionStatementSQL(
        $field,
        $value,
        AssociationMetadata $association = null,
        $comparison = null
    ) {
        return $this->persister->getSelectConditionStatementSQL($field, $value, $association, $comparison);
    }

    /**
     * {@inheritdoc}
     */
    public function exists($entity, Criteria $extraConditions = null)
    {
        if ($extraConditions === null) {
            $key = new EntityCacheKey($this->class->getRootClassName(), $this->getIdentifier($entity));

            if ($this->region->contains($key)) {
                return true;
            }
        }

        return $this->persister->exists($entity, $extraConditions);
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheRegion()
    {
        return $this->region;
    }

    /**
     * @return \Doctrine\ORM\Cache\EntityHydrator
     */
    public function getEntityHydrator()
    {
        return $this->hydrator;
    }

    /**
     * {@inheritdoc}
     */
    public function storeEntityCache($entity, EntityCacheKey $key)
    {
        $class      = $this->class;
        $className  = StaticClassNameConverter::getClass($entity);

        if ($className !== $this->class->getClassName()) {
            $class = $this->metadataFactory->getMetadataFor($className);
        }

        $entry  = $this->hydrator->buildCacheEntry($class, $key, $entity);
        $cached = $this->region->put($key, $entry);

        if ($this->cacheLogger && $cached) {
            $this->cacheLogger->entityCachePut($this->regionName, $key);
        }

        return $cached;
    }

    /**
     * @param object $entity
     */
    private function storeJoinedAssociations($entity)
    {
        if ($this->joinedAssociations === null) {
            $associations = [];

            foreach ($this->class->getDeclaredPropertiesIterator() as $association) {
                if ($association instanceof ToOneAssociationMetadata &&
                    $association->getCache() &&
                    ($association->getFetchMode() === FetchMode::EAGER || ! $association->isOwningSide())) {
                    $associations[] = $association->getName();
                }
            }

            $this->joinedAssociations = $associations;
        }

        foreach ($this->joinedAssociations as $name) {
            $association  = $this->class->getProperty($name);
            $assocEntity  = $association->getValue($entity);
            $targetEntity = $association->getTargetEntity();

            if ($assocEntity === null) {
                continue;
            }

            $assocId        = $this->uow->getEntityIdentifier($assocEntity);
            $assocMetadata  = $this->metadataFactory->getMetadataFor($targetEntity);
            $assocKey       = new EntityCacheKey($assocMetadata->getRootClassName(), $assocId);
            $assocPersister = $this->uow->getEntityPersister($targetEntity);

            $assocPersister->storeEntityCache($assocEntity, $assocKey);
        }
    }

    /**
     * Generates a string of currently query
     *
     * @param array   $query
     * @param string  $criteria
     * @param array   $orderBy
     * @param integer $limit
     * @param integer $offset
     *
     * @return string
     */
    protected function getHash($query, $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        list($params) = ($criteria instanceof Criteria)
            ? $this->persister->expandCriteriaParameters($criteria)
            : $this->persister->expandParameters($criteria);

        return sha1($query . serialize($params) . serialize($orderBy) . $limit . $offset);
    }

    /**
     * {@inheritdoc}
     */
    public function expandParameters($criteria)
    {
        return $this->persister->expandParameters($criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function expandCriteriaParameters(Criteria $criteria)
    {
        return $this->persister->expandCriteriaParameters($criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function getClassMetadata()
    {
        return $this->persister->getClassMetadata();
    }

    /**
     * {@inheritdoc}
     */
    public function getManyToManyCollection(
        ManyToManyAssociationMetadata $association,
        $sourceEntity,
        $offset = null,
        $limit = null
    ) {
        return $this->persister->getManyToManyCollection($association, $sourceEntity, $offset, $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function getOneToManyCollection(
        OneToManyAssociationMetadata $association,
        $sourceEntity,
        $offset = null,
        $limit = null
    ) {
        return $this->persister->getOneToManyCollection($association, $sourceEntity, $offset, $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function getOwningTable($fieldName)
    {
        return $this->persister->getOwningTable($fieldName);
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier($entity) : array
    {
        return $this->persister->getIdentifier($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function setIdentifier($entity, array $id) : void
    {
        $this->persister->setIdentifier($entity, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnValue($entity, string $columnName)
    {
        return $this->persister->getColumnValue($entity, $columnName);
    }

    /**
     * {@inheritdoc}
     */
    public function insert($entity)
    {
        $this->queuedCache['insert'][] = $entity;

        $this->persister->insert($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function load(
        array $criteria,
        $entity = null,
        AssociationMetadata $association = null,
        array $hints = [],
        $lockMode = null,
        $limit = null,
        array $orderBy = null
    ) {
        if ($entity !== null || $association !== null || ! empty($hints) || $lockMode !== null) {
            return $this->persister->load($criteria, $entity, $association, $hints, $lockMode, $limit, $orderBy);
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

        $result = $this->persister->load($criteria, $entity, $association, $hints, $lockMode, $limit, $orderBy);

        if ($result === null) {
            return null;
        }

        $cached = $queryCache->put($queryKey, $rsm, [$result]);

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
     * {@inheritdoc}
     */
    public function loadAll(array $criteria = [], array $orderBy = [], $limit = null, $offset = null)
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
     * {@inheritdoc}
     */
    public function loadById(array $identifier, $entity = null)
    {
        $cacheKey   = new EntityCacheKey($this->class->getRootClassName(), $identifier);
        $cacheEntry = $this->region->get($cacheKey);
        $class      = $this->class;

        if ($cacheEntry !== null) {
            if ($cacheEntry->class !== $this->class->getClassName()) {
                $class = $this->metadataFactory->getMetadataFor($cacheEntry->class);
            }

            if (($entity = $this->hydrator->loadCacheEntry($class, $cacheKey, $cacheEntry, $entity)) !== null) {
                if ($this->cacheLogger) {
                    $this->cacheLogger->entityCacheHit($this->regionName, $cacheKey);
                }

                return $entity;
            }
        }

        $entity = $this->persister->loadById($identifier, $entity);

        if ($entity === null) {
            return null;
        }

        $class      = $this->class;
        $className  = StaticClassNameConverter::getClass($entity);

        if ($className !== $this->class->getClassName()) {
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
     * {@inheritdoc}
     */
    public function loadCriteria(Criteria $criteria)
    {
        $orderBy     = $criteria->getOrderings();
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
     * {@inheritdoc}
     */
    public function loadManyToManyCollection(
        ManyToManyAssociationMetadata $association,
        $sourceEntity,
        PersistentCollection $collection
    ) {
        $persister = $this->uow->getCollectionPersister($association);
        $hasCache  = ($persister instanceof CachedPersister);
        $key       = null;

        if ( ! $hasCache) {
            return $this->persister->loadManyToManyCollection($association, $sourceEntity, $collection);
        }

        $ownerId = $this->uow->getEntityIdentifier($collection->getOwner());
        $key     = $this->buildCollectionCacheKey($association, $ownerId);
        $list    = $persister->loadCollectionCache($collection, $key);

        if ($list !== null) {
            if ($this->cacheLogger) {
                $this->cacheLogger->collectionCacheHit($persister->getCacheRegion()->getName(), $key);
            }

            return $list;
        }

        $list = $this->persister->loadManyToManyCollection($association, $sourceEntity, $collection);

        $persister->storeCollectionCache($key, $list);

        if ($this->cacheLogger) {
            $this->cacheLogger->collectionCacheMiss($persister->getCacheRegion()->getName(), $key);
        }

        return $list;
    }

    /**
     * {@inheritdoc}
     */
    public function loadOneToManyCollection(
        OneToManyAssociationMetadata $association,
        $sourceEntity,
        PersistentCollection $collection
    ) {
        $persister = $this->uow->getCollectionPersister($association);
        $hasCache  = ($persister instanceof CachedPersister);

        if ( ! $hasCache) {
            return $this->persister->loadOneToManyCollection($association, $sourceEntity, $collection);
        }

        $ownerId = $this->uow->getEntityIdentifier($collection->getOwner());
        $key     = $this->buildCollectionCacheKey($association, $ownerId);
        $list    = $persister->loadCollectionCache($collection, $key);

        if ($list !== null) {
            if ($this->cacheLogger) {
                $this->cacheLogger->collectionCacheHit($persister->getCacheRegion()->getName(), $key);
            }

            return $list;
        }

        $list = $this->persister->loadOneToManyCollection($association, $sourceEntity, $collection);

        $persister->storeCollectionCache($key, $list);

        if ($this->cacheLogger) {
            $this->cacheLogger->collectionCacheMiss($persister->getCacheRegion()->getName(), $key);
        }

        return $list;
    }

    /**
     * {@inheritdoc}
     */
    public function loadToOneEntity(ToOneAssociationMetadata $association, $sourceEntity, array $identifier = [])
    {
        return $this->persister->loadToOneEntity($association, $sourceEntity, $identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function lock(array $criteria, $lockMode)
    {
        $this->persister->lock($criteria, $lockMode);
    }

    /**
     * {@inheritdoc}
     */
    public function refresh(array $id, $entity, $lockMode = null)
    {
        $this->persister->refresh($id, $entity, $lockMode);
    }

    /**
     * @param array $association
     * @param array $ownerId
     *
     * @return CollectionCacheKey
     */
    protected function buildCollectionCacheKey(AssociationMetadata $association, $ownerId)
    {
        /** @var ClassMetadata $metadata */
        $metadata = $this->metadataFactory->getMetadataFor($association->getSourceEntity());

        return new CollectionCacheKey($metadata->getRootClassName(), $association->getName(), $ownerId);
    }
}
