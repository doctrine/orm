<?php
class QueryTest_Rank extends Doctrine_Record 
{
    /**
     * Initializes the table definition.
     */
    public static function initMetadata($class)
    {        
        $class->setColumn('title as title', 'string', 100,
                array('notnull'));
        $class->setColumn('color as color', 'string', 20,
                array('notnull', 'regexp' => '/^[a-zA-Z\-]{3,}|#[0-9a-fA-F]{6}$/D'));
        $class->setColumn('icon as icon', 'string', 50,
                array('notnull', 'default' => ' ', 'regexp' => '/^[a-zA-Z0-9_\-]+\.(jpg|gif|png)$/D')); 
        
        $class->hasMany('QueryTest_User as users', array('local' => 'rankId', 'foreign' => 'userId', 'refClass' => 'QueryTest_UserRank'));     
    }
}
