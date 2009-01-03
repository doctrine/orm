<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Doctrine_UnitOfWorkMock
 *
 * @author robo
 */
class Doctrine_UnitOfWorkMock extends Doctrine_ORM_UnitOfWork {
    private $_mockDataChangeSets = array();

    /**
     * @param <type> $entity
     * @override
     */
    public function getDataChangeSet($entity) {
        $oid = spl_object_hash($entity);
        return isset($this->_mockDataChangeSets[$oid]) ?
                $this->_mockDataChangeSets[$oid] : parent::getDataChangeSet($entity);
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

