<?php
class RTC3 extends Doctrine_Record {
    public function setTableDefinition() { 
        $this->hasColumn('name', 'string', 200);
    }
    public function setUp() {
        $this->hasMany('M2MTest as RTC3', 'JC2.c2_id');
        $this->hasMany('M2MTest as RTC4', 'JC1.c2_id');
    }
}

