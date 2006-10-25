<?php
require_once("UnitTestCase.php");

class Doctrine_Record_TestCase extends Doctrine_UnitTestCase {

    public function prepareTables() {
        $this->tables[] = "enumTest";
        $this->tables[] = "fieldNameTest";
        $this->tables[] = "GzipTest";
        parent::prepareTables();
    }

    public function testIssetForPrimaryKey() {
        $this->assertTrue(isset($this->users[0]->id));
        $this->assertTrue(isset($this->users[0]['id']));
        $this->assertTrue($this->users[0]->contains('id'));

        $user = new User();

        $this->assertFalse(isset($user->id));
        $this->assertFalse(isset($user['id']));
        $this->assertFalse($user->contains('id'));
    }
    public function testUnknownColumn() {
                                        	
    }                                    	
    public function testNotNullConstraint() {
        $null = new NotNullTest();

        $null->name = null;

        $null->type = 1;
        try {
            $null->save();
            $this->fail();
        } catch(Doctrine_Exception $e) {
            $this->pass();
            $this->connection->rollback();
        }

    }

    public function testGzipType() {
        $gzip = new GzipTest();
        $gzip->gzip = "compressed";

        $this->assertEqual($gzip->gzip, "compressed");
        $gzip->save();
        $this->assertEqual($gzip->gzip, "compressed");
        $gzip->refresh();
        $this->assertEqual($gzip->gzip, "compressed");

        $this->connection->clear();
        $gzip = $gzip->getTable()->find($gzip->id);
        $this->assertEqual($gzip->gzip, "compressed");
        
        $gzip->gzip = "compressed 2";

        $this->assertEqual($gzip->gzip, "compressed 2");
        $gzip->save();
        $this->assertEqual($gzip->gzip, "compressed 2");
        $gzip->refresh();
        $this->assertEqual($gzip->gzip, "compressed 2");
    }

    public function testDefaultValues() {

        $test = new FieldNameTest;
        
        $this->assertEqual($test->someColumn, 'some string');
        $this->assertEqual($test->someEnum, 'php');
        $this->assertEqual($test->someArray, array());
        $this->assertTrue(is_object($test->someObject));
        $this->assertEqual($test->someInt, 11);
    }

