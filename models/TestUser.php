<?php
class TestUser extends Doctrine_Record 
{
    public function setUp() 
    {
        $this->hasMany('TestMovie as UserBookmarks', 
                        array('local' => 'user_id',
                              'foreign' => 'movie_id',
                              'refClass' => 'TestMovieUserBookmark'));

        $this->hasMany('TestMovie as UserVotes', 
                        array('local' => 'user_id',
                              'foreign' => 'movie_id',
                              'refClass' => 'TestMovieUserVote'));

    }
    public function setTableDefinition() 
    {
        $this->hasColumn('name', 'string', 30);
    }
}
