<?php

class gnatUserTable { }

class gnatUser extends Doctrine_Record 
{
    public function setTableDefinition() 
    {
        $this->hasColumn('name', 'string', 150);
        $this->hasColumn('email_id', 'integer', 10, array (  'unique' => true,));
    }
    
    public function setUp()
    {
        parent::setUp();
        $this->ownsOne('Email', array('local'=>'email_id','foreign'=>'id','onDelete'=>'CASCADE'));        
    }
    
}

