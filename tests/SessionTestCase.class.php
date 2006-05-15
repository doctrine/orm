<?php
require_once("UnitTestCase.class.php");
class Doctrine_SessionTestCase extends Doctrine_UnitTestCase {
    public function testBuildFlushTree() {
        $tree = $this->session->buildFlushTree();

        //print_r($tree);
    }
    public function testBulkInsert() {
        $u1 = new User();
        $u1->name = "Jean Reno";
        $u1->save();

        $id = $u1->getID();
        $u1->delete();

    }

    public function testFlush() {
        $user = $this->session->getTable("User")->find(4);
        $this->assertTrue(is_numeric($user->Phonenumber[0]->entity_id));

        $user    = $this->session->create("Email");
        $user    = $this->session->create("User");
        $record  = $this->session->create("Phonenumber");

        $user->Email->address = "example@drinkmore.info";
        $this->assertTrue($user->email_id instanceof Email);

        $user->name = "Example user";
        $user->Group[0]->name = "Example group 1";
        $user->Group[1]->name = "Example group 2";

        $user->Phonenumber[0]->phonenumber = "123 123";

        $user->Phonenumber[1]->phonenumber = "321 2132";
        $user->Phonenumber[2]->phonenumber = "123 123";
        $user->Phonenumber[3]->phonenumber = "321 2132";



        $this->assertTrue($user->Phonenumber[0]->entity_id instanceof User);
        $this->assertTrue($user->Phonenumber[2]->entity_id instanceof User);

        $this->session->flush();

        $this->assertTrue(is_numeric($user->Phonenumber[0]->entity_id));

        $this->assertEqual(count($user->Group), 2);

        $user = $this->objTable->find($user->getID());

        $this->assertEqual($user->getID(), $user->getID());

        $this->assertTrue(is_numeric($user->getID()));
        $this->assertTrue(is_numeric($user->email_id));

        $this->assertTrue(is_numeric($user->Phonenumber[0]->entity_id));
        $this->assertTrue($user->Phonenumber->count(), 4);
        $this->assertEqual($user->Group->count(), 2);

        $user = $this->objTable->find(5);

        $pf   = $this->session->getTable("Phonenumber");

        $this->assertTrue($user->Phonenumber instanceof Doctrine_Collection);
        $this->assertTrue($user->Phonenumber->count() == 3);

        $coll = new Doctrine_Collection($pf);

        $user->Phonenumber = $coll;
        $this->assertTrue($user->Phonenumber->count() == 0);

        $this->session->flush();
        unset($user);
        $user = $this->objTable->find(5);

        $this->assertEqual($user->Phonenumber->count(), 0);

        // ADDING REFERENCES

        $user->Phonenumber[0]->phonenumber = "123 123";
        $this->assertTrue(is_numeric($user->Phonenumber[0]->entity_id));

        $user->Phonenumber[1]->phonenumber = "123 123";
        $this->session->flush();


        $this->assertTrue($user->Phonenumber->count() == 2);

        unset($user);
        $user = $this->objTable->find(5);
        $this->assertEqual($user->Phonenumber->count(), 2);

        $user->Phonenumber[3]->phonenumber = "123 123";
        $this->session->flush();

        $this->assertTrue($user->Phonenumber->count() == 3);
        unset($user);
        $user = $this->objTable->find(5);
        $this->assertTrue($user->Phonenumber->count() == 3);

        // DELETING REFERENCES

        $user->Phonenumber->delete();

        $this->assertTrue($user->Phonenumber->count() == 0);
        unset($user);
        $user = $this->objTable->find(5);
        $this->assertTrue($user->Phonenumber->count() == 0);
        
        // ADDING REFERENCES WITH STRING KEYS

        $user->Phonenumber["home"]->phonenumber = "123 123";
        $user->Phonenumber["work"]->phonenumber = "444 444";

        $this->assertEqual($user->Phonenumber->count(), 2);
        $this->session->flush();

        $this->assertTrue($user->Phonenumber->count() == 2);
        unset($user);
        $user = $this->objTable->find(5);
        $this->assertTrue($user->Phonenumber->count() == 2);

        // REPLACING ONE-TO-MANY REFERENCE

        unset($coll);
        $coll = new Doctrine_Collection($pf);
        $coll[0]->phonenumber = "123 123";
        $coll["home"]->phonenumber = "444 444";
        $coll["work"]->phonenumber = "444 444";




        $user->Phonenumber = $coll;
        $this->session->flush();
        $this->assertEqual($user->Phonenumber->count(), 3);
        $user = $this->objTable->find(5);
        $this->assertEqual($user->Phonenumber->count(), 3);

        
        // ONE-TO-ONE REFERENCES

        $user->Email->address = "drinker@drinkmore.info";
        $this->assertTrue($user->Email instanceof Email);
        $this->session->flush();
        $this->assertTrue($user->Email instanceof Email);
        $user = $this->objTable->find(5);
        $this->assertEqual($user->Email->address, "drinker@drinkmore.info");
        $id = $user->Email->getID();

        // REPLACING ONE-TO-ONE REFERENCES

        $email = $this->session->create("Email");
        $email->address = "absolutist@nottodrink.com";
        $user->Email = $email;

        $this->assertTrue($user->Email instanceof Email);
        $this->assertEqual($user->Email->address, "absolutist@nottodrink.com");
        $this->session->flush();
        unset($user);

        $user = $this->objTable->find(5);
        $this->assertTrue($user->Email instanceof Email);
        $this->assertEqual($user->Email->address, "absolutist@nottodrink.com");
        
        $emails = $this->session->query("FROM Email WHERE Email.id = $id");
        //$this->assertEqual(count($emails),0);


    }

