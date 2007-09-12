<?php
class InheritanceDeal extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('inheritance_deal');
        
        $this->hasColumn('id', 'integer', 4, array (  'primary' => true,  'autoincrement' => true,));
        $this->hasColumn('name', 'string', 255, array ());
    }
  
    public function setUp()
    {
        $this->hasMany('InheritanceUser as Users', array('refClass' => 'InheritanceDealUser', 'local' => 'entity_id', 'foreign' => 'user_id'));
    }
}