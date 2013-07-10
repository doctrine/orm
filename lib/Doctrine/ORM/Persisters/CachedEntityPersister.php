<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Persisters;

use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Cache\ConcurrentRegionAccess;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Collections\Criteria;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 * @since 2.5
 */
class CachedEntityPersister implements CachedPersister, EntityPersister
{
     /**
     * @var \Doctrine\ORM\UnitOfWork
     */
    private $uow;

    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadataFactory
     */
    private $metadataFactory;

    /**
     * @var \Doctrine\ORM\Persisters\EntityPersister
     */
    private $persister;

    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadata
     */
    protected $class;

     /**
     * @var array
     */
    protected $queuedCache = array();

    /**
     * @var boolean
     */
    private $isConcurrentRegion = false;

    /**
     * @var \Doctrine\ORM\Cache\RegionAccess|Doctrine\ORM\Cache\ConcurrentRegionAccess
     */
    protected $cacheRegionAccess;

    /**
     * @var \Doctrine\ORM\Cache\EntityEntryStructure
     */
    protected $cacheEntryStructure;

    /**
     * @var \Doctrine\ORM\Cache\Logging\CacheLogger
     */
    protected $cacheLogger;

    public function __construct(EntityPersister $persister, EntityManagerInterface $em, ClassMetadata $class)
    {
        $config  = $em->getConfiguration();
        $factory = $config->getSecondLevelCacheFactory();

        $this->class                = $class;
        $this->persister            = $persister;
        $this->uow                  = $em->getUnitOfWork();
        $this->metadataFactory      = $em->getMetadataFactory();
        $this->cacheLogger          = $config->getSecondLevelCacheLogger();
        $this->cacheEntryStructure  = $factory->buildEntityEntryStructure($em);
        $this->cacheRegionAccess    = $factory->buildEntityRegionAccessStrategy($this->class);
        $this->isConcurrentRegion   = ($this->cacheRegionAccess instanceof ConcurrentRegionAccess);
    }

    /**
     * {@inheritdoc}
     */
    public function addInsert($entity)
    {
        $this->persister->addInsert($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getInserts()
    {
        return $this->persister->getInserts();
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
    public function getSelectConditionStatementSQL($field, $value, $assoc = null, $comparison = null)
    {
        return $this->persister->getSelectConditionStatementSQL($field, $value, $assoc, $comparison);
    }

    /**
     * {@inheritdoc}
     */
    public function afterTransactionComplete()
    {
        if (isset($this->queuedCache['insert'])) {
            foreach ($this->queuedCache['insert'] as $item) {

                $class      = $this->class;
                $className  = ClassUtils::getClass($item['entity']);

                if ($className !== $this->class->name) {
                    $class = $this->metadataFactory->getMetadataFor($className);
                }

                $key    = $item['key'] ?: new EntityCacheKey($class->rootEntityName, $this->uow->getEntityIdentifier($item['entity']));
                $entry  = $this->cacheEntryStructure->buildCacheEntry($class, $key, $item['entity']);
                $cached = $this->cacheRegionAccess->afterInsert($key, $entry);

                if ($this->cacheLogger && $cached) {
                    $this->cacheLogger->entityCachePut($this->cacheRegionAccess->getRegion()->getName(), $key);
                }
            }
        }

        if (isset($this->queuedCache['update'])) {
            foreach ($this->queuedCache['update'] as $item) {

                $class      = $this->class;
                $className  = ClassUtils::getClass($item['entity']);

                if ($className !== $this->class->name) {
                    $class = $this->metadataFactory->getMetadataFor($className);
                }

                $key    = $item['key'] ?: new EntityCacheKey($class->rootEntityName, $this->uow->getEntityIdentifier($item['entity']));
                $entry  = $this->cacheEntryStructure->buildCacheEntry($class, $key, $item['entity']);
                $cached = $this->cacheRegionAccess->afterUpdate($key, $entry);

                if ($this->cacheLogger && $cached) {
                    $this->cacheLogger->entityCachePut($this->cacheRegionAccess->getRegion()->getName(), $key);
                }

                if ($item['lock'] !== null) {
                    $this->cacheRegionAccess->unlockItem($key, $item['lock']);
                }
            }
        }

        if (isset($this->queuedCache['delete'])) {
            foreach ($this->queuedCache['delete'] as $item) {
                $this->cacheRegionAccess->evict($item['key']);
            }
        }

        $this->queuedCache = array();
    }

    /**
     * {@inheritdoc}
     */
    public function afterTransactionRolledBack()
    {
        if ( ! $this->isConcurrentRegion) {
            $this->queuedCache = array();

            return;
        }

        if (isset($this->queuedCache['update'])) {
            foreach ($this->queuedCache['update'] as $item) {
                $this->cacheRegionAccess->unlockItem($item['key'], $item['lock']);
            }
        }

        if (isset($this->queuedCache['delete'])) {
            foreach ($this->queuedCache['delete'] as $item) {
                $this->cacheRegionAccess->unlockItem($item['key'], $item['lock']);
            }
        }

        $this->queuedCache = array();
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity)
    {
        $key   = new EntityCacheKey($this->class->rootEntityName, $this->uow->getEntityIdentifier($entity));
        $lock  = null;

        if ($this->isConcurrentRegion) {
            $lock = $this->cacheRegionAccess->lockItem($key);
        }

        $this->persister->delete($entity);

        $this->queuedCache['delete'][] = array(
            'entity' => $entity,
            'lock'   => $lock,
            'key'    => $key
        );
    }

    /**
     * {@inheritdoc}
     */
    public function update($entity)
    {
        $key   = null;
        $lock  = null;

        if ($this->isConcurrentRegion) {
            $key  = new EntityCacheKey($this->class->rootEntityName, $this->uow->getEntityIdentifier($entity));
            $lock = $this->cacheRegionAccess->lockItem($key);
        }

        $this->persister->update($entity);

        $this->queuedCache['update'][] = array(
            'entity' => $entity,
            'lock'   => $lock,
            'key'    => $key
        );
    }

    /**
     * {@inheritdoc}
     */
    public function executeInserts()
    {
        foreach ($this->persister->getInserts() as $entity) {
            $this->queuedCache['insert'][] = array(
                'entity' => $entity,
                'lock'   => null,
                'key'    => null
            );
        }

        return $this->persister->executeInserts();
    }

    /**
     * {@inheritdoc}
     */
    public function exists($entity, array $extraConditions = array())
    {
        if (empty($extraConditions)) {

            $region = $this->cacheRegionAccess->getRegion();
            $key    = new EntityCacheKey($this->class->rootEntityName, $this->uow->getEntityIdentifier($entity));

            if ($region->contains($key)) {
                return true;
            }
        }

        return $this->persister->exists($entity, $extraConditions);
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheRegionAcess()
    {
        return $this->cacheRegionAccess;
    }

    public function getCacheEntryStructure()
    {
        return $this->cacheEntryStructure;
    }

    public function putEntityCache($entity, EntityCacheKey $key)
    {
        $class      = $this->class;
        $className  = ClassUtils::getClass($entity);

        if ($className !== $this->class->name) {
            $class = $this->metadataFactory->getMetadataFor($className);
        }

        $entry  = $this->cacheEntryStructure->buildCacheEntry($class, $key, $entity);
        $cached = $this->cacheRegionAccess->put($key, $entry);

        if ($this->cacheLogger && $cached) {
            $this->cacheLogger->entityCachePut($this->cacheRegionAccess->getRegion()->getName(), $key);
        }

        return $cached;
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
    public function getManyToManyCollection(array $assoc, $sourceEntity, $offset = null, $limit = null)
    {
        return $this->persister->getManyToManyCollection($assoc, $sourceEntity, $offset, $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function getOneToManyCollection(array $assoc, $sourceEntity, $offset = null, $limit = null)
    {
        return $this->persister->getOneToManyCollection($assoc, $sourceEntity, $offset, $limit);
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
    public function load(array $criteria, $entity = null, $assoc = null, array $hints = array(), $lockMode = 0, $limit = null, array $orderBy = null)
    {
        return $this->persister->load($criteria, $entity, $assoc, $hints, $lockMode, $limit, $orderBy);
    }

    /**
     * {@inheritdoc}
     */
    public function loadAll(array $criteria = array(), array $orderBy = null, $limit = null, $offset = null)
    {
        return $this->persister->loadAll($criteria, $orderBy, $limit, $offset);
    }

    /**
     * {@inheritdoc}
     */
    public function loadById(array $identifier, $entity = null)
    {
        $cacheKey   = new EntityCacheKey($this->class->rootEntityName, $identifier);
        $cacheEntry = $this->cacheRegionAccess->get($cacheKey);

        if ($cacheEntry !== null && ($entity = $this->cacheEntryStructure->loadCacheEntry($this->class, $cacheKey, $cacheEntry, $entity)) !== null) {

            if ($this->cacheLogger) {
                $this->cacheLogger->entityCacheHit($this->cacheRegionAccess->getRegion()->getName(), $cacheKey);
            }

            return $entity;
        }

        $entity = $this->persister->loadById($identifier, $entity);

        if ($entity === null) {
            return null;
        }

        $class      = $this->class;
        $className  = ClassUtils::getClass($entity);

        if ($className !== $this->class->name) {
            $class = $this->metadataFactory->getMetadataFor($className);
        }

        $cacheEntry = $this->cacheEntryStructure->buildCacheEntry($class, $cacheKey, $entity);
        $cached     = $this->cacheRegionAccess->put($cacheKey, $cacheEntry);

        if ($this->cacheLogger && $cached) {
            $this->cacheLogger->entityCachePut($this->cacheRegionAccess->getRegion()->getName(), $cacheKey);
        }

        if ($this->cacheLogger) {
            $this->cacheLogger->entityCacheMiss($this->cacheRegionAccess->getRegion()->getName(), $cacheKey);
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function loadCriteria(Criteria $criteria)
    {
        return $this->persister->loadCriteria($criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function loadManyToManyCollection(array $assoc, $sourceEntity, PersistentCollection $coll)
    {
        $persister = $this->uow->getCollectionPersister($assoc);
        $hasCache  = ($persister instanceof CachedPersister);
        $key       = null;

        if ($hasCache) {
            $ownerId = $this->uow->getEntityIdentifier($coll->getOwner());
            $key     = new CollectionCacheKey($assoc['sourceEntity'], $assoc['fieldName'], $ownerId);
            $list    = $persister->loadCollectionCache($coll, $key);

            if ($list !== null) {

                if ($this->cacheLogger) {
                    $this->cacheLogger->collectionCacheHit($persister->getCacheRegionAcess()->getRegion()->getName(), $key);
                }

                return $list;
            }
        }

        $list = $this->persister->loadManyToManyCollection($assoc, $sourceEntity, $coll);

        if ($hasCache && ! empty($list)) {
            $persister->saveCollectionCache($key, $list);

            if ($this->cacheLogger) {
                $this->cacheLogger->collectionCacheMiss($persister->getCacheRegionAcess()->getRegion()->getName(), $key);
            }
        }

        return $list;
    }

    /**
     * {@inheritdoc}
     */
    public function loadOneToManyCollection(array $assoc, $sourceEntity, PersistentCollection $coll)
    {
        $persister = $this->uow->getCollectionPersister($assoc);
        $hasCache  = ($persister instanceof CachedPersister);

        if ($hasCache) {
            $ownerId = $this->uow->getEntityIdentifier($coll->getOwner());
            $key     = new CollectionCacheKey($assoc['sourceEntity'], $assoc['fieldName'], $ownerId);
            $list    = $persister->loadCollectionCache($coll, $key);

            if ($list !== null) {

                if ($this->cacheLogger) {
                    $this->cacheLogger->collectionCacheHit($persister->getCacheRegionAcess()->getRegion()->getName(), $key);
                }

                return $list;
            }
        }

        $list = $this->persister->loadOneToManyCollection($assoc, $sourceEntity, $coll);

        if ($hasCache && ! empty($list)) {
            $persister->saveCollectionCache($key, $list);

            if ($this->cacheLogger) {
                $this->cacheLogger->collectionCacheMiss($persister->getCacheRegionAcess()->getRegion()->getName(), $key);
            }
        }

        return $list;
    }

    /**
     * {@inheritdoc}
     */
    public function loadOneToOneEntity(array $assoc, $sourceEntity, array $identifier = array())
    {
        return $this->persister->loadOneToOneEntity($assoc, $sourceEntity, $identifier);
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
    public function refresh(array $id, $entity, $lockMode = 0)
    {
        $this->persister->refresh($id, $entity, $lockMode);
    }

}
