<?php
class CPK_Test extends Doctrine_Record {
    public static function initMetadata($class) {
        $class->setColumn('name', 'string', 255);
        $class->hasMany('CPK_Test2 as Test', array('local' => 'test_id', 'foreign' => 'test2_id', 'refClass' => 'CPK_Association'));
    }
}
