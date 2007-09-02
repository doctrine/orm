<?php
class M2MTest2 extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('oid', 'integer', 11, array('autoincrement', 'primary'));
        $this->hasColumn('name', 'string', 20);
    }
    public function setUp() {
        $this->hasMany('RTC4 as RTC5', 'JC3.c1_id');
    }
}

