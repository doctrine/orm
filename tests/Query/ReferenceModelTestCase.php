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
 * Doctrine_Query_ReferenceModel_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_ReferenceModel_TestCase extends Doctrine_UnitTestCase {
    public function prepareTables() {
        $this->tables = array();
        $this->tables[] = "Forum_Category";
        $this->tables[] = "Forum_Entry";
        $this->tables[] = "Forum_Board";
        $this->tables[] = "Forum_Thread";

        parent::prepareTables();
        $this->connection->clear();
    }
    public function prepareData() 
    { }

    public function testInitializeData() {
        $query = new Doctrine_Query($this->connection);

        $category = new Forum_Category();

        $category->name = 'Root';
        $category->Subcategory[0]->name = 'Sub 1';
        $category->Subcategory[1]->name = 'Sub 2';
        $category->Subcategory[0]->Subcategory[0]->name = 'Sub 1 Sub 1';
        $category->Subcategory[0]->Subcategory[1]->name = 'Sub 1 Sub 2';
        $category->Subcategory[1]->Subcategory[0]->name = 'Sub 2 Sub 1';
        $category->Subcategory[1]->Subcategory[1]->name = 'Sub 2 Sub 2';

        $this->connection->flush();
        $this->connection->clear();

        $category = $category->getTable()->find($category->id);

        $this->assertEqual($category->name, 'Root');
        $this->assertEqual($category->Subcategory[0]->name, 'Sub 1');
        $this->assertEqual($category->Subcategory[1]->name, 'Sub 2');
        $this->assertEqual($category->Subcategory[0]->Subcategory[0]->name, 'Sub 1 Sub 1');
        $this->assertEqual($category->Subcategory[0]->Subcategory[1]->name, 'Sub 1 Sub 2');
        $this->assertEqual($category->Subcategory[1]->Subcategory[0]->name, 'Sub 2 Sub 1');
        $this->assertEqual($category->Subcategory[1]->Subcategory[1]->name, 'Sub 2 Sub 2');

        $this->connection->clear();
    }

    public function testSelfReferencingWithNestedOrderBy() {
        $query = new Doctrine_Query();
        
        $query->from('Forum_Category.Subcategory.Subcategory');
        $query->orderby('Forum_Category.id ASC, Forum_Category.Subcategory.name DESC');

        $coll = $query->execute();
        
        $category = $coll[0];

        $this->assertEqual($category->name, 'Root');
        $this->assertEqual($category->Subcategory[0]->name, 'Sub 2');
        $this->assertEqual($category->Subcategory[1]->name, 'Sub 1');
        $this->assertEqual($category->Subcategory[1]->Subcategory[0]->name, 'Sub 1 Sub 1');
        $this->assertEqual($category->Subcategory[1]->Subcategory[1]->name, 'Sub 1 Sub 2');
        $this->assertEqual($category->Subcategory[0]->Subcategory[0]->name, 'Sub 2 Sub 1');
        $this->assertEqual($category->Subcategory[0]->Subcategory[1]->name, 'Sub 2 Sub 2');

        $this->connection->clear();
    }
    /**
    public function testSelfReferencingWithDoubleNesting() {
        $query = new Doctrine_Query();
        $category = new Forum_Category();

        $query->from('Forum_Category.Subcategory.Subcategory');
        $coll = $query->execute();
        $category = $coll[0];

        $count = count($this->dbh);

        $this->assertEqual($category->name, 'Root');
        
        $this->assertEqual($count, count($this->dbh));
        $this->assertEqual($category->Subcategory[0]->name, 'Sub 1');
        $this->assertEqual($category->Subcategory[1]->name, 'Sub 2');

        $this->assertEqual($count, count($this->dbh));
        $this->assertEqual($category->Subcategory[0]->Subcategory[0]->name, 'Sub 1 Sub 1');
        $this->assertEqual($category->Subcategory[0]->Subcategory[1]->name, 'Sub 1 Sub 2');
        $this->assertEqual($category->Subcategory[1]->Subcategory[0]->name, 'Sub 2 Sub 1');
        $this->assertEqual($category->Subcategory[1]->Subcategory[1]->name, 'Sub 2 Sub 2');
        $this->assertEqual($count, count($this->dbh));
        
        $this->connection->clear();
    }
    public function testSelfReferencingWithNestingAndConditions() {
        $query = new Doctrine_Query();
        $query->from("Forum_Category.Parent.Parent")->where("Forum_Category.name LIKE 'Sub%Sub%'");
        $coll = $query->execute();


        $count = count($this->dbh);
        $this->assertEqual($coll->count(), 4);
        $this->assertEqual($coll[0]->name, 'Sub 1 Sub 1');
        $this->assertEqual($coll[1]->name, 'Sub 1 Sub 2');
        $this->assertEqual($coll[2]->name, 'Sub 2 Sub 1');
        $this->assertEqual($coll[3]->name, 'Sub 2 Sub 2');

        $this->assertEqual($count, count($this->dbh));

        $this->assertEqual($coll[0]->Parent->name, 'Sub 1');
        $this->assertEqual($coll[1]->Parent->name, 'Sub 1');
        $this->assertEqual($coll[2]->Parent->name, 'Sub 2');
        $this->assertEqual($coll[3]->Parent->name, 'Sub 2');
        
        $this->assertEqual($count, count($this->dbh));

        $this->assertEqual($coll[0]->Parent->Parent->name, 'Root');
        $this->assertEqual($coll[1]->Parent->Parent->name, 'Root');
        $this->assertEqual($coll[2]->Parent->Parent->name, 'Root');
        $this->assertEqual($coll[3]->Parent->Parent->name, 'Root');

        $this->assertEqual($count, count($this->dbh));
    }
    public function testSelfReferencingWithNestingAndMultipleConditions() {
        $query = new Doctrine_Query();
        $query->from("Forum_Category.Parent, Forum_Category.Subcategory")->where("Forum_Category.name = 'Sub 1' || Forum_Category.name = 'Sub 2'");


        $coll = $query->execute();
        
        $count = count($this->dbh);
        
        $this->assertEqual($coll->count(), 2);
        $this->assertEqual($coll[0]->name, 'Sub 1');
        $this->assertEqual($coll[1]->name, 'Sub 2');
        
        $this->assertEqual($count, count($this->dbh));

        $this->assertEqual($coll[0]->Subcategory[0]->name, 'Sub 1 Sub 1');
        $this->assertEqual($coll[0]->Subcategory[1]->name, 'Sub 1 Sub 2');
        $this->assertEqual($coll[1]->Subcategory[0]->name, 'Sub 2 Sub 1');
        $this->assertEqual($coll[1]->Subcategory[1]->name, 'Sub 2 Sub 2');

        $this->assertEqual($count, count($this->dbh));

        $this->assertEqual($coll[0]->Parent->name, 'Root');
        $this->assertEqual($coll[1]->Parent->name, 'Root');

        $this->assertEqual($count, count($this->dbh));
        
        $this->connection->clear();
    }
    public function testSelfReferencingWithIsNull() {
        $query = new Doctrine_Query();
        $query->from('Forum_Category.Subcategory.Subcategory')->where('Forum_Category.parent_category_id IS NULL');
        $coll = $query->execute();
        $this->assertEqual($coll->count(), 1);
        
        $count = count($this->dbh);
        $category = $coll[0];
        $this->assertEqual($category->name, 'Root');
        
        $this->assertEqual($count, count($this->dbh));
        $this->assertEqual($category->Subcategory[0]->name, 'Sub 1');
        $this->assertEqual($category->Subcategory[1]->name, 'Sub 2');

        $this->assertEqual($count, count($this->dbh));
        $this->assertEqual($category->Subcategory[0]->Subcategory[0]->name, 'Sub 1 Sub 1');
        $this->assertEqual($category->Subcategory[0]->Subcategory[1]->name, 'Sub 1 Sub 2');
        $this->assertEqual($category->Subcategory[1]->Subcategory[0]->name, 'Sub 2 Sub 1');
        $this->assertEqual($category->Subcategory[1]->Subcategory[1]->name, 'Sub 2 Sub 2');
        $this->assertEqual($count, count($this->dbh));
    }
    */
}
