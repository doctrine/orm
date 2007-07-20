<?php
class Doctrine_Relation_ManyToMany2_TestCase extends Doctrine_UnitTestCase {
	public function prepareData() {
	}
	
    public function prepareTables() {
    	$this->tables = array('TestUser', 'TestMovie', 'TestMovieUserBookmark', 'TestMovieUserVote');
        parent::prepareTables();
    }
    
    public function testManyToManyCreateSelectAndUpdate() {
    	$user = new TestUser();
        $user['name'] = 'tester';
        $user->save();
    	
    	$data = new TestMovie();
    	$data['name'] = 'movie';
    	$data['User'] =  $user;
    	$data['MovieBookmarks'][0] = $user;
    	$data['MovieVotes'][0] = $user;
    	$data->save(); //All ok here
        
        $this->conn->clear();

    	$q = new Doctrine_Query();
    	$newdata = $q->select('m.*')
    	 			 ->from('TestMovie m')
	 				 ->execute()
	 				 ->getFirst();	 
    	$newdata['name'] = 'movie2';	
    	try {
			$newdata->save(); //big failure here
			$this->pass();
        } catch(Doctrine_Exception $e) {
                                       	print $e;
            $this->fail();
        }
        
    }
}

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

class TestMovieUserBookmark extends Doctrine_Record 
{
    public function setTableDefinition() 
    {
        $this->hasColumn('user_id', 'integer', null, array('primary' => true));
        $this->hasColumn('movie_id', 'integer', null, array('primary' => true));
    }
}

class TestMovieUserVote extends Doctrine_Record  
{    
    public function setTableDefinition() {
        $this->hasColumn('vote', 'string', 30);
        $this->hasColumn('user_id', 'integer', null, array('primary' => true));
        $this->hasColumn('movie_id', 'integer', null, array('primary' => true));
    }
}
