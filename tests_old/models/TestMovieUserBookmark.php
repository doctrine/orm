<?php
class TestMovieUserBookmark extends Doctrine_Entity 
{
    public static function initMetadata($class) 
    {
        $class->setColumn('user_id', 'integer', null, array('primary' => true));
        $class->setColumn('movie_id', 'integer', null, array('primary' => true));
    }
}

