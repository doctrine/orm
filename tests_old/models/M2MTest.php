<?php
class M2MTest extends Doctrine_Entity {
    public static function initMetadata($class) {
        $class->setColumn('name', 'string', 200);
        $class->setColumn('child_id', 'integer');
        $class->hasMany('RTC1 as RTC1', array('local' => 'c2_id', 'foreign' => 'c1_id', 'refClass' => 'JC1'));
        $class->hasMany('RTC2 as RTC2', array('local' => 'c2_id', 'foreign' => 'c1_id', 'refClass' => 'JC1'));
        $class->hasMany('RTC3 as RTC3', array('local' => 'c2_id', 'foreign' => 'c1_id', 'refClass' => 'JC2'));
        $class->hasMany('RTC3 as RTC4', array('local' => 'c2_id', 'foreign' => 'c1_id', 'refClass' => 'c1_id'));
    }
}

