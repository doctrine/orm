<?php
class FooRecord extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('foo');
        
        $this->hasColumn('name', 'string', 200, array('notnull' => true));
        $this->hasColumn('parent_id', 'integer');
        $this->hasColumn('local_foo', 'integer');
    }
    public function setUp()
    {
        $this->hasMany('FooRecord as FooFriend', array('local'    => 'foo1',
                                                       'foreign'  => 'foo2',
                                                       'equal'    => true,
                                                       'refClass' => 'FooReferenceRecord',
                                                       ));

        $this->hasMany('FooRecord as FooParents', array('local'    => 'foo1',
                                                        'foreign'  => 'foo2',
                                                        'refClass' => 'FooReferenceRecord',
                                                        'onDelete' => 'CASCADE',
                                                        ));

        $this->hasMany('FooRecord as FooChildren', array('local'    => 'foo2',
                                                         'foreign'  => 'foo1',
                                                         'refClass' => 'FooReferenceRecord',
                                                         ));

        $this->hasMany('FooRecord as Children', array('local' => 'id', 'foreign' => 'parent_id'));

        $this->hasOne('FooRecord as Parent', array('local' => 'parent_id', 'foreign' => 'id', 'onDelete' => 'CASCADE'));
        $this->hasOne('FooForeignlyOwnedWithPk', array('local' => 'id', 'foreign' => 'id', 'constraint' => true));
        $this->hasOne('FooLocallyOwned', array('local' => 'local_foo', 'onDelete' => 'RESTRICT'));

    }
}
class FooBarRecord extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('fooId', 'integer', null, array('primary' => true)); 
        $this->hasColumn('barId', 'integer', null, array('primary' => true)); 	
    }
}
class BarRecord extends Doctrine_Record 
{
    public function setTableDefinition()
    {
    	$this->hasColumn('name', 'string', 200);
    }
}
class FooLocallyOwned extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', 200);
    }
}
class FooForeignlyOwned extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', 200);
        $this->hasColumn('fooId', 'integer');
    }
}
class FooForeignlyOwnedWithPk extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', 200);
    }
    public function setUp()
    {
        $this->hasOne('FooRecord', array('local' => 'id', 'foreign' => 'id'));
    }
}
class FooReferenceRecord extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('foo_reference');
        
        $this->hasColumn('foo1', 'integer', null, array('primary' => true));
        $this->hasColumn('foo2', 'integer', null, array('primary' => true));
    }
}
