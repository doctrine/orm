<?php
class MysqlGroup extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', null);

        $this->hasMany('MysqlUser', 'MysqlGroupMember.user_id');
    }
}
