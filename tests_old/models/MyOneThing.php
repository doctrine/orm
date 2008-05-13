<?php
class MyOneThing extends Doctrine_Entity {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string');
        $this->hasColumn('user_id', 'integer');
    }
    public function setUp() {
		$this->hasMany('MyUserOneThing', 'MyUserOneThing.one_thing_id');
    }
}
