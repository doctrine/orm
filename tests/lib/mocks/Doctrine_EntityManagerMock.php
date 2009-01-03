<?php

require_once 'lib/mocks/Doctrine_EntityPersisterMock.php';

/**
 * Special EntityManager mock used for testing purposes.
 */
class Doctrine_EntityManagerMock extends Doctrine_ORM_EntityManager
{
    private $_persisterMock;
    private $_uowMock;
    private $_idGenerators = array();
    
    /**
     * @override
     */
    public function getEntityPersister($entityName)
    {
        return isset($this->_persisterMock[$entityName]) ?
                $this->_persisterMock[$entityName] : parent::getEntityPersister($entityName);
    }

    /**
     * @override
     */
    public function getUnitOfWork()
    {
        return isset($this->_uowMock) ? $this->_uowMock : parent::getUnitOfWork();
    }
    
    /* Mock API */

    /**
     * Sets a (mock) UnitOfWork that will be returned when getUnitOfWork() is called.
     *
     * @param <type> $uow
     */
    public function setUnitOfWork($uow)
    {
        $this->_uowMock = $uow;
    }

    /**
     * Sets a (mock) persister for an entity class that will be returned when
     * getEntityPersister() is invoked for that class.
     *
     * @param <type> $entityName
     * @param <type> $persister
     */
    public function setEntityPersister($entityName, $persister)
    {
        $this->_persisterMock[$entityName] = $persister;
    }
    
    /**
     * Mock factory method to create an EntityManager.
     *
     * @param unknown_type $conn
     * @param unknown_type $name
     * @param Doctrine_Configuration $config
     * @param Doctrine_EventManager $eventManager
     * @return Doctrine\ORM\EntityManager
     */
    public static function create($conn, $name, Doctrine_ORM_Configuration $config = null,
            Doctrine_Common_EventManager $eventManager = null)
    {
        if (is_null($config)) {
            $config = new Doctrine_ORM_Configuration();
        }
        if (is_null($eventManager)) {
            $eventManager = new Doctrine_Common_EventManager();
        }
        
        return new Doctrine_EntityManagerMock($conn, $name, $config, $eventManager);   
    }

    public function setIdGenerator($className, $generator)
    {
        $this->_idGenerators[$className] = $generator;
    }

    public function getIdGenerator($className)
    {
        if (isset($this->_idGenerators[$className])) {
            return $this->_idGenerators[$className];
        }
        return parent::getIdGenerator($className);
    }
}

?>