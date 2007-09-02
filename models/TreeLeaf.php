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
        $this->hasOne('TreeLeaf as Parent', 'TreeLeaf.parent_id');
        $this->hasMany('TreeLeaf as Children', 'TreeLeaf.parent_id');
    }
}
