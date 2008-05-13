<?php
class NestTest extends Doctrine_Entity
{
    public static function initMetadata($class) 
    {
        $class->setColumn('name', 'string');
        $class->hasMany('NestTest as Parents', array('local' => 'child_id',
                                                    'refClass' => 'NestReference',
                                                    'foreign' => 'parent_id'));
        $class->hasMany('NestTest as Children', array('local' => 'parent_id',
                                                     'refClass' => 'NestReference',
                                                     'foreign' => 'child_id'));
                                                     
        $class->hasMany('NestTest as Relatives', array('local' => 'child_id',
                                                      'refClass' => 'NestReference',
                                                      'foreign' => 'parent_id',
                                                      'equal'   => true));
    }
}
