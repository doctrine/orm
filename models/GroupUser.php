<?php
class Groupuser extends Doctrine_Record
{
    public function setTableDefinition() 
    {
        $this->hasColumn('added', 'integer');
        $this->hasColumn('group_id', 'integer');
        $this->hasColumn('user_id', 'integer');
    }
    
    public function setUp()
    {
        $this->hasOne('Group', array('local' => 'group_id', 'foreign' => 'id'));
        $this->hasOne('User', array('local' => 'user_id', 'foreign' => 'id'));
    }
}
