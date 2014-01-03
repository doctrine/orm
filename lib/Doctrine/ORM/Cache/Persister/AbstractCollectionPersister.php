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

namespace Doctrine\ORM\Cache\Persister;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Persisters\CollectionPersister;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Cache\Region;
use Doctrine\Common\Util\ClassUtils;

/**
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 * @since 2.5
 */
abstract class AbstractCollectionPersister implements CachedCollectionPersister
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
     * @var \Doctrine\ORM\Persisters\CollectionPersister
     */
    protected $persister;

    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadata
     */
    protected $sourceEntity;

    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadata
     */
    protected $targetEntity;

    /**
     * @var array
     */
    protected $association;

     /**
     * @var array
     */
    protected $queuedCache = array();

    /**
     * @var \Doctrine\ORM\Cache\Region
     */
    protected $region;

    /**
     * @var string
     */
    protected $regionName;

    /**
     * @var \Doctrine\ORM\Cache\CollectionHydrator
     */
    protected $hydrator;

    /**
     * @var \Doctrine\ORM\Cache\Logging\CacheLogger
     */
    protected $cacheLogger;

    /**
     * @param \Doctrine\ORM\Persisters\CollectionPersister $persister   The collection persister that will be cached.
     * @param \Doctrine\ORM\Cache\Region                   $region      The collection region.
     * @param \Doctrine\ORM\EntityManagerInterface         $em          The entity manager.
     * @param array                                        $association The association mapping.
     */
    public function __construct(CollectionPersister $persister, Region $region, EntityManagerInterface $em, array $association)
    {
        $configuration  = $em->getConfiguration();
        $cacheConfig    = $configuration->getSecondLevelCacheConfiguration();
        $cacheFactory   = $cacheConfig->getCacheFactory();

        $this->region           = $region;
        $this->persister        = $persister;
        $this->association      = $association;
        $this->regionName       = $region->getName();
        $this->uow              = $em->getUnitOfWork();
        $this->metadataFactory  = $em->getMetadataFactory();
        $this->cacheLogger      = $cacheConfig->getCacheLogger();
        $this->hydrator         = $cacheFactory->buildCollectionHydrator($em, $association);
        $this->sourceEntity     = $em->getClassMetadata($association['sourceEntity']);
        $this->targetEntity     = $em->getClassMetadata($association['targetEntity']);
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
     * @param \Doctrine\ORM\PersistentCollection     $collection
     * @param \Doctrine\ORM\Cache\CollectionCacheKey $key
     *
     * @return \Doctrine\ORM\PersistentCollection|null
     */
    public function loadCollectionCache(PersistentCollection $collection, CollectionCacheKey $key)
    {
        if (($cache = $this->region->get($key)) === null) {
            return null;
        }

        if (($cache = $this->hydrator->loadCacheEntry($this->sourceEntity, $key, $cache, $collection)) === null) {
            return null;
        }

        return $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function storeCollectionCache(CollectionCacheKey $key, $elements)
    {
        $targetPersister    = $this->uow->getEntityPersister($this->targetEntity->rootEntityName);
        $targetRegion       = $targetPersister->getCacheRegion();
        $targetHydrator     = $targetPersister->getEntityHydrator();
        $entry              = $this->hydrator->buildCacheEntry($this->targetEntity, $key, $elements);

        foreach ($entry->identifiers as $index => $identifier) {
            $entityKey = new EntityCacheKey($this->targetEntity->rootEntityName, $identifier);

            if ($targetRegion->contains($entityKey)) {
                continue;
            }

            $class      = $this->targetEntity;
            $className  = ClassUtils::getClass($elements[$index]);

            if ($className !== $this->targetEntity->name) {
                $class = $this->metadataFactory->getMetadataFor($className);
            }

            $entity       = $elements[$index];
            $entityEntry  = $targetHydrator->buildCacheEntry($class, $entityKey, $entity);

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
        $ownerId = $this->uow->getEntityIdentifier($collection->getOwner());
        $key     = new CollectionCacheKey($this->sourceEntity->rootEntityName, $this->association['fieldName'], $ownerId);
        $entry   = $this->region->get($key);

        if ($entry !== null) {
            return count($entry->identifiers);
        }

        return $this->persister->count($collection);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteRows(PersistentCollection $collection)
    {
        $this->persister->deleteRows($collection);
    }

    /**
     * {@inheritdoc}
     */
    public function insertRows(PersistentCollection $collection)
    {
        $this->persister->insertRows($collection);
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
        return $this->persister->removeElement($collection, $element);
    }

    /**
     * {@inheritdoc}
     */
    public function removeKey(PersistentCollection $collection, $key)
    {
        return $this->persister->removeKey($collection, $key);
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
}
