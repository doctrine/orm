<?php
class Entity extends Doctrine_Record 
{
    public function setUp() 
    {
        $this->ownsOne('Email', array('local' => 'email_id'));
        $this->hasMany('Phonenumber', array('local' => 'id', 'foreign' => 'entity_id'));
        $this->ownsOne('Account', array('foreign' => 'entity_id'));
        $this->hasMany('Entity', array('local' => 'entity1', 
            'refClass' => 'EntityReference',
            'foreign' => 'entity2',
            'equal'    => true));
    }
    public function setTableDefinition() 
    {
        $this->hasColumn('id', 'integer',20, 'autoincrement|primary');
        $this->hasColumn('name', 'string',50);
        $this->hasColumn('loginname', 'string',20, array('unique'));
        $this->hasColumn('password', 'string',16);
        $this->hasColumn('type', 'integer',1);
        $this->hasColumn('created', 'integer',11);
        $this->hasColumn('updated', 'integer',11);
        $this->hasColumn('email_id', 'integer');
        $this->setSubclasses(array("User" => array("type" => 0), "Group" => array("type" => 1)));
    }
}
