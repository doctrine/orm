<?php
class TestRecord extends Doctrine_Record 
{
    public static function initMetadata($class)
    {
        $class->setTableName('test');
    }
}
