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
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 * @since 2.5
 */
class CachedCollectionPersister implements CachedPersister, CollectionPersister
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
     * @var \Doctrine\ORM\Persisters\CollectionPersister
     */
    private $persister;

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
     * @var boolean
     */
    private $isConcurrentRegion = false;

    /**
     * @var \Doctrine\ORM\Cache\RegionAccess|Doctrine\ORM\Cache\ConcurrentRegionAccess
     */
    protected $cacheRegionAccess;

    /**
     * @var \Doctrine\ORM\Cache\CollectionEntryStructure
     */
    protected $cacheEntryStructure;

    /**
     * @var \Doctrine\ORM\Cache\Logging\CacheLogger
     */
    protected $cacheLogger;

    public function __construct(CollectionPersister $persister, EntityManagerInterface $em, array $association)
    {
        $configuration  = $em->getConfiguration();
        $cacheFactory   = $configuration->getSecondLevelCacheFactory();

        $this->persister            = $persister;
        $this->association          = $association;
        $this->uow                  = $em->getUnitOfWork();
        $this->metadataFactory      = $em->getMetadataFactory();
        $this->sourceEntity         = $persister->getSourceEntityMetadata();
        $this->targetEntity         = $persister->getTargetEntityMetadata();
        $this->cacheLogger          = $configuration->getSecondLevelCacheLogger();
        $this->cacheEntryStructure  = $cacheFactory->buildCollectionEntryStructure($em);
        $this->isConcurrentRegion   = ($this->cacheRegionAccess instanceof ConcurrentRegionAccess);
        $this->cacheRegionAccess    = $cacheFactory->buildCollectionRegionAccessStrategy($this->sourceEntity, $association['fieldName']);
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheRegionAcess()
    {
        return $this->cacheRegionAccess;
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
     * {@inheritdoc}
     */
    public function afterTransactionComplete()
    {
        if (isset($this->queuedCache['update'])) {
            foreach ($this->queuedCache['update'] as $item) {

                $this->saveCollectionCache($item['key'], $item['list'], $item['lock']);

                if ($item['lock'] !== null) {
                    $this->cacheRegionAccess->unlockItem($item['key'], $item['lock']);
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
     * @param \Doctrine\ORM\PersistentCollection $collection
     * @param \Doctrine\ORM\Cache\CollectionCacheKey $key
     *
     * @return \Doctrine\ORM\PersistentCollection|null
     */
    public function loadCollectionCache(PersistentCollection $collection, CollectionCacheKey $key)
    {

        if (($cache = $this->cacheRegionAccess->get($key)) === null) {
            return null;
        }

        if (($cache = $this->cacheEntryStructure->loadCacheEntry($this->sourceEntity, $key, $cache, $collection)) === null) {
            return null;
        }

        return $cache;
    }

    /**
     * @param \Doctrine\ORM\Cache\CollectionCacheKey        $key
     * @param array|\Doctrine\Common\Collections\Collection $elements
     *
     * @return void
     */
    public function saveCollectionCache(CollectionCacheKey $key, $elements)
    {
        $targetPersister    = $this->uow->getEntityPersister($this->targetEntity->rootEntityName);
        $targetRegionAcess  = $targetPersister->getCacheRegionAcess();
        $targetStructure    = $targetPersister->getCacheEntryStructure();
        $targetRegion       = $targetRegionAcess->getRegion();
        $entry              = $this->cacheEntryStructure->buildCacheEntry($this->targetEntity, $key, $elements);

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
            $entityEntry  = $targetStructure->buildCacheEntry($class, $entityKey, $entity);

            $targetRegionAcess->put($entityKey, $entityEntry);
        }

        $cached = $this->cacheRegionAccess->put($key, $entry);

        if ($this->cacheLogger && $cached) {
            $this->cacheLogger->collectionCachePut($this->cacheRegionAccess->getRegion()->getName(), $key);
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
        return $this->persister->count($collection);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(PersistentCollection $collection)
    {
        $lock    = null;
        $ownerId = $this->uow->getEntityIdentifier($collection->getOwner());
        $key     = new CollectionCacheKey($this->sourceEntity->rootEntityName, $this->association['fieldName'], $ownerId);

        if ($this->isConcurrentRegion) {
            $lock = $this->cacheRegionAccess->lockItem($key);
        }

        $this->persister->delete($collection);

        $this->queuedCache['delete'][] = array(
            'list'  => null,
            'key'   => $key,
            'lock'  => $lock
        );
    }

    /**
     * {@inheritdoc}
     */
    public function update(PersistentCollection $collection)
    {
        $lock    = null;
        $ownerId = $this->uow->getEntityIdentifier($collection->getOwner());
        $key     = new CollectionCacheKey($this->sourceEntity->rootEntityName, $this->association['fieldName'], $ownerId);

        if ($this->isConcurrentRegion) {
            $lock = $this->cacheRegionAccess->lockItem($key);
        }

        $this->persister->update($collection);

        $this->queuedCache['update'][] = array(
            'list'  => $collection,
            'key'   => $key,
            'lock'  => $lock
        );
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
}
