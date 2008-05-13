<?php
class mmrGroup_C extends Doctrine_Entity
{
    public static function initMetadata($class)
    {
        $class->setColumn('g_id as id', 'string', 30, array('primary' => true));
        $class->setColumn('name', 'string', 30);
        $class->hasMany('mmrUser_C', array('local' => 'group_id',
                                          'foreign' => 'user_id',
                                          'refClass' => 'mmrGroupUser_C'));
    }
}
