<?php
class Groupuser extends Doctrine_Record
{
    public static function initMetadata($class) 
    {
        $class->setColumn('added', 'integer');
        $class->setColumn('group_id', 'integer', null /*,array('primary' => true)*/);
        $class->setColumn('user_id', 'integer', null /*,array('primary' => true)*/);
        $class->hasOne('Group', array('local' => 'group_id', 'foreign' => 'id'));
        $class->hasOne('User', array('local' => 'user_id', 'foreign' => 'id'));
    }
}