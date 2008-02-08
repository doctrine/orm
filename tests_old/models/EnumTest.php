<?php
class EnumTest extends Doctrine_Record 
{
    public static function initMetadata($class) {
        $class->setColumn('status', 'enum', 11, array('values' => array('open', 'verified', 'closed')));
        $class->setColumn('text', 'string');
        $class->hasMany('EnumTest2 as Enum2', array('local' => 'id', 'foreign' => 'enum_test_id'));
        $class->hasMany('EnumTest3 as Enum3', array('local' => 'text', 'foreign' => 'text'));
    }
}
