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
 * <http://www.phpdoctrine.org>.
 */

/**
 * Doctrine_BatchIterator_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_BatchIterator_TestCase extends Doctrine_UnitTestCase {
    public function prepareTables() {
        $this->tables = array("Entity", "User", "Group", "Address", "Email", "Phonenumber");
        parent::prepareTables();
    }

    public function testIterator() {
        $graph = new Doctrine_Query($this->connection);
        $entities = $graph->query("FROM Entity");
        $i = 0;
        foreach($entities as $entity) {
            $this->assertEqual(gettype($entity->name),"string");
            $i++;
        }
        $this->assertTrue($i == $entities->count());
        
        $user = $graph->query("FROM User");
        foreach($user[1]->Group as $group) {
            $this->assertTrue(is_string($group->name));
        }     
        
        $user = new User();
        $user->name = "tester";
        
        $user->Address[0]->address = "street 1";
        $user->Address[1]->address = "street 2";
        
        $this->assertEqual($user->name, "tester");
        $this->assertEqual($user->Address[0]->address, "street 1");
        $this->assertEqual($user->Address[1]->address, "street 2");

        foreach($user->Address as $address) {
            $a[] = $address->address;
        }
        $this->assertEqual($a, array("street 1", "street 2"));   

        $user->save();
        
        $user = $user->getTable()->find($user->obtainIdentifier());
        $this->assertEqual($user->name, "tester");
        $this->assertEqual($user->Address[0]->address, "street 1");
        $this->assertEqual($user->Address[1]->address, "street 2");
        
        $user = $user->getTable()->find($user->obtainIdentifier());
        $a = array();
        foreach($user->Address as $address) {
            $a[] = $address->address;
        }
        $this->assertEqual($a, array("street 1", "street 2"));                                    


        $user = $graph->query("FROM User");
    }

}
