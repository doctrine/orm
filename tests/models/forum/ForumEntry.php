<?php

#namespace Doctrine::Test::ORM::Models;

#use Doctrine::ORM::Entity;

class ForumEntry extends Doctrine_ORM_Entity
{
    #protected $id;
    #protected $topic;
    
    public static function initMetadata($mapping) 
    {
        $mapping->mapField(array(
                'fieldName' => 'id',
                'type' => 'integer',
                'length' => 4,
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