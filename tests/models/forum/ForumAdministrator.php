<?php

#namespace Doctrine\Tests\Models\Forum;

class ForumAdministrator extends ForumUser
{
    public $accessLevel;

    public static function initMetadata($mapping) 
    {
        $mapping->mapField(array(
            'fieldName' => 'accessLevel',
            'columnName' => 'access_level',
            'type' => 'integer'
        ));
    }
}