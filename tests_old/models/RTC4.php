<?php
class RTC4 extends Doctrine_Record {
    public static function initMetadata($class) {
        $class->setColumn('oid', 'integer', 11, array('autoincrement', 'primary'));  
        $class->setColumn('name', 'string', 20);
        $class->hasMany('M2MTest2', array('local' => 'c1_id', 'foreign' => 'c2_id', 'refClass' => 'JC3'));
    }
}
