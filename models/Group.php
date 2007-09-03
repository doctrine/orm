<?php
// grouptable doesn't extend Doctrine_Table -> Doctrine_Connection
// won't initialize grouptable when Doctrine_Connection->getTable('Group') is called

require_once('Entity.php');

class GroupTable { }
class Group extends Entity {
    public function setUp() {
        parent::setUp();
        $this->hasMany('User', 'Groupuser.user_id');
//        $this->option('inheritanceMap', array('type' => 1));
    }
}

