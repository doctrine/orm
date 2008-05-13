<?php
class TestRecord extends Doctrine_Entity 
{
    public static function initMetadata($class)
    {
        $class->setTableName('test');
    }
}
