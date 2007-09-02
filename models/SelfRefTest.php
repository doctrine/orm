<?php
class SelfRefTest extends Doctrine_Record
{
    public function setTableDefinition() 
    {
        $this->hasColumn('name', 'string', 50);
        $this->hasColumn('created_by', 'integer');
    }
    public function setUp()
    {
        $this->hasOne('SelfRefTest as createdBy', array('local' => 'created_by'));
    }
}

