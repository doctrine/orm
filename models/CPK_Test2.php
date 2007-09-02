<?php
class CPK_Test2 extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string', 255);
    }
    public function setUp() {
        $this->hasMany('CPK_Test as Test', 'CPK_Association.test1_id');
    }
}
