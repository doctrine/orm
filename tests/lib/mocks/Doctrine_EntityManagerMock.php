<?php

require_once 'lib/mocks/Doctrine_EntityPersisterMock.php';

class Doctrine_EntityManagerMock extends Doctrine_EntityManager
{
    private $_persisterMock;
    
    /**
     * Enter description here...
     *
     * @param unknown_type $entityName
     * @override
     */
    public function getEntityPersister($entityName)
    {
        return $this->_persisterMock;
    }
    
    /* Mock API */
    
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
    public static function create($conn, $name, Doctrine_Configuration $config = null,
            Doctrine_EventManager $eventManager = null)
    {
        if (is_null($config)) {
            $config = new Doctrine_Configuration();
        }
        if (is_null($eventManager)) {
            $eventManager = new Doctrine_EventManager();
        }
        
        return new Doctrine_EntityManagerMock($conn, $name, $config, $eventManager);   
    }
}

?>