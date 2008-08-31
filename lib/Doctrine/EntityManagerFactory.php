<?php

#namespace Doctrine::ORM;

#use Doctrine::DBAL::ConnectionFactory;
#use Doctrine::Common::Configuration;
#use Doctrine::Common::EventManager;

/**
 * The EntityManagerFactory is responsible for bootstrapping EntityManager
 * instances as well as keeping track of all created EntityManagers and
 * hard bindings to Entities.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
class Doctrine_EntityManagerFactory
{
    /**
     * Map of all created EntityManagers, keys are the names.
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
     * The ConnectionFactory used to create DBAL connections.
     *
     * @var unknown_type
     */
    private $_connFactory;
    
    /**
     * The EventManager that is injected into all created Connections
     * and EntityManagers.
     *
     * @var EventManager
     */
    private $_eventManager;
    
    /**
     * The Configuration that is injected into all creatd Connections
     * and EntityManagers.
     *
     * @var Configuration
     */
    private $_config;
    
    /**
     * Constructor.
     * Creates a new EntityManagerFactory.
     */
    public function __construct()
    {
        
    }
    
    /**
     * Sets the Configuration that is injected into all EntityManagers
     * (and their Connections) that are created by this factory.
     *
     * @param Doctrine_Configuration $eventManager
     */
    public function setConfiguration(Doctrine_Configuration $config)
    {
        $this->_config = $config;
    }
    
    /**
     * Sets the EventManager that is injected into all EntityManagers
     * (and their Connections) that are created by this factory.
     *
     * @param Doctrine_EventManager $eventManager
     */
    public function setEventManager(Doctrine_EventManager $eventManager)
    {
        $this->_eventManager = $eventManager;
    }
    
    /**
     * Creates an EntityManager.
     *
     * @param unknown_type $connParams
     * @param unknown_type $name
     * @return unknown
     */
    public function createEntityManager($connParams, $name = null)
    {
        if ( ! $this->_connFactory) {
            // Initialize connection factory
            $this->_connFactory = new Doctrine_ConnectionFactory();
            if ( ! $this->_config) {
                $this->_config = new Doctrine_Configuration();
            }
            if ( ! $this->_eventManager) {
                $this->_eventManager = new Doctrine_EventManager();
            }
            $this->_connFactory->setConfiguration($this->_config);
            $this->_connFactory->setEventManager($this->_eventManager);
        }
        
        $conn = $this->_connFactory->createConnection($connParams);
        
        $em = new Doctrine_EntityManager($conn);
        $em->setEventManager($this->_eventManager);
        $em->setConfiguration($this->_config);
        
        if ($name !== null) {
            self::$_ems[$name] = $em;
        } else {
            self::$_ems[] = $em;
        }
        
        return $em;
    }
    
    /**
     * Gets the EntityManager that is responsible for the Entity.
     * Static method, so that ActiveEntities can look up the right EntityManager
     * without having a reference to the factory at hand.
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
                throw Doctrine_EntityManagerFactory_Exception::noManagerWithName($emName);   
            }
        } else if (self::$_ems) {
            return current(self::$_ems);
        } else {
            throw Doctrine_EntityManagerFactory_Exception::noEntityManagerAvailable();   
        }
    }
    
    /**
     * Gets the EntityManager that is responsible for the Entity.
     *
     * @param unknown_type $entityName
     * @return unknown
     */
    public function getEntityManager($entityName = null)
    {
        return self::getManager($entityName);
    }
    
    /**
     * Binds an Entity to a specific EntityManager.
     *
     * @param string $entityName
     * @param string $emName
     */
    public function bindEntityToManager($entityName, $emName)
    {
        if (isset(self::$_emBindings[$entityName])) {
            throw Doctrine_EntityManagerFactory_Exception::entityAlreadyBound($entityName);
        }
        self::$_emBindings[$entityName] = $emName;
    }
    
    /**
     * Clears all bindings between Entities and EntityManagers.
     */
    public function unbindAllManagers()
    {
        self::$_emBindings = array();
    }
    
    /**
     * Releases all EntityManagers.
     *
     */
    public function releaseAllManagers()
    {
        self::unbindAllManagers();
        self::$_ems = array();
    }
    
    public function releaseAllBindings()
    {
        self::$_emBindings = array();
    }
    
    public function releaseEntityManager($name)
    {
        if (isset(self::$_ems[$name])) {
            unset(self::$_ems[$name]);
            return true;
        }
        return false;
    }
    
}

?>