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
                                                        'onDelete' => 'RESTRICT',
                                                        ));

        $this->hasMany('FooRecord as FooChildren', array('local'    => 'foo2',
                                                         'foreign'  => 'foo1',
                                                         'refClass' => 'FooReferenceRecord',
                                                         ));

        $this->hasMany('FooRecord as Children', array('local' => 'id', 'foreign' => 'parent_id'));

        $this->hasOne('FooRecord as Parent', array('local' => 'parent_id', 'foreign' => 'id', 'onDelete' => 'CASCADE'));
        $this->hasOne('FooForeignlyOwnedWithPk', array('local' => 'id', 'foreign' => 'id', 'constraint' => true));
        $this->hasOne('FooLocallyOwned', array('local' => 'local_foo', 'onDelete' => 'RESTRICT'));
        
        $this->hasMany('BarRecord as Bar', array('local' => 'fooId',
                                                 'foreign' => 'barId',
                                                 'refClass' => 'FooBarRecord',
                                                 'onUpdate' => 'RESTRICT'));

    }
}
