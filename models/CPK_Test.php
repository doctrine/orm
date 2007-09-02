<?php
class CPK_Test extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string', 255);
    }
    public function setUp() {
        $this->hasMany('CPK_Test2 as Test', 'CPK_Association.test2_id');
    }
}
