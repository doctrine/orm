<?php

require_once 'lib/mocks/Doctrine_EntityPersisterMock.php';

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
        return isset($this->_persisterMock) ? $this->_persisterMock :
                parent::getEntityPersister($entityName);
    }

    /**
     * @override
     */
    public function getUnitOfWork()
    {
        return isset($this->_uowMock) ? $this->_uowMock : parent::getUnitOfWork();
    }
    
    /* Mock API */

    public function setUnitOfWork($uow)
    {
        $this->_uowMock = $uow;
    }

    public function setEntityPersister($persister)
    {
        $this->_persisterMock = $persister;
    }
    
    /**
     * Mock factory method.
     *
     * @param unknown_type $conn
     * @param unknown_type $name
     * @param Doctrine_Configuration $config
     * @param Doctrine_EventManager $eventManager
     * @return unknown
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