<?php
class EnumTest2 extends Doctrine_Entity 
{
    public static function initMetadata($class) {
        $class->setColumn('status', 'enum', 11, array('values' => array('open', 'verified', 'closed')));
        $class->setColumn('enum_test_id', 'integer');
    }
}
