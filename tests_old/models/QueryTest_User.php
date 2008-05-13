<?php
class QueryTest_User extends Doctrine_Entity 
{   

    public static function initMetadata($class)
    {        
        $class->setColumn('username as username', 'string', 50,
                array('notnull'));
        $class->setColumn('visibleRankId', 'integer', 4);
        $class->hasOne('QueryTest_Rank as visibleRank', array('local' => 'visibleRankId', 'foreign' => 'id'));
        $class->hasMany('QueryTest_Rank as ranks', array('local' => 'userId', 'foreign' => 'rankId', 'refClass' => 'QueryTest_UserRank'));
    }
}
