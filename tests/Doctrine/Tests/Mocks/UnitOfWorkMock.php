<?php

namespace Doctrine\Tests\Mocks;

class UnitOfWorkMock extends \Doctrine\ORM\UnitOfWork
{
    private $_mockDataChangeSets = array();
    private $_persisterMock;

    /**
     * @override
     */
    public function getEntityPersister($entityName)
    {
        return isset($this->_persisterMock[$entityName]) ?
                $this->_persisterMock[$entityName] : parent::getEntityPersister($entityName);
    }

    /**
     * @param <type> $entity
     * @override
     */
    public function getEntityChangeSet($entity)
    {
        $oid = spl_object_hash($entity);
        return isset($this->_mockDataChangeSets[$oid]) ?
                $this->_mockDataChangeSets[$oid] : parent::getEntityChangeSet($entity);
    }

    /* MOCK API */

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

    public function setDataChangeSet($entity, array $mockChangeSet)
    {
        $this->_mockDataChangeSets[spl_object_hash($entity)] = $mockChangeSet;
    }

    public function setEntityState($entity, $state)
    {
        $this->_entityStates[spl_object_hash($entity)] = $state;
    }

    public function setOriginalEntityData($entity, array $originalData)
    {
        $this->_originalEntityData[spl_object_hash($entity)] = $originalData;
    }
}