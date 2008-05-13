<?php
class MigrationTest extends Doctrine_Entity
{
    public static function initMetadata($class) 
    {
        $class->setColumn('field1', 'string');
    }
}