<?php
class BarRecord extends Doctrine_Record
{
    public function setTableDefinition()
    {
    	$this->setTableName('bar');
    	$this->hasColumn('name', 'string', 200);
    }
    public function setUp()
    {
        $this->hasMany('FooRecord as Foo', array('local' => 'barId', 'foreign' => 'fooId', 'refClass' => 'FooBarRecord'));
    }
}
