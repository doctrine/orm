<?php

#namespace Doctrine\Tests\Models\Forum;

#use Doctrine\ORM\Entity;

class ForumEntry implements Doctrine_ORM_Entity
{
    public $id;
    public $topic;

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

