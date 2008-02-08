<?php
class CustomSequenceRecord extends Doctrine_Record {
    public static function initMetadata($class)
    {
        $class->setColumn('id', 'integer', null, array('primary', 'sequence' => 'custom_seq'));
        $class->setColumn('name', 'string');
    }
}

