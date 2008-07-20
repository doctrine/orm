<?php

#namespace Doctrine::ORM::Mappings;

class Doctrine_Association_OneToMany extends Doctrine_Association
{
    /** The target foreign key fields that reference the sourceKeyFields. */
    protected $_targetForeignKeyFields;

    /** The (typically primary) source key fields that are referenced by the targetForeignKeyFields. */
    protected $_sourceKeyFields;

    /** This maps the target foreign key fields to the corresponding (primary) source key fields. */
    protected $_targetForeignKeysToSourceKeys;
    
    /** This maps the (primary) source key fields to the corresponding target foreign key fields. */
    protected $_sourceKeysToTargetForeignKeys;
    
    /** Whether to delete orphaned elements (removed from the collection) */
    protected $_isCascadeDeleteOrphan = false;
    
    public function isCascadeDeleteOrphan()
    {
        return $this->_isCascadeDeleteOrphan;
    }
}

?>