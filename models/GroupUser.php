<?php
class Groupuser extends Doctrine_Record
{
    public function setTableDefinition() 
    {
        $this->hasColumn('added', 'integer');
        $this->hasColumn('group_id', 'integer');
        $this->hasColumn('user_id', 'integer');
    }
}
