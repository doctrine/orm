<?php

#namespace Doctrine\Tests\Models\Forum;

#use Doctrine\ORM\Entity;

class ForumUser
{
    public $id;
    public $username;
    public $avatar;
    
    public static function initMetadata($mapping) 
    {
        /*$mapping->setClassMetadata(array(
            'doctrine.inheritanceType' => 'joined',
            'doctrine.discriminatorColumn' => 'dtype',
            'doctrine.discriminatorMap' => array('user' => 'ForumUser', 'admin' => 'ForumAdministrator'),
            'doctrine.subclasses' => array('ForumAdministrator')
        ));
        $mapping->setFieldMetadata('id', array(
            'doctrine.type' => 'integer',
            'doctrine.id' => true,
            'doctrine.idGenerator' => 'auto'
        ));*/

        // inheritance mapping
        $mapping->setInheritanceType('joined', array(
                'discriminatorColumn' => 'dtype',
                'discriminatorMap' => array(
                        'user' => 'ForumUser',
                        'admin' => 'ForumAdministrator')
                ));
        // register subclasses
        $mapping->setSubclasses(array('ForumAdministrator'));
        
        // column-to-field mapping
        $mapping->mapField(array(
            'fieldName' => 'id',
            'type' => 'integer',
            'id' => true,
            'idGenerator' => 'auto'
        ));
        $mapping->mapField(array(
            'fieldName' => 'username',
            'type' => 'string',
            'length' => 50
        ));
        
        $mapping->mapOneToOne(array(
            'fieldName' => 'avatar',
            'targetEntity' => 'ForumAvatar',
            'joinColumns' => array('avatar_id' => 'id'),
            'cascade' => array('save')
        ));   
    }
}