<?php
class CascadeDeleteTest extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string');
    }
    public function setUp()
    {
        $this->hasMany('CascadeDeleteRelatedTest as Related', 
                        array('local' => 'id',
                              'foreign' => 'cscd_id'));
    }
}
