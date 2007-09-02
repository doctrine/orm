<?php
class Auth extends Doctrine_Record 
{
    public function setTableDefinition() 
    {
        $this->hasColumn('roleid', 'integer', 10);
        $this->hasColumn('name', 'string', 50);
    }
    public function setUp() 
    {
        $this->hasOne('Role', array('local' => 'roleid', 'foreign' => 'id'));
    }
}

