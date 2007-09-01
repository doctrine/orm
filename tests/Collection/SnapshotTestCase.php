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
 * Doctrine_Collection_Snapshot_TestCase
 *
 * This test case is used for testing the snapshot functionality
 * of the Doctrine_Collection
 *
 * Snapshots are used for counting the diff between original and changed 
 * state of the collection.
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Collection_Snapshot_TestCase extends Doctrine_UnitTestCase
{
    public function prepareTables()
    {
    	$this->tables = array('Entity', 'User', 'Group', 'GroupUser', 'Account', 'Album', 'Phonenumber', 'Email', 'Book');
    	
    	parent::prepareTables();
    }

    public function testDiffForSimpleCollection()
    {
        $q = Doctrine_Query::create()->from('User u')->orderby('u.id');

        $coll = $q->execute();
        $this->assertEqual($coll->count(), 8);

        unset($coll[0]);
        unset($coll[1]);

        $coll[]->name = 'new user';

        $this->assertEqual($coll->count(), 7);
        $this->assertEqual(count($coll->getSnapshot()), 8);

        $count = $this->conn->count();

        $coll->save();
        print $this->conn->count();
        print $count;

        $this->connection->clear();
        $coll = Doctrine_Query::create()->from('User u')->execute();
        $this->assertEqual($coll->count(), 7);
    }

    public function testDiffForOneToManyRelatedCollection()
    {
        $q = new Doctrine_Query();
        $q->from('User u LEFT JOIN u.Phonenumber p')
             ->where('u.id = 8');

        $coll = $q->execute();

        $this->assertEqual($coll->count(), 1);

        $this->assertEqual($coll[0]->Phonenumber->count(), 3);
        $this->assertTrue($coll[0]->Phonenumber instanceof Doctrine_Collection);

        unset($coll[0]->Phonenumber[0]);
        $coll[0]->Phonenumber->remove(2);

        $this->assertEqual(count($coll[0]->Phonenumber->getSnapshot()), 3);
        $coll[0]->save();

        $this->assertEqual($coll[0]->Phonenumber->count(), 1);

        $this->connection->clear();

        $q = new Doctrine_Query();
        $q = Doctrine_Query::create()->from('User u LEFT JOIN u.Phonenumber p')->where('u.id = 8');

        $coll = $q->execute();

        $this->assertEqual($coll[0]->Phonenumber->count(), 1);

    }

    public function testDiffForManyToManyRelatedCollection()
    {
        $user = new User();
        $user->name = 'zYne';
        $user->Group[0]->name = 'PHP';
        $user->Group[1]->name = 'Web';
        $user->save();

        $this->connection->clear();

        $users = Doctrine_Query::create()->from('User u LEFT JOIN u.Group g')
                 ->where('u.id = ' . $user->id)->execute();

        $this->assertEqual($users[0]->Group[0]->name, 'PHP');
        $this->assertEqual($users[0]->Group[1]->name, 'Web');
        $this->assertEqual(count($user->Group->getSnapshot()), 2);
        unset($user->Group[0]);

        $user->save();
        $this->assertEqual(count($user->Group), 1);

        $this->assertEqual(count($user->Group->getSnapshot()), 1);
        unset($user->Group[1]);
        $this->assertEqual(count($user->Group->getSnapshot()), 1);
        
        $count = count($this->conn);
        $user->save();

        $this->assertEqual(count($user->Group->getSnapshot()), 0);
        
        $this->conn->clear();

        $users = Doctrine_Query::create()->from('User u LEFT JOIN u.Group g')
                 ->where('u.id = ' . $user->id)->execute();
        
        $this->assertEqual(count($user->Group), 0);

    }

}
