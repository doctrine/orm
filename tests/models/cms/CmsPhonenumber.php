<?php
class CmsPhonenumber extends Doctrine_Entity
{
    #protected $user_id;
    #protected $phonenumber;
    
    public static function initMetadata($mapping)
    {
        $mapping->mapField(array(
            'fieldName' => 'user_id',
            'type' => 'integer',
            'length' => 4
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
