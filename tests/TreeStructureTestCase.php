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
 * Doctrine_TreeStructure_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_TreeStructure_TestCase extends Doctrine_UnitTestCase
{
    public function prepareTables() 
    { 
        // we don't need the standard tables here
        $this->tables = array('TreeLeaf');
        parent::prepareTables();
    }
    
    public function prepareData()
    {

    }

    public function testSelfReferentialRelationship() 
    {
        $component = new TreeLeaf();

        try {
            $rel = $component->getTable()->getRelation('Parent');
            $rel = $component->getTable()->getRelation('Children');
            $this->pass();
        } catch(Doctrine_Exception $e) {
            $this->fail();
        }
    }

    public function testLocalAndForeignKeysAreSetCorrectly() {
        $component = new TreeLeaf();

        $rel = $component->getTable()->getRelation('Parent');
        $this->assertEqual($rel->getLocal(), 'parent_id');
        $this->assertEqual($rel->getForeign(), 'id');

        $rel = $component->getTable()->getRelation('Children');
        $this->assertEqual($rel->getLocal(), 'id');
        $this->assertEqual($rel->getForeign(), 'parent_id');
    }

    public function testTreeLeafRelationships() 
    {
        /* structure:
         *
         * o1
         *  -> o2
         *  -> o3
         *
         * o4
         *
         * thus it would be expected that o1 has 2 children and o4 is a
         * leaf with no parents or children.
         */

        $o1 = new TreeLeaf();
        $o1->name = 'o1';
        $o1->save();

        $o2 = new TreeLeaf();
        $o2->name   = 'o2';
        $o2->Parent = $o1;
        $o2->save();

        $o3 = new TreeLeaf();
        $o3->name   = 'o3';
        $o3->Parent = $o1;
        $o3->save();

        //$o1->refresh();

        $o4 = new TreeLeaf();
        $o4->name = 'o4';
        $o4->save();

        $o1->Children;
        $this->assertFalse(isset($o1->Parent));
        $this->assertTrue(count($o1->Children) == 2);
        $this->assertTrue(count($o1->get('Children')) == 2);

        $this->assertTrue(isset($o2->Parent));
        $this->assertTrue($o2->Parent === $o1);

        $this->assertTrue(count($o4->Children) == 0);
        $this->assertFalse(isset($o4->Parent));
    }
    public function testTreeStructureFetchingWorksWithDql()
    {
        $q = new Doctrine_Query();
        $q->select('l.*, c.*')
          ->from('TreeLeaf l, l.Children c')
          ->where('l.parent_id IS NULL')
          ->groupby('l.id, c.id');

        $coll = $q->execute();

    }
}

