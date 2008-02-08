<?php
class Auth extends Doctrine_Record 
{
    public static function initMetadata($class) 
    {
        $class->setColumn('roleid', 'integer', 10);
        $class->setColumn('name', 'string', 50);
        $class->hasOne('Role', array('local' => 'roleid', 'foreign' => 'id'));
    }
}

