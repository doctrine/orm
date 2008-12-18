<?php

class Doctrine_EntityPersisterMock extends Doctrine_ORM_Persisters_StandardEntityPersister
{
    private $_inserts = array();
    private $_updates = array();
    private $_deletes = array();
    
    private $_identityColumnValueCounter = 0;
    
    public function insert($entity)
    {
        $class = $this->_em->getClassMetadata(get_class($entity));
        if ($class->isIdGeneratorIdentity()) {
            $class->setEntityIdentifier($entity, $this->_identityColumnValueCounter++);
            $this->_em->getUnitOfWork()->addToIdentityMap($entity);
        }
        
        $this->_inserts[] = $entity;
    }
    
    public function update(Doctrine_ORM_Entity $entity)
    {
        $this->_updates[] = $entity;
    }
    
    public function delete(Doctrine_ORM_Entity $entity)
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