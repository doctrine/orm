<?php
class QueryTest_User extends Doctrine_Record 
{   

    public function setTableDefinition()
    {        
        $this->hasColumn('username as username', 'string', 50,
                array('notnull'));
        $this->hasColumn('visibleRankId', 'integer', 4);
    }
    
    /**
     * Runtime definition of the relationships to other entities.
     */
    public function setUp()
    {
        $this->hasOne('QueryTest_Rank as visibleRank', 'QueryTest_User.visibleRankId');
        $this->hasMany('QueryTest_Rank as ranks', 'QueryTest_UserRank.rankId');
    }
}
