<?php
class CPK_Test2 extends Doctrine_Entity {
    public static function initMetadata($class) {
        $class->setColumn('name', 'string', 255);
        $class->hasMany('CPK_Test as Test', array('local' => 'test2_id', 'test1_id', 'refClass' => 'CPK_Association'));
    }
}
