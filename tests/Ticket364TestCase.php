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
 * Doctrine_Ticket364_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Jan Schaefer <tanken@gmx.de> 
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Ticket364_TestCase extends Doctrine_UnitTestCase {

    public function prepareData() 
    { }
    public function prepareTables() 
    {
        $this->tables = array('NestTest', 'NestReference');
        
        parent::prepareTables();
    }

	public function testMultiplePrimaryKeys()
	{
        $r = new Doctrine_Collection('NestReference');
        $r[0]->parent_id = 1;
        $r[0]->child_id = 2;
        $r[1]->parent_id = 2;
        $r[1]->child_id = 3;
        $r->save();

        $r->delete();
        $this->conn->clear();
        $q = new Doctrine_Query();
        $coll = $q->from('NestReference')->execute();
        $this->assertEqual(count($coll), 0);
    }

    public function testCircularNonEqualSelfReferencingRelationSaving() {
        $n1 = new NestTest();
        $n1->set('name', 'node1');
        $n1->save();
        $n2 = new NestTest();
        $n2->set('name', 'node2');
        $n2->save();

        $n1->get('Children')->add($n2);
        $n1->save();
        $n2->get('Children')->add($n1);
        $n2->save();

        $q = new Doctrine_Query();
        $coll = $q->from('NestReference')->execute();

        $this->assertEqual(count($coll), 2);

        $coll->delete();
        $this->conn->clear();

        $q = new Doctrine_Query();
        $coll = $q->from('NestReference')->execute();
        $this->assertEqual(count($coll), 0);
    }

    public function testCircularEqualSelfReferencingRelationSaving() {
        $n1 = new NestTest();
        $n1->set('name', 'node1');
        $n1->save();
        $n2 = new NestTest();
        $n2->set('name', 'node2');
        $n2->save();

        $n1->get('Relatives')->add($n2);
        $n1->save();
        $n2->get('Relatives')->add($n1);
        $n2->save();

        $q = new Doctrine_Query();
        $coll = $q->from('NestReference')->execute(array(), Doctrine::FETCH_ARRAY);

        $this->assertEqual(count($coll), 1);
    }

}
