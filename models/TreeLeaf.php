<?php
class TreeLeaf extends Doctrine_Record
{
    public function setTableDefinition()
    {
    	$this->hasColumn('name', 'string');
        $this->hasColumn('parent_id', 'integer');
    }

    public function setUp() 
    {
        $this->hasOne('TreeLeaf as Parent', array('local' => 'parent_id', 'foreign' => 'id'));
        $this->hasMany('TreeLeaf as Children', array('local' => 'id', 'foreign' => 'parent_id'));
    }
}