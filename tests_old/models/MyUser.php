<?php
class MyUser extends Doctrine_Entity {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string');
    }
    public function setUp() {
		$this->hasMany('MyOneThing', 'MyOneThing.user_id');
		$this->hasMany('MyOtherThing', 'MyOtherThing.user_id');
    }
}
