<?php

class mmrGroupUser_C extends Doctrine_Record 
{
    public function setTableDefinition() 
    {
        $this->hasColumn('user_id', 'string', 30, array('primary' => true));
        $this->hasColumn('group_id', 'string', 30, array('primary' => true));
    }
}
