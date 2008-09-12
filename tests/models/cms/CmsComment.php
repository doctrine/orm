<?php

#namespace Doctrine::Tests::ORM::Models::CMS;

#use Doctrine::ORM::Entity;

class CmsComment extends Doctrine_ORM_Entity
{
    #protected $id;
    #protected $topic;
    #protected $text;
    #protected $article_id;
    
    public static function initMetadata($mapping)
    {
        $mapping->mapField(array(
            'fieldName' => 'id',
            'type' => 'integer',
            'length' => 4,
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
            'type' => 'integer',
            'length' => 4
        ));
        
        $mapping->mapManyToOne(array(
            'fieldName' => 'article',
            'targetEntity' => 'CmsArticle',
            'joinColumns' => array('article_id' => 'id')
        ));
    }
}
