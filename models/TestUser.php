<?php
class TestUser extends Doctrine_Record 
{
    public static function initMetadata($class) 
    {
        $class->setColumn('name', 'string', 30);
        $class->hasMany('TestMovie as UserBookmarks', 
                        array('local' => 'user_id',
                              'foreign' => 'movie_id',
                              'refClass' => 'TestMovieUserBookmark'));

        $class->hasMany('TestMovie as UserVotes', 
                        array('local' => 'user_id',
                              'foreign' => 'movie_id',
                              'refClass' => 'TestMovieUserVote'));

    }

}
