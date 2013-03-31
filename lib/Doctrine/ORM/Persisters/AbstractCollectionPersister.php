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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Cache\ConcurrentRegionAccess;
use Doctrine\ORM\Cache\CollectionEntryStructure;

/**
 * Base class for all collection persisters.
 *
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 */
abstract class AbstractCollectionPersister
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $conn;

    /**
     * @var \Doctrine\ORM\UnitOfWork
     */
    protected $uow;

    /**
     * The database platform.
     *
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    protected $platform;
    
    /**
     * The quote strategy.
     *
     * @var \Doctrine\ORM\Mapping\QuoteStrategy
     */
    protected $quoteStrategy;

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
    protected $hasCache = false;

    /**
     * @var boolean
     */
    protected $isConcurrentRegion = false;

    /**
     * @var \Doctrine\ORM\Cache\RegionAccess|Doctrine\ORM\Cache\ConcurrentRegionAccess
     */
    protected $cacheRegionAccess;

    /**
     * @var \Doctrine\ORM\Cache\CollectionEntryStructure
     */
    protected $cacheEntryStructure;

    /**
     * Initializes a new instance of a class derived from AbstractCollectionPersister.
     *
     * @param \Doctrine\ORM\EntityManager $em
     * @param array $association
     */
    public function __construct(EntityManager $em, array $association)
    {
        $this->em               = $em;
        $this->association      = $association;
        $this->uow              = $em->getUnitOfWork();
        $this->conn             = $em->getConnection();
        $this->platform         = $this->conn->getDatabasePlatform();
        $this->quoteStrategy    = $em->getConfiguration()->getQuoteStrategy();
        $this->hasCache         = isset($association['cache']) && $em->getConfiguration()->isSecondLevelCacheEnabled();

        if ($this->hasCache) {
            $sourceClass             = $em->getClassMetadata($association['sourceEntity']);
            $this->cacheRegionAccess = $em->getConfiguration()
                ->getSecondLevelCacheAccessProvider()
                ->buildCollectionRegionAccessStrategy($sourceClass, $association['fieldName']);

            $this->cacheEntryStructure  = new CollectionEntryStructure($em);
            $this->isConcurrentRegion   = ($this->cacheRegionAccess instanceof ConcurrentRegionAccess);
        }
    }

    /**
     * @return boolean
     */
    public function hasCache()
    {
        return $this->hasCache;
    }

    /**
     * @return \Doctrine\ORM\Cache\RegionAccess
     */
    public function getCacheRegionAcess()
    {
        return $this->cacheRegionAccess;
    }

    /**
     * Deletes the persistent state represented by the given collection.
     *
     * @param \Doctrine\ORM\PersistentCollection $coll
     *
     * @return void
     */
    public function delete(PersistentCollection $coll)
    {
        $mapping = $coll->getMapping();

        if ( ! $mapping['isOwningSide']) {
            return; // ignore inverse side
        }

        $sql = $this->getDeleteSQL($coll);

        $this->conn->executeUpdate($sql, $this->getDeleteSQLParameters($coll));

        if ($this->hasCache) {
            $this->queuedCachedCollectionChange($coll->getOwner(), $coll->toArray());
        }
    }

    /**
     * Gets the SQL statement for deleting the given collection.
     *
     * @param \Doctrine\ORM\PersistentCollection $coll
     *
     * @return string
     */
    abstract protected function getDeleteSQL(PersistentCollection $coll);

    /**
     * Gets the SQL parameters for the corresponding SQL statement to delete
     * the given collection.
     *
     * @param \Doctrine\ORM\PersistentCollection $coll
     *
     * @return array
     */
    abstract protected function getDeleteSQLParameters(PersistentCollection $coll);

    /**
     * Updates the given collection, synchronizing its state with the database
     * by inserting, updating and deleting individual elements.
     *
     * @param \Doctrine\ORM\PersistentCollection $coll
     *
     * @return void
     */
    public function update(PersistentCollection $coll)
    {
        $mapping = $coll->getMapping();

        if ( ! $mapping['isOwningSide']) {
            return; // ignore inverse side
        }

        $this->deleteRows($coll);
        $this->insertRows($coll);

        if ($this->hasCache) {
            $this->queuedCachedCollectionChange($coll->getOwner(), $coll->toArray());
        }
    }

    /**
     * @param object $owner
     * @param array  $elements
     */
    public function queuedCachedCollectionChange($owner, array $elements)
    {
        $this->queuedCache[] = array(
            'owner'         => $owner,
            'elements'      => $elements,
        );
    }

    /**
     * Deletes rows.
     *
     * @param \Doctrine\ORM\PersistentCollection $coll
     *
     * @return void
     */
    public function deleteRows(PersistentCollection $coll)
    {
        $diff   = $coll->getDeleteDiff();
        $sql    = $this->getDeleteRowSQL($coll);

        foreach ($diff as $element) {
            $this->conn->executeUpdate($sql, $this->getDeleteRowSQLParameters($coll, $element));
        }
    }

    /**
     * Inserts rows.
     *
     * @param \Doctrine\ORM\PersistentCollection $coll
     *
     * @return void
     */
    public function insertRows(PersistentCollection $coll)
    {
        $diff   = $coll->getInsertDiff();
        $sql    = $this->getInsertRowSQL($coll);

        foreach ($diff as $element) {
            $this->conn->executeUpdate($sql, $this->getInsertRowSQLParameters($coll, $element));
        }
    }

    /**
     * @param \Doctrine\ORM\PersistentCollection $collection
     * @param \Doctrine\ORM\Cache\CollectionCacheKey $key
     *
     * @return \Doctrine\ORM\PersistentCollection|null
     */
    public function loadCachedCollection(PersistentCollection $collection, CollectionCacheKey $key)
    {
        $metadata   = $collection->getTypeClass();
        $cache      = $this->cacheRegionAccess->get($key);

        if ($cache === null) {
            return null;
        }

        return $this->cacheEntryStructure->loadCacheEntry($metadata, $key, $cache, $collection);
    }

    /**
     * @param \Doctrine\ORM\Mapping\ClassMetadata       $targetMetadata
     * @param \Doctrine\ORM\Cache\CollectionCacheKey    $key
     *
     * @return void
     */
    public function saveLoadedCollection(ClassMetadata $targetMetadata, CollectionCacheKey $key, array $list)
    {
        $targetClass        = $this->association['targetEntity'];
        $targetPersister    = $this->uow->getEntityPersister($targetClass);
        $targetRegionAcess  = $targetPersister->getCacheRegionAcess();
        $listData           = $this->cacheEntryStructure->buildCacheEntry($targetMetadata, $key, $list);

        foreach ($listData as $index => $identifier) {
            $entityKey = new EntityCacheKey($targetClass, $identifier);

            if ($targetRegionAcess->getRegion()->contains($entityKey)) {
                continue;
            }

            $entity       = $list[$index];
            $entityEntry  = $targetPersister->getCacheEntryStructure()->buildCacheEntry($targetMetadata, $entityKey, $entity);

            $targetRegionAcess->put($entityKey, $entityEntry);
        }

        $this->cacheRegionAccess->put($key, $listData);
    }

    /**
     * Execute operations after transaction complete
     *
     * @return void
     */
    public function afterTransactionComplete()
    {
        // @TODO - handle locks ?

        $uow = $this->em->getUnitOfWork();

        if ( ! empty($this->queuedCache)) {

            $association  = $this->association['fieldName'];
            $mapping      = $this->em->getClassMetadata($this->association['sourceEntity']);

            foreach ($this->queuedCache as $item) {
                $elements       = $item['elements'];
                $ownerId        = $uow->getEntityIdentifier($item['owner']);
                $key            = new CollectionCacheKey($mapping->rootEntityName, $association, $ownerId);

                $this->saveLoadedCollection($mapping, $key, $elements);
            }
        }

        $this->queuedCache = array();
    }

    /**
     * Execute operations after transaction rollback
     *
     * @return void
     */
    public function afterTransactionRolledBack()
    {
        // @TODO - handle locks ?

        if ( ! $this->isConcurrentRegion) {
            $this->queuedCache = array();

            return;
        }

        $this->queuedCache = array();
    }

    /**
     * Counts the size of this persistent collection.
     *
     * @param \Doctrine\ORM\PersistentCollection $coll
     * 
     * @return integer
     *
     * @throws \BadMethodCallException
     */
    public function count(PersistentCollection $coll)
    {
        throw new \BadMethodCallException("Counting the size of this persistent collection is not supported by this CollectionPersister.");
    }

    /**
     * Slices elements.
     *
     * @param \Doctrine\ORM\PersistentCollection $coll
     * @param integer                            $offset
     * @param integer                            $length
     *
     * @return  array
     *
     * @throws \BadMethodCallException
     */
    public function slice(PersistentCollection $coll, $offset, $length = null)
    {
        throw new \BadMethodCallException("Slicing elements is not supported by this CollectionPersister.");
    }

    /**
     * Checks for existence of an element.
     *
     * @param \Doctrine\ORM\PersistentCollection $coll
     * @param object                             $element
     *
     * @return boolean
     *
     * @throws \BadMethodCallException
     */
    public function contains(PersistentCollection $coll, $element)
    {
        throw new \BadMethodCallException("Checking for existence of an element is not supported by this CollectionPersister.");
    }

    /**
     * Checks for existence of a key.
     *
     * @param \Doctrine\ORM\PersistentCollection $coll
     * @param mixed                              $key
     *
     * @return boolean
     *
     * @throws \BadMethodCallException
     */
    public function containsKey(PersistentCollection $coll, $key)
    {
        throw new \BadMethodCallException("Checking for existence of a key is not supported by this CollectionPersister.");
    }

    /**
     * Removes an element.
     *
     * @param \Doctrine\ORM\PersistentCollection $coll
     * @param object                             $element
     *
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function removeElement(PersistentCollection $coll, $element)
    {
        throw new \BadMethodCallException("Removing an element is not supported by this CollectionPersister.");
    }

    /**
     * Removes an element by key.
     *
     * @param \Doctrine\ORM\PersistentCollection $coll
     * @param mixed                              $key
     *
     * @return void
     *
     * @throws \BadMethodCallException
     */
    public function removeKey(PersistentCollection $coll, $key)
    {
        throw new \BadMethodCallException("Removing a key is not supported by this CollectionPersister.");
    }

    /**
     * Gets an element by key.
     * 
     * @param \Doctrine\ORM\PersistentCollection $coll
     * @param mixed                              $index
     * 
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function get(PersistentCollection $coll, $index)
    {
        throw new \BadMethodCallException("Selecting a collection by index is not supported by this CollectionPersister.");
    }

    /**
     * Gets the SQL statement used for deleting a row from the collection.
     *
     * @param \Doctrine\ORM\PersistentCollection $coll
     *
     * @return string
     */
    abstract protected function getDeleteRowSQL(PersistentCollection $coll);

    /**
     * Gets the SQL parameters for the corresponding SQL statement to delete the given
     * element from the given collection.
     *
     * @param \Doctrine\ORM\PersistentCollection $coll
     * @param mixed                              $element
     *
     * @return array
     */
    abstract protected function getDeleteRowSQLParameters(PersistentCollection $coll, $element);

    /**
     * Gets the SQL statement used for updating a row in the collection.
     *
     * @param \Doctrine\ORM\PersistentCollection $coll
     *
     * @return string
     */
    abstract protected function getUpdateRowSQL(PersistentCollection $coll);

    /**
     * Gets the SQL statement used for inserting a row in the collection.
     *
     * @param \Doctrine\ORM\PersistentCollection $coll
     *
     * @return string
     */
    abstract protected function getInsertRowSQL(PersistentCollection $coll);

    /**
     * Gets the SQL parameters for the corresponding SQL statement to insert the given
     * element of the given collection into the database.
     *
     * @param \Doctrine\ORM\PersistentCollection $coll
     * @param mixed                              $element
     *
     * @return array
     */
    abstract protected function getInsertRowSQLParameters(PersistentCollection $coll, $element);
}
