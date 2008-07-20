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
        if ( ! $this->_persisterMock) {
            $this->_persisterMock = new Doctrine_EntityPersisterMock($this, $this->getClassMetadata($entityName));
        }
        return $this->_persisterMock;
    }
}

?>