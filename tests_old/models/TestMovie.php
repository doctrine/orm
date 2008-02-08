<?php
class TestMovie extends Doctrine_Record
{

    public static function initMetadata($class) 
    {
        $class->setColumn('user_id', 'integer', null);
        $class->setColumn('name', 'string', 30);
        
    	$class->hasOne('TestUser as User', 
                        array('local' => 'user_id', 
                              'foreign' => 'id'));

        $class->hasMany('TestUser as MovieBookmarks', 
                        array('local' => 'movie_id',
                              'foreign' => 'user_id',
                              'refClass' => 'TestMovieUserBookmark'));

        $class->hasMany('TestUser as MovieVotes', 
                        array('local' => 'movie_id',
                              'foreign' => 'user_id',
                              'refClass' => 'TestMovieUserVote'));
    }

}
