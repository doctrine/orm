<?php
require_once("UnitTestCase.class.php");

class Doctrine_RecordTestCase extends Doctrine_UnitTestCase {

    public function testJoinTableSelfReferencing() {
        $e = new Entity();
        $e->name = "Entity test";
        $this->assertTrue($e->Entity[0] instanceof Entity);
        $this->assertTrue($e->Entity[1] instanceof Entity);

        $this->assertEqual($e->Entity[0]->getState(), Doctrine_Record::STATE_TCLEAN);
        $this->assertEqual($e->Entity[1]->getState(), Doctrine_Record::STATE_TCLEAN);
        
        $e->Entity[0]->name = "Subentity 1";
        $e->Entity[1]->name = "Subentity 2";
        
        $this->assertEqual($e->Entity[0]->name, "Subentity 1");
        $this->assertEqual($e->Entity[1]->name, "Subentity 2");   

        $this->assertEqual($e->Entity[0]->getState(), Doctrine_Record::STATE_TDIRTY);
        $this->assertEqual($e->Entity[1]->getState(), Doctrine_Record::STATE_TDIRTY);

        $e->save();

        $this->assertTrue($e->Entity[0] instanceof Entity);
        $this->assertTrue($e->Entity[1] instanceof Entity);
        
        $this->assertEqual($e->Entity[0]->name, "Subentity 1");
        $this->assertEqual($e->Entity[1]->name, "Subentity 2");

        $this->assertEqual($e->Entity[0]->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($e->Entity[1]->getState(), Doctrine_Record::STATE_CLEAN);

        $e = $e->getTable()->find($e->getID());
        
        $this->assertTrue($e->Entity[0] instanceof Entity);
        $this->assertTrue($e->Entity[1] instanceof Entity);

        $this->assertEqual($e->Entity[0]->name, "Subentity 1");
        $this->assertEqual($e->Entity[1]->name, "Subentity 2");

        $this->assertEqual($e->Entity[0]->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($e->Entity[1]->getState(), Doctrine_Record::STATE_CLEAN);
        
        $coll = $this->session->query("FROM Entity WHERE Entity.name = 'Subentity 1'");
        $this->assertEqual($coll->count(), 1);
        $this->assertEqual($coll[0]->getState(), Doctrine_Record::STATE_CLEAN);
        
        $this->assertEqual($coll[0]->name, "Subentity 1");
    }

    public function testCompositePK() {
        $record = new EntityReference();
        $this->assertEqual($record->getTable()->getIdentifier(), array("entity1","entity2"));
        $this->assertEqual($record->getTable()->getIdentifierType(), Doctrine_Identifier::COMPOSITE);
        $this->assertEqual($record->getID(), array("entity1" => null, "entity2" => null));
        $this->assertEqual($record->getState(), Doctrine_Record::STATE_TCLEAN);

        $record->entity1 = 3;
        $record->entity2 = 4;
        $this->assertEqual($record->entity2, 4);
        $this->assertEqual($record->entity1, 3);
        $this->assertEqual($record->getState(), Doctrine_Record::STATE_TDIRTY);
        $this->assertEqual($record->getID(), array("entity1" => null, "entity2" => null));

        $record->save();
        $this->assertEqual($record->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($record->entity2, 4);
        $this->assertEqual($record->entity1, 3);
        $this->assertEqual($record->getID(), array("entity1" => 3, "entity2" => 4));

        $record = $record->getTable()->find($record->getID());
        $this->assertEqual($record->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($record->entity2, 4);
        $this->assertEqual($record->entity1, 3);

        $this->assertEqual($record->getID(), array("entity1" => 3, "entity2" => 4));
        
        $record->entity2 = 5;
        $record->entity1 = 2;
        $this->assertEqual($record->getState(), Doctrine_Record::STATE_DIRTY);
        $this->assertEqual($record->entity2, 5);
        $this->assertEqual($record->entity1, 2);
        $this->assertEqual($record->getID(), array("entity1" => 3, "entity2" => 4));

        $record->save();
        $this->assertEqual($record->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($record->entity2, 5);
        $this->assertEqual($record->entity1, 2);
        $this->assertEqual($record->getID(), array("entity1" => 2, "entity2" => 5));
        $record = $record->getTable()->find($record->getID());

        $this->assertEqual($record->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($record->entity2, 5);
        $this->assertEqual($record->entity1, 2);
        $this->assertEqual($record->getID(), array("entity1" => 2, "entity2" => 5));
        
        $record->refresh();
        $this->assertEqual($record->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($record->entity2, 5);
        $this->assertEqual($record->entity1, 2);
        $this->assertEqual($record->getID(), array("entity1" => 2, "entity2" => 5));

        $record = new EntityReference();
        $record->entity2 = 6;
        $record->entity1 = 2;
        $record->save();
        
        $coll = $this->session->query("FROM EntityReference-b");
        $this->assertTrue($coll[0] instanceof EntityReference);
        $this->assertEqual($coll[0]->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertTrue($coll[1] instanceof EntityReference);
        $this->assertEqual($coll[1]->getState(), Doctrine_Record::STATE_CLEAN);

        $coll = $this->session->query("FROM EntityReference-b WHERE EntityReference.entity2 = 5");
        $this->assertEqual($coll->count(), 1);
    }


    public function testManyToManyTreeStructure() {

        $task = $this->session->create("Task");
        $this->assertEqual($task->getTable()->getAlias("Resource"), "ResourceAlias");

        $task->name = "Task 1";
        $task->ResourceAlias[0]->name = "Resource 1";

        $this->session->flush();

        $this->assertTrue($task->ResourceAlias[0] instanceof Resource);
        $this->assertEqual($task->ResourceAlias[0]->name, "Resource 1");
        $this->assertEqual($this->dbh->query("SELECT COUNT(*) FROM assignment")->fetch(PDO::FETCH_NUM),array(1));

        $task = new Task();
        $this->assertTrue($task instanceof Task);
        $this->assertEqual($task->getState(), Doctrine_Record::STATE_TCLEAN);
        $this->assertTrue($task->Subtask[0] instanceof Task);

        //$this->assertEqual($task->Subtask[0]->getState(), Doctrine_Record::STATE_TDIRTY);
        $this->assertTrue($task->ResourceAlias[0] instanceof Resource);
        $this->assertEqual($task->ResourceAlias[0]->getState(), Doctrine_Record::STATE_TCLEAN);

        $task->name = "Task 1";
        $task->ResourceAlias[0]->name = "Resource 1";
        $task->Subtask[0]->name = "Subtask 1";

        $this->assertEqual($task->name, "Task 1");
        $this->assertEqual($task->ResourceAlias[0]->name, "Resource 1");
        $this->assertEqual($task->ResourceAlias->count(), 1);
        $this->assertEqual($task->Subtask[0]->name, "Subtask 1");

        $this->session->flush();

        $task = $task->getTable()->find($task->getID());

        $this->assertEqual($task->name, "Task 1");
        $this->assertEqual($task->ResourceAlias[0]->name, "Resource 1");
        $this->assertEqual($task->ResourceAlias->count(), 1);
        $this->assertEqual($task->Subtask[0]->name, "Subtask 1");

    }

    public function testOne2OneForeign() {

        $user = new User();
        $user->name = "Richard Linklater";
        $account = $user->Account;
        $account->amount = 1000;
        $this->assertTrue($account instanceof Account);
        $this->assertEqual($account->getState(), Doctrine_Record::STATE_TDIRTY);
        $this->assertEqual($account->entity_id, $user);
        $this->assertEqual($account->amount, 1000);
        $this->assertEqual($user->name, "Richard Linklater");

        $user->save();

        $user->refresh();
        $account = $user->Account;
        $this->assertTrue($account instanceof Account);
        $this->assertEqual($account->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($account->entity_id, $user->getID());
        $this->assertEqual($account->amount, 1000);
        $this->assertEqual($user->name, "Richard Linklater");


        $user = new User();
        $user->name = "John Rambo";
        $account = $user->Account;
        $account->amount = 2000;
        $this->assertEqual($account->getTable()->getColumnNames(), array("id","entity_id","amount"));

        $this->session->flush();
        $this->assertEqual($user->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertTrue($account instanceof Account);

        $this->assertEqual($account->getTable()->getColumnNames(), array("id","entity_id","amount"));
        $this->assertEqual($account->entity_id, $user->getID());
        $this->assertEqual($account->amount, 2000);


        $user = $user->getTable()->find($user->getID());
        $this->assertEqual($user->getState(), Doctrine_Record::STATE_CLEAN);


        $account = $user->Account;
        $this->assertTrue($account instanceof Account);

        $this->assertEqual($account->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($account->getTable()->getColumnNames(), array("id","entity_id","amount"));

        $this->assertEqual($account->entity_id, $user->getID());
        $this->assertEqual($account->amount, 2000);
        $this->assertEqual($user->name, "John Rambo");

    }

    public function testGet() {
        $user = new User();
        $user->name = "Jack Daniels";
        $this->assertEqual($user->name, "Jack Daniels");
        $this->assertEqual($user->created, null);
        $this->assertEqual($user->updated, null);
        $user->save();
        $id = $user->getID();
        $user = $user->getTable()->find($id);
        $this->assertEqual($user->name, "Jack Daniels");
        $this->assertEqual($user->created, null);
        $this->assertEqual($user->updated, null);
        $this->assertEqual($user->getTable()->getData(), array());

    }

    public function testNewOperator() {
        $table = $this->session->getTable("User");

        $this->assertEqual($this->session->getTable("User")->getData(), array());
        $user = new User();
        $this->assertEqual(Doctrine_Lib::getRecordStateAsString($user->getState()), Doctrine_Lib::getRecordStateAsString(Doctrine_Record::STATE_TCLEAN));
        $user->name = "John Locke";

        $this->assertTrue($user->name,"John Locke");
        $this->assertTrue($user->getState() == Doctrine_Record::STATE_TDIRTY);
        $user->save();
        $this->assertTrue($user->getState() == Doctrine_Record::STATE_CLEAN);
        $this->assertTrue($user->name,"John Locke");
    }

    public function testTreeStructure() {
        $e = new Element();

        $fk = $e->getTable()->getForeignKey("Child");
        $this->assertTrue($fk instanceof Doctrine_ForeignKey);
        $this->assertEqual($fk->getType(), Doctrine_Relation::MANY_AGGREGATE);
        $this->assertEqual($fk->getForeign(), "parent_id");
        $this->assertEqual($fk->getLocal(), "id");
        


        $e->name = "parent";
        $e->Child[0]->name = "child 1";
        $e->Child[1]->name = "child 2";

        $e->Child[1]->Child[0]->name = "child 1's child 1";
        $e->Child[1]->Child[1]->name = "child 1's child 1";

        $this->assertEqual($e->name,"parent");

        $this->assertEqual($e->Child[0]->name,"child 1");
        $this->assertEqual($e->Child[1]->name,"child 2");
        $this->assertEqual($e->Child[1]->Child[0]->name,"child 1's child 1");
        $this->assertEqual($e->Child[1]->Child[1]->name,"child 1's child 1");



        $this->session->flush();

        $e = $e->getTable()->find(1);
        $this->assertEqual($e->name,"parent");

        $this->assertEqual($e->Child[0]->name,"child 1");

        $c = $e->getTable()->find(2);
        $this->assertEqual($c->name, "child 1");

        $this->assertEqual($e->Child[0]->parent_id, 1);
        $this->assertEqual($e->Child[0]->Parent->getID(), $e->getID());
        

        $this->assertEqual($e->Child[1]->parent_id, 1);
        $this->assertEqual($e->Child[1]->Child[0]->name,"child 1's child 1");
        $this->assertEqual($e->Child[1]->Child[1]->name,"child 1's child 1");
        $this->assertEqual($e->Child[1]->Child[0]->parent_id, 3);
        $this->assertEqual($e->Child[1]->Child[1]->parent_id, 3);

    }

    public function testUniqueKeyComponent() {
        $e = new Error();
        $e->message  = "user error";
        $e->file_md5 = md5(0);
        $e->code     = 1;

        // ADDING NEW RECORD
        $this->assertEqual($e->code,1);
        $this->assertEqual($e->file_md5, md5(0));
        $this->assertEqual($e->message, "user error");

        $e2 = new Error();
        $e2->message  = "user error2";
        $e2->file_md5 = md5(1);
        $e2->code     = 2;

        $this->assertEqual($e2->code,2);
        $this->assertEqual($e2->file_md5, md5(1));
        $this->assertEqual($e2->message, "user error2");
        

        $fk = $e->getTable()->getForeignKey("Description");
        $this->assertTrue($fk instanceof Doctrine_ForeignKey);
        $this->assertEqual($fk->getLocal(),"file_md5");
        $this->assertEqual($fk->getForeign(),"file_md5");
        $this->assertTrue($fk->getTable() instanceof Doctrine_Table);

        $e->Description[0]->description = "This is the 1st description";
        $e->Description[1]->description = "This is the 2nd description";
        $this->assertEqual($e->Description[0]->description, "This is the 1st description");
        $this->assertEqual($e->Description[1]->description, "This is the 2nd description");
        $this->assertEqual($e->Description[0]->file_md5, $e->file_md5);
        $this->assertEqual($e->Description[1]->file_md5, $e->file_md5);
       
        $this->assertEqual($e2->Description[0]->description, null);
        $this->assertEqual($e2->Description[1]->description, null);
        $this->assertEqual($e2->Description[0]->file_md5, $e2->file_md5);
        $this->assertEqual($e2->Description[1]->file_md5, $e2->file_md5);

        $e->save();
        
        $coll = $this->session->query("FROM Error-I");
        $e = $coll[0];


        $this->assertEqual($e->code,1);
        $this->assertEqual($e->file_md5, md5(0));
        $this->assertEqual($e->message, "user error");

        $this->assertTrue($e->Description instanceof Doctrine_Collection);
        $this->assertTrue($e->Description[0] instanceof Description);
        $this->assertTrue($e->Description[1] instanceof Description);

        $this->assertEqual($e->Description[0]->description, "This is the 1st description");
        $this->assertEqual($e->Description[1]->description, "This is the 2nd description");

        // UPDATING
         
        $e->code = 2;
        $e->message = "changed message";
        $e->Description[0]->description = "1st changed description";
        $e->Description[1]->description = "2nd changed description";


        $this->assertEqual($e->code,2);
        $this->assertEqual($e->message,"changed message");
        $this->assertEqual($e->Description[0]->description, "1st changed description");
        $this->assertEqual($e->Description[1]->description, "2nd changed description");

        $e->save();
        $this->assertEqual($e->code,2);
        $this->assertEqual($e->message,"changed message");
        $this->assertEqual($e->Description[0]->description, "1st changed description");
        $this->assertEqual($e->Description[1]->description, "2nd changed description");
    }

    public function testInsert() {
        $user = new User();
        $user->name = "John Locke";
        $user->save();
        
        $this->assertTrue($user->getModified() == array());
        $this->assertTrue($user->getState() == Doctrine_Record::STATE_CLEAN);

        $debug = $this->listener->getMessages();
        $p = array_pop($debug);
        $this->assertTrue($p->getObject() instanceof Doctrine_Session);
        $this->assertTrue($p->getCode() == Doctrine_Debugger::EVENT_COMMIT);

        $user->delete();
        $this->assertTrue($user->getState() == Doctrine_Record::STATE_TCLEAN);
    }

    public function testUpdate() {
        $user = $this->session->getTable("User")->find(4);
        $user->set("name","Jack Daniels",true);


        $user->save();
        //print $this->old->name;

        $this->assertEqual($user->getModified(), array());
        $this->assertEqual($user->name, "Jack Daniels");
        
        $debug = $this->listener->getMessages();
        $p = array_pop($debug);
        $this->assertTrue($p->getObject() instanceof Doctrine_Session);
        $this->assertTrue($p->getCode() == Doctrine_Debugger::EVENT_COMMIT);
        
        $p = array_pop($debug);
        $this->assertTrue($p->getObject() instanceof Doctrine_Record);
        $this->assertTrue($p->getCode() == Doctrine_Debugger::EVENT_SAVE);

        $p = array_pop($debug);
        $this->assertTrue($p->getObject() instanceof Doctrine_Record);
        $this->assertTrue($p->getCode() == Doctrine_Debugger::EVENT_UPDATE);


    }
    public function testCopy() {
        $user = $this->session->getTable("User")->find(4);
        $new = $user->copy();
        $this->assertTrue($new instanceof Doctrine_Record);
        $this->assertTrue($new->getState() == Doctrine_Record::STATE_TDIRTY);
    }

    public function testReferences() {

        $user = $this->objTable->find(5);

        $pf   = $this->session->getTable("Phonenumber");

        $this->assertTrue($user->Phonenumber instanceof Doctrine_Collection);
        $this->assertEqual($user->Phonenumber->count(), 3);

        $coll = new Doctrine_Collection($pf);

        $user->Phonenumber = $coll;
        $this->assertTrue($user->Phonenumber->count() == 0);
        $user->save();
        unset($user);
        $user = $this->objTable->find(5);

        $this->assertEqual($user->Phonenumber->count(), 0);

        // ADDING REFERENCES

        $user->Phonenumber[0]->phonenumber = "123 123";

        $user->Phonenumber[1]->phonenumber = "123 123";
        $user->save();


        $this->assertTrue($user->Phonenumber->count() == 2);

        unset($user);
        $user = $this->objTable->find(5);
        $this->assertEqual($user->Phonenumber->count(), 2);

        $user->Phonenumber[3]->phonenumber = "123 123";
        $user->save();

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
        $user->save();

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
        $user->save();
        $this->assertEqual($user->Phonenumber->count(), 3);

        $user = $this->objTable->find(5);
        //$this->assertEqual($user->Phonenumber->count(), 3);


        // ONE-TO-ONE REFERENCES

        $user->Email->address = "drinker@drinkmore.info";
        $this->assertTrue($user->Email instanceof Email);        
        $this->assertEqual($user->Email->address, "drinker@drinkmore.info");

        $user->save();

        $this->assertTrue($user->Email instanceof Email);
        $this->assertEqual($user->Email->address, "drinker@drinkmore.info");
        $this->assertEqual($user->Email->getID(), $user->email_id);

        $user = $this->objTable->find(5);

        $this->assertTrue($user->Email instanceof Email);
        $this->assertEqual($user->Email->getID(), $user->email_id);
        $this->assertEqual($user->Email->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($user->Email->address, "drinker@drinkmore.info");
        $id = $user->Email->getID();

        // REPLACING ONE-TO-ONE REFERENCES

        $email = $this->session->create("Email");
        $email->address = "absolutist@nottodrink.com";
        $user->Email = $email;

        $this->assertTrue($user->Email instanceof Email);
        $this->assertEqual($user->Email->address, "absolutist@nottodrink.com");
        $user->save();
        unset($user);

        $user = $this->objTable->find(5);
        $this->assertTrue($user->Email instanceof Email);
        $this->assertEqual($user->Email->address, "absolutist@nottodrink.com");
        
        $emails = $this->session->query("FROM Email WHERE Email.id = $id");
        //$this->assertEqual(count($emails),0);

    }

    public function testDeleteReference() {
        $user = $this->objTable->find(5);
        $int  = $user->Phonenumber->delete();

        $this->assertTrue($user->Phonenumber->count() == 0);
    }

    public function testSaveAssociations() {
        $user = $this->objTable->find(5);

        $gf   = $this->session->getTable("Group");

        $this->assertTrue($user->Group instanceof Doctrine_Collection);


        // ADDING ASSOCIATED REFERENCES


        $record = $gf->find(1);
        $record2 = $gf->find(2);
        $user->Group[0] = $record;
        $user->Group[1] = $record2;

        $this->assertTrue($user->Group->count() == 2);

        $user->save();
        

        // UNSETTING ASSOCIATED REFERENCES


        unset($user);
        $user = $this->objTable->find(5);
        $this->assertTrue($user->Group->count() == 2);

        unset($user->Group[0]);
        $this->assertTrue($user->Group->count() == 1);

        unset($user->Group[1]);
        $this->assertTrue($user->Group->count() == 0);

        $user->save();
        $this->assertTrue($user->Group->count() == 0);
        unset($user);


        // CHECKING THE PERSISTENCE OF UNSET ASSOCIATED REFERENCES


        $user = $this->objTable->find(5);
        $this->assertTrue($user->Group->count() == 0);


        // REPLACING OLD ASSOCIATED REFERENCE


        $user->Group[0] = $record;
        $user->save();

        $user->Group[0] = $record2;
        $user->save();

        $this->assertEqual($user->Group->count(), 1);
        $this->assertEqual($user->Group[0]->getID(), $record2->getID());
        $this->assertFalse($user->Group[0]->getID() == $record->getID());


        $user->Group[0] = $record;
        $user->Group[1] = $gf->find(3);

        $user->save();
        $this->assertEqual($user->Group->count(), 2);
        $user = $this->objTable->find(5);
        $this->assertEqual($user->Group->count(), 2);



        $user->Group = new Doctrine_Collection($gf);
        $user->save();
        $this->assertEqual($user->Group->count(), 0);
        $user = $this->objTable->find(5);
        $this->assertEqual($user->Group->count(), 0);  
        

        // ACCESSING ASSOCIATION OBJECT PROPERTIES

        $user = new User();
        $this->assertTrue($user->getTable()->getForeignKey("Groupuser") instanceof Doctrine_ForeignKey);
        $this->assertTrue($user->Groupuser instanceof Doctrine_Collection);
        $this->assertTrue($user->Groupuser[0] instanceof Groupuser);
        
        $user->name = "Jack Daniels";
        $user->Group[0]->name = "Group #1";
        $user->Group[1]->name = "Group #2";
        $t1 = time();
        $t2 = time();
        $user->Groupuser[0]->added = $t1;
        $user->Groupuser[1]->added = $t2;
        
        $this->assertEqual($user->Groupuser[0]->added, $t1);
        $this->assertEqual($user->Groupuser[1]->added, $t2);
        
        $user->save();

        $user->refresh();
        $this->assertEqual($user->Groupuser[0]->added, $t1);
        $this->assertEqual($user->Groupuser[1]->added, $t2);
    }

    public function testCount() {
        $user = $this->session->getTable("User")->find(4);

        $this->assertTrue(is_integer($user->count()));
    }

    public function testGetReference() {
        $user = $this->session->getTable("User")->find(4);

        $this->assertTrue($user->Email instanceof Doctrine_Record);
        $this->assertTrue($user->Phonenumber instanceof Doctrine_Collection);
        $this->assertTrue($user->Group instanceof Doctrine_Collection);

        $this->assertTrue($user->Phonenumber->count() == 1);
    }

    public function testSerialize() {
        $user = $this->session->getTable("User")->find(4);
        $str = serialize($user);

        $this->assertEqual(unserialize($str)->getID(),$user->getID());
    }

    public function testGetIterator() {
        $user = $this->session->getTable("User")->find(4);
        $this->assertTrue($user->getIterator() instanceof ArrayIterator);
    }

}
?>
