<?php
class Phonenumber extends Doctrine_Record 
{
    public function setTableDefinition() 
    {
        $this->hasColumn('phonenumber', 'string',20);
        $this->hasColumn('entity_id', 'integer');
    }
    public function setUp() 
    {
        $this->hasOne('Entity', array('local' => 'entity_id', 
                                      'foreign' => 'id', 
                                      'onDelete' => 'CASCADE'));
        
        $this->hasOne('Group', array('local' => 'entity_id', 
                                      'foreign' => 'id', 
                                      'onDelete' => 'CASCADE'));
          
        $this->hasOne('User', array('local' => 'entity_id', 
                                    'foreign' => 'id', 
                                    'onDelete' => 'CASCADE'));
    }
}
