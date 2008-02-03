<?php
class CPK_Association extends Doctrine_Record {
    public static function initMetadata($class) {
        $class->setColumn('test1_id', 'integer', 11, 'primary');
        $class->setColumn('test2_id', 'integer', 11, 'primary');
    }
}
