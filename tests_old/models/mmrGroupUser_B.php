<?php
class mmrGroupUser_B extends Doctrine_Entity 
{
    public static function initMetadata($class) 
    {
        $class->setColumn('user_id', 'string', 30, array('primary' => true));
        $class->setColumn('group_id', 'string', 30, array('primary' => true));
    }
}
