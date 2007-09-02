<?php
class Role extends Doctrine_Record 
{
    public function setTableDefinition() 
    {
        $this->hasColumn('name', 'string', 20, array('unique' => true));
    }
    public function setUp() 
    {
        $this->hasMany('Auth', array('local' => 'id', 'foreign' => 'roleid'));
    }
}

