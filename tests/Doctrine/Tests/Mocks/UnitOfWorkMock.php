<?php

namespace Doctrine\Tests\Mocks;

/**
 * Description of Doctrine_UnitOfWorkMock
 *
 * @author robo
 */
class UnitOfWorkMock extends \Doctrine\ORM\UnitOfWork {
    private $_mockDataChangeSets = array();

    /**
     * @param <type> $entity
     * @override
     */
    public function getEntityChangeSet($entity) {
        $oid = spl_object_hash($entity);
        return isset($this->_mockDataChangeSets[$oid]) ?
                $this->_mockDataChangeSets[$oid] : parent::getEntityChangeSet($entity);
    }

    /* MOCK API */

    public function setDataChangeSet($entity, array $mockChangeSet) {
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

