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
 * Doctrine_Connection_UnitOfWork_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Connection_UnitOfWork_TestCase extends Doctrine_UnitTestCase 
{
    public function testFlush() 
    {
        $uow = $this->connection->unitOfWork;
        
        $user = $this->connection->getTable('User')->find(4);
        $this->assertTrue(is_numeric($user->Phonenumber[0]->entity_id));

        $user    = $this->connection->create('Email');
        $user    = $this->connection->create('User');
        $record  = $this->connection->create('Phonenumber');

        $user->Email->address = 'example@drinkmore.info';
        $this->assertTrue($user->email_id instanceof Email);

        $user->name = 'Example user';
        $user->Group[0]->name = 'Example group 1';
        $user->Group[1]->name = 'Example group 2';

        $user->Phonenumber[0]->phonenumber = '123 123';

        $user->Phonenumber[1]->phonenumber = '321 2132';
        $user->Phonenumber[2]->phonenumber = '123 123';
        $user->Phonenumber[3]->phonenumber = '321 2132';



        $this->assertTrue($user->Phonenumber[0]->entity_id instanceof User);
        $this->assertTrue($user->Phonenumber[2]->entity_id instanceof User);

        $uow->saveAll();

        $this->assertTrue(is_numeric($user->Phonenumber[0]->entity_id));

        $this->assertEqual(count($user->Group), 2);
        $user2 = $user;

        $user = $this->objTable->find($user->id);

        $this->assertEqual($user->id, $user2->id);

        $this->assertTrue(is_numeric($user->id));
        $this->assertTrue(is_numeric($user->email_id));

        $this->assertTrue(is_numeric($user->Phonenumber[0]->entity_id));
        $this->assertTrue($user->Phonenumber->count(), 4);
        $this->assertEqual($user->Group->count(), 2);


        $user = $this->objTable->find(5);

        $pf   = $this->connection->getTable('Phonenumber');

        $this->assertTrue($user->Phonenumber instanceof Doctrine_Collection);
        $this->assertTrue($user->Phonenumber->count() == 3);

        $coll = new Doctrine_Collection($pf);

        $user->Phonenumber = $coll;
        $this->assertTrue($user->Phonenumber->count() == 0);

        $uow->saveAll();
        unset($user);
        $user = $this->objTable->find(5);

        $this->assertEqual($user->Phonenumber->count(), 0);

        // ADDING REFERENCES

        $user->Phonenumber[0]->phonenumber = '123 123';
        $this->assertTrue(is_numeric($user->Phonenumber[0]->entity_id));

        $user->Phonenumber[1]->phonenumber = '123 123';
        $uow->saveAll();


        $this->assertEqual($user->Phonenumber->count(), 2);

        unset($user);
        $user = $this->objTable->find(5);
        $this->assertEqual($user->Phonenumber->count(), 2);

        $user->Phonenumber[3]->phonenumber = '123 123';
        $uow->saveAll();

        $this->assertEqual($user->Phonenumber->count(), 3);
        unset($user);
        $user = $this->objTable->find(5);
        $this->assertEqual($user->Phonenumber->count(), 3);

        // DELETING REFERENCES

        $user->Phonenumber->delete();

        $this->assertEqual($user->Phonenumber->count(), 0);
        unset($user);
        $user = $this->objTable->find(5);
        $this->assertEqual($user->Phonenumber->count(), 0);
        
        // ADDING REFERENCES WITH STRING KEYS

        $user->Phonenumber['home']->phonenumber = '123 123';
        $user->Phonenumber['work']->phonenumber = '444 444';

        $this->assertEqual($user->Phonenumber->count(), 2);
        $uow->saveAll();

        $this->assertEqual($user->Phonenumber->count(), 2);
        unset($user);
        $user = $this->objTable->find(5);
        $this->assertEqual($user->Phonenumber->count(), 2);

        // REPLACING ONE-TO-MANY REFERENCE

        unset($coll);
        $coll = new Doctrine_Collection($pf);
        $coll[0]->phonenumber = '123 123';
        $coll['home']->phonenumber = '444 444';
        $coll['work']->phonenumber = '444 444';




        $user->Phonenumber = $coll;
        $uow->saveAll();
        $this->assertEqual($user->Phonenumber->count(), 3);
        $user = $this->objTable->find(5);
        $this->assertEqual($user->Phonenumber->count(), 3);

        
        // ONE-TO-ONE REFERENCES

        $user->Email->address = 'drinker@drinkmore.info';
        $this->assertTrue($user->Email instanceof Email);
        $uow->saveAll();
        $this->assertTrue($user->Email instanceof Email);
        $user = $this->objTable->find(5);
        $this->assertEqual($user->Email->address, 'drinker@drinkmore.info');
        $id = $user->Email->id;

        // REPLACING ONE-TO-ONE REFERENCES

        $email = $this->connection->create('Email');
        $email->address = 'absolutist@nottodrink.com';
        $user->Email = $email;

        $this->assertTrue($user->Email instanceof Email);
        $this->assertEqual($user->Email->address, 'absolutist@nottodrink.com');
        $uow->saveAll();
        unset($user);

        $user = $this->objTable->find(5);
        $this->assertTrue($user->Email instanceof Email);
        $this->assertEqual($user->Email->address, 'absolutist@nottodrink.com');
        
        $emails = $this->connection->query("FROM Email WHERE Email.id = $id");
        //$this->assertEqual(count($emails),0);
    }

    public function testTransactions() 
    {
        $uow = $this->connection->unitOfWork;
        $this->connection->beginTransaction();
        $this->assertEqual($this->connection->transaction->getState(),Doctrine_Transaction::STATE_ACTIVE);
        $this->connection->commit();
        $this->assertEqual($this->connection->transaction->getState(),Doctrine_Transaction::STATE_SLEEP);

        $this->connection->beginTransaction();
        
        $user = $this->objTable->find(6);
        
        $user->name = 'Jack Daniels';
        $uow->saveAll();
        $this->connection->commit();

        $user = $this->objTable->find(6);
        $this->assertEqual($user->name, 'Jack Daniels');

    }


}
