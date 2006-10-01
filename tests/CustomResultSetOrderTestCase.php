<?PHP
class Doctrine_CustomResultSetOrderTestCase extends Doctrine_UnitTestCase {
    
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
    public function testQueryWithOrdering() {
        $categories = $this->connection->query("FROM CategoryWithPosition.Boards
                ORDER BY CategoryWithPosition.position ASC, CategoryWithPosition.Boards.position ASC");
        
        // Check each category
        foreach ($categories as $category) {
            
            switch ($category->name) {
                case "First":
                    // The first category should have 3 boards, right?
                    // It has only 1! The other two slipped to the 2nd category!
                    $this->assertEqual(3, $category->Boards->count());
                break;
                case "Second":
                    // The second category should have 1 board, but it got 3 now
                    $this->assertEqual(1, $category->Boards->count());
                break;
                case "Third":
                    // The third has no boards as expected.
                    $this->assertEqual(0, $category->Boards->count());
                break;  
            }
            
        }
    }    

}














?>