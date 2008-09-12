<?php

#namespace Doctrine::Tests::ORM::Models::CMS;

#use Doctrine::ORM::Entity;

class CmsArticle extends Doctrine_ORM_Entity
{
    #protected $id;
    #protected $topic;
    #protected $text;
    #protected $user_id;
    
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
            'length' => 255
        ));
        $mapping->mapField(array(
            'fieldName' => 'text',
            'type' => 'string'
        ));
        $mapping->mapField(array(
            'fieldName' => 'user_id',
            'type' => 'integer',
            'length' => 4
        ));
        
        
        $mapping->mapOneToMany(array(
            'fieldName' => 'comments',
            'targetEntity' => 'CmsComment',
            'mappedBy' => 'article'
        ));
        
        $mapping->mapManyToOne(array(
            'fieldName' => 'user',
            'targetEntity' => 'CmsUser',
            'joinColumns' => array('user_id' => 'id')
        ));
    }
}
