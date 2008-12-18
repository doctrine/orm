<?php

#namespace Doctrine::Tests::ORM::Models::CMS;

#use Doctrine::ORM::Entity;

class CmsComment
{
    public $id;
    public $topic;
    public $text;
    public $article;

    /*static function construct() {
        Doctrine_Common_VirtualPropertySystem::register(__CLASS__, 'id', 'int');
        Doctrine_Common_VirtualPropertySystem::register(__CLASS__, 'topic', 'string');
        Doctrine_Common_VirtualPropertySystem::register(__CLASS__, 'text', 'string');
        Doctrine_Common_VirtualPropertySystem::register(__CLASS__, 'article_id', 'int');
        Doctrine_Common_VirtualPropertySystem::register(__CLASS__, 'article', 'CmsArticle');
    }*/
    
    public static function initMetadata($mapping)
    {
        $mapping->mapField(array(
            'fieldName' => 'id',
            'type' => 'integer',
            'id' => true,
            'generatorType' => 'auto'
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
            'fieldName' => 'article_id',
            'type' => 'integer'
        ));
        
        $mapping->mapManyToOne(array(
            'fieldName' => 'article',
            'targetEntity' => 'CmsArticle',
            'joinColumns' => array('article_id' => 'id')
        ));
    }
}
