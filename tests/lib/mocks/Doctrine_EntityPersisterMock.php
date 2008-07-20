<?php

class Doctrine_EntityPersisterMock extends Doctrine_EntityPersister_Standard
{
    private $_inserts = array();
    private $_updates = array();
    private $_deletes = array();
    
    private $_identityColumnValueCounter = 0;
    
    public function insert($entity)
    {
        if ($entity->getClass()->isIdGeneratorIdentity()) {    
            $entity->_assignIdentifier($this->_identityColumnValueCounter++);
            $this->_em->getUnitOfWork()->addToIdentityMap($entity);
        }
        
        $this->_inserts[] = $entity;
    }
    
    public function update($entity)
    {
        $this->_updates[] = $entity;
    }
    
    public function delete($entity)
    {
        $this->_deletes[] = $entity;
    }
    
    public function getInserts()
    {
        return $this->_inserts;
    }
    
    public function getUpdates()
    {
        return $this->_updates;
    }
    
    public function getDeletes()
    {
        return $this->_deletes;
    }
    
    public function reset()
    {
        $this->_identityColumnValueCounter = 0;
        $this->_inserts = array();
        $this->_updates = array();
        $this->_deletes = array();
    }
    
}

?>