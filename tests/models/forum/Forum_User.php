<?php

class Forum_User extends Doctrine_Record
{
    public static function initMetadata($class) 
    {
        // inheritance mapping
        $class->setInheritanceType(Doctrine::INHERITANCETYPE_JOINED, array(
                'discriminatorColumn' => 'dtype',
                'discriminatorMap' => array(
                        1 => 'Forum_User',
                        2 => 'Forum_Administrator')
                ));
        $class->setSubclasses(array('Forum_Administrator'));
        
        // property mapping
        $class->addMappedColumn('id', 'integer', 4, array(
                'primary' => true,
                'autoincrement' => true));
        $class->addMappedColumn('username', 'string', 50);
    }
}