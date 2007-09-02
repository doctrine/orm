<?php
class MysqlUser extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', null);

        $this->hasMany('MysqlGroup', 'MysqlGroupMember.group_id');
    }
}
