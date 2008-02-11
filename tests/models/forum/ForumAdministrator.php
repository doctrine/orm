<?php

class ForumAdministrator extends ForumUser
{
    public static function initMetadata($class) 
    {
        $class->addMappedColumn('foo', 'string', 50);
    }
}