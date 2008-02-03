<?php
class BooleanTest extends Doctrine_Record {
    public static function initMetadata($class) {
        $class->setColumn('is_working', 'boolean');
        $class->setColumn('is_working_notnull', 'boolean', 1, array('default' => false, 'notnull' => true));
    }
}
