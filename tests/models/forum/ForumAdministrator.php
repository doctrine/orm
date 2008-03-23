<?php

class ForumAdministrator extends ForumUser
{
    public static function initMetadata($class) 
    {
        $class->mapColumn('access_level as accessLevel', 'integer', 1);
    }
    
    public function banUser(ForumUser $user) {}
}