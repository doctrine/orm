<?php

#namespace Doctrine\Tests\ORM\Models\Cms;

#use Doctrine\ORM\Entity;
#use Doctrine\Common\VirtualPropertySystem;

class CmsUser
{
    public $id;
    public $status;
    public $username;
    public $name;
    public $phonenumbers;
    public $articles;

    /*static function construct() {
        Doctrine_Common_VirtualPropertySystem::register(__CLASS__, 'id', 'int');
        Doctrine_Common_VirtualPropertySystem::register(__CLASS__, 'status', 'int');
        Doctrine_Common_VirtualPropertySystem::register(__CLASS__, 'username', 'string');
        Doctrine_Common_VirtualPropertySystem::register(__CLASS__, 'name', 'string');
        Doctrine_Common_VirtualPropertySystem::register(__CLASS__, 'phonenumbers', 'CmsPhonenumber');
        Doctrine_Common_VirtualPropertySystem::register(__CLASS__, 'articles', 'CmsArticle');
    }*/
    
    public static function initMetadata($mapping)
    {
        /* NEW
        $mapping->addFieldMetadata('id', array(
            'doctrine.id' => true, 'doctrine.idGenerator' => 'auto'
        ));
        $mapping->addFieldMetadata('status', array(
            'doctrine.length' => 50
        ));
        $mapping->addFieldMetadata('phonenumbers', array(
            'doctrine.oneToMany' => array('mappedBy' => 'user')
        ));
        $mapping->addFieldMetadata('articles', array(
            'doctrine.oneToMany' => array('mappedBy' => 'user')
        ));
        */

        $mapping->mapField(array(
            'fieldName' => 'id',
            'type' => 'integer',
            'id' => true,
            'idGenerator' => 'auto'
        ));
        $mapping->mapField(array(
            'fieldName' => 'status',
            'type' => 'string',
            'length' => 50
        ));
        $mapping->mapField(array(
            'fieldName' => 'username',
            'type' => 'string',
            'length' => 255
        ));
        $mapping->mapField(array(
            'fieldName' => 'name',
            'type' => 'string',
            'length' => 255
        ));
        
        $mapping->mapOneToMany(array(
            'fieldName' => 'phonenumbers',
            'targetEntity' => 'CmsPhonenumber',
            'mappedBy' => 'user'
        ));
        
        $mapping->mapOneToMany(array(
            'fieldName' => 'articles',
            'targetEntity' => 'CmsArticle',
            'mappedBy' => 'user'
        ));
        
    }
}
