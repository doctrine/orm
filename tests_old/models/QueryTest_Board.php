<?php
class QueryTest_Board extends Doctrine_Entity
{
    /**
     * Initializes the table definition.
     */
    public static function initMetadata($class)
    {        
        $class->setColumn('categoryId as categoryId', 'integer', 4,
                array('notnull'));
        $class->setColumn('name as name', 'string', 100,
                array('notnull', 'unique'));
        $class->setColumn('lastEntryId as lastEntryId', 'integer', 4,
                array('default' => 0, 'notnull'));
        $class->setColumn('position as position', 'integer', 4,
                array('default' => 0, 'notnull'));
                
        $class->hasOne('QueryTest_Category as category', array('local' => 'categoryId', 'foreign' => 'id'));
        $class->hasOne('QueryTest_Entry as lastEntry', array('local' => 'lastEntryId', 'foreign' => 'id'));
    }
}
