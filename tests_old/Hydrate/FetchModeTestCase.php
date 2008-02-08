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
 * Doctrine_Hydrate_FetchMode_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Hydrate_FetchMode_TestCase extends Doctrine_UnitTestCase 
{

    public function testFetchArraySupportsOneToManyRelations()
    {
        $q = new Doctrine_Query();

        $q->select('u.*, p.*')->from('User u')->innerJoin('u.Phonenumber p')->where("u.name = 'zYne'");;
        
        $users = $q->execute(array(), Doctrine::FETCH_ARRAY);

        $this->assertTrue(is_array($users));

        $this->assertEqual(count($users), 1);
    }
    
    public function testFetchArraySupportsOneToManyRelations2()
    {
        $q = new Doctrine_Query();

        $q->select('u.*, p.*')->from('User u')->innerJoin('u.Phonenumber p');
        
        $users = $q->execute(array(), Doctrine::FETCH_ARRAY);

        $this->assertTrue(is_array($users));

        $this->assertEqual(count($users), 8);
    }
    
    public function testFetchArraySupportsOneToManyRelations3()
    {
        $q = new Doctrine_Query();

        $q->select('u.*, p.*')->from('User u')->innerJoin('u.Phonenumber p')->where("u.name = 'Jean Reno'");
        
        $users = $q->execute(array(), Doctrine::FETCH_ARRAY);

        $this->assertTrue(is_array($users));

        $this->assertEqual(count($users), 1);
        $this->assertEqual(count($users[0]['Phonenumber']), 3);
    }
    
    public function testFetchArraySupportsOneToOneRelations()
    {
        $q = new Doctrine_Query();

        $q->select('u.*, e.*')->from('User u')->innerJoin('u.Email e');
        
        $users = $q->execute(array(), Doctrine::FETCH_ARRAY);

        $this->assertEqual(count($users), 8);
        $this->assertEqual($users[0]['Email']['address'], 'zYne@example.com');
    }
    
    public function testFetchArraySupportsOneToOneRelations2()
    {
        $q = new Doctrine_Query();

        $q->select('u.*, e.*')->from('User u')->innerJoin('u.Email e')->where("u.name = 'zYne'");
        
        $users = $q->execute(array(), Doctrine::FETCH_ARRAY);

        $this->assertEqual(count($users), 1);
        $this->assertEqual($users[0]['Email']['address'], 'zYne@example.com');
    }

    public function testFetchRecordSupportsOneToOneRelations()
    {
        $q = new Doctrine_Query();

        $q->select('u.*, e.*')->from('User u')->innerJoin('u.Email e');
        $count = count($this->conn);
        $users = $q->execute(array(), Doctrine::FETCH_RECORD);

        $this->assertEqual(count($users), 8);

        $this->assertEqual($users[0]['Email']['address'], 'zYne@example.com');
        $this->assertTrue($users[0] instanceof User);
        $this->assertTrue($users instanceof Doctrine_Collection);  
        $this->assertEqual($users[0]->state(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($users[0]->id, 4);

        $this->assertTrue($users[0]['Email'] instanceof Email);
        $this->assertEqual($users[0]['email_id'], 1);

        $this->assertEqual(count($this->conn), $count + 1);
    }

    public function testFetchRecordSupportsOneToManyRelations()
    {
        $q = new Doctrine_Query();

        $q->select('u.*, p.*')->from('User u')->innerJoin('u.Phonenumber p');
        $users = $q->execute(array(), Doctrine::FETCH_RECORD);

        $this->assertEqual(count($users), 8);
        $this->assertTrue($users[0] instanceof User);
        $this->assertEqual($users[0]->state(), Doctrine_Record::STATE_CLEAN);
        $this->assertTrue($users instanceof Doctrine_Collection);
        $this->assertTrue($users[0]->Phonenumber instanceof Doctrine_Collection);
    }

    public function testFetchRecordSupportsSimpleFetching()
    {
        $q = new Doctrine_Query();

        $q->select('u.*')->from('User u');
        $count = $this->conn->count();
        $users = $q->execute(array(), Doctrine::FETCH_RECORD);

        $this->assertEqual(count($users), 8);
        $this->assertTrue($users[0] instanceof User);
        $this->assertEqual($users[0]->state(), Doctrine_Record::STATE_CLEAN);


        $this->assertEqual($this->conn->count(), $count + 1);
    }

    public function testFetchArrayNull()
    {
        $u = new User();
        $u->name = "fetch_array_test";
        $u->created = null;
        $u->save();
  
        $q = new Doctrine_Query();
        $q->select('u.*')->from('User u')->where('u.id = ?');
        $users = $q->execute(array($u->id), Doctrine::HYDRATE_ARRAY);
        $this->assertEqual($users[0]['created'], null);
    }
    
    public function testHydrateNone()
    {
        $u = new User();
        $u->name = "fetch_array_test";
        $u->created = null;
        $u->save();
  
        $q = new Doctrine_Query();
        $q->select('COUNT(u.id) num')->from('User u')->where('u.id = ?');
        $res = $q->execute(array($u->id), Doctrine::HYDRATE_NONE);
        $this->assertEqual(1, $res[0][0]);
    }
}
