<?php
class TestMovie extends Doctrine_Record
{

    public function setUp() 
    {
    	$this->hasOne('TestUser as User', 
                        array('local' => 'user_id', 
                              'foreign' => 'id'));

        $this->hasMany('TestUser as MovieBookmarks', 
                        array('local' => 'movie_id',
                              'foreign' => 'user_id',
                              'refClass' => 'TestMovieUserBookmark'));

        $this->hasMany('TestUser as MovieVotes', 
                        array('local' => 'movie_id',
                              'foreign' => 'user_id',
                              'refClass' => 'TestMovieUserVote'));
    }

    public function setTableDefinition() 
    {
    	$this->hasColumn('user_id', 'integer', null);
        $this->hasColumn('name', 'string', 30);
    }
}
