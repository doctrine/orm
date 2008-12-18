<?php
class CmsPhonenumber
{
    public $phonenumber;
    public $user;

    /*static function construct() {
        Doctrine_Common_VirtualPropertySystem::register(__CLASS__, 'user_id', 'int');
        Doctrine_Common_VirtualPropertySystem::register(__CLASS__, 'phonenumber', 'string');
        Doctrine_Common_VirtualPropertySystem::register(__CLASS__, 'user', 'CmsUser');
    }*/
    
    public static function initMetadata($mapping)
    {
        $mapping->mapField(array(
            'fieldName' => 'user_id',
            'type' => 'integer'
        ));
        $mapping->mapField(array(
            'fieldName' => 'phonenumber',
            'type' => 'string',
            'length' => 50,
            'id' => true
        ));
        
        $mapping->mapManyToOne(array(
            'fieldName' => 'user',
            'targetEntity' => 'CmsUser',
            'joinColumns' => array('user_id' => 'id')
        ));
    }
}
