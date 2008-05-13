<?php
class Bookmark extends Doctrine_Entity
{
    public static function initMetadata($class)
    {
        $class->setColumn('user_id', 'integer', null, array('primary' => true));
        $class->setColumn('page_id', 'integer', null, array('primary' => true));
    }
}
