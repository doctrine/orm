<?php

require_once('Entity.php');

// grouptable doesn't extend Doctrine_Table -> Doctrine_Connection
// won't initialize grouptable when Doctrine_Connection->getTable('Group') is called
class GroupTable { }

class Group extends Entity
{
    public function setUp()
    {
        parent::setUp();
        $this->hasMany('User', array(
            'local' => 'group_id',
            'foreign' => 'user_id',
            'refClass' => 'Groupuser',
            'refRelationName' => 'GroupGroupuser',
            'refReverseRelationName' => 'UserGroupuser'
        ));
        /*$this->hasMany('Groupuser as User', array(
                'local' => 'id', 'foreign' => 'group_id'
        ));*/
    }
}

