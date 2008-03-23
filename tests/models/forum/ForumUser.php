<?php

class ForumUser extends Doctrine_Record
{
    public static function initMetadata($class) 
    {
        // inheritance mapping
        $class->setInheritanceType(Doctrine::INHERITANCE_TYPE_JOINED, array(
                'discriminatorColumn' => 'dtype',
                'discriminatorMap' => array(
                        'user' => 'ForumUser',
                        'admin' => 'ForumAdministrator')
                ));
        // register subclasses
        $class->setSubclasses(array('ForumAdministrator'));
        // the discriminator column
        $class->mapColumn('dtype', 'string', 50);
        
        // column-to-field mapping
        $class->mapColumn('id', 'integer', 4, array(
                'primary' => true,
                'autoincrement' => true));
        $class->mapColumn('username', 'string', 50);
        
    }
    /*
    public function getUsername()
    {
        return $this->rawGet('username') . "!";
    }
    */
}