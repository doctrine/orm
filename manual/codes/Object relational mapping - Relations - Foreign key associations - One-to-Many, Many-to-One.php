<?php
class User extends Doctrine_Record {
    public function setUp() {
        $this->ownsMany('Phonenumber','Phonenumber.user_id');
    }
    public function setTableDefition() {
        $this->hasColumn('name','string',50);
        $this->hasColumn('loginname','string',20);
        $this->hasColumn('password','string',16);
    }
}
class Phonenumber extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('phonenumber','string',50);
        $this->hasColumn('user_id','integer');
    }
}
?>
