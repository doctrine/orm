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

#namespace Doctrine::ORM;

/**
 * The EntityManager is a central access point to ORM functionality.
 *
 * @package     Doctrine
 * @subpackage  EntityManager
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 * @author      Roman Borschel <roman@code-factory.org>
 * @todo package:orm
 */
class Doctrine_EntityManager
{
    /**
     * The unique name of the EntityManager. The name is used to bind entity classes
     * to certain EntityManagers.
     *
     * @var string
     */
    private $_name;
    
    /**
     * The database connection used by the EntityManager.
     *
     * @var Doctrine_Connection
     */
    private $_conn;
    
    /**
     * Flush modes enumeration.
     */
    private static $_flushModes = array(
            // auto: Flush occurs automatically after each operation that issues database
            // queries. No operations are queued.
            'auto',
            // commit: Flush occurs automatically at transaction commit.
            'commit',
            // manual: Flush occurs never automatically.
            'manual'
    );
    
    /**
     * The metadata factory, used to retrieve the metadata of entity classes.
     *
     * @var Doctrine_ClassMetadata_Factory
     */
    private $_metadataFactory;
    
    /**
     * The EntityPersister instances.
     * @todo Implementation.
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
     * Map of all EntityManagers, keys are the names.
     *
     * @var array
     */
    private static $_ems = array();
    
    /**
     * EntityManager to Entity bindings.
     *
     * @var array
     */
    private static $_emBindings = array();
    
    /**
     * The unit of work.
     *
     * @var UnitOfWork
     */
    private $_unitOfWork;
    
    /**
     * Enter description here...
     *
     * @var unknown_type
     */
    //private $_dataTemplates = array();
    
    /**
     * Creates a new EntityManager that operates on the given database connection.
     *
     * @param Doctrine_Connection $conn
     * @param string $name
     */
    public function __construct(Doctrine_Connection $conn, $name = null)
    {
        $this->_conn = $conn;
        $this->_name = $name;
        $this->_metadataFactory = new Doctrine_ClassMetadata_Factory($this,
                new Doctrine_ClassMetadata_CodeDriver());
        $this->_unitOfWork = new Doctrine_Connection_UnitOfWork($conn);
        if ($name !== null) {
            self::$_ems[$name] = $this;
        } else {
            self::$_ems[] = $this;
        }
    }
    
