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
 * Doctrine_Record_State_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_NestedSet_SingleRoot_TestCase extends Doctrine_UnitTestCase
{
    public function prepareTables()
    {
        $this->tables[] = 'NestedSetTest_SingleRootNode';
        parent::prepareTables();
    }

    public function prepareData()
    {
        $node = new NestedSetTest_SingleRootNode();
        $node->name = 'root';
        $treeMngr = $this->conn->getTable('NestedSetTest_SingleRootNode')->getTree();
        $treeMngr->createRoot($node);
        
        $node2 = new NestedSetTest_SingleRootNode();
        $node2->name = 'node2';
        $node2->getNode()->insertAsLastChildOf($node);
        
        $node3 = new NestedSetTest_SingleRootNode();
        $node3->name = 'node3';
        $node3->getNode()->insertAsLastChildOf($node2);
    }
    
    public function testLftRgtValues()
    {
        $treeMngr = $this->conn->getTable('NestedSetTest_SingleRootNode')->getTree();
        $root = $treeMngr->fetchRoot();
        $this->assertEqual(1, $root['lft']);
        $this->assertEqual(6, $root['rgt']);
    }

    public function testGetDescendants()
    {
        $treeMngr = $this->conn->getTable('NestedSetTest_SingleRootNode')->getTree();
        $root = $treeMngr->fetchRoot();
        $desc = $root->getNode()->getDescendants();
        $this->assertTrue($desc !== false);
        $this->assertEqual(2, count($desc));
        $this->assertEqual('node2', $desc[0]['name']);
        $this->assertEqual(1, $desc[0]['level']);
    }

	public function testGetNumberChildren()
	{
		$treeMngr = $this->conn->getTable('NestedSetTest_SingleRootNode')->getTree();
        $root = $treeMngr->fetchRoot();
		$this->assertEqual(1, $root->getNode()->getNumberChildren());
	}
    
    public function testGetAncestors()
    {
        $node = $this->conn->query("SELECT n.* FROM NestedSetTest_SingleRootNode n WHERE n.name = ?",
                array('node2'))->getFirst();
        $anc = $node->getNode()->getAncestors();
        $this->assertTrue($anc !== false);
        $this->assertEqual(1, count($anc));
        $this->assertEqual('root', $anc[0]['name']);
        $this->assertEqual(0, $anc[0]['level']);
    }

}
