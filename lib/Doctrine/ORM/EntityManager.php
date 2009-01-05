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
 * <http://www.phpdoctrine.org>.
 */

#namespace Doctrine\ORM;

#use Doctrine\Common\Configuration;
#use Doctrine\Common\EventManager;
#use Doctrine\DBAL\Connection;
#use Doctrine\ORM\Exceptions\EntityManagerException;
#use Doctrine\ORM\Internal\UnitOfWork;
#use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * The EntityManager is the central access point to ORM functionality.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 * @author      Roman Borschel <roman@code-factory.org>
 */
class Doctrine_ORM_EntityManager
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
     * The currently active EntityManager. Only one EntityManager can be active
     * at any time.
     *
     * @var Doctrine::ORM::EntityManager
     */
    private static $_activeEm;
    
    /**
     * The unique name of the EntityManager. The name is used to bind entity classes
     * to certain EntityManagers.
     *
     * @var string
     */
    private $_name;
    
    /**
     * The used Configuration.
     *
     * @var Configuration
     */
    private $_config;
    
    /**
     * The database connection used by the EntityManager.
     *
     * @var Connection
     */
    private $_conn;
    
    /**
     * The metadata factory, used to retrieve the metadata of entity classes.
     *
     * @var Doctrine\ORM\Mapping\ClassMetadataFactory
     */
    private $_metadataFactory;
    
    /**
     * The EntityPersister instances.
     *
     * @var array
     */
    private $_persisters = array();
    
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
     * The unit of work used to coordinate object-level transactions.
     *
     * @var UnitOfWork
     */
    private $_unitOfWork;
    
    /**
     * The event manager that is the central point of the event system.
     *
     * @var EventManager
     */
    private $_eventManager;

    /**
     * The maintained (cached) Id generators.
     *
     * @var <type>
     */
    private $_idGenerators = array();

    /** Whether the EntityManager is closed or not. */
    private $_closed = false;
    
    /**
     * Creates a new EntityManager that operates on the given database connection
     * and uses the given Configuration and EventManager implementations.
     *
     * @param Doctrine\DBAL\Connection $conn
     * @param string $name
     */
    protected function __construct(
            Doctrine_DBAL_Connection $conn,
            $name,
            Doctrine_ORM_Configuration $config,
            Doctrine_Common_EventManager $eventManager)
    {
        $this->_conn = $conn;
        $this->_name = $name;
        $this->_config = $config;
        $this->_eventManager = $eventManager;
        $this->_metadataFactory = new Doctrine_ORM_Mapping_ClassMetadataFactory(
                new Doctrine_ORM_Mapping_Driver_AnnotationDriver(),
                $this->_conn->getDatabasePlatform());
        $this->_unitOfWork = new Doctrine_ORM_UnitOfWork($this);
        $this->_nullObject = Doctrine_ORM_Internal_Null::$INSTANCE;
    }
    
    /**
     * Gets the database connection object used by the EntityManager.
     *
     * @return Doctrine_Connection
     */
    public function getConnection()
    {
        return $this->_conn;
    }

    /**
     * Gets the metadata factory used to gather the metadata of classes.
     */
    public function getMetadataFactory()
    {
        return $this->_metadataFactory;
    }
    
    /**
     * Gets the name of the EntityManager.
     *
     * @return string The name of the EntityManager.
     */
    public function getName()
    {
        return $this->_name;
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
     * @return Doctrine_Metadata
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
     * Used to lazily create the id generator.
     *
     * @param string $generatorType
     * @return void
     */
    protected function _createIdGenerator($generatorType)
    {
        if ($generatorType == Doctrine_ORM_Mapping_ClassMetadata::GENERATOR_TYPE_IDENTITY) {
            return new Doctrine_ORM_Id_IdentityGenerator($this);
        } else if ($generatorType == Doctrine_ORM_Mapping_ClassMetadata::GENERATOR_TYPE_SEQUENCE) {
            return new Doctrine_ORM_Id_SequenceGenerator($this);
        } else if ($generatorType == Doctrine_ORM_Mapping_ClassMetadata::GENERATOR_TYPE_TABLE) {
            return new Doctrine_ORM_Id_TableGenerator($this);
        } else {
            return new Doctrine_ORM_Id_Assigned($this);
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
        $query = new Doctrine_ORM_Query($this);
        if ( ! empty($dql)) {
            $query->setDql($dql);
        }
        return $query;
    }
    
    /**
     * Gets the EntityPersister for an Entity.
     * 
     * This is usually not of interest for users, mainly for internal use.
     *
     * @param string $entityName  The name of the Entity.
     * @return Doctrine\ORM\Persister\AbstractEntityPersister
     */
    public function getEntityPersister($entityName)
    {
        if ( ! isset($this->_persisters[$entityName])) {
            $class = $this->getClassMetadata($entityName);
            if ($class->isInheritanceTypeJoined()) {
                $persister = new Doctrine_EntityPersister_JoinedSubclass($this, $class);
            } else {
                $persister = new Doctrine_ORM_Persisters_StandardEntityPersister($this, $class);
            }
            $this->_persisters[$entityName] = $persister;
        }
        return $this->_persisters[$entityName];
    }
    
    /**
     * Detaches an entity from the manager. It's lifecycle is no longer managed.
     *
     * @param Doctrine\ORM\Entity $entity
     * @return boolean
     */
    public function detach(Doctrine_ORM_Entity $entity)
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
     */
    public function flush()
    {
        $this->_errorIfNotActiveOrClosed();
        $this->_unitOfWork->commit();
    }
    
    /**
     * Finds an Entity by its identifier.
     * This is just a convenient shortcut for getRepository($entityName)->find($id).
     *
     * @param string $entityName
     * @param mixed $identifier
     * @return Doctrine\ORM\Entity
     */
    public function find($entityName, $identifier)
    {
        return $this->getRepository($entityName)->find($identifier);
    }
    
    /**
     * Sets the flush mode.
     *
     * @param string $flushMode
     */
    public function setFlushMode($flushMode)
    {
        if ( ! $this->_isFlushMode($flushMode)) {
            throw Doctrine_ORM_Exceptions_EntityManagerException::invalidFlushMode();
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
     * @param Doctrine\ORM\Entity $entity
     */
    public function save(Doctrine_ORM_Entity $entity)
    {
        $this->_errorIfNotActiveOrClosed();
        $this->_unitOfWork->save($entity);
        if ($this->_flushMode == self::FLUSHMODE_IMMEDIATE) {
            $this->flush();
        }
    }
    
    /**
     * Deletes the persistent state of the given entity.
     * 
     * @param Doctrine\ORM\Entity $entity
     */
    public function delete(Doctrine_ORM_Entity $entity)
    {
        $this->_errorIfNotActiveOrClosed();
        $this->_unitOfWork->delete($entity);
        if ($this->_flushMode == self::FLUSHMODE_IMMEDIATE) {
            $this->flush();
        }
    }
    
    /**
     * Refreshes the persistent state of the entity from the database,
     * overriding any local changes that have not yet been persisted.
     *
     * @param Doctrine\ORM\Entity $entity
     * @todo FIX Impl
     */
    public function refresh(Doctrine_ORM_Entity $entity)
    {
        $this->_mergeData($entity, $entity->getRepository()->find(
                $entity->identifier(), Doctrine_Query::HYDRATE_ARRAY),
                true);
    }
    
    /**
     * Creates a copy of the given entity. Can create a shallow or a deep copy.
     *
     * @param Doctrine\ORM\Entity $entity  The entity to copy.
     * @return Doctrine\ORM\Entity  The new entity.
     */
    public function copy(Doctrine_ORM_Entity $entity, $deep = false)
    {
        //...
    }
    
    /**
     * Gets the repository for an Entity.
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
            $repository = new Doctrine_ORM_EntityRepository($entityName, $metadata);
        }
        $this->_repositories[$entityName] = $repository;

        return $repository;
    }
    
    /**
     * Checks if the instance is managed by the EntityManager.
     * 
     * @param Doctrine\ORM\Entity $entity
     * @return boolean TRUE if this EntityManager currently manages the given entity
     *                 (and has it in the identity map), FALSE otherwise.
     */
    public function contains(Doctrine_ORM_Entity $entity)
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
    private function _errorIfNotActiveOrClosed()
    {
        if ( ! $this->isActive() || $this->_closed) {
            throw Doctrine_EntityManagerException::notActiveOrClosed($this->_name);
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
     * Checks whether this EntityManager is the currently active one.
     *
     * Note:This is only useful in scenarios where {@link ActiveEntity}s are used.
     *
     * @return boolean
     */
    public function isActive()
    {
        return self::$_activeEm === $this;
    }
    
    /**
     * Makes this EntityManager the currently active one.
     *
     * Note: This is only useful in scenarios where {@link ActiveEntity}s are used.
     */
    public function activate()
    {
        self::$_activeEm = $this;
    }
    
    /**
     * Factory method to create EntityManager instances.
     *
     * A newly created EntityManager is immediately activated, making it the
     * currently active EntityManager.
     *
     * @param mixed $conn An array with the connection parameters or an existing
     *      Connection instance.
     * @param string $name The name of the EntityManager.
     * @param Configuration $config The Configuration instance to use.
     * @param EventManager $eventManager The EventManager instance to use.
     * @return EntityManager The created EntityManager.
     */
    public static function create(
            $conn,
            $name,
            Doctrine_ORM_Configuration $config = null,
            Doctrine_Common_EventManager $eventManager = null)
    {
        if (is_array($conn)) {
            $conn = Doctrine_DBAL_DriverManager::getConnection($conn, $config, $eventManager);
        } else if ( ! $conn instanceof Doctrine_DBAL_Connection) {
            throw new Doctrine_Exception("Invalid parameter '$conn'.");
        }
        
        if (is_null($config)) {
            $config = new Doctrine_ORM_Configuration();
        }
        if (is_null($eventManager)) {
            $eventManager = new Doctrine_Common_EventManager();
        }
        
        $em = new Doctrine_ORM_EntityManager($conn, $name, $config, $eventManager);
        $em->activate();
        
        return $em;
    }
    
    /**
     * Static lookup to get the currently active EntityManager.
     *
     * Note: Used by {@link ActiveEntity}s to actively lookup an EntityManager.
     * 
     * @return Doctrine\ORM\EntityManager
     */
    public static function getActiveEntityManager()
    {
        return self::$_activeEm;
    }
}

?>