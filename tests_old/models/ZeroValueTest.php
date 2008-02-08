<?php
class ZeroValueTest extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->setColumn('id', 'integer', 4, array('primary' => true,  'autoincrement' => true,));
        $class->setColumn('username', 'string', 128, array('notnull' => true,));
        $class->setColumn('algorithm', 'string', 128, array('default' => 'sha1', 'notnull' => true,));
        $class->setColumn('salt', 'string', 128, array('notnull' => true,));
        $class->setColumn('password', 'string', 128, array('notnull' => true,));
        $class->setColumn('created_at', 'timestamp', null, array());
        $class->setColumn('last_login', 'timestamp', null, array());
        $class->setColumn('is_active', 'boolean', null, array('default' => true, 'notnull' => true,));
        $class->setColumn('is_super_admin', 'boolean', null, array('default' => false, 'notnull' => true,));
    }
}
