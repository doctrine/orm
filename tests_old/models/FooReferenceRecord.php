<?php
class FooReferenceRecord extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->setTableName('foo_reference');
        $class->setColumn('foo1', 'integer', null, array('primary' => true));
        $class->setColumn('foo2', 'integer', null, array('primary' => true));
    }
}
