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
        $oid = spl_object_id($entity);
        return isset($this->_mockDataChangeSets[$oid]) ?
                $this->_mockDataChangeSets[$oid] : parent::getDataChangeSet($entity);
    }

    /* MOCK API */

    public function setDataChangeSet($entity, array $mockChangeSet) {
        $this->_mockDataChangeSets[spl_object_id($entity)] = $mockChangeSet;
    }
}