    public function testJoinTableSelfReferencingInsertingData() {
        $e = new Entity();
        $e->name = "Entity test";
        $this->assertTrue($e->Entity[0] instanceof Entity);
        $this->assertTrue($e->Entity[1] instanceof Entity);

        $this->assertEqual($e->Entity[0]->getState(), Doctrine_Record::STATE_TCLEAN);
        $this->assertEqual($e->Entity[1]->getState(), Doctrine_Record::STATE_TCLEAN);
        
        $e->Entity[0]->name = "Friend 1";
        $e->Entity[1]->name = "Friend 2";
        
        $e->Entity[0]->Entity[0]->name = "Friend 1 1";
        $e->Entity[0]->Entity[1]->name = "Friend 1 2";

        $e->Entity[1]->Entity[0]->name = "Friend 2 1";
        $e->Entity[1]->Entity[1]->name = "Friend 2 2";

        $this->assertEqual($e->Entity[0]->name, "Friend 1");
        $this->assertEqual($e->Entity[1]->name, "Friend 2");

        $this->assertEqual($e->Entity[0]->Entity[0]->name, "Friend 1 1");
        $this->assertEqual($e->Entity[0]->Entity[1]->name, "Friend 1 2");

        $this->assertEqual($e->Entity[1]->Entity[0]->name, "Friend 2 1");
        $this->assertEqual($e->Entity[1]->Entity[1]->name, "Friend 2 2");


        $this->assertEqual($e->Entity[0]->getState(), Doctrine_Record::STATE_TDIRTY);
        $this->assertEqual($e->Entity[1]->getState(), Doctrine_Record::STATE_TDIRTY);

        $count = count($this->dbh);

        $e->save();

        $this->assertEqual(($count + 13), $this->dbh->count());
        $this->assertEqual($e->getState(), Doctrine_Record::STATE_CLEAN);

        $this->assertTrue($e->Entity[0] instanceof Entity);
        $this->assertTrue($e->Entity[1] instanceof Entity);

        $this->assertEqual($e->Entity[0]->name, "Friend 1");
        $this->assertEqual($e->Entity[1]->name, "Friend 2");

        $this->assertEqual($e->Entity[0]->Entity[0]->name, "Friend 1 1");
        $this->assertEqual($e->Entity[0]->Entity[1]->name, "Friend 1 2");

        $this->assertEqual($e->Entity[1]->Entity[0]->name, "Friend 2 1");
        $this->assertEqual($e->Entity[1]->Entity[1]->name, "Friend 2 2");

        $this->assertEqual($e->Entity[0]->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($e->Entity[1]->getState(), Doctrine_Record::STATE_CLEAN);

        $this->assertTrue(is_numeric($e->id));

        $result = $this->dbh->query("SELECT * FROM entity_reference")->fetchAll(PDO::FETCH_ASSOC);

        $this->assertEqual(count($result), 6);

        //$stmt = $this->dbh->prepare($q);

        //$stmt->execute(array(18));
        //$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        //print_r($result);

        $this->connection->clear();

        $e = $e->getTable()->find($e->id);

        $count = count($this->dbh);

        $this->assertTrue($e instanceof Entity);

        $this->assertTrue($e->Entity[0] instanceof Entity);
        $this->assertTrue($e->Entity[1] instanceof Entity);



        $this->assertEqual(count($this->dbh), ($count + 1));

        $this->assertEqual($e->Entity[0]->name, "Friend 1");
        $this->assertEqual($e->Entity[1]->name, "Friend 2");

        $this->assertEqual($e->Entity[0]->Entity[0]->name, "Friend 1 1");
        $this->assertEqual($e->Entity[0]->Entity[1]->name, "Friend 1 2");

        $this->assertEqual(count($this->dbh), ($count + 2));

        $this->assertEqual($e->Entity[1]->Entity[0]->name, "Friend 2 1");
        $this->assertEqual($e->Entity[1]->Entity[1]->name, "Friend 2 2");

        $this->assertEqual(count($this->dbh), ($count + 3));

        $this->assertEqual($e->Entity[0]->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($e->Entity[1]->getState(), Doctrine_Record::STATE_CLEAN);
        
        $coll = $this->connection->query("FROM Entity WHERE Entity.name = 'Friend 1'");
        $this->assertEqual($coll->count(), 1);
        $this->assertEqual($coll[0]->getState(), Doctrine_Record::STATE_CLEAN);
        
        $this->assertEqual($coll[0]->name, "Friend 1");
        
        $query = new Doctrine_Query($this->connection);

        $query->from("Entity.Entity")->where("Entity.Entity.name = 'Friend 1 1'");

        $coll = $query->execute();

        $this->assertEqual($coll->count(), 1);
    }


    public function testToArray() {
        $user = new User();
        
        $a = $user->toArray();

        $this->assertTrue(is_array($a));
        $this->assertTrue(array_key_exists('name', $a));
        $this->assertEqual($a['name'], null);
        $this->assertTrue(array_key_exists('id', $a));
        $this->assertEqual($a['id'], null);
        
        $user->name = 'Someone';

        $user->save();

        $a = $user->toArray();

        $this->assertTrue(is_array($a));
        $this->assertTrue(array_key_exists('name', $a));
        $this->assertEqual($a['name'], 'Someone');
        $this->assertTrue(array_key_exists('id', $a));
        $this->assertTrue(is_numeric($a['id']));
        
        $user->refresh();

        $a = $user->toArray();

        $this->assertTrue(is_array($a));
        $this->assertTrue(array_key_exists('name', $a));
        $this->assertEqual($a['name'], 'Someone');
        $this->assertTrue(array_key_exists('id', $a));
        $this->assertTrue(is_numeric($a['id']));
        $this->connection->clear();
        $user = $user->getTable()->find($user->id);

        $a = $user->toArray();

        $this->assertTrue(is_array($a));
        $this->assertTrue(array_key_exists('name', $a));
        $this->assertEqual($a['name'], 'Someone');
        $this->assertTrue(array_key_exists('id', $a));
        $this->assertTrue(is_numeric($a['id']));
    }

    public function testReferences2() {
        $user = new User();
        $user->Phonenumber[0]->phonenumber = '123 123';
        $this->assertEqual($user->Phonenumber[0]->entity_id, $user);
    }


    public function testCountRelated() {
        $user = $this->connection->getTable('Entity')->find(5);
        $c = $user->countRelated('Phonenumber');

        $this->assertEqual($c, 3);
        
        $user = $this->connection->getTable('Entity')->find(7);
        $c = $user->countRelated('Phonenumber');

        $this->assertEqual($c, 1);

        $user = $this->connection->getTable('Entity')->find(8);
        $c = $user->countRelated('Phonenumber');

        $this->assertEqual($c, 3);
    }
    public function testUpdatingWithNullValue() {
        $user = $this->connection->getTable('User')->find(5);
        $user->name = null;
        $this->assertEqual($user->name, null);

        $user->save();

        $this->assertEqual($user->name, null);
        
        $this->connection->clear();

        $user = $this->connection->getTable('User')->find(5);
        $this->assertEqual($user->name, null);

    }

    public function testDateTimeType() {
        $date = new DateTest();

                                       	
    }   


    public function testSerialize() {
        $user = $this->connection->getTable("User")->find(4);
        $str = serialize($user);
        $user2 = unserialize($str);

        $this->assertTrue($user2 instanceof User);
        $this->assertEqual($user2->obtainIdentifier(),$user->obtainIdentifier());
    }

    public function testCallback() {
        $user = new User();
        $user->name = " zYne ";
        $user->call('trim', 'name');
        $this->assertEqual($user->name, 'zYne');
        $user->call('substr', 'name', 0, 1);
        $this->assertEqual($user->name, 'z');
    }



    public function testCompositePK() {
        $record = new EntityReference();
        $this->assertEqual($record->getTable()->getIdentifier(), array("entity1","entity2"));
        $this->assertEqual($record->getTable()->getIdentifierType(), Doctrine_Identifier::COMPOSITE);
        $this->assertEqual($record->obtainIdentifier(), array("entity1" => null, "entity2" => null));
        $this->assertEqual($record->getState(), Doctrine_Record::STATE_TCLEAN);

        $record->entity1 = 3;
        $record->entity2 = 4;
        $this->assertEqual($record->entity2, 4);
        $this->assertEqual($record->entity1, 3);
        $this->assertEqual($record->getState(), Doctrine_Record::STATE_TDIRTY);
        $this->assertEqual($record->obtainIdentifier(), array("entity1" => null, "entity2" => null));

        $record->save();
        $this->assertEqual($record->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($record->entity2, 4);
        $this->assertEqual($record->entity1, 3);
        $this->assertEqual($record->obtainIdentifier(), array("entity1" => 3, "entity2" => 4));

        $record = $record->getTable()->find($record->obtainIdentifier());
        $this->assertEqual($record->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($record->entity2, 4);
        $this->assertEqual($record->entity1, 3);

        $this->assertEqual($record->obtainIdentifier(), array("entity1" => 3, "entity2" => 4));
        
        $record->entity2 = 5;
        $record->entity1 = 2;
        $this->assertEqual($record->getState(), Doctrine_Record::STATE_DIRTY);
        $this->assertEqual($record->entity2, 5);
        $this->assertEqual($record->entity1, 2);
        $this->assertEqual($record->obtainIdentifier(), array("entity1" => 3, "entity2" => 4));

        $record->save();
        $this->assertEqual($record->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($record->entity2, 5);
        $this->assertEqual($record->entity1, 2);
        $this->assertEqual($record->obtainIdentifier(), array("entity1" => 2, "entity2" => 5));
        $record = $record->getTable()->find($record->obtainIdentifier());

        $this->assertEqual($record->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($record->entity2, 5);
        $this->assertEqual($record->entity1, 2);
        $this->assertEqual($record->obtainIdentifier(), array("entity1" => 2, "entity2" => 5));
        
        $record->refresh();
        $this->assertEqual($record->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($record->entity2, 5);
        $this->assertEqual($record->entity1, 2);
        $this->assertEqual($record->obtainIdentifier(), array("entity1" => 2, "entity2" => 5));

        $record = new EntityReference();
        $record->entity2 = 6;
        $record->entity1 = 2;
        $record->save();
        
        $coll = $this->connection->query("FROM EntityReference-b");
        $this->assertTrue($coll[0] instanceof EntityReference);
        $this->assertEqual($coll[0]->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertTrue($coll[1] instanceof EntityReference);
        $this->assertEqual($coll[1]->getState(), Doctrine_Record::STATE_CLEAN);

        $coll = $this->connection->query("FROM EntityReference-b WHERE EntityReference.entity2 = 5");
        $this->assertEqual($coll->count(), 1);
    }

    public function testManyToManyTreeStructure() {

        $task = $this->connection->create("Task");
        $this->assertEqual($task->getTable()->getAlias("Resource"), "ResourceAlias");

        $task->name = "Task 1";
        $task->ResourceAlias[0]->name = "Resource 1";

        $this->connection->flush();

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

        $this->connection->flush();

        $task = $task->getTable()->find($task->obtainIdentifier());

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
        $this->assertEqual($account->entity_id, $user->id);
        $this->assertEqual($account->amount, 1000);
        $this->assertEqual($user->name, "Richard Linklater");


        $user = new User();
        $user->name = "John Rambo";
        $account = $user->Account;
        $account->amount = 2000;
        $this->assertEqual($account->getTable()->getColumnNames(), array("id","entity_id","amount"));

        $this->connection->flush();
        $this->assertEqual($user->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertTrue($account instanceof Account);

        $this->assertEqual($account->getTable()->getColumnNames(), array("id","entity_id","amount"));
        $this->assertEqual($account->entity_id, $user->id);
        $this->assertEqual($account->amount, 2000);


        $user = $user->getTable()->find($user->id);
        $this->assertEqual($user->getState(), Doctrine_Record::STATE_CLEAN);


        $account = $user->Account;
        $this->assertTrue($account instanceof Account);

        $this->assertEqual($account->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($account->getTable()->getColumnNames(), array("id","entity_id","amount"));

        $this->assertEqual($account->entity_id, $user->id);
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
        $id = $user->obtainIdentifier();
        $user = $user->getTable()->find($id);
        $this->assertEqual($user->name, "Jack Daniels");
        $this->assertEqual($user->created, null);
        $this->assertEqual($user->updated, null);
        $this->assertEqual($user->getTable()->getData(), array());

    }

    public function testNewOperator() {
        $table = $this->connection->getTable("User");

        $this->assertEqual($this->connection->getTable("User")->getData(), array());
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

        $fk = $e->getTable()->getRelation("Child");
        $this->assertTrue($fk instanceof Doctrine_Relation_ForeignKey);
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



        $this->connection->flush();
        $elements = $this->connection->query("FROM Element-l");
        $this->assertEqual($elements->count(), 5);

        $e = $e->getTable()->find(1);
        $this->assertEqual($e->name,"parent");

        $this->assertEqual($e->Child[0]->name,"child 1");

        $c = $e->getTable()->find(2);
        $this->assertEqual($c->name, "child 1");

        $this->assertEqual($e->Child[0]->parent_id, 1);
        $this->assertEqual($e->Child[0]->Parent->obtainIdentifier(), $e->obtainIdentifier());


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
        

        $fk = $e->getTable()->getRelation("Description");
        $this->assertTrue($fk instanceof Doctrine_Relation_ForeignKey);
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
        
        $coll = $this->connection->query("FROM Error-I");
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
        
        $this->assertTrue(is_numeric($user->id) && $user->id > 0);
        
        $this->assertTrue($user->getModified() == array());
        $this->assertTrue($user->getState() == Doctrine_Record::STATE_CLEAN);

        $user->delete();
        $this->assertEqual($user->getState(), Doctrine_Record::STATE_TCLEAN);
    }

    public function testUpdate() {
        $user = $this->connection->getTable("User")->find(4);
        $user->set("name","Jack Daniels",true);


        $user->save();
        //print $this->old->name;

        $this->assertEqual($user->getModified(), array());
        $this->assertEqual($user->name, "Jack Daniels");
    }

    public function testCopy() {
        $user = $this->connection->getTable("User")->find(4);
        $new = $user->copy();

        $this->assertTrue($new instanceof Doctrine_Record);
        $this->assertTrue($new->getState() == Doctrine_Record::STATE_TDIRTY);

        $new = $user->copy();
        $new->save();
        $this->assertEqual($user->name, $new->name);
        $this->assertTrue(is_numeric($new->id) && $new->id > 0);
        $new->refresh();
        $this->assertEqual($user->name, $new->name);
        $this->assertTrue(is_numeric($new->id) && $new->id > 0);
    }

    public function testCopyAndModify() {
        $user = $this->connection->getTable("User")->find(4);
        $new = $user->copy();

        $this->assertTrue($new instanceof Doctrine_Record);
        $this->assertTrue($new->getState() == Doctrine_Record::STATE_TDIRTY);

        $new->loginname = 'jackd';

        $this->assertEqual($user->name, $new->name);
        $this->assertEqual($new->loginname, 'jackd');

        $new->save();
        $this->assertTrue(is_numeric($new->id) && $new->id > 0);

        $new->refresh();
        $this->assertEqual($user->name, $new->name);
        $this->assertEqual($new->loginname, 'jackd');
    }

    public function testReferences() {

        $user = $this->objTable->find(5);

        $pf   = $this->connection->getTable("Phonenumber");

        $this->assertTrue($user->Phonenumber instanceof Doctrine_Collection);
        $this->assertEqual($user->Phonenumber->count(), 3);

        $coll = new Doctrine_Collection($pf);

        $user->Phonenumber = $coll;
        $this->assertEqual($user->Phonenumber->count(), 0);
        $user->save();

        $user->getTable()->clear();

        $user = $this->objTable->find(5);

        $this->assertEqual($user->Phonenumber->count(), 0);
        $this->assertEqual(get_class($user->Phonenumber), "Doctrine_Collection_Immediate");

        $user->Phonenumber[0]->phonenumber;
        $this->assertEqual($user->Phonenumber->count(), 1);

        // ADDING REFERENCES

        $user->Phonenumber[0]->phonenumber = "123 123";

        $this->assertEqual($user->Phonenumber->count(), 1);
        $user->Phonenumber[1]->phonenumber = "123 123";
        $this->assertEqual($user->Phonenumber->count(), 2);

        $user->save();


        $this->assertEqual($user->Phonenumber->count(), 2);

        unset($user);
        $user = $this->objTable->find(5);
        $this->assertEqual($user->Phonenumber->count(), 2);

        $user->Phonenumber[3]->phonenumber = "123 123";
        $user->save();

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
        $user->save();

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
        $this->assertEqual($user->Email->id, $user->email_id);

        $user = $this->objTable->find(5);

        $this->assertTrue($user->Email instanceof Email);
        $this->assertEqual($user->Email->id, $user->email_id);
        $this->assertEqual($user->Email->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($user->Email->address, "drinker@drinkmore.info");
        $id = $user->Email->id;

        // REPLACING ONE-TO-ONE REFERENCES

        $email = $this->connection->create("Email");
        $email->address = "absolutist@nottodrink.com";
        $user->Email = $email;

        $this->assertTrue($user->Email instanceof Email);
        $this->assertEqual($user->Email->address, "absolutist@nottodrink.com");
        $user->save();
        unset($user);

        $user = $this->objTable->find(5);
        $this->assertTrue($user->Email instanceof Email);
        $this->assertEqual($user->Email->address, "absolutist@nottodrink.com");
        
        $emails = $this->connection->query("FROM Email WHERE Email.id = $id");
        //$this->assertEqual(count($emails),0);

    }

    public function testDeleteReference() {
        $user = $this->objTable->find(5);
        $int  = $user->Phonenumber->delete();

        $this->assertTrue($user->Phonenumber->count() == 0);
    }

    public function testSaveAssociations() {
        $user = $this->objTable->find(5);

        $gf   = $this->connection->getTable("Group");

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
        $this->assertEqual($user->Group[0]->obtainIdentifier(), $record2->obtainIdentifier());
        $this->assertFalse($user->Group[0]->obtainIdentifier() == $record->obtainIdentifier());


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
        $this->assertTrue($user->getTable()->getRelation("Groupuser") instanceof Doctrine_Relation_ForeignKey);
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
        $user = $this->connection->getTable("User")->find(4);

        $this->assertTrue(is_integer($user->count()));
    }

    public function testGetReference() {
        $user = $this->connection->getTable("User")->find(4);

        $this->assertTrue($user->Email instanceof Doctrine_Record);
        $this->assertTrue($user->Phonenumber instanceof Doctrine_Collection);
        $this->assertTrue($user->Group instanceof Doctrine_Collection);

        $this->assertTrue($user->Phonenumber->count() == 1);
    }
    public function testGetIterator() {
        $user = $this->connection->getTable("User")->find(4);
        $this->assertTrue($user->getIterator() instanceof ArrayIterator);
    }

}
?>
