<?php
class M2MTest2 extends Doctrine_Record {
    public static function initMetadata($class) {
        $class->setColumn('oid', 'integer', 11, array('autoincrement', 'primary'));
        $class->setColumn('name', 'string', 20);
        $class->hasMany('RTC4 as RTC5', array('local' => 'c2_id', 'foreign' => 'c1_id', 'refClass' => 'JC1'));
    }
}

