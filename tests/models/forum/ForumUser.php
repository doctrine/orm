<?php

#namespace Doctrine::Tests::ORM::Models::Forum;

#use Doctrine::ORM::Entity;

class ForumUser extends Doctrine_Entity
{
    #protected $dtype;
    #protected $id;
    #protected $username;
    
    public static function initMetadata($mapping) 
    {
        // inheritance mapping
        $mapping->setInheritanceType('joined', array(
                'discriminatorColumn' => 'dtype',
                'discriminatorMap' => array(
                        'user' => 'ForumUser',
                        'admin' => 'ForumAdministrator')
                ));
        // register subclasses
        $mapping->setSubclasses(array('ForumAdministrator'));
        // the discriminator column
        $mapping->mapField(array(
            'fieldName' => 'dtype',
            'type' => 'string',
            'length' => 50
        ));
        
        // column-to-field mapping
        $mapping->mapField(array(
            'fieldName' => 'id',
            'type' => 'integer',
            'length' => 4,
            'id' => true,
            'idGenerator' => 'auto'
        ));
        $mapping->mapField(array(
            'fieldName' => 'username',
            'type' => 'string',
            'length' => 50
        ));
        
    }
    
}