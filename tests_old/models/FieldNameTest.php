<?php
class FieldNameTest extends Doctrine_Record 
{
    public static function initMetadata($class) 
    {
        $class->setColumn('someColumn', 'string', 200, array('default' => 'some string'));
        $class->setColumn('someEnum', 'enum', 4, array('default' => 'php', 'values' => array('php', 'java', 'python')));
        $class->setColumn('someArray', 'array', 100, array('default' => array()));
        $class->setColumn('someObject', 'object', 200, array('default' => new stdClass));
        $class->setColumn('someInt', 'integer', 20, array('default' => 11));
    }
}
