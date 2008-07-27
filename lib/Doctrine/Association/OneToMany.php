<?php

#namespace Doctrine::ORM::Mappings;

class Doctrine_Association_OneToMany extends Doctrine_Association
{
    /** The target foreign key columns that reference the sourceKeyColumns. */
    protected $_targetForeignKeyColumns;

    /** The (typically primary) source key columns that are referenced by the targetForeignKeyColumns. */
    protected $_sourceKeyColumns;

    /** This maps the target foreign key columns to the corresponding (primary) source key columns. */
    protected $_targetForeignKeysToSourceKeys;
    
    /** This maps the (primary) source key columns to the corresponding target foreign key columns. */
    protected $_sourceKeysToTargetForeignKeys;
    
    /** Whether to delete orphaned elements (removed from the collection) */
    protected $_deleteOrphans = false;
    
    public function shouldDeleteOrphans()
    {
        return $this->_deleteOrphans;
    }
}

?>