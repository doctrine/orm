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
}

?>