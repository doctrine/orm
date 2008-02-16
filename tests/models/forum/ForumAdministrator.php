<?php

class ForumAdministrator extends ForumUser
{
    public static function initMetadata($class) 
    {
        $class->mapColumn('foo', 'string', 50);
    }
}