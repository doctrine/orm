<?php

namespace Doctrine\ORM\Persisters;

use Doctrine\ORM\Collection;

class AbstractCollectionPersister
{
    
    public function recreate(Doctrine_Collection $coll)
    {
        if ($coll->getRelation()->isInverseSide()) {
            return;
        }
        //...
    }
    
    public function delete(Doctrine_Collection $coll)
    {
        if ($coll->getRelation()->isInverseSide()) {
            return;
        }
        //...
    }
    
    /* collection update actions */
    
    public function deleteRows(Collection $coll)
    {
        //$collection->getDeleteDiff();
    }
    
    public function updateRows(Collection $coll)
    {
        
    }
    
    public function insertRows(Collection $coll)
    {
        //$collection->getInsertDiff();
    }

    protected function _getDeleteRowSql()
    {
        
    }

    protected function _getUpdateRowSql()
    {
        
    }

    protected function _getDeleteRowSql()
    {
        
    }
}

