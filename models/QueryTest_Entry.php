<?php
class QueryTest_Entry extends Doctrine_Record
{
    /**
     * Table structure.
     */
    public static function initMetadata($class)
    {        
        $class->setColumn('authorId', 'integer', 4,
                array('notnull'));
        $class->setColumn('date', 'integer', 4,
                array('notnull'));
        $class->hasOne('QueryTest_User as author', array('local' => 'authorId', 'foreign' => 'id'));
    }
}
