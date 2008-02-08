<?php
class MysqlGroupMember extends Doctrine_Record
{
    public function setTableDefinition() 
    {
        $this->hasColumn('group_id', 'integer', null, 'primary');
        $this->hasColumn('user_id', 'integer', null, 'primary');
    }
}

