<?php
class TestMovieUserVote extends Doctrine_Record  
{    
    public function setTableDefinition() {
        $this->hasColumn('vote', 'string', 30);
        $this->hasColumn('user_id', 'integer', null, array('primary' => true));
        $this->hasColumn('movie_id', 'integer', null, array('primary' => true));
    }
}
