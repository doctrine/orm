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
 * Doctrine_CustomResultSetOrder_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_CustomResultSetOrder_TestCase extends Doctrine_UnitTestCase {
    
    /**
     * Prepares the data under test.
     * 
     * 1st category: 3 Boards
     * 2nd category: 1 Board
     * 3rd category: 0 boards
     * 
     */
    public function prepareData() {
        $cat1 = new CategoryWithPosition();
        $cat1->position = 0;
        $cat1->name = "First";
        
        $cat2 = new CategoryWithPosition();
        $cat2->position = 0; // same 'priority' as the first
        $cat2->name = "Second";
        
        $cat3 = new CategoryWithPosition();
        $cat3->position = 1;
        $cat3->name = "Third";
        
        $board1 = new BoardWithPosition();
        $board1->position = 0;
        
        $board2 = new BoardWithPosition();
        $board2->position = 1;
        
        $board3 = new BoardWithPosition();
        $board3->position = 2;
        
        // The first category gets 3 boards!
        $cat1->Boards[0] = $board1;
        $cat1->Boards[1] = $board2;
        $cat1->Boards[2] = $board3;
        
        $board4 = new BoardWithPosition();
        $board4->position = 0;
        
        // The second category gets 1 board!
        $cat2->Boards[0] = $board4;
        
        $this->connection->flush();
    }
    
    /**
     * Prepares the tables.
     */
    public function prepareTables() {
        $this->tables[] = "CategoryWithPosition";
		$this->tables[] = "BoardWithPosition";
        parent::prepareTables();
    }
    /**
     * Checks whether the boards are correctly assigned to the categories.
     *
     * The 'evil' result set that confuses the object population is displayed below.
     * 
     * catId | catPos | catName  | boardPos | board.category_id
     *  1    | 0      | First    | 0        | 1
     *  2    | 0      | Second   | 0        | 2   <-- The split that confuses the object population
     *  1    | 0      | First    | 1        | 1
     *  1    | 0      | First    | 2        | 1
     *  3    | 2      | Third    | NULL
     */

    public function testQueryWithOrdering2() {
        $q = new Doctrine_Query($this->connection);

        $categories = $q->select('c.*, b.*')
                ->from('CategoryWithPosition c')
                ->leftJoin('c.Boards b')
                ->orderBy('c.position ASC, b.position ASC')
                ->execute(array(), Doctrine::FETCH_ARRAY);

        $this->assertEqual(3, count($categories), 'Some categories were doubled!');
                
        // Check each category
        foreach ($categories as $category) {
            switch ($category['name']) {
                case 'First':
                    // The first category should have 3 boards, right?
                    // It has only 1! The other two slipped to the 2nd category!
                    $this->assertEqual(3, count($category['Boards']));
                break;
                case 'Second':
                    // The second category should have 1 board, but it got 3 now
                    $this->assertEqual(1, count($category['Boards']));;
                break;
                case 'Third':
                    // The third has no boards as expected.
                    //print $category->Boards[0]->position;
                    $this->assertEqual(0, count($category['Boards']));
                break;
            }
            
        }
    }

    /**
     * Checks whether the boards are correctly assigned to the categories.
     *
     * The 'evil' result set that confuses the object population is displayed below.
     * 
     * catId | catPos | catName  | boardPos | board.category_id 
     *  1    | 0      | First    | 0        | 1
     *  2    | 0      | Second   | 0        | 2   <-- The split that confuses the object population
     *  1    | 0      | First    | 1        | 1
     *  1    | 0      | First    | 2        | 1
     *  3    | 2      | Third    | NULL
     */

    public function testQueryWithOrdering() {
        $q = new Doctrine_Query($this->connection);
        $categories = $q->select('c.*, b.*')
                ->from('CategoryWithPosition c')
                ->leftJoin('c.Boards b')
                ->orderBy('c.position ASC, b.position ASC')
                ->execute();

        $this->assertEqual(3, $categories->count(), 'Some categories were doubled!');
                
        // Check each category
        foreach ($categories as $category) {
            
            switch ($category->name) {
                case 'First':
                    // The first category should have 3 boards

                    $this->assertEqual(3, $category->Boards->count());
                break;
                case 'Second':
                    // The second category should have 1 board

                    $this->assertEqual(1, $category->Boards->count());
                break;
                case 'Third':
                    // The third has no boards as expected.
                    //print $category->Boards[0]->position;
                    $this->assertEqual(0, $category->Boards->count());
                break;
            }
            
        }
    }
}
