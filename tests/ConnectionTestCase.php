<?php
require_once("UnitTestCase.php");
class Doctrine_ConnectionTestCase extends Doctrine_UnitTestCase {

    public function testBuildFlushTree() {
        $correct = array("Task","ResourceType","Resource","Assignment","ResourceReference");

        // new model might switch some many-to-many components (NO HARM!)
        
        $correct2 = array (
              0 => 'Resource',
              1 => 'Task',
              2 => 'ResourceType',
              3 => 'Assignment',
              4 => 'ResourceReference',
            );

        $task = new Task();

        $tree = $this->connection->buildFlushTree(array("Task"));
        $this->assertEqual($tree,array("Resource","Task","Assignment"));

        $tree = $this->connection->buildFlushTree(array("Task","Resource"));
        $this->assertEqual($tree,$correct);

        $tree = $this->connection->buildFlushTree(array("Task","Assignment","Resource"));
        $this->assertEqual($tree,$correct);

        $tree = $this->connection->buildFlushTree(array("Assignment","Task","Resource"));

        $this->assertEqual($tree,$correct2);


        $correct = array("Forum_Category","Forum_Board","Forum_Thread");

        $tree = $this->connection->buildFlushTree(array("Forum_Board"));
        $this->assertEqual($tree, $correct);
        $tree = $this->connection->buildFlushTree(array("Forum_Category","Forum_Board"));
        $this->assertEqual($tree, $correct);

        $correct = array("Forum_Category","Forum_Board","Forum_Thread","Forum_Entry");

        $tree = $this->connection->buildFlushTree(array("Forum_Entry","Forum_Board"));
        $this->assertEqual($tree, $correct);
        $tree = $this->connection->buildFlushTree(array("Forum_Board","Forum_Entry"));
        $this->assertEqual($tree, $correct);

        $tree = $this->connection->buildFlushTree(array("Forum_Thread","Forum_Board"));
        $this->assertEqual($tree, $correct);
        $tree = $this->connection->buildFlushTree(array("Forum_Board","Forum_Thread"));
        $this->assertEqual($tree, $correct);

        $tree = $this->connection->buildFlushTree(array("Forum_Board","Forum_Thread","Forum_Entry"));
        $this->assertEqual($tree, $correct);
        $tree = $this->connection->buildFlushTree(array("Forum_Board","Forum_Entry","Forum_Thread"));
        $this->assertEqual($tree, $correct);

        $tree = $this->connection->buildFlushTree(array("Forum_Entry","Forum_Board","Forum_Thread"));
        $this->assertEqual($tree, $correct);
        $tree = $this->connection->buildFlushTree(array("Forum_Entry","Forum_Thread","Forum_Board"));
        $this->assertEqual($tree, $correct);

        $tree = $this->connection->buildFlushTree(array("Forum_Thread","Forum_Board","Forum_Entry"));
        $this->assertEqual($tree, $correct);
        $tree = $this->connection->buildFlushTree(array("Forum_Thread","Forum_Entry","Forum_Board"));
        $this->assertEqual($tree, $correct);


        $this->assertEqual($tree, $correct);
        $tree = $this->connection->buildFlushTree(array("Forum_Board","Forum_Thread","Forum_Category"));
        $this->assertEqual($tree, $correct);
        $tree = $this->connection->buildFlushTree(array("Forum_Category","Forum_Thread","Forum_Board"));
        $this->assertEqual($tree, $correct);
        $tree = $this->connection->buildFlushTree(array("Forum_Thread","Forum_Board","Forum_Category"));
        $this->assertEqual($tree, $correct);

        $tree = $this->connection->buildFlushTree(array("Forum_Board","Forum_Thread","Forum_Category","Forum_Entry"));
        $this->assertEqual($tree, $correct);
        $tree = $this->connection->buildFlushTree(array("Forum_Board","Forum_Thread","Forum_Entry","Forum_Category"));
        $this->assertEqual($tree, $correct);
        $tree = $this->connection->buildFlushTree(array("Forum_Board","Forum_Category","Forum_Thread","Forum_Entry"));
        $this->assertEqual($tree, $correct);

        $tree = $this->connection->buildFlushTree(array("Forum_Entry","Forum_Thread","Forum_Board","Forum_Category"));
        $this->assertEqual($tree, $correct);
        $tree = $this->connection->buildFlushTree(array("Forum_Entry","Forum_Thread","Forum_Category","Forum_Board"));
        $this->assertEqual($tree, $correct);
        $tree = $this->connection->buildFlushTree(array("Forum_Entry","Forum_Category","Forum_Board","Forum_Thread"));
        $this->assertEqual($tree, $correct);

        $tree = $this->connection->buildFlushTree(array("Forum_Thread","Forum_Category","Forum_Board","Forum_Entry"));
        $this->assertEqual($tree, $correct);
        $tree = $this->connection->buildFlushTree(array("Forum_Thread","Forum_Entry","Forum_Category","Forum_Board"));
        $this->assertEqual($tree, $correct);
        $tree = $this->connection->buildFlushTree(array("Forum_Thread","Forum_Board","Forum_Entry","Forum_Category"));
        $this->assertEqual($tree, $correct);

        $tree = $this->connection->buildFlushTree(array("Forum_Category","Forum_Entry","Forum_Board","Forum_Thread"));
        $this->assertEqual($tree, $correct);
        $tree = $this->connection->buildFlushTree(array("Forum_Category","Forum_Thread","Forum_Entry","Forum_Board"));
        $this->assertEqual($tree, $correct);
        $tree = $this->connection->buildFlushTree(array("Forum_Category","Forum_Board","Forum_Thread","Forum_Entry"));
        $this->assertEqual($tree, $correct);

    }

    public function testBulkInsert() {
        $u1 = new User();
        $u1->name = "Jean Reno";
        $u1->save();

        $id = $u1->obtainIdentifier();
        $u1->delete();

    }

    public function testFlush() {
        $user = $this->connection->getTable("User")->find(4);
        $this->assertTrue(is_numeric($user->Phonenumber[0]->entity_id));

        $user    = $this->connection->create("Email");
        $user    = $this->connection->create("User");
        $record  = $this->connection->create("Phonenumber");

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

        $this->connection->flush();

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
        
        $this->assertTrue($this->dbh instanceof Doctrine_DB);

        $user = $this->objTable->find(5);

        $pf   = $this->connection->getTable("Phonenumber");

        $this->assertTrue($user->Phonenumber instanceof Doctrine_Collection);
        $this->assertTrue($user->Phonenumber->count() == 3);

        $coll = new Doctrine_Collection($pf);

        $user->Phonenumber = $coll;
        $this->assertTrue($user->Phonenumber->count() == 0);

        $this->connection->flush();
        unset($user);
        $user = $this->objTable->find(5);

        $this->assertEqual($user->Phonenumber->count(), 0);

        // ADDING REFERENCES

        $user->Phonenumber[0]->phonenumber = "123 123";
        $this->assertTrue(is_numeric($user->Phonenumber[0]->entity_id));

        $user->Phonenumber[1]->phonenumber = "123 123";
        $this->connection->flush();


        $this->assertEqual($user->Phonenumber->count(), 2);

        unset($user);
        $user = $this->objTable->find(5);
        $this->assertEqual($user->Phonenumber->count(), 2);

        $user->Phonenumber[3]->phonenumber = "123 123";
        $this->connection->flush();

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

        $user->Phonenumber["home"]->phonenumber = "123 123";
        $user->Phonenumber["work"]->phonenumber = "444 444";

        $this->assertEqual($user->Phonenumber->count(), 2);
        $this->connection->flush();

        $this->assertEqual($user->Phonenumber->count(), 2);
        unset($user);
        $user = $this->objTable->find(5);
        $this->assertEqual($user->Phonenumber->count(), 2);

        // REPLACING ONE-TO-MANY REFERENCE

        unset($coll);
        $coll = new Doctrine_Collection($pf);
        $coll[0]->phonenumber = "123 123";
        $coll["home"]->phonenumber = "444 444";
        $coll["work"]->phonenumber = "444 444";




        $user->Phonenumber = $coll;
        $this->connection->flush();
        $this->assertEqual($user->Phonenumber->count(), 3);
        $user = $this->objTable->find(5);
        $this->assertEqual($user->Phonenumber->count(), 3);

        
        // ONE-TO-ONE REFERENCES

        $user->Email->address = "drinker@drinkmore.info";
        $this->assertTrue($user->Email instanceof Email);
        $this->connection->flush();
        $this->assertTrue($user->Email instanceof Email);
        $user = $this->objTable->find(5);
        $this->assertEqual($user->Email->address, "drinker@drinkmore.info");
        $id = $user->Email->id;

        // REPLACING ONE-TO-ONE REFERENCES

        $email = $this->connection->create("Email");
        $email->address = "absolutist@nottodrink.com";
        $user->Email = $email;

        $this->assertTrue($user->Email instanceof Email);
        $this->assertEqual($user->Email->address, "absolutist@nottodrink.com");
        $this->connection->flush();
        unset($user);

        $user = $this->objTable->find(5);
        $this->assertTrue($user->Email instanceof Email);
        $this->assertEqual($user->Email->address, "absolutist@nottodrink.com");
        
        $emails = $this->connection->query("FROM Email WHERE Email.id = $id");
        //$this->assertEqual(count($emails),0);


    }