    /**
     * Gets the EntityManager that is responsible for the Entity.
     *
     * @param string $entityName
     * @return EntityManager
     * @throws Doctrine_EntityManager_Exception  If a suitable manager can not be found.
     */
    public static function getManager($entityName = null)
    {
        if ( ! is_null($entityName) && isset(self::$_emBindings[$entityName])) {
            $emName = self::$_emBindings[$entityName];
            if (isset(self::$_ems[$emName])) {
                return self::$_ems[$emName];
            } else {
                throw Doctrine_EntityManager_Exception::noManagerWithName($emName);   
            }
        } else if (self::$_ems) {
            return current(self::$_ems);
        } else {
            throw Doctrine_EntityManager_Exception::noEntityManagerAvailable();   
        }
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $entityName
     * @param unknown_type $emName
     */
    public static function bindEntityToManager($entityName, $emName)
    {
        if (isset(self::$_emBindings[$entityName])) {
            throw Doctrine_EntityManager_Exception::entityAlreadyBound($entityName);
        }
        self::$_emBindings[$entityName] = $emName;
    }
    
    /**
     * Clears all bindings between Entities and EntityManagers.
     */
    public static function unbindAllManagers()
    {
        self::$_emBindings = array();
    }
    
    /**
     * Releases all EntityManagers.
     *
     */
    public static function releaseAllManagers()
    {
        self::$_ems = array();
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
     * Returns the metadata for a class. Alias for getClassMetadata().
     *
     * @return Doctrine_Metadata
     * @todo package:orm
     */
    public function getMetadata($className)
    {
        return $this->getClassMetadata($className);
    }

    /**
     * Returns the metadata for a class.
     *
     * @return Doctrine_Metadata
     */
    public function getClassMetadata($className)
    {        
        return $this->_metadataFactory->getMetadataFor($className);
    }
    
    /**
     * Sets the driver that is used to obtain metadata informations about entity
     * classes.
     *
     * @param $driver  The driver to use.
     */
    public function setClassMetadataDriver($driver)
    {
        $this->_metadataFactory->setDriver($driver);
    }
    
    /**
     * Creates a new Doctrine_Query object that operates on this connection.
     * 
     * @return Doctrine_Query
     * @todo package:orm
     */
    public function createQuery($dql = "")
    {
        $query = new Doctrine_Query($this);
        if ( ! empty($dql)) {
            $query->parseQuery($dql);
        }
        
        return $query;
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $entityName
     * @return unknown
     */
    public function getEntityPersister($entityName)
    {
        if ( ! isset($this->_persisters[$entityName])) {
            $class = $this->getClassMetadata($entityName);
            if ($class->getInheritanceType() == Doctrine::INHERITANCE_TYPE_JOINED) {
                $persister = new Doctrine_EntityPersister_JoinedSubclass($this, $class);
            } else {
                $persister = new Doctrine_EntityPersister_Standard($this, $class);
            }
            $this->_persisters[$entityName] = $persister;
        }
        return $this->_persisters[$entityName];
    }
    
    /**
     * Detaches an entity from the manager. It's lifecycle is no longer managed.
     *
     * @param Doctrine_Entity $entity
     * @return unknown
     */
    public function detach(Doctrine_Entity $entity)
    {
        return $this->_unitOfWork->unregisterIdentity($entity);
    }
    
    /**
     * Returns the current internal transaction nesting level.
     *
     * @return integer  The nesting level. A value of 0 means theres no active transaction.
     * @todo package:orm???
     */
    public function getInternalTransactionLevel()
    {
        return $this->transaction->getInternalTransactionLevel();
    }
    
    /**
     * Initiates a transaction.
     *
     * This method must only be used by Doctrine itself to initiate transactions.
     * Userland-code must use {@link beginTransaction()}.
     *
     * @todo package:orm???
     */
    public function beginInternalTransaction($savepoint = null)
    {
        return $this->transaction->beginInternalTransaction($savepoint);
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
     *
     * @todo package:orm
     */
    public function flush()
    {
        $this->beginInternalTransaction();
        $this->_unitOfWork->flush();
        $this->commit();
    }
    
    /**
     * Sets the flush mode.
     *
     * @param string $flushMode
     */
    public function setFlushMode($flushMode)
    {
        if ( ! in_array($flushMode, self::$_flushModes)) {
            throw Doctrine_EntityManager_Exception::invalidFlushMode();
        }
        $this->_flushMode = $flushMode;
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
     * Clears the persistence context, detaching all entities.
     *
     * @return void
     * @todo package:orm
     */
    public function clear($entityName = null)
    {
        if ($entityName === null) {
            $this->_unitOfWork->detachAll();
            foreach ($this->_mappers as $mapper) {
                $mapper->clear(); // clear identity map of each mapper
            }
        } else {
            $this->getMapper($entityName)->clear();   
        }
    }
    
    /**
     * Releases the EntityManager.
     *
     */
    public function close()
    {
        
    }
    
    /**
     * getResultCacheDriver
     *
     * @return Doctrine_Cache_Interface
     * @todo package:orm
     */
    public function getResultCacheDriver()
    {
        if ( ! $this->getAttribute(Doctrine::ATTR_RESULT_CACHE)) {
            throw new Doctrine_Exception('Result Cache driver not initialized.');
        }
        
        return $this->getAttribute(Doctrine::ATTR_RESULT_CACHE);
    }

    /**
     * getQueryCacheDriver
     *
     * @return Doctrine_Cache_Interface
     * @todo package:orm
     */
    public function getQueryCacheDriver()
    {
        if ( ! $this->getAttribute(Doctrine::ATTR_QUERY_CACHE)) {
            throw new Doctrine_Exception('Query Cache driver not initialized.');
        }
        
        return $this->getAttribute(Doctrine::ATTR_QUERY_CACHE);
    }
    
    /**
     * Saves the given entity, persisting it's state.
     */
    public function save(Doctrine_Entity $entity)
    {
        //...
    }
    
    /**
     * Removes the given entity from the persistent store.
     */
    public function delete(Doctrine_Entity $entity)
    {
        //...
    }
    
    /**
     * Gets the repository for the given entity name.
     *
     * @return Doctrine_EntityRepository  The repository.
     * @todo Implementation.
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
            $repository = new Doctrine_EntityRepository($entityName, $metadata);
        }
        $this->_repositories[$entityName] = $repository;

        return $repository;
    }
    
    /**
     * Creates an entity. Used to reconstitution as well as new creation.
     *
     * @param
     * @param
     * @return Doctrine_Entity
     */
    public function createEntity($className, array $data)
    {
        $className = $this->_getClassnameToReturn($data, $className);
        $classMetadata = $this->getClassMetadata($className);
        if ( ! empty($data)) {
            $identifierFieldNames = $classMetadata->getIdentifier();
            $isNew = false;
            foreach ($identifierFieldNames as $fieldName) {
                if ( ! isset($data[$fieldName])) {
                    // id field not found return new entity
                    $isNew = true;
                    break;
                }
                $id[] = $data[$fieldName];
            }
            if ($isNew) {
                return new $className(true, $data);
            }

            $idHash = $this->_unitOfWork->getIdentifierHash($id);

            if ($entity = $this->_unitOfWork->tryGetByIdHash($idHash,
                    $classMetadata->getRootClassName())) {
                return $entity;
            } else {
                $entity = new $className(false, $data);
                $this->_unitOfWork->registerIdentity($entity);
            }
            $data = array();
        } else {
            $entity = new $className(true, $data);
        }

        return $entity;
    }
    
    /**
     * Check the dataset for a discriminator column to determine the correct
     * class to instantiate. If no discriminator column is found, the given
     * classname will be returned.
     *
     * @return string The name of the class to instantiate.
     * @todo Can be optimized performance-wise.
     * @todo Move to EntityManager::createEntity()
     */
    private function _getClassnameToReturn(array $data, $className)
    {
        $class = $this->getClassMetadata($className);

        $discCol = $class->getInheritanceOption('discriminatorColumn');
        if ( ! $discCol) {
            return $className;
        }
        
        $discMap = $class->getInheritanceOption('discriminatorMap');
        
        if (isset($data[$discCol], $discMap[$data[$discCol]])) {
            return $discMap[$data[$discCol]];
        } else {
            return $className;
        }
    }
    
    public function getUnitOfWork()
    {
        return $this->_unitOfWork;
    }
}

?>