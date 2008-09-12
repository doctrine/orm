<?php

class Doctrine_ORM_Persisters_AbstractCollectionPersister
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
        if ($coll->getRelation() instanceof Doctrine_Association_OneToManyMapping) {
            //...
        } else if ($coll->getRelation() instanceof Doctrine_Association_ManyToManyMapping) {
            //...
        }
    }
    
    /* collection update actions */
    
    public function deleteRows()
    {
        
    }
    
    public function updateRows()
    {
        
    }
    
    public function insertRows()
    {
        
    }
    
}

?>