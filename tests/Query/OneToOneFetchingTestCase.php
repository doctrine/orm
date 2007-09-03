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
 * Doctrine_Query_MultiJoin2_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_OneToOneFetching_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareData()
    { }
    public function prepareTables()
    {
        $this->tables[] = 'QueryTest_Category';
        $this->tables[] = 'QueryTest_Board';
        $this->tables[] = 'QueryTest_User';
        $this->tables[] = 'QueryTest_Entry';
        $this->tables[] = 'QueryTest_Rank';
        parent::prepareTables();
    }
    public function testInitializeData() 
    {
        $query = new Doctrine_Query($this->connection);
        
        $cat = new QueryTest_Category();

        $cat->rootCategoryId = 0;
        $cat->parentCategoryId = 0;
        $cat->name = "Testcat";
        $cat->position = 0;
        $cat->save();
        
        $board = new QueryTest_Board();
        $board->name = "Testboard";
        $board->categoryId = $cat->id;
        $board->position = 0;
        $board->save();
        
        $author = new QueryTest_User();
        $author->username = "romanbb";
        $author->save();
        
        $lastEntry = new QueryTest_Entry();
        $lastEntry->authorId = $author->id;
        $lastEntry->date = 1234;
        $lastEntry->save();
        
        // Set the last entry
        $board->lastEntry = $lastEntry;
        $board->save();
        
        $visibleRank = new QueryTest_Rank();
        $visibleRank->title = "Freak";
        $visibleRank->color = "red";
        $visibleRank->icon = "freak.png";
        $visibleRank->save();
        
        // grant him a rank
        $author->visibleRank = $visibleRank;
        $author->save();

    }
    
    /**
     * Tests that one-one relations are correctly loaded with array fetching
     * when the related records EXIST.
     * 
     * !!! Currently it produces a notice with:
     * !!! Array to string conversion in Doctrine\Hydrate.php on line 937 
     * 
     * !!! And shortly after exits with a fatal error:
     * !!! Fatal error:  Cannot create references to/from string offsets nor overloaded objects
     * !!! in Doctrine\Hydrate.php on line 939
     */
    public function testOneToOneArrayFetchingWithExistingRelations()
    {
        $query = new Doctrine_Query($this->connection);
        try {
            $categories = $query->select("c.*, b.*, le.*, a.username, vr.title, vr.color, vr.icon")
                    ->from("QueryTest_Category c")
                    ->leftJoin("c.boards b")
                    ->leftJoin("b.lastEntry le")
                    ->leftJoin("le.author a")
                    ->leftJoin("a.visibleRank vr")
                    ->execute(array(), Doctrine::FETCH_ARRAY);

            // --> currently quits here with a fatal error! <--
                    
            // check boards/categories
            $this->assertEqual(1, count($categories));
            $this->assertTrue(isset($categories[0]['boards']));
            $this->assertEqual(1, count($categories[0]['boards']));
            
            // get the baord for inspection
            $board = $categories[0]['boards'][0];
            
            $this->assertTrue(isset($board['lastEntry']));
            
            // lastentry should've 2 fields. one regular field, one relation.
            //$this->assertEqual(2, count($board['lastEntry']));
            $this->assertEqual(1234, (int)$board['lastEntry']['date']);
            $this->assertTrue(isset($board['lastEntry']['author']));

            // author should've 2 fields. one regular field, one relation.
            //$this->assertEqual(2, count($board['lastEntry']['author']));
            $this->assertEqual('romanbb', $board['lastEntry']['author']['username']);
            $this->assertTrue(isset($board['lastEntry']['author']['visibleRank']));

            // visibleRank should've 3 regular fields
            //$this->assertEqual(3, count($board['lastEntry']['author']['visibleRank']));
            $this->assertEqual('Freak', $board['lastEntry']['author']['visibleRank']['title']);
            $this->assertEqual('red', $board['lastEntry']['author']['visibleRank']['color']);
            $this->assertEqual('freak.png', $board['lastEntry']['author']['visibleRank']['icon']);
                    
        } catch (Doctrine_Exception $e) {
            $this->fail();                                    
        }
    }

     /**
     * Tests that one-one relations are correctly loaded with array fetching
     * when the related records DONT EXIST.
     */

    public function testOneToOneArrayFetchingWithEmptyRelations()
    {
        // temporarily remove the relation to fake a non-existant one
        $board = $this->connection->query("FROM QueryTest_Board b WHERE b.name = ?", array('Testboard'))->getFirst();
        $lastEntryId = $board->lastEntryId;
        $board->lastEntryId = 0;
        $board->save();
        
        $query = new Doctrine_Query($this->connection);
        try {
            $categories = $query->select("c.*, b.*, le.*, a.username, vr.title, vr.color, vr.icon")
                    ->from("QueryTest_Category c")
                    ->leftJoin("c.boards b")
                    ->leftJoin("b.lastEntry le")
                    ->leftJoin("le.author a")
                    ->leftJoin("a.visibleRank vr")
                    ->execute(array(), Doctrine::FETCH_ARRAY);


            // check boards/categories
            $this->assertEqual(1, count($categories));
            $this->assertTrue(isset($categories[0]['boards']));
            $this->assertEqual(1, count($categories[0]['boards']));
            
            // get the board for inspection
            $tmpBoard = $categories[0]['boards'][0];
            
            $this->assertTrue( ! isset($tmpBoard['lastEntry']));
                    
        } catch (Doctrine_Exception $e) {
            $this->fail();                                    
        }
        
        $board->lastEntryId = $lastEntryId;
        $board->save();
    }

    // Tests that one-one relations are correctly loaded with record fetching
    // when the related records EXIST.
    public function testOneToOneRecordFetchingWithExistingRelations()
    {
        $query = new Doctrine_Query($this->connection);
        try {
            $categories = $query->select("c.*, b.*, le.date, a.username, vr.title, vr.color, vr.icon")
                    ->from("QueryTest_Category c")
                    ->leftJoin("c.boards b")
                    ->leftJoin("b.lastEntry le")
                    ->leftJoin("le.author a")
                    ->leftJoin("a.visibleRank vr")
                    ->execute();
 
            // check boards/categories
            $this->assertEqual(1, count($categories));
            $this->assertEqual(1, count($categories[0]['boards']));
            
            // get the baord for inspection
            $board = $categories[0]['boards'][0];

            $this->assertEqual(1234, (int)$board['lastEntry']['date']);
            $this->assertTrue(isset($board['lastEntry']['author']));
            
            $this->assertEqual('romanbb', $board['lastEntry']['author']['username']);
            $this->assertTrue(isset($board['lastEntry']['author']['visibleRank']));
            
            $this->assertEqual('Freak', $board['lastEntry']['author']['visibleRank']['title']);
            $this->assertEqual('red', $board['lastEntry']['author']['visibleRank']['color']);
            $this->assertEqual('freak.png', $board['lastEntry']['author']['visibleRank']['icon']);
                    
        } catch (Doctrine_Exception $e) {
            $this->fail();                                    
        }
    }


    // Tests that one-one relations are correctly loaded with record fetching
    // when the related records DONT EXIST.

    public function testOneToOneRecordFetchingWithEmptyRelations()
    {
        // temporarily remove the relation to fake a non-existant one
        $board = $this->connection->query("FROM QueryTest_Board b WHERE b.name = ?", array('Testboard'))->getFirst();
        $lastEntryId = $board->lastEntryId;
        $board->lastEntryId = 0;
        $board->save();
        
        $query = new Doctrine_Query($this->connection);
        try {
            $categories = $query->select("c.*, b.*, le.*, a.username, vr.title, vr.color, vr.icon")
                    ->from("QueryTest_Category c")
                    ->leftJoin("c.boards b")
                    ->leftJoin("b.lastEntry le")
                    ->leftJoin("le.author a")
                    ->leftJoin("a.visibleRank vr")
                    ->execute();

            // check boards/categories
            $this->assertEqual(1, count($categories));
            $this->assertTrue(isset($categories[0]['boards']));
            $this->assertEqual(1, count($categories[0]['boards']));

            // get the board for inspection
            $tmpBoard = $categories[0]['boards'][0];
            
            $this->assertTrue( ! isset($tmpBoard['lastEntry']));

        } catch (Doctrine_Exception $e) {
            print $e;
            $this->fail();
        }

        $board->lastEntryId = $lastEntryId;
        //$board->save();
    }

}
