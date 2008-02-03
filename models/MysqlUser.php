<?php
class MysqlUser extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->setColumn('name', 'string', null);
        $class->hasMany('MysqlGroup', array('local' => 'id', 'foreign' => 'group_id'));
    }
}
