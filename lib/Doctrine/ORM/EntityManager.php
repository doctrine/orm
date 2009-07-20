<?php
/*
 *  $Id$
 *
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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM;

use Doctrine\Common\EventManager;
use Doctrine\Common\DoctrineException;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Proxy\ProxyClassGenerator;

/**
 * The EntityManager is the central access point to ORM functionality.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 * @author      Roman Borschel <roman@code-factory.org>
 */
class EntityManager
{
    /**
     * IMMEDIATE: Flush occurs automatically after each operation that issues database
     * queries. No operations are queued.
     */ 
    const FLUSHMODE_IMMEDIATE = 1;
    /**
     * AUTO: Flush occurs automatically in the following situations:
     * - Before any query executions (to prevent getting stale data)
     * - On EntityManager#commit()
     */
    const FLUSHMODE_AUTO = 2;
    /**
     * COMMIT: Flush occurs automatically only on EntityManager#commit().
     */
    const FLUSHMODE_COMMIT = 3;
    /**
     * MANUAL: Flush occurs never automatically. The only way to flush is
     * through EntityManager#flush().
     */
    const FLUSHMODE_MANUAL = 4;
    
    /**
     * The used Configuration.
     *
     * @var Doctrine\ORM\Configuration
     */
    private $_config;
    
    /**
     * The database connection used by the EntityManager.
     *
     * @var Doctrine\DBAL\Connection
     */
    private $_conn;
    
    /**
     * The metadata factory, used to retrieve the ORM metadata of entity classes.
     *
     * @var Doctrine\ORM\Mapping\ClassMetadataFactory
     */
    private $_metadataFactory;
    
    /**
     * The EntityRepository instances.
     *
     * @var array
     */
    private $_repositories = array();
    
    /**
     * The currently used flush mode. Defaults to 'commit'.
     *
     * @var string
     */
    private $_flushMode = self::FLUSHMODE_COMMIT;
    
    /**
     * The UnitOfWork used to coordinate object-level transactions.
     *
     * @var Doctrine\ORM\UnitOfWork
     */
    private $_unitOfWork;
    
    /**
     * The event manager that is the central point of the event system.
     *
     * @var Doctrine\Common\EventManager
     */
    private $_eventManager;

    /**
     * The maintained (cached) hydrators. One instance per type.
     *
     * @var array
     */
    private $_hydrators = array();

    /**
     * The proxy factory which creates association or reference proxies.
     *
     * @var ProxyFactory
     */
    private $_proxyFactory;

    /**
     * Whether the EntityManager is closed or not.
     */
    private $_closed = false;
    
    /**
     * Creates a new EntityManager that operates on the given database connection
     * and uses the given Configuration and EventManager implementations.
     *
     * @param Doctrine\DBAL\Connection $conn
     * @param string $name
     * @param Doctrine\ORM\Configuration $config
     * @param Doctrine\Common\EventManager $eventManager
     */
    protected function __construct(Connection $conn, Configuration $config, EventManager $eventManager)
    {
        $this->_conn = $conn;
        $this->_config = $config;
        $this->_eventManager = $eventManager;
        $this->_metadataFactory = new ClassMetadataFactory($this);
        $this->_metadataFactory->setCacheDriver($this->_config->getMetadataCacheImpl());
        $this->_unitOfWork = new UnitOfWork($this);
        //FIX: this should be in a factory
        $this->_proxyFactory = new ProxyFactory($this, new ProxyClassGenerator($this, $this->_config->getCacheDir()));
    }
    
    /**
     * Gets the database connection object used by the EntityManager.
     *
     * @return Doctrine\DBAL\Connection
     */
    public function getConnection()
    {
        return $this->_conn;
    }

    /**
     * Gets the metadata factory used to gather the metadata of classes.
     *
     * @return Doctrine\ORM\Mapping\ClassMetadataFactory
     */
    public function getMetadataFactory()
    {
        return $this->_metadataFactory;
    }
    
    /**
     * Starts a transaction on the underlying database connection.
     */
    public function beginTransaction()
    {
        $this->_conn->beginTransaction();
    }
    
    /**
     * Commits a running transaction.
     * 
     * This causes a flush() of the EntityManager if the flush mode is set to
     * AUTO or COMMIT.
     */
    public function commit()
    {
        if ($this->_flushMode == self::FLUSHMODE_AUTO || $this->_flushMode == self::FLUSHMODE_COMMIT) {
            $this->flush();
        }
        $this->_conn->commit();
    }
    
    /**
     * Performs a rollback on the underlying database connection and closes the
     * EntityManager as it may now be in a corrupted state.
     */
    public function rollback()
    {
        $this->_conn->rollback();
        $this->close();
    }

    /**
     * Returns the metadata for a class.
     *
     * @return Doctrine\ORM\Mapping\ClassMetadata
     * @internal Performance-sensitive method.
     */
    public function getClassMetadata($className)
    {        
        return $this->_metadataFactory->getMetadataFor($className);
    }
    
    /**
     * Creates a new Query object.
     * 
     * @param string  The DQL string.
     * @return Doctrine\ORM\Query
     */
    public function createQuery($dql = "")
    {
        $query = new Query($this);
        if ( ! empty($dql)) {
            $query->setDql($dql);
        }
        return $query;
    }

    /**
     * Creates a DQL query with the specified name.
     *
     * @todo Implementation.
     * @throws DoctrineException  If there is no query registered with the given name.
     */
    public function createNamedQuery($name)
    {
        //...
    }
    
    /**
     * Creates a native SQL query.
     *
     * @param string $sql
     * @param ResultSetMapping $rsm The ResultSetMapping to use.
     * @return Query
     */
    public function createNativeQuery($sql, \Doctrine\ORM\Query\ResultSetMapping $rsm)
    {
        $query = new NativeQuery($this);
        $query->setSql($sql);
        $query->setResultSetMapping($rsm);
        return $query;
    }
    
    /**
     * @todo Implementation.
     */
    public function createNamedNativeQuery($name)
    {
        //...
    }
    
    /**
     * Create a QueryBuilder instance
     *
     * @return QueryBuilder $qb
     */
    public function createQueryBuilder()
    {
        return new QueryBuilder($this);
    }
    
    /**
     * Flushes all changes to objects that have been queued up to now to the database.
     * This effectively synchronizes the in-memory state of managed objects with the
     * database.
     */
    public function flush()
    {
        $this->_errorIfClosed();
        $this->_unitOfWork->commit();
    }
    
    /**
     * Finds an Entity by its identifier.
     *
     * This is just a convenient shortcut for getRepository($entityName)->find($id).
     *
     * @param string $entityName
     * @param mixed $identifier
     * @return object
     */
    public function find($entityName, $identifier)
    {
        return $this->getRepository($entityName)->find($identifier);
    }

    /**
     * Gets a reference to the entity identified by the given type and identifier
     * without actually loading it. Only the identifier of the returned entity
     * will be populated.
     *
     * NOTE: There is currently no magic proxying in place, that means the full state
     * of the entity will not be loaded upon accessing it.
     *
     * @return object The entity reference.
     */
    public function getReference($entityName, $identifier)
    {
        $entity = new $entityName;
        $this->getClassMetadata($entityName)->setEntityIdentifier($entity, $identifier);
        return $entity;
    }
    
    /**
     * Sets the flush mode to use.
     *
     * @param string $flushMode
     */
    public function setFlushMode($flushMode)
    {
        if ( ! $this->_isFlushMode($flushMode)) {
            throw EntityManagerException::invalidFlushMode();
        }
        $this->_flushMode = $flushMode;
    }
    
    /**
     * Checks whether the given value is a valid flush mode.
     *
     * @param string $value
     * @return boolean
     */
    private function _isFlushMode($value)
    {
        return $value >= 1 && $value <= 4;
    }
    
    /**
     * Gets the currently used flush mode.
     *
     * @return string
     */
    public function getFlushMode()
    {
        return $this->_flushMode;
    }
    
    /**
     * Clears the EntityManager. All entities that are currently managed
     * by this EntityManager become detached.
     *
     * @param string $entityName
     */
    public function clear($entityName = null)
    {
        if ($entityName === null) {
            $this->_unitOfWork->clear();
        } else {
            //TODO
            throw DoctrineException::notImplemented();
        }
    }
    
    /**
     * Closes the EntityManager. All entities that are currently managed
     * by this EntityManager become detached. The EntityManager may no longer
     * be used after it is closed.
     */
    public function close()
    {
        $this->clear();
        $this->_closed = true;
    }
    
    /**
     * Tells the EntityManager to make an instance managed and persistent.
     * 
     * The entity will be entered into the database at or before transaction
     * commit or as a result of the flush operation.
     * 
     * @param object $object The instance to make managed and persistent.
     */
    public function persist($object)
    {
        $this->_errorIfClosed();
        $this->_unitOfWork->persist($object);
        if ($this->_flushMode == self::FLUSHMODE_IMMEDIATE) {
            $this->flush();
        }
    }
    
    /**
     * Removes an entity instance.
     * 
     * A removed entity will be removed from the database at or before transaction commit
     * or as a result of the flush operation. 
     * 
     * @param object $entity The entity instance to remove.
     */
    public function remove($entity)
    {
        $this->_errorIfClosed();
        $this->_unitOfWork->remove($entity);
        if ($this->_flushMode == self::FLUSHMODE_IMMEDIATE) {
            $this->flush();
        }
    }
    
    /**
     * Refreshes the persistent state of an entity from the database,
     * overriding any local changes that have not yet been persisted.
     *
     * @param object $entity
     * @todo Implementation
     */
    public function refresh($entity)
    {
        $this->_errorIfClosed();
        throw DoctrineException::notImplemented();
    }

    /**
     * Detaches an entity from the EntityManager.
     *
     * @param object $entity The entity to detach.
     * @return boolean
     */
    public function detach($entity)
    {
        return $this->_unitOfWork->removeFromIdentityMap($entity);
    }

