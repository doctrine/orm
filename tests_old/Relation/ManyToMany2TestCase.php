<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.phpdoctrine.com>.
 */

/**
 * Doctrine_Relation_Parser_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Relation_ManyToMany2_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareData() 
    {
    }
    
    public function prepareTables() 
    {
        $this->tables = array('TestUser', 'TestMovie', 'TestMovieUserBookmark', 'TestMovieUserVote');
        parent::prepareTables();
    }
    
    public function testManyToManyCreateSelectAndUpdate() 
    {
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
            $this->fail();
        }
        
    }
    public function testManyToManyJoinsandSave()
    {
        $q = new Doctrine_Query();
        $newdata = $q->select('d.*, i.*, u.*, c.*')
                       ->from('TestMovie d, d.MovieBookmarks i, i.UserVotes u, u.User c')
                       ->execute()
                       ->getFirst();
                       
        $newdata['MovieBookmarks'][0]['UserVotes'][0]['User']['name'] = 'user2';
        try {
            $newdata->save();
            $this->pass();
        } catch(Doctrine_Exception $e) {
            $this->fail();
        }
    }

    public function testInitMoreData()
    {
        $user = new TestUser();
        $user->name = 'test user';
        $user->save();

        $movie = new TestMovie();
        $movie->name = 'test movie';
        $movie->save();

        $movie = new TestMovie();
        $movie->name = 'test movie 2';
        $movie->save();

        $this->conn->clear();
    }

    public function testManyToManyDirectLinksUpdating()
    {
        $users = $this->conn->query("FROM TestUser u WHERE u.name = 'test user'");

        $this->assertEqual($users->count(), 1);

        $movies = $this->conn->query("FROM TestMovie m WHERE m.name IN ('test movie', 'test movie 2')");

        $this->assertEqual($movies->count(), 2);

        $profiler = new Doctrine_Connection_Profiler();
        
        $this->conn->addListener($profiler);

        $this->assertEqual($users[0]->UserBookmarks->count(), 0);
        $users[0]->UserBookmarks = $movies;
        $this->assertEqual($users[0]->UserBookmarks->count(), 2);

        $users[0]->save();

        $this->assertEqual($users[0]->UserBookmarks->count(), 2);
        /**
        foreach ($profiler->getAll() as $event) {
            print $event->getQuery() . "<br>";
        }
        */
    }
}
