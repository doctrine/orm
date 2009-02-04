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
     * @todo Identifier quoting.
     * @see _getDeleteRowSqlParameters()
     */
    protected function _getDeleteRowSql(PersistentCollection $coll)
    {
        $mapping = $coll->getMapping();
        $joinTable = $mapping->getJoinTable();
        $columns = array_merge($mapping->getSourceKeyColumns(), $mapping->getTargetKeyColumns());
        return "DELETE FROM $joinTable WHERE " . implode(' = ?, ', $columns) . ' = ?';
    }

    /**
     *
     * @param <type> $element
     * @override
     * @see _getDeleteRowSql()
     */
    protected function _getDeleteRowSqlParameters(PersistentCollection $coll, $element)
    {
        $owner = $coll->getOwner();

        
        
    }
}

