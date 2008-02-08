<?php
class BarRecord extends Doctrine_Record
{
    public static function initMetadata($class)
    {
    	$class->setTableName('bar');
    	$class->setColumn('name', 'string', 200);
    	$class->hasMany('FooRecord as Foo', array('local' => 'barId', 'foreign' => 'fooId', 'refClass' => 'FooBarRecord'));
    }
}
