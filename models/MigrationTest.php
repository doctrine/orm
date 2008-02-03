<?php
class MigrationTest extends Doctrine_Record
{
    public static function initMetadata($class) 
    {
        $class->setColumn('field1', 'string');
    }
}