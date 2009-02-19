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
    const FLUSHMODE_IMMEDIATE = 'immediate';
    /**
     * AUTO: Flush occurs automatically in the following situations:
     * - Before any query executions (to prevent getting stale data)
     * - On EntityManager#commit()
     */
    const FLUSHMODE_AUTO = 'auto';
    /**
     * COMMIT: Flush occurs automatically only on EntityManager#commit().
     */
    const FLUSHMODE_COMMIT = 'commit';
    /**
     * MANUAL: Flush occurs never automatically. The only way to flush is
     * through EntityManager#flush().
     */
    const FLUSHMODE_MANUAL = 'manual';
    
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
    private $_flushMode = 'commit';
    
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
     * The maintained (cached) Id generators.
     *
     * @var array
     */
    private $_idGenerators = array();

    /**
     * The maintained (cached) hydrators. One instance per type.
     *
     * @var array
     */
    private $_hydrators = array();

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
        $this->_metadataFactory = new ClassMetadataFactory(
                $this->_config->getMetadataDriverImpl(),
                $this->_conn->getDatabasePlatform());
        $this->_metadataFactory->setCacheDriver($this->_config->getMetadataCacheImpl());
        $this->_unitOfWork = new UnitOfWork($this);
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
     * Starts a transaction on the underlying connection.
     */
    public function beginTransaction()
    {
        return $this->_conn->beginTransaction();
    }
    
    /**
     * Commits a running transaction.
     * 
     * This causes a flush() of the EntityManager if the flush mode is set to
     * AUTO or COMMIT.
     *
     * @return boolean
     */
    public function commit()
    {
        if ($this->_flushMode == self::FLUSHMODE_AUTO || $this->_flushMode == self::FLUSHMODE_COMMIT) {
            $this->flush();
        }
        return $this->_conn->commitTransaction();
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
     * Gets an IdGenerator that can be used to generate identifiers for the specified
     * class.
     */
    public function getIdGenerator($className)
    {
        if (!isset($this->_idGenerators[$className])) {
            $this->_idGenerators[$className] = $this->_createIdGenerator(
                    $this->getClassMetadata($className)->getIdGeneratorType());
        }
        return $this->_idGenerators[$className];
    }

    /**
     * Used to lazily create an ID generator.
     *
     * @param string $generatorType
     * @return object
     */
    protected function _createIdGenerator($generatorType)
    {
        if ($generatorType == ClassMetadata::GENERATOR_TYPE_IDENTITY) {
            return new \Doctrine\ORM\Id\IdentityGenerator($this);
        } else if ($generatorType == ClassMetadata::GENERATOR_TYPE_SEQUENCE) {
            return new \Doctrine\ORM\Id\SequenceGenerator($this);
        } else if ($generatorType == ClassMetadata::GENERATOR_TYPE_TABLE) {
            return new \Doctrine\ORM\Id\TableGenerator($this);
        } else {
            return new \Doctrine\ORM\Id\Assigned($this);
        }
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
     * Detaches an entity from the manager. It's lifecycle is no longer managed.
     *
     * @param object $entity
     * @return boolean
     */
    public function detach($entity)
    {
        return $this->_unitOfWork->removeFromIdentityMap($entity);
    }
    
    /**
     * Creates a query with the specified name.
     *
     * @todo Implementation.
     * @throws SomeException  If there is no query registered with the given name.
     */
    public function createNamedQuery($name)
    {
        //...
    }
    
    /**
     * @todo Implementation.
     */
    public function createNativeQuery($sql = "")
    {
        //...
    }
    
    /**
     * @todo Implementation.
     */
    public function createNamedNativeQuery($name)
    {
        //...
    }
    
    /**
     * @todo Implementation.
     */
    public function createCriteria()
    {
        //...
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
        return $value == self::FLUSHMODE_AUTO ||
                $value == self::FLUSHMODE_COMMIT ||
                $value == self::FLUSHMODE_IMMEDIATE ||
                $value == self::FLUSHMODE_MANUAL;
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
     * Clears the persistence context, effectively detaching all managed entities.
     */
    public function clear($entityName = null)
    {
        if ($entityName === null) {
            $this->_unitOfWork->detachAll();
        } else {
            //TODO
        }
    }
    
    /**
     * Closes the EntityManager.
     */
    public function close()
    {
        $this->_closed = true;
    }
    
    /**
     * Saves the given entity, persisting it's state.
     * 
     * @param object $object
     */
    public function save($object)
    {
        $this->_errorIfClosed();
        $this->_unitOfWork->save($object);
        if ($this->_flushMode == self::FLUSHMODE_IMMEDIATE) {
            $this->flush();
        }
    }
    
    /**
     * Deletes the persistent state of the given entity.
     * 
     * @param object $entity
     */
    public function delete($entity)
    {
        $this->_errorIfClosed();
        $this->_unitOfWork->delete($entity);
        if ($this->_flushMode == self::FLUSHMODE_IMMEDIATE) {
            $this->flush();
        }
    }
    
    /**
     * Refreshes the persistent state of the entity from the database,
     * overriding any local changes that have not yet been persisted.
     *
     * @param object $entity
     * @todo FIX Impl
     */
    public function refresh($entity)
    {
        /*$this->_mergeData($entity, $this->getRepository(get_class($entity))->find(
                $entity->identifier(), Query::HYDRATE_ARRAY),
                true);*/
    }
    
    /**
     * Creates a copy of the given entity. Can create a shallow or a deep copy.
     *
     * @param object $entity  The entity to copy.
     * @return object  The new entity.
     */
    public function copy($entity, $deep = false)
    {
        //...
    }

/*
    public function toArray($entity, $deep = false)
    {
        $array = array();
        foreach ($entity as $key => $value) {
            if ($deep && is_object($value)) {
                $array[$key] = $this->toArray($value, $deep);
            } else if ( ! is_object($value)) {
                $array[$key] = $value;
            }
        }
        return $array;
    }

    public function fromArray($entity, array $array, $deep = false)
    {
        foreach ($array as $key => $value) {
            if ($deep && is_array($value)) {
                $entity->$key = $this->fromArray($entity, $value, $deep);
            } else if ( ! is_array($value)) {
                $entity->$key = $value;
            }
        }
    }
*/

    /**
     * Gets the repository for an entity class.
     *
     * @param string $entityName  The name of the Entity.
     * @return Doctrine\ORM\EntityRepository  The repository.
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
            $repository = new \Doctrine\ORM\EntityRepository($this, $metadata);
        }
        $this->_repositories[$entityName] = $repository;

        return $repository;
    }
    
    /**
     * Checks if the instance is managed by the EntityManager.
     * 
     * @param object $entity
     * @return boolean TRUE if this EntityManager currently manages the given entity
     *                 (and has it in the identity map), FALSE otherwise.
     */
    public function contains($entity)
    {
        return $this->_unitOfWork->isInIdentityMap($entity) &&
                ! $this->_unitOfWork->isRegisteredRemoved($entity);
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
                    \Doctrine\Common\DoctrineException::updateMe("No hydrator found for hydration mode '$hydrationMode'.");
            }
        } else if ($this->_hydrators[$hydrationMode] instanceof Closure) {
            $this->_hydrators[$hydrationMode] = $this->_hydrators[$hydrationMode]($this);
        }
        return $this->_hydrators[$hydrationMode];
    }

    /**
     * Sets a hydrator for a hydration mode.
     *
     * @param mixed $hydrationMode
     * @param object $hydrator Either a hydrator instance or a closure that creates a
     *          hydrator instance.
     */
    public function setHydrator($hydrationMode, $hydrator)
    {
        $this->_hydrators[$hydrationMode] = $hydrator;
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
            \Doctrine\Common\DoctrineException::updateMe("Invalid parameter '$conn'.");
        }
        
        if (is_null($config)) {
            $config = new Configuration();
        }
        if (is_null($eventManager)) {
            $eventManager = new EventManager();
        }
        
        $em = new EntityManager($conn, $config, $eventManager);
        
        return $em;
    }
}