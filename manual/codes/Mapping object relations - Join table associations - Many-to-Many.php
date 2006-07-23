<?php
class User extends Doctrine_Record { 
    public function setUp() {
        $this->hasMany("Group","Groupuser.group_id");
    }
    public function setTableDefinition() {
        $this->hasColumn("name","string",30);
    }
}

class Group extends Doctrine_Record {
    public function setUp() {
        $this->hasMany("User","Groupuser.user_id");
    }
    public function setTableDefinition() {
        $this->hasColumn("name","string",30);
    }
}

class Groupuser extends Doctrine_Record { 
    public function setTableDefinition() {
        $this->hasColumn("user_id","integer");
        $this->hasColumn("group_id","integer");
    }
}


$user = new User();

// add two groups
$user->Group[0]->name = "First Group";

$user->Group[1]->name = "Second Group";

// save changes into database
$user->save();

$groups = new Doctrine_Collection($session->getTable("Group"));

$groups[0]->name = "Third Group";

$groups[1]->name = "Fourth Group";

$user->Group[2] = $groups[0];
// $user will now have 3 groups

$user->Group = $groups;
// $user will now have two groups 'Third Group' and 'Fourth Group'
?>
