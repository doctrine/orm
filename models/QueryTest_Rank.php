<?php
class QueryTest_Rank extends Doctrine_Record 
{
    /**
     * Initializes the table definition.
     */
    public function setTableDefinition()
    {        
        $this->hasColumn('title as title', 'string', 100,
                array('notnull'));
        $this->hasColumn('color as color', 'string', 20,
                array('notnull', 'regexp' => '/^[a-zA-Z\-]{3,}|#[0-9a-fA-F]{6}$/D'));
        $this->hasColumn('icon as icon', 'string', 50,
                array('notnull', 'default' => ' ', 'regexp' => '/^[a-zA-Z0-9_\-]+\.(jpg|gif|png)$/D'));        
    }
    public function setUp()
    {
        $this->hasMany('QueryTest_User as users', 'QueryTest_UserRank.userId');
    }
}