    public function testGetManager() {
        $this->assertEqual($this->connection->getManager(),$this->manager);
    }
    public function testQuery() {
        $this->assertTrue($this->connection->query("FROM User") instanceof Doctrine_Collection);
    }

    public function testDelete() {
        $user = $this->connection->create("User");
        $this->connection->delete($user);
        $this->assertEqual($user->getState(),Doctrine_Record::STATE_TCLEAN);
    }
    public function testGetTable() {
        $table = $this->connection->getTable("Group");
        $this->assertTrue($table instanceof Doctrine_Table);
        try {
            $table = $this->connection->getTable("Unknown");
            $f = false;
        } catch(Doctrine_Exception $e) {
            $f = true;
        }
        $this->assertTrue($f);

        $table = $this->connection->getTable("User");
        $this->assertTrue($table instanceof UserTable);

    }
    public function testCreate() {
        $email = $this->connection->create("Email");
        $this->assertTrue($email instanceof Email);
    }
    public function testGetDBH() {
        $this->assertTrue($this->connection->getDBH() instanceof PDO);
    }
    public function testCount() {
        $this->assertTrue(is_integer(count($this->connection)));
    }
    public function testGetIterator() {
        $this->assertTrue($this->connection->getIterator() instanceof ArrayIterator);
    }
    public function testGetState() {
        $this->assertEqual($this->connection->getState(),Doctrine_Connection::STATE_OPEN);
        $this->assertEqual(Doctrine_Lib::getConnectionStateAsString($this->connection->getState()), "open");
    }
    public function testGetTables() {
        $this->assertTrue(is_array($this->connection->getTables()));
    }

    public function testTransactions() {

        $this->connection->beginTransaction();
        $this->assertEqual($this->connection->getState(),Doctrine_Connection::STATE_ACTIVE);
        $this->connection->commit();
        $this->assertEqual($this->connection->getState(),Doctrine_Connection::STATE_OPEN);

        $this->connection->beginTransaction();
        
        $user = $this->objTable->find(6);
        
        $user->name = "Jack Daniels";
        $this->connection->flush();
        $this->connection->commit();

        $user = $this->objTable->find(6);
        $this->assertEqual($user->name, "Jack Daniels");

    }

    public function testRollback() {
        $this->connection->beginTransaction();
        $this->assertEqual($this->connection->getTransactionLevel(),1);
        $this->assertEqual($this->connection->getState(),Doctrine_Connection::STATE_ACTIVE);
        $this->connection->rollback();
        $this->assertEqual($this->connection->getState(),Doctrine_Connection::STATE_OPEN);
        $this->assertEqual($this->connection->getTransactionLevel(),0);
    }
    public function testNestedTransactions() {
        $this->assertEqual($this->connection->getTransactionLevel(),0);
        $this->connection->beginTransaction();
        $this->assertEqual($this->connection->getTransactionLevel(),1);
        $this->assertEqual($this->connection->getState(),Doctrine_Connection::STATE_ACTIVE);
        $this->connection->beginTransaction();
        $this->assertEqual($this->connection->getState(),Doctrine_Connection::STATE_BUSY);
        $this->assertEqual($this->connection->getTransactionLevel(),2);
        $this->connection->commit();
        $this->assertEqual($this->connection->getState(),Doctrine_Connection::STATE_ACTIVE);
        $this->assertEqual($this->connection->getTransactionLevel(),1);
        $this->connection->commit();
        $this->assertEqual($this->connection->getState(),Doctrine_Connection::STATE_OPEN);
        $this->assertEqual($this->connection->getTransactionLevel(),0);
    }
}
?>
