<?php
class NestTest extends Doctrine_Record
{
    public function setTableDefinition() 
    {
        $this->hasColumn('name', 'string');
    }
    public function setUp()
    {
        $this->hasMany('NestTest as Parents', array('local' => 'child_id',
                                                    'refClass' => 'NestReference',
                                                    'foreign' => 'parent_id'));
        $this->hasMany('NestTest as Children', array('local' => 'parent_id',
                                                     'refClass' => 'NestReference',
                                                     'foreign' => 'child_id'));
                                                     
        $this->hasMany('NestTest as Relatives', array('local' => 'child_id',
                                                      'refClass' => 'NestReference',
                                                      'foreign' => 'parent_id',
                                                      'equal'   => true));
    }
}
