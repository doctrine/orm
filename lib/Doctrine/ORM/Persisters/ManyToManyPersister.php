<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Persisters;

/**
 * Persister for many-to-many collections.
 *
 * @author robo
 */
class ManyToManyPersister extends AbstractCollectionPersister
{
    /**
     *
     * @param <type> $coll
     * @override
     */
    protected function _getDeleteRowSql(PersistentCollection $coll)
    {
        
    }
}

