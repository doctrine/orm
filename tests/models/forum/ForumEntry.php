<?php

#namespace Doctrine::Test::ORM::Models;

#use Doctrine::ORM::Entity;

class ForumEntry extends Doctrine_ORM_Entity
{
    public $id;
    public $topic;

    static function construct() {
        Doctrine_Common_VirtualPropertySystem::register(__CLASS__, 'id', 'int');
        Doctrine_Common_VirtualPropertySystem::register(__CLASS__, 'topic', 'string');
    }
    
    public static function initMetadata($mapping) 
    {
        $mapping->mapField(array(
                'fieldName' => 'id',
                'type' => 'integer',
                'id' => true,
                'idGenerator' => 'auto'
                ));
        $mapping->mapField(array(
                'fieldName' => 'topic',
                'type' => 'string',
                'length' => 50
                ));
        
    }

    
}

?>