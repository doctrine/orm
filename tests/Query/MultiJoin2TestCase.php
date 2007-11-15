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
class Doctrine_Query_MultiJoin2_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareData()
    { }
    public function prepareTables()
    { 
        $this->tables = array('QueryTest_Category', 'QueryTest_Board', 'QueryTest_User', 'QueryTest_Entry');
        
        parent::prepareTables();
    }
    public function testInitializeData() 
    {
        $query = new Doctrine_Query($this->connection);
        
        $cat = new QueryTest_Category();

        $cat->rootCategoryId = 0;
        $cat->parentCategoryId = 0;
        $cat->name = "Cat1";
        $cat->position = 0;
        $cat->save();
        
        $board = new QueryTest_Board();
        $board->name = "B1";
        $board->categoryId = $cat->id;
        $board->position = 0;
        $board->save();
        
        $author = new QueryTest_User();
        $author->username = "romanb";
        $author->save();

        $lastEntry = new QueryTest_Entry();
        $lastEntry->authorId = $author->id;
        $lastEntry->date = 1234;
        $lastEntry->save();

    }

    public function testMultipleJoinFetchingWithDeepJoins() 
    {
        $query = new Doctrine_Query($this->connection);
        $queryCount = $this->connection->count();
        try {
            $categories = $query->select('c.*, subCats.*, b.*, le.*, a.*')
                    ->from('QueryTest_Category c')
                    ->leftJoin('c.subCategories subCats')
                    ->leftJoin('c.boards b')
                    ->leftJoin('b.lastEntry le')
                    ->leftJoin('le.author a')
                    ->where('c.parentCategoryId = 0')
                    ->orderBy('c.position ASC, subCats.position ASC, b.position ASC')
                    ->execute();
            // Test that accessing a loaded (but empty) relation doesnt trigger an extra query
            $this->assertEqual($queryCount + 1, $this->connection->count());

            $categories[0]->subCategories;
            $this->assertEqual($queryCount + 1, $this->connection->count());
        } catch (Doctrine_Exception $e) {
            $this->fail($e->getMessage());
        }
    }
    
    public function testMultipleJoinFetchingWithArrayFetching() 
    {
        $query = new Doctrine_Query($this->connection);
        $queryCount = $this->connection->count();
        try {
            $categories = $query->select('c.*, subCats.*, b.*, le.*, a.*')
                    ->from('QueryTest_Category c')
                    ->leftJoin('c.subCategories subCats')
                    ->leftJoin('c.boards b')
                    ->leftJoin('b.lastEntry le')
                    ->leftJoin('le.author a')
                    ->where('c.parentCategoryId = 0')
                    ->orderBy('c.position ASC, subCats.position ASC, b.position ASC')
                    ->execute(array(), Doctrine::FETCH_ARRAY);
            $this->pass();
        } catch (Doctrine_Exception $e) {
            $this->fail($e->getMessage());
        }
    }
}
