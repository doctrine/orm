<?php
/**
 * EntityPersister implementation used for mocking during tests.
 */
class Doctrine_EntityPersisterMock extends Doctrine_ORM_Persisters_StandardEntityPersister
{
    private $_inserts = array();
    private $_updates = array();
    private $_deletes = array();
    private $_identityColumnValueCounter = 0;
    private $_mockIdGeneratorType;

    /**
     * @param <type> $entity
     * @return <type>
     * @override
     */
    public function insert($entity)
    {
        $this->_inserts[] = $entity;
        if ( ! is_null($this->_mockIdGeneratorType) && $this->_mockIdGeneratorType == Doctrine_ORM_Mapping_ClassMetadata::GENERATOR_TYPE_IDENTITY
                || $this->_classMetadata->isIdGeneratorIdentity()) {
            return $this->_identityColumnValueCounter++;
        }
        return null;
    }

    public function setMockIdGeneratorType($genType) {
        $this->_mockIdGeneratorType = $genType;
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

