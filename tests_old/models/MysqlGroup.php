<?php
class MysqlGroup extends Doctrine_Entity
{
    public static function initMetadata($class)
    {
        $class->setColumn('name', 'string', null);
        $class->hasMany('MysqlUser', array('local' => 'id', 'foreign' => 'user_id'));
    }
}
