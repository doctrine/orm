<?php

class ForumUser extends Doctrine_Record
{
    public static function initMetadata($class) 
    {
        // inheritance mapping
        $class->setInheritanceType(Doctrine::INHERITANCETYPE_JOINED, array(
                'discriminatorColumn' => 'dtype',
                'discriminatorMap' => array(
                        1 => 'ForumUser',
                        2 => 'ForumAdministrator')
                ));
        $class->setSubclasses(array('ForumAdministrator'));
        
        // property mapping
        $class->addMappedColumn('id', 'integer', 4, array(
                'primary' => true,
                'autoincrement' => true));
        $class->addMappedColumn('username', 'string', 50);
    }
}