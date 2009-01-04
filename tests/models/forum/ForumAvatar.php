<?php

#namespace Doctrine\Tests\Models\Forum;

#use Doctrine\ORM\Entity;

class ForumAvatar
{
    public $id;

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