<?php
class MysqlGroupMember extends Doctrine_Entity
{
    public function setTableDefinition() 
    {
        $this->hasColumn('group_id', 'integer', null, 'primary');
        $this->hasColumn('user_id', 'integer', null, 'primary');
    }
}

