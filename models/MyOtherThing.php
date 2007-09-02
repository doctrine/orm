<?php
class MyOtherThing extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string');
        $this->hasColumn('user_id', 'integer');
    }
    public function setUp() {
		$this->hasMany('MyUserOtherThing', 'MyUserOtherThing.other_thing_id');
    }
}