    public function testGetManager() {
        $this->assertEqual($this->session->getManager(),$this->manager);
    }
    public function testQuery() {
        $this->assertTrue($this->session->query("FROM User") instanceof Doctrine_Collection);
    }

    public function testDelete() {
        $user = $this->session->create("User");
        $this->session->delete($user);
        $this->assertEqual($user->getState(),Doctrine_Record::STATE_TCLEAN);
    }
    public function testGetTable() {
        $table = $this->session->getTable("Group");
        $this->assertTrue($table instanceof Doctrine_Table);
        try {
            $table = $this->session->getTable("Unknown");
            $f = false;
        } catch(Doctrine_Exception $e) {
            $f = true;
        }
        $this->assertTrue($f);

        $table = $this->session->getTable("User");
        $this->assertTrue($table instanceof UserTable);

    }
    public function testCreate() {
        $email = $this->session->create("Email");
        $this->assertTrue($email instanceof Email);
    }
    public function testGetDBH() {
        $this->assertTrue($this->session->getDBH() instanceof PDO);
    }
    public function testCount() {
        $this->assertTrue(is_integer(count($this->session)));
    }
    public function testGetIterator() {
        $this->assertTrue($this->session->getIterator() instanceof ArrayIterator);
    }
    public function testGetState() {
        $this->assertEqual($this->session->getState(),Doctrine_Session::STATE_OPEN);
        $this->assertEqual(Doctrine_Lib::getSessionStateAsString($this->session->getState()), "open");
    }
    public function testGetTables() {
        $this->assertTrue(is_array($this->session->getTables()));
    }

    public function testTransactions() {

        $this->session->beginTransaction();
        $this->assertEqual($this->session->getState(),Doctrine_Session::STATE_ACTIVE);
        $this->session->commit();
        $this->assertEqual($this->session->getState(),Doctrine_Session::STATE_OPEN);

        $this->session->beginTransaction();
        
        $user = $this->objTable->find(6);
        
        $user->name = "Jack Daniels";
        $this->session->flush();
        $this->session->commit();

        $user = $this->objTable->find(6);
        $this->assertEqual($user->name, "Jack Daniels");

    }

    public function testRollback() {
        $this->session->beginTransaction();
        $this->assertEqual($this->session->getTransactionLevel(),1);
        $this->assertEqual($this->session->getState(),Doctrine_Session::STATE_ACTIVE);
        $this->session->rollback();
        $this->assertEqual($this->session->getState(),Doctrine_Session::STATE_OPEN);
        $this->assertEqual($this->session->getTransactionLevel(),0);
    }
    public function testNestedTransactions() {
        $this->assertEqual($this->session->getTransactionLevel(),0);
        $this->session->beginTransaction();
        $this->assertEqual($this->session->getTransactionLevel(),1);
        $this->assertEqual($this->session->getState(),Doctrine_Session::STATE_ACTIVE);
        $this->session->beginTransaction();
        $this->assertEqual($this->session->getState(),Doctrine_Session::STATE_BUSY);
        $this->assertEqual($this->session->getTransactionLevel(),2);
        $this->session->commit();
        $this->assertEqual($this->session->getState(),Doctrine_Session::STATE_ACTIVE);
        $this->assertEqual($this->session->getTransactionLevel(),1);
        $this->session->commit();
        $this->assertEqual($this->session->getState(),Doctrine_Session::STATE_OPEN);
        $this->assertEqual($this->session->getTransactionLevel(),0);
    }
    public function testClear() {
        $this->session->clear();
        $this->assertEqual($this->session->getTables(), array());
    }

}
?>
