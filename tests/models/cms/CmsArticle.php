<?php

#namespace Doctrine::Tests::ORM::Models::CMS;

#use Doctrine::ORM::Entity;

class CmsArticle
{
    public $id;
    public $topic;
    public $text;
    public $user;
    public $comments;

    /*static function construct() {
        Doctrine_Common_VirtualPropertySystem::register(__CLASS__, 'id', 'int');
        Doctrine_Common_VirtualPropertySystem::register(__CLASS__, 'topic', 'string');
        Doctrine_Common_VirtualPropertySystem::register(__CLASS__, 'text', 'string');
        Doctrine_Common_VirtualPropertySystem::register(__CLASS__, 'user_id', 'int');
        Doctrine_Common_VirtualPropertySystem::register(__CLASS__, 'user', 'CmsUser');
        Doctrine_Common_VirtualPropertySystem::register(__CLASS__, 'comments', 'collection');
    }*/
    
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
            'length' => 255
        ));
        $mapping->mapField(array(
            'fieldName' => 'text',
            'type' => 'string'
        ));
        $mapping->mapField(array(
            'fieldName' => 'user_id',
            'type' => 'integer'
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