    /**
     * Merges the state of a detached entity into the persistence context
     * of this EntityManager.
     *
     * @param object $entity The entity to merge into the persistence context.
     * @return object The managed copy of the entity.
     */
    public function merge($entity)
    {
        return $this->_unitOfWork->merge($entity);
    }
    
    /**
     * Creates a copy of the given entity. Can create a shallow or a deep copy.
     *
     * @param object $entity  The entity to copy.
     * @return object  The new entity.
     * @todo Implementation or remove.
     */
    public function copy($entity, $deep = false)
    {
        throw DoctrineException::notImplemented();
    }

    /**
     * Gets the repository for an entity class.
     *
     * @param string $entityName  The name of the Entity.
     * @return EntityRepository  The repository.
     */
    public function getRepository($entityName)
    {
        if (isset($this->_repositories[$entityName])) {
            return $this->_repositories[$entityName];
        }

        $metadata = $this->getClassMetadata($entityName);
        $customRepositoryClassName = $metadata->getCustomRepositoryClass();
        if ($customRepositoryClassName !== null) {
            $repository = new $customRepositoryClassName($entityName, $metadata);
        } else {
            $repository = new EntityRepository($this, $metadata);
        }
        $this->_repositories[$entityName] = $repository;

        return $repository;
    }
    
    /**
     * Determines whether an entity instance is managed in this EntityManager.
     * 
     * @param object $entity
     * @return boolean TRUE if this EntityManager currently manages the given entity
     *                 (and has it in the identity map), FALSE otherwise.
     */
    public function contains($entity)
    {
        return $this->_unitOfWork->isInIdentityMap($entity) &&
                ! $this->_unitOfWork->isScheduledForDelete($entity);
    }
    
    /**
     * Gets the EventManager used by the EntityManager.
     *
     * @return Doctrine\Common\EventManager
     */
    public function getEventManager()
    {
        return $this->_eventManager;
    }
    
    /**
     * Gets the Configuration used by the EntityManager.
     *
     * @return Doctrine\ORM\Configuration
     */
    public function getConfiguration()
    {
        return $this->_config;
    }
    
    /**
     * Throws an exception if the EntityManager is closed or currently not active.
     *
     * @throws EntityManagerException If the EntityManager is closed or not active.
     */
    private function _errorIfClosed()
    {
        if ($this->_closed) {
            throw EntityManagerException::notActiveOrClosed();
        }
    }
    
    /**
     * Gets the UnitOfWork used by the EntityManager to coordinate operations.
     *
     * @return Doctrine\ORM\UnitOfWork
     */
    public function getUnitOfWork()
    {
        return $this->_unitOfWork;
    }
    
    /**
     * Gets a hydrator for the given hydration mode.
     *
     * @param  $hydrationMode
     */
    public function getHydrator($hydrationMode)
    {
        if ( ! isset($this->_hydrators[$hydrationMode])) {
            switch ($hydrationMode) {
                case Query::HYDRATE_OBJECT:
                    $this->_hydrators[$hydrationMode] = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this);
                    break;
                case Query::HYDRATE_ARRAY:
                    $this->_hydrators[$hydrationMode] = new \Doctrine\ORM\Internal\Hydration\ArrayHydrator($this);
                    break;
                case Query::HYDRATE_SCALAR:
                    $this->_hydrators[$hydrationMode] = new \Doctrine\ORM\Internal\Hydration\ScalarHydrator($this);
                    break;
                case Query::HYDRATE_SINGLE_SCALAR:
                    $this->_hydrators[$hydrationMode] = new \Doctrine\ORM\Internal\Hydration\SingleScalarHydrator($this);
                    break;
                case Query::HYDRATE_NONE:
                    $this->_hydrators[$hydrationMode] = new \Doctrine\ORM\Internal\Hydration\NoneHydrator($this);
                    break;
                default:
                    throw DoctrineException::updateMe("No hydrator found for hydration mode '$hydrationMode'.");
            }
        }
        return $this->_hydrators[$hydrationMode];
    }

    /**
     * Gets the proxy factory used by the EntityManager to create entity proxies.
     * 
     * @return ProxyFactory
     */
    public function getProxyFactory()
    {
        return $this->_proxyFactory;
    }
    
    /**
     * Factory method to create EntityManager instances.
     *
     * @param mixed $conn An array with the connection parameters or an existing
     *      Connection instance.
     * @param string $name The name of the EntityManager.
     * @param Configuration $config The Configuration instance to use.
     * @param EventManager $eventManager The EventManager instance to use.
     * @return EntityManager The created EntityManager.
     */
    public static function create($conn, Configuration $config = null, EventManager $eventManager = null)
    {
        if (is_array($conn)) {
            $conn = \Doctrine\DBAL\DriverManager::getConnection($conn, $config, $eventManager);
        } else if ( ! $conn instanceof Connection) {
            throw DoctrineException::updateMe("Invalid parameter '$conn'.");
        }
        
        if ($config === null) {
            $config = new Configuration();
        }
        if ($eventManager === null) {
            $eventManager = new EventManager();
        }
        
        return new EntityManager($conn, $config, $eventManager);
    }
}
