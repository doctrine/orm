<?php

class ForumAdministrator extends ForumUser
{
    public static function initMetadata($mapping) 
    {
        $mapping->mapField(array(
            'fieldName' => 'accessLevel',
            'columnName' => 'access_level',
            'type' => 'integer',
            'length' => 1
        ));
    }
    
    public function banUser(ForumUser $user) {}
}