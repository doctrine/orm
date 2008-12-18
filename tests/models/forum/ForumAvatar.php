<?php

#namespace Doctrine\Tests\ORM\Models\Forum;

#use Doctrine\ORM\Entity;
#use Doctrine\Common\VirtualPropertySystem;

class ForumAvatar
{
    public $id;

    /*static function construct() {
        Doctrine_Common_VirtualPropertySystem::register(__CLASS__, 'id', 'int');
    }*/

    public static function initMetadata($mapping) 
    {
        $mapping->mapField(array(
            'fieldName' => 'id',
            'type' => 'integer',
            'id' => true,
            'idGenerator' => 'auto'
        ));
    }
}


?>