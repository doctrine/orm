<?php
class mmrUser_C extends Doctrine_Record 
{
    public function setUp() 
    {
        $this->hasMany('mmrGroup_C as Group', array('local' => 'user_id', 
                                                    'foreign' => 'group_id',
                                                    'refClass' => 'mmrGroupUser_C'));

    }

    public function setTableDefinition()
    {
        // Works when
        $this->hasColumn('u_id as id', 'string', 30, array('primary' => true));
        $this->hasColumn('name', 'string', 30);
    }
}

