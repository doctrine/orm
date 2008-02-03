<?php
class FooRecord extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->setTableName('foo');
        $class->setColumn('name', 'string', 200, array('notnull' => true));
        $class->setColumn('parent_id', 'integer');
        $class->setColumn('local_foo', 'integer');
        
        $class->hasMany('FooRecord as FooFriend', array('local'    => 'foo1',
                                                       'foreign'  => 'foo2',
                                                       'equal'    => true,
                                                       'refClass' => 'FooReferenceRecord',
                                                       ));

        $class->hasMany('FooRecord as FooParents', array('local'    => 'foo1',
                                                        'foreign'  => 'foo2',
                                                        'refClass' => 'FooReferenceRecord',
                                                        'onDelete' => 'RESTRICT',
                                                        ));

        $class->hasMany('FooRecord as FooChildren', array('local'    => 'foo2',
                                                         'foreign'  => 'foo1',
                                                         'refClass' => 'FooReferenceRecord',
                                                         ));

        $class->hasMany('FooRecord as Children', array('local' => 'id', 'foreign' => 'parent_id'));

        $class->hasOne('FooRecord as Parent', array('local' => 'parent_id', 'foreign' => 'id', 'onDelete' => 'CASCADE'));
        $class->hasOne('FooLocallyOwned', array('local' => 'local_foo', 'onDelete' => 'RESTRICT'));
        
        $class->hasMany('BarRecord as Bar', array('local' => 'fooId',
                                                 'foreign' => 'barId',
                                                 'refClass' => 'FooBarRecord',
                                                 'onUpdate' => 'RESTRICT'));
    }
}
