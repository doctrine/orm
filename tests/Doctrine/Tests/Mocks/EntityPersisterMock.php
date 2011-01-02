<?php

namespace Doctrine\Tests\Mocks;

/**
 * EntityPersister implementation used for mocking during tests.
 */
class EntityPersisterMock extends \Doctrine\ORM\Persisters\BasicEntityPersister
{
    private $_inserts = array();
    private $_updates = array();
    private $_deletes = array();
    private $_identityColumnValueCounter = 0;
    private $_mockIdGeneratorType;
    private $_postInsertIds = array();
    private $existsCalled = false;

    /**
     * @param <type> $entity
     * @return <type>
     * @override
     */
    public function insert($entity)
    {
        $this->_inserts[] = $entity;
        if ( ! is_null($this->_mockIdGeneratorType) && $this->_mockIdGeneratorType == \Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_IDENTITY
                || $this->_class->isIdGeneratorIdentity()) {
            $id = $this->_identityColumnValueCounter++;
            $this->_postInsertIds[$id] = $entity;
            return $id;
        }
        return null;
    }

    public function addInsert($entity)
    {
        $this->_inserts[] = $entity;
        if ( ! is_null($this->_mockIdGeneratorType) && $this->_mockIdGeneratorType == \Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_IDENTITY
                || $this->_class->isIdGeneratorIdentity()) {
            $id = $this->_identityColumnValueCounter++;
            $this->_postInsertIds[$id] = $entity;
            return $id;
        }
        return null;
    }

    public function executeInserts()
    {
        return $this->_postInsertIds;
    }

    public function setMockIdGeneratorType($genType)
    {
        $this->_mockIdGeneratorType = $genType;
    }
    
    public function update($entity)
    {
        $this->_updates[] = $entity;
    }

    public function exists($entity, array $extraConditions = array())
    {
        $this->existsCalled = true;
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
        $this->existsCalled = false;
        $this->_identityColumnValueCounter = 0;
        $this->_inserts = array();
        $this->_updates = array();
        $this->_deletes = array();
    }

    public function isExistsCalled()
    {
        return $this->existsCalled;
    }
}