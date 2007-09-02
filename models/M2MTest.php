<?php
class M2MTest extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string', 200);
        $this->hasColumn('child_id', 'integer');
    }
    public function setUp() {

        $this->hasMany('RTC1 as RTC1', 'JC1.c1_id');
        $this->hasMany('RTC2 as RTC2', 'JC1.c1_id');
        $this->hasMany('RTC3 as RTC3', 'JC2.c1_id');
        $this->hasMany('RTC3 as RTC4', 'JC1.c1_id');

    }
}

