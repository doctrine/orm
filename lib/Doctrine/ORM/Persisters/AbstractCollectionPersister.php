<?php

namespace Doctrine\ORM\Persisters;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\PersistentCollection;

abstract class AbstractCollectionPersister
{
    protected $_em;
    protected $_conn;

    public function __construct(EntityManager $em)
    {
        $this->_em = $em;
        $this->_conn = $em->getConnection();
    }

    public function recreate(PersistentCollection $coll)
    {
        if ($coll->getRelation()->isInverseSide()) {
            return;
        }
        //...
    }
    
    public function delete(PersistentCollection $coll)
    {
        if ($coll->getRelation()->isInverseSide()) {
            return;
        }
        //...
    }

    public function update(PersistentCollection $coll)
    {
        $this->deleteRows($coll);
        $this->updateRows($coll);
        $this->insertRows($coll);
    }
    
    /* collection update actions */
    
    public function deleteRows(PersistentCollection $coll)
    {
        if ($coll->getMapping()->isInverseSide()) {
            return; // ignore inverse side
        }
        
        $deleteDiff = $coll->getDeleteDiff();
        $sql = $this->_getDeleteRowSql($coll);
        $uow = $this->_em->getUnitOfWork();
        foreach ($deleteDiff as $element) {
            $this->_conn->exec($sql, $uow->getEntityIdentifier($element));
        }
    }
    
    public function updateRows(PersistentCollection $coll)
    {
        
    }
    
    public function insertRows(PersistentCollection $coll)
    {
        if ($coll->getMapping()->isInverseSide()) {
            return; // ignore inverse side
        }

        $insertDiff = $coll->getInsertDiff();
        $sql = $this->_getInsertRowSql($coll);
        $uow = $this->_em->getUnitOfWork();
        foreach ($insertDiff as $element) {
            $this->_conn->exec($sql/*, $uow->getEntityIdentifier($element)*/);
        }
    }

    /**
     * Gets the SQL statement used for deleting a row from the collection.
     * 
     * @param PersistentCollection $coll
     */
    abstract protected function _getDeleteRowSql(PersistentCollection $coll);

    /**
     * Gets the SQL statement used for updating a row in the collection.
     *
     * @param PersistentCollection $coll
     */
    abstract protected function _getUpdateRowSql();

    /**
     * Gets the SQL statement used for inserting a row from to the collection.
     *
     * @param PersistentCollection $coll
     */
    abstract protected function _getInsertRowSql();
}

