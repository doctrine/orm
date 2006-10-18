<?php
class Doctrine_Query_TestCase extends Doctrine_UnitTestCase {
    public function prepareTables() {
        $this->tables[] = "Forum_Category";
        $this->tables[] = "Forum_Entry";
        $this->tables[] = "Forum_Board";
        $this->tables[] = "Forum_Thread";

        $this->tables[] = "ORM_TestEntry";
        $this->tables[] = "ORM_TestItem";
        $this->tables[] = "Log_Status";
        $this->tables[] = "Log_Entry";
        $this->tables[] = "EnumTest";
        
        $this->tables[] = "Task";
        $this->tables[] = "Resource";
        $this->tables[] = "ResourceType";

        try {
            $this->dbh->query("DROP TABLE test_items");
        } catch(PDOException $e) {

        }
        try {
            $this->dbh->query("DROP TABLE test_entries");
        } catch(PDOException $e) {

        }
        parent::prepareTables();
        $this->connection->clear();
    }
    public function testValidLazyPropertyFetching() {
        $q = new Doctrine_Query($this->connection);
        $q->from("User(id, name)");
        $users = $q->execute();
        $this->assertEqual($users->count(), 8);
        $this->assertTrue($users instanceof Doctrine_Collection);
        $count = count($this->dbh);
        $this->assertTrue(is_string($users[0]->name));
        $this->assertEqual($count, count($this->dbh));
        $count = count($this->dbh);
        $this->assertTrue(is_numeric($users[0]->email_id));
        $this->assertEqual($count + 1, count($this->dbh));
        
        $this->connection->clear();

        $q->from("User(name)");
        $users = $q->execute();
        $this->assertEqual($users->count(), 8);
        $count = count($this->dbh);
        $this->assertTrue(is_string($users[0]->name));
        $this->assertEqual($count, count($this->dbh));
        $count = count($this->dbh);
        $this->assertTrue($users[0]->getState(), Doctrine_Record::STATE_PROXY);
        $this->assertTrue(is_numeric($users[0]->email_id));
        $this->assertTrue($users[0]->getState(), Doctrine_Record::STATE_CLEAN);

        $this->assertEqual($count + 1, count($this->dbh));
        $this->assertTrue($users[1]->getState(), Doctrine_Record::STATE_PROXY);
        $this->assertTrue(is_numeric($users[1]->email_id));
        $this->assertTrue($users[1]->getState(), Doctrine_Record::STATE_CLEAN);

        $this->assertEqual($count + 2, count($this->dbh));
        $this->assertTrue($users[2]->getState(), Doctrine_Record::STATE_PROXY);
        $this->assertTrue(is_numeric($users[2]->email_id));
        $this->assertTrue($users[2]->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($count + 3, count($this->dbh));
    }
    public function testMultilineParsing() {
        $dql = "
           FROM User u
           WHERE User.id = ?
        ";

        $q     = new Doctrine_Query();
        
        $q->parseQuery($dql);

        $this->assertEqual($q->getQuery(), "SELECT entity.id AS entity__id, entity.name AS entity__name, entity.loginname AS entity__loginname, entity.password AS entity__password, entity.type AS entity__type, entity.created AS entity__created, entity.updated AS entity__updated, entity.email_id AS entity__email_id FROM entity WHERE entity.id = ? AND (entity.type = 0)");
    }
    public function testUnknownFunction() {
        $q = new Doctrine_Query();
        $f = false;
        try {
            $q->from('User')->where('User.name.someunknownfunc()');
        } catch(Doctrine_Query_Exception $e) {
            $f = true;
        }
        $this->assertTrue($f);
    }
    public function testBadFunctionLogic() {
        $q = new Doctrine_Query();
        $f = false;
        try {
            $q->from('User')->where('User.name.contains(?)');
        } catch(Doctrine_Query_Exception $e) {
            $f = true;
        }
        $this->assertTrue($f);
    }
    public function testDqlContainsFunction() {
        $q = new Doctrine_Query();
        $this->connection->clear();

        $q->from('User')->where('User.Phonenumber.phonenumber.contains(?)');
        $this->assertEqual(count($q->getTableStack()), 2);
        $this->assertEqual(count($q->getRelationStack()), 1);

        $coll = $q->execute(array('123 123'));

        $this->assertEqual($q->getQuery(), 'SELECT entity.id AS entity__id, entity.name AS entity__name, entity.loginname AS entity__loginname, entity.password AS entity__password, entity.type AS entity__type, entity.created AS entity__created, entity.updated AS entity__updated, entity.email_id AS entity__email_id FROM entity LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE entity.id IN (SELECT entity_id FROM phonenumber WHERE phonenumber = ?) AND (entity.type = 0)');

        $this->assertEqual($coll->count(), 3);
        $this->assertEqual($coll[0]->name, 'zYne');
        $this->assertEqual($coll[0]->Phonenumber->count(), 1);
        $this->assertEqual($coll[1]->Phonenumber->count(), 3);
        $this->assertEqual($coll[2]->Phonenumber->count(), 1);
    }
    public function testDqlFunctionWithMultipleParams() {
        $q = new Doctrine_Query();
        $this->connection->clear();

        $q->from('User')->where('User.Phonenumber.phonenumber.like(?,?)');
        $this->assertEqual(count($q->getTableStack()), 2);
        $this->assertEqual(count($q->getRelationStack()), 1);

        $coll = $q->execute(array('%123%', '%5%'));

        $this->assertEqual($q->getQuery(), 'SELECT entity.id AS entity__id, entity.name AS entity__name, entity.loginname AS entity__loginname, entity.password AS entity__password, entity.type AS entity__type, entity.created AS entity__created, entity.updated AS entity__updated, entity.email_id AS entity__email_id FROM entity LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE entity.id IN (SELECT entity_id FROM phonenumber WHERE phonenumber LIKE ?) AND entity.id IN (SELECT entity_id FROM phonenumber WHERE phonenumber LIKE ?) AND (entity.type = 0)');

        $this->assertEqual($coll->count(), 3);
        $this->assertEqual($coll[0]->name, 'Arnold Schwarzenegger');
        $this->assertEqual($coll[0]->Phonenumber->count(), 3);
        $this->assertEqual($coll[1]->Phonenumber->count(), 3);
        $this->assertEqual($coll[2]->Phonenumber->count(), 3);
    }
    public function testDqlLikeFunction() {
        $q = new Doctrine_Query();
        $this->connection->clear();

        $q->from('User')->where('User.Phonenumber.phonenumber.like(?)');
        $this->assertEqual(count($q->getTableStack()), 2);
        $this->assertEqual(count($q->getRelationStack()), 1);

        $coll = $q->execute(array('123%'));

        $this->assertEqual($q->getQuery(), 'SELECT entity.id AS entity__id, entity.name AS entity__name, entity.loginname AS entity__loginname, entity.password AS entity__password, entity.type AS entity__type, entity.created AS entity__created, entity.updated AS entity__updated, entity.email_id AS entity__email_id FROM entity LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE entity.id IN (SELECT entity_id FROM phonenumber WHERE phonenumber LIKE ?) AND (entity.type = 0)');

        $this->assertEqual($coll->count(), 5);
        $this->assertEqual($coll[0]->name, 'zYne');
        $this->assertEqual($coll[0]->Phonenumber->count(), 1);
        $this->assertEqual($coll[1]->Phonenumber->count(), 3);
        $this->assertEqual($coll[2]->Phonenumber->count(), 1);
        $this->assertEqual($coll[3]->Phonenumber->count(), 3);
        $this->assertEqual($coll[4]->Phonenumber->count(), 3);
    }
    public function testEnumConversion() {
        $e[0] = new EnumTest();
        $e[0]->status = 'open';

        $e[1] = new EnumTest();
        $e[1]->status = 'verified';

        $this->connection->flush();
        $this->assertEqual($e[0]->id, 1);
        $this->assertEqual($e[1]->id, 2);

        $q = new Doctrine_Query;
        
        $coll = $q->from('EnumTest')
                ->where("EnumTest.status = 'open'")
                ->execute();
        
        $this->assertEqual($q->getQuery(), 'SELECT enum_test.id AS enum_test__id, enum_test.status AS enum_test__status FROM enum_test WHERE enum_test.status = 0');
        $this->assertEqual($coll->count(), 1);
        
        $q = new Doctrine_Query;
        
        $coll = $q->from('EnumTest')
                ->where("EnumTest.status = 'verified'")
                ->execute();
        
        $this->assertEqual($q->getQuery(), 'SELECT enum_test.id AS enum_test__id, enum_test.status AS enum_test__status FROM enum_test WHERE enum_test.status = 1');
        $this->assertEqual($coll->count(), 1);
    }

    public function testManyToManyFetchingWithColumnAggregationInheritance() {

        $query = new Doctrine_Query($this->connection);

        $query->from('User-l:Group-l');

        $users = $query->execute();
        $this->assertEqual($users->count(), 1);
        $this->assertEqual($users[0]->Group->count(), 1);

        $query->from('User-l.Group-l');

        $users = $query->execute();
        $this->assertEqual($users->count(), 8);
        $this->assertEqual($users[0]->Group->count(), 0);
        $this->assertEqual($users[1]->Group->count(), 1);
        $this->assertEqual($users[2]->Group->count(), 0);

        $this->assertEqual($users[0]->getState(), Doctrine_Record::STATE_PROXY);
        $this->assertEqual($users[1]->getState(), Doctrine_Record::STATE_PROXY);
        $this->assertEqual($users[2]->getState(), Doctrine_Record::STATE_PROXY);

        $this->assertEqual($users[0]->getModified(), array());
        $this->assertEqual($users[1]->getModified(), array());
        $this->assertEqual($users[2]->getModified(), array());
        $this->assertEqual($users[6]->getModified(), array());

        $this->assertEqual($users[0]->type, 0);
        $this->assertEqual($users[1]->type, 0);
        $this->assertEqual($users[2]->type, 0);

        $this->connection->flush();

        $users = $query->query("FROM User-b WHERE User.Group.name = 'Action Actors'");

        $this->assertEqual(trim($query->getQuery()),
        "SELECT entity.id AS entity__id FROM entity LEFT JOIN groupuser ON entity.id = groupuser.user_id LEFT JOIN entity AS entity2 ON entity2.id = groupuser.group_id WHERE entity2.name = 'Action Actors' AND (entity.type = 0 AND (entity2.type = 1 OR entity2.type IS NULL))");
        $this->assertTrue($users instanceof Doctrine_Collection);
        $this->assertEqual($users->count(),1);

        $this->assertEqual(count($this->dbh->query($query->getQuery())->fetchAll()),1);

        $users = $query->query("FROM User-b WHERE User.Group.Phonenumber.phonenumber LIKE '123 123'");

        $this->assertEqual(trim($query->getQuery()),
        "SELECT entity.id AS entity__id FROM entity LEFT JOIN groupuser ON entity.id = groupuser.user_id LEFT JOIN entity AS entity2 ON entity2.id = groupuser.group_id LEFT JOIN phonenumber ON entity2.id = phonenumber.entity_id WHERE phonenumber.phonenumber LIKE '123 123' AND (entity.type = 0 AND (entity2.type = 1 OR entity2.type IS NULL))");
        $this->assertTrue($users instanceof Doctrine_Collection);
        $this->assertEqual($users->count(),1);

        $query = new Doctrine_Query();
        $users = $query->query("FROM User.Group WHERE User.Group.name = 'Action Actors'");
        $this->assertEqual($users->count(), 1);
        $count = $this->dbh->count();

        $this->assertTrue($users instanceof Doctrine_Collection);
        $this->assertEqual(get_class($users[0]), 'User');
        $this->assertEqual($users[0]->Group->count(), 1);
        $this->assertEqual($count, $this->dbh->count());
        $this->assertEqual($users[0]->Group[0]->name, 'Action Actors');
        $this->assertEqual($count, $this->dbh->count());
    }

    public function testSelectingAggregateValues() {

        $q = new Doctrine_Query();
        $q->from("User(COUNT(1), MAX(name))");
        $array = $q->execute();
        $this->assertTrue(is_array($array));
        $this->assertEqual($array, array(array('COUNT(1)' => '8', 'MAX(entity.name)' => 'zYne')));
        $this->assertEqual($q->getQuery(), "SELECT COUNT(1), MAX(entity.name) FROM entity WHERE (entity.type = 0)");

        $q = new Doctrine_Query();
        $q->from("Phonenumber(COUNT(1))");

        $array = $q->execute();
        $this->assertTrue(is_array($array));
        $this->assertEqual($array, array(array('COUNT(1)' => '15')));
        $this->assertEqual($q->getQuery(), "SELECT COUNT(1) FROM phonenumber");

        $q = new Doctrine_Query();
        $q->from("User.Phonenumber(COUNT(id))");
        $array = $q->execute();
        $this->assertTrue(is_array($array));

        $this->assertEqual($array[0]['COUNT(phonenumber.id)'], 14);
        $this->assertEqual($q->getQuery(), "SELECT entity.id AS entity__id, entity.name AS entity__name, entity.loginname AS entity__loginname, entity.password AS entity__password, entity.type AS entity__type, entity.created AS entity__created, entity.updated AS entity__updated, entity.email_id AS entity__email_id, COUNT(phonenumber.id) FROM entity LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE (entity.type = 0)");

        $q = new Doctrine_Query();
        $q->from("User(MAX(id)).Email(MIN(address))");
        $array = $q->execute();
        $this->assertTrue(is_array($array));
        $this->assertEqual($array[0]['MAX(entity.id)'], 11);
        $this->assertEqual($array[0]['MIN(email.address)'], 'arnold@example.com');

        $q = new Doctrine_Query();
        $q->from("User(MAX(id)).Email(MIN(address)), User.Phonenumber(COUNT(1))");
        $array = $q->execute();
        $this->assertTrue(is_array($array));

        $this->assertEqual($array[0]['MAX(entity.id)'], 11);
        $this->assertEqual($array[0]['MIN(email.address)'], 'arnold@example.com');
        $this->assertEqual($array[0]['COUNT(1)'], 14);

        //$q = new Doctrine_Query();
        //$q->from("User.Phonenumber(COUNT(id))")->groupby("User.id");
        //$coll = $q->execute();
        //print Doctrine_Lib::formatSql($q->getQuery());
        //print_r($coll);
        //$this->assertEqual(count($coll), 8);

    }


    public function testMultipleFetching() {
        $count = $this->dbh->count();
        $this->connection->getTable('User')->clear();
        $this->connection->getTable('Email')->clear();
        $this->connection->getTable('Phonenumber')->clear();

        $users = $this->query->from("User-l.Phonenumber-i, User-l:Email-i")->execute();
        $this->assertEqual(($count + 1),$this->dbh->count());
        $this->assertEqual(count($users), 8);

        $this->assertEqual($users[0]->Phonenumber->count(), 1);
        $this->assertEqual(($count + 1), $this->dbh->count());
        $this->assertEqual($users[0]->Phonenumber[0]->phonenumber, "123 123");
        $this->assertEqual(($count + 1), $this->dbh->count());

        $this->assertEqual($users[1]->Phonenumber->count(), 3);
        $this->assertEqual(($count + 1), $this->dbh->count());
        $this->assertEqual($users[1]->Phonenumber[0]->phonenumber, "123 123");
        $this->assertEqual($users[1]->Phonenumber[1]->phonenumber, "456 456");
        $this->assertEqual($users[1]->Phonenumber[2]->phonenumber, "789 789");
        $this->assertEqual(($count + 1), $this->dbh->count());

        $this->assertEqual($users[7]->Phonenumber->count(), 1);
        $this->assertEqual(($count + 1), $this->dbh->count());
        $this->assertEqual($users[7]->Phonenumber[0]->phonenumber, "111 567 333");
        $this->assertEqual(($count + 1), $this->dbh->count());

        $this->assertTrue($users[0]->Email instanceof Email);
        $this->assertEqual($users[0]->Email->address, "zYne@example.com");
        $this->assertEqual(($count + 1), $this->dbh->count());
        $this->assertEqual($users[0]->email_id, $users[0]->Email->id);
        $this->assertEqual(($count + 1), $this->dbh->count());
        
        $this->assertTrue($users[1]->Email instanceof Email);
        $this->assertEqual($users[1]->Email->address, "arnold@example.com");
        $this->assertEqual(($count + 1), $this->dbh->count());
        $this->assertEqual($users[1]->email_id, $users[1]->Email->id);
        $this->assertEqual(($count + 1), $this->dbh->count());
    }


    public function testGetPath() {
        $this->query->from("User.Group.Email");
        
        $this->assertEqual($this->query->getTableAlias("User"),  "entity");
        $this->assertEqual($this->query->getTableAlias("User.Group"), "entity2");


        $this->query->from("Task.Subtask.Subtask");
        $this->assertEqual($this->query->getTableAlias("Task"), "task");
        $this->assertEqual($this->query->getTableAlias("Task.Subtask"), "task2");
        $this->assertEqual($this->query->getTableAlias("Task.Subtask.Subtask"), "task3");
        

        $this->assertEqual($this->query->getQuery(), 
        "SELECT task.id AS task__id, task.name AS task__name, task.parent_id AS task__parent_id, task2.id AS task2__id, task2.name AS task2__name, task2.parent_id AS task2__parent_id, task3.id AS task3__id, task3.name AS task3__name, task3.parent_id AS task3__parent_id FROM task LEFT JOIN task AS task2 ON task.id = task2.parent_id LEFT JOIN task AS task3 ON task2.id = task3.parent_id");
    }

    public function testMultiComponentFetching2() {
        $this->connection->clear();

        $query = new Doctrine_Query($this->connection);

        $query->from("User.Email, User.Phonenumber");
        

        $users = $query->execute();

        $count = count($this->dbh);

        $this->assertEqual($users->count(), 8);
        $this->assertTrue($users[0]->Email instanceof Email);
        $this->assertEqual($users[0]->Phonenumber->count(), 1);
        $this->assertEqual($count, count($this->dbh));
    }

    public function testHaving() {
        $this->connection->clear();


        $query = new Doctrine_Query($this->connection);
        $query->from('User-l.Phonenumber-l');
        $query->having("COUNT(User.Phonenumber.phonenumber) > 2");
        $query->groupby('User.id');
        
        $users = $query->execute();

        $this->assertEqual($users->count(), 3);
        
        // test that users are in right order
        $this->assertEqual($users[0]->id, 5);
        $this->assertEqual($users[1]->id, 8);
        $this->assertEqual($users[2]->id, 10);
        

        // test expanding
        $count = $this->dbh->count();
        $this->assertEqual($users[0]->Phonenumber->count(), 1);
        $this->assertEqual($users[1]->Phonenumber->count(), 1);
        $this->assertEqual($users[2]->Phonenumber->count(), 1);
        
        $users[0]->Phonenumber[1];
        $this->assertEqual(++$count, $this->dbh->count());
        $this->assertEqual($users[0]->Phonenumber->count(), 3);
        
        $users[1]->Phonenumber[1];
        $this->assertEqual(++$count, $this->dbh->count());
        $this->assertEqual($users[1]->Phonenumber->count(), 3);

        $users[2]->Phonenumber[1];
        $this->assertEqual(++$count, $this->dbh->count());
        $this->assertEqual($users[2]->Phonenumber->count(), 3);

        $this->connection->clear();
        $query->from('User-l.Phonenumber-l');
        $query->having("COUNT(User.Phonenumber.phonenumber) > 2");
        $query->groupby('User.id');

        $users = $this->connection->query("FROM User-l.Phonenumber-l GROUP BY User.id HAVING COUNT(User.Phonenumber.phonenumber) > 2");

        $this->assertEqual($users->count(), 3);
        
        // test that users are in right order
        $this->assertEqual($users[0]->id, 5);
        $this->assertEqual($users[1]->id, 8);
        $this->assertEqual($users[2]->id, 10);
    }



    public function testNestedManyToManyRelations() {
        $task = new Task();
        $task->name = "T1";
        $task->ResourceAlias[0]->name = "R1";
        $task->ResourceAlias[1]->name = "R2";
        $task->ResourceAlias[0]->Type[0]->type = 'TY1';
        //$task->ResourceAlias[1]->Type[0]->type = 'TY2';

        $task = new Task();
        $task->name = "T2";
        $task->ResourceAlias[0]->name = "R3";
        $task->ResourceAlias[0]->Type[0]->type = 'TY2';
        $task->ResourceAlias[0]->Type[1]->type = 'TY3';
        $task->ResourceAlias[1]->name = "R4";
        $task->ResourceAlias[2]->name = "R5";
        $task->ResourceAlias[2]->Type[0]->type = 'TY4';
        $task->ResourceAlias[2]->Type[1]->type = 'TY5';
        $task->ResourceAlias[3]->name = "R6";

        $this->assertEqual($task->ResourceAlias[0]->name, "R3");
        $this->assertEqual($task->ResourceAlias[1]->name, "R4");
        $this->assertEqual($task->ResourceAlias[2]->name, "R5");
        $this->assertEqual($task->ResourceAlias[3]->name, "R6");

        $task = new Task();
        $task->name = "T3";
        $task->ResourceAlias[0]->name = "R7";

        $task = new Task();
        $task->name = "T4";

        $this->connection->flush();

        $this->connection->clear();

        $query = new Doctrine_Query($this->connection);
        $query->from("Task.ResourceAlias.Type");
        $tasks = $query->execute();

        $this->assertEqual($tasks->count(), 4);

        $this->assertEqual($tasks[0]->ResourceAlias->count(), 2);
        $this->assertEqual($tasks[1]->ResourceAlias->count(), 4);
        $this->assertEqual($tasks[2]->ResourceAlias->count(), 1);
        $this->assertEqual($tasks[3]->ResourceAlias->count(), 0);

        $this->assertEqual($tasks[0]->ResourceAlias[0]->Type->count(), 1);
        $this->assertEqual($tasks[0]->ResourceAlias[1]->Type->count(), 0);

        $this->assertEqual($tasks[1]->ResourceAlias->count(), 4);

        $this->connection->clear();
        
        $query->from("Task")->where("Task.ResourceAlias.Type.type = 'TY2' || Task.ResourceAlias.Type.type = 'TY1'");
        $tasks = $query->execute();

        $this->assertEqual($tasks->count(),2);
        $this->assertEqual(count($this->dbh->query($query->getQuery())->fetchAll(PDO::FETCH_ASSOC)),2);
        $this->assertEqual($tasks[0]->name, 'T1');
        $this->assertEqual($tasks[1]->name, 'T2');
    }


    public function testManyToManyFetchingWithColonOperator() {
        $query = new Doctrine_Query($this->connection);

        $task = new Task();

        // clear identity maps
        $this->connection->getTable('Task')->clear();
        $this->connection->getTable('Assignment')->clear();
        $this->connection->getTable('Resource')->clear();

        $tasks[1] = $task->getTable()->find(2);
        $this->assertEqual($tasks[1]->ResourceAlias[0]->name, "R3");
        $this->assertEqual($tasks[1]->ResourceAlias[1]->name, "R4");
        $this->assertEqual($tasks[1]->ResourceAlias[2]->name, "R5");
        $this->assertEqual($tasks[1]->ResourceAlias[3]->name, "R6");

        // clear identity maps
        $task->getTable()->clear();
        $this->connection->getTable('Assignment')->clear();
        $this->connection->getTable('Resource')->clear();

        $query->from("Task-l:ResourceAlias-l");
        $tasks = $query->execute();
        $this->assertEqual($tasks->count(), 3);
        $this->assertTrue($tasks instanceof Doctrine_Collection_Lazy);

        $this->assertEqual($tasks[0]->ResourceAlias->count(), 2);
        $this->assertTrue($tasks[0]->ResourceAlias instanceof Doctrine_Collection_Lazy);


        $this->assertEqual($tasks[1]->ResourceAlias->count(), 4);
        $this->assertTrue($tasks[1]->ResourceAlias instanceof Doctrine_Collection_Lazy);
        // sanity checking
        $this->assertEqual($tasks[1]->ResourceAlias[0]->getState(), Doctrine_Record::STATE_PROXY);
        $this->assertEqual($tasks[1]->ResourceAlias[1]->getState(), Doctrine_Record::STATE_PROXY);
        $this->assertEqual($tasks[1]->ResourceAlias[2]->getState(), Doctrine_Record::STATE_PROXY);
        $this->assertEqual($tasks[1]->ResourceAlias[3]->getState(), Doctrine_Record::STATE_PROXY);

        $count = count($this->dbh);
        
        $this->assertEqual($tasks[1]->ResourceAlias[0]->name, "R3");
        $this->assertEqual($tasks[1]->ResourceAlias[1]->name, "R4");
        $this->assertEqual($tasks[1]->ResourceAlias[2]->name, "R5");
        $this->assertEqual($tasks[1]->ResourceAlias[3]->name, "R6");

        $this->assertEqual(count($this->dbh), ($count + 4));

        $this->assertEqual($tasks[2]->ResourceAlias->count(), 1);
        $this->assertTrue($tasks[2]->ResourceAlias instanceof Doctrine_Collection_Lazy);
    }

    public function testManyToManyFetchingWithDotOperator() {
        $query = new Doctrine_Query($this->connection);

        $this->connection->getTable('Task')->clear();
        $this->connection->getTable('Assignment')->clear();
        $this->connection->getTable('Resource')->clear();

        $tasks = $query->query("FROM Task-l.ResourceAlias-l");
        $this->assertEqual($tasks->count(), 4);
        $this->assertTrue($tasks instanceof Doctrine_Collection_Lazy);

        $this->assertEqual($tasks[0]->ResourceAlias->count(), 2);
        $this->assertTrue($tasks[0]->ResourceAlias instanceof Doctrine_Collection_Lazy);

        $this->assertEqual($tasks[1]->ResourceAlias->count(), 4);
        $this->assertTrue($tasks[1]->ResourceAlias instanceof Doctrine_Collection_Lazy);
        
        $this->assertEqual($tasks[1]->ResourceAlias->count(), 4);
        $this->assertTrue($tasks[1]->ResourceAlias instanceof Doctrine_Collection_Lazy);
        // sanity checking
        $this->assertEqual($tasks[1]->ResourceAlias[0]->getState(), Doctrine_Record::STATE_PROXY);
        $this->assertEqual($tasks[1]->ResourceAlias[1]->getState(), Doctrine_Record::STATE_PROXY);
        $this->assertEqual($tasks[1]->ResourceAlias[2]->getState(), Doctrine_Record::STATE_PROXY);
        $this->assertEqual($tasks[1]->ResourceAlias[3]->getState(), Doctrine_Record::STATE_PROXY);
        
        $count = count($this->dbh);
        
        $this->assertEqual($tasks[1]->ResourceAlias[0]->name, "R3");
        $this->assertEqual($tasks[1]->ResourceAlias[1]->name, "R4");
        $this->assertEqual($tasks[1]->ResourceAlias[2]->name, "R5");
        $this->assertEqual($tasks[1]->ResourceAlias[3]->name, "R6");

        $this->assertEqual(count($this->dbh), ($count + 4));

        $this->assertEqual($tasks[2]->ResourceAlias->count(), 1);
        $this->assertTrue($tasks[2]->ResourceAlias instanceof Doctrine_Collection_Lazy);

        $this->assertEqual($tasks[3]->ResourceAlias->count(), 0);
        $this->assertTrue($tasks[3]->ResourceAlias instanceof Doctrine_Collection);
    }

    public function testManyToManyFetchingWithDotOperatorAndLoadedIdentityMaps() {
        $query = new Doctrine_Query($this->connection);

        $tasks = $query->query("FROM Task-l.ResourceAlias-l");
        $this->assertEqual($tasks->count(), 4);
        $this->assertTrue($tasks instanceof Doctrine_Collection_Lazy);

        $this->assertEqual($tasks[0]->ResourceAlias->count(), 2);
        $this->assertTrue($tasks[0]->ResourceAlias instanceof Doctrine_Collection_Lazy);

        $this->assertEqual($tasks[1]->ResourceAlias->count(), 4);
        $this->assertTrue($tasks[1]->ResourceAlias instanceof Doctrine_Collection_Lazy);
        
        $this->assertEqual($tasks[1]->ResourceAlias->count(), 4);
        $this->assertTrue($tasks[1]->ResourceAlias instanceof Doctrine_Collection_Lazy);
        // sanity checking
        $this->assertEqual($tasks[1]->ResourceAlias[0]->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($tasks[1]->ResourceAlias[1]->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($tasks[1]->ResourceAlias[2]->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($tasks[1]->ResourceAlias[3]->getState(), Doctrine_Record::STATE_CLEAN);
        
        $count = count($this->dbh);
        
        $this->assertEqual($tasks[1]->ResourceAlias[0]->name, "R3");
        $this->assertEqual($tasks[1]->ResourceAlias[1]->name, "R4");
        $this->assertEqual($tasks[1]->ResourceAlias[2]->name, "R5");
        $this->assertEqual($tasks[1]->ResourceAlias[3]->name, "R6");

        $this->assertEqual(count($this->dbh), $count);

        $this->assertEqual($tasks[2]->ResourceAlias->count(), 1);
        $this->assertTrue($tasks[2]->ResourceAlias instanceof Doctrine_Collection_Lazy);

        $this->assertEqual($tasks[3]->ResourceAlias->count(), 0);
        $this->assertTrue($tasks[3]->ResourceAlias instanceof Doctrine_Collection);
    }

    public function testOneToOneSharedRelations() {
        $status = new Log_Status();
        $status->name = 'success';


        $entries[0] = new Log_Entry();
        $entries[0]->stamp = '2006-06-06';
        $entries[0]->Log_Status = $status;
        $this->assertTrue($entries[0]->Log_Status instanceof Log_Status);

        $entries[1] = new Log_Entry();
        $entries[1]->stamp = '2006-06-06';
        $entries[1]->Log_Status = $status;



        $this->connection->flush();
        
        // clear the identity maps

        $entries[0]->Log_Status->getTable()->clear();
        $entries[0]->getTable()->clear();

        $entries = $this->connection->query("FROM Log_Entry-I.Log_Status-i");

        $this->assertEqual($entries->count(), 2);

        $this->assertTrue($entries[0]->Log_Status instanceof Log_Status);
        $this->assertEqual($entries[0]->Log_Status->name, 'success');

        // the second Log_Status is fetched from identityMap

        $this->assertTrue($entries[1]->Log_Status instanceof Log_Status);
        $this->assertEqual($entries[1]->Log_Status->name, 'success');

        // the following line is possible since doctrine uses identityMap

        $this->assertEqual($entries[0]->Log_Status, $entries[1]->Log_Status);

        $entries[0]->Log_Status->delete();
        $this->assertEqual($entries[0]->Log_Status, $entries[1]->Log_Status);
        $this->assertEqual($entries[0]->Log_Status->getState(), Doctrine_Record::STATE_TCLEAN);

        // clear the identity maps

        $entries[0]->Log_Status->getTable()->clear();
        $entries[0]->getTable()->clear();

        $entries = $this->connection->query("FROM Log_Entry-I.Log_Status-i");
        $this->assertEqual($entries->count(), 2);

        $this->assertTrue($entries[0]->Log_Status instanceof Log_Status);

        $this->assertEqual($entries[0]->Log_Status->name, null);
        $this->assertEqual($entries[1]->Log_Status->name, null);

    }

    public function testOneToOneRelationFetchingWithCustomTableNames() {
        $entry = new ORM_TestEntry();
        $entry->name = 'entry 1';
        $entry->amount = '123.123';
        $entry->ORM_TestItem->name = 'item 1';

        $entry = new ORM_TestEntry();
        $entry->name = 'entry 2';
        $entry->amount = '123.123';
        $entry->ORM_TestItem->name = 'item 2';
        
        $this->connection->flush();
        
        $count = $this->dbh->count();

        $entries = $this->connection->query("FROM ORM_TestEntry-i.ORM_TestItem-i");

        $this->assertEqual($entries->count(), 2);

        $this->assertTrue($entries[0] instanceof ORM_TestEntry);
        $this->assertTrue($entries[0]->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($entries[0]->name, 'entry 1');
        $this->assertTrue($entries[1] instanceof ORM_TestEntry);
        $this->assertTrue($entries[1]->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($entries[1]->name, 'entry 2');


        $this->assertEqual(($count + 1), $this->dbh->count());

        $this->assertTrue($entries[0]->ORM_TestItem instanceof ORM_TestItem);
        $this->assertEqual($entries[0]->ORM_TestItem->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($entries[0]->ORM_TestItem->name, 'item 1');
        $this->assertTrue($entries[1]->ORM_TestItem instanceof ORM_TestItem);
        $this->assertEqual($entries[1]->ORM_TestItem->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($entries[1]->ORM_TestItem->name, 'item 2');

        $this->assertEqual(($count + 1), $this->dbh->count());
    }

    public function testImmediateFetching() {
        $count = $this->dbh->count();
        $this->connection->getTable('User')->clear();
        $this->connection->getTable('Email')->clear();
        $this->connection->getTable('Phonenumber')->clear();

        $users = $this->query->from("User-i.Email-i")->execute();
        $this->assertEqual(($count + 1),$this->dbh->count());
        $this->assertEqual(count($users), 8);
        
        $this->assertEqual(get_class($users[0]->Email), 'Email');
        $this->assertEqual(($count + 1),$this->dbh->count());

        $this->assertEqual($users[0]->Email->address, 'zYne@example.com');
        $this->assertEqual(($count + 1),$this->dbh->count());
        
        $this->assertEqual(get_class($users[1]->Email), 'Email');
        $this->assertEqual(($count + 1),$this->dbh->count());

        $this->assertEqual($users[1]->Email->address, 'arnold@example.com');
        $this->assertEqual(($count + 1),$this->dbh->count());
    }

    public function testLazyPropertyFetchingWithMultipleColumns() {

        $q = new Doctrine_Query($this->connection);
        $q->from("User-l(name, email_id)");
        $users = $q->execute();
        $this->assertEqual($users->count(), 8);
        $this->assertTrue($users instanceof Doctrine_Collection_Lazy);
        $count = count($this->dbh);
        $this->assertTrue(is_string($users[0]->name));
        $this->assertEqual($count, count($this->dbh));
        $count = count($this->dbh);
        $this->assertTrue(is_numeric($users[0]->email_id));
        $this->assertEqual($count, count($this->dbh));
        
        $users[0]->getTable()->clear();

        $q->from("User-b(name, email_id)");
        $users = $q->execute();
        $this->assertEqual($users->count(), 8);
        $this->assertTrue($users instanceof Doctrine_Collection_Batch);
        $count = count($this->dbh);
        $this->assertTrue(is_string($users[0]->name));
        $this->assertEqual($count, count($this->dbh));
        $count = count($this->dbh);
        $this->assertTrue(is_numeric($users[0]->email_id));
        $this->assertEqual($count, count($this->dbh));
        $this->assertTrue(is_numeric($users[1]->email_id));
        $this->assertEqual($count, count($this->dbh));
        $this->assertTrue(is_numeric($users[2]->email_id));
        $this->assertEqual($count, count($this->dbh));
        
        $q->from("User-b(name, email_id):Email, User-b(name, email_id).Phonenumber");
        $users = $q->execute();
        $this->assertEqual($users->count(), 8);
        $this->assertTrue($users instanceof Doctrine_Collection_Batch);
        $count = count($this->dbh);
        $this->assertTrue(is_string($users[0]->name));
        $this->assertEqual($count, count($this->dbh));
        $count = count($this->dbh);
        $this->assertTrue(is_numeric($users[0]->email_id));
        $this->assertEqual($count, count($this->dbh));
        $this->assertTrue(is_numeric($users[1]->email_id));
        $this->assertEqual($count, count($this->dbh));
        $this->assertTrue(is_numeric($users[2]->email_id));
        $this->assertEqual($count, count($this->dbh));
    }



    public function testForeignKeyRelationFetching() {
        $count = $this->dbh->count();
        $users = $this->query->from("User-l.Phonenumber-i")->execute();
        $this->assertEqual(($count + 1), $this->dbh->count());
        $this->assertEqual(count($users), 8);

        $this->assertEqual($users[0]->Phonenumber->count(), 1);
        $this->assertEqual(($count + 1), $this->dbh->count());
        $this->assertEqual($users[0]->Phonenumber[0]->phonenumber, "123 123");
        $this->assertEqual(($count + 1), $this->dbh->count());

        $this->assertEqual($users[1]->Phonenumber->count(), 3);
        $this->assertEqual(($count + 1), $this->dbh->count());
        $this->assertEqual($users[1]->Phonenumber[0]->phonenumber, "123 123");
        $this->assertEqual($users[1]->Phonenumber[1]->phonenumber, "456 456");
        $this->assertEqual($users[1]->Phonenumber[2]->phonenumber, "789 789");
        $this->assertEqual(($count + 1), $this->dbh->count());

        $this->assertEqual($users[7]->Phonenumber->count(), 1);
        $this->assertEqual(($count + 1), $this->dbh->count());
        $this->assertEqual($users[7]->Phonenumber[0]->phonenumber, "111 567 333");
        $this->assertEqual(($count + 1), $this->dbh->count());

        $this->assertEqual($users[0]->name, "zYne");
        $this->assertEqual(($count + 2), $this->dbh->count());
    }

    public function testOneToOneRelationFetching() {

        $count = $this->dbh->count();
        $users = $this->query->from("User-l:Email-i")->execute();
        $this->assertEqual(($count + 1), $this->dbh->count());
        $this->assertTrue($users instanceof Doctrine_Collection_Lazy);
        $count = $this->dbh->count();

        foreach($users as $user) {
            // iterate through users and test that no additional queries are needed
            $user->Email->address;
            $this->assertEqual($count, $this->dbh->count());
        }
        $users[0]->Account->amount = 3000;
        $this->assertEqual($users[0]->Account->amount, 3000);
        $this->assertTrue($users[0]->Account instanceof Account);
        $this->assertEqual($users[0]->id, $users[0]->Account->entity_id);

        $users[0]->Account->save();

        $users[0]->refresh();
        $this->assertEqual($users[0]->Account->amount, 3000);
        $this->assertTrue($users[0]->Account instanceof Account);

        $this->assertEqual($users[0]->id, $users[0]->Account->entity_id);

        $this->assertEqual($users[0]->Account->getState(), Doctrine_Record::STATE_CLEAN);
        $users[0]->getTable()->clear();
        $users[0]->Account->getTable()->clear();

        $count = $this->dbh->count();
        $users = $this->query->from("User-l:Account-i")->execute();
        $this->assertEqual(($count + 1), $this->dbh->count());
        $this->assertTrue($users instanceof Doctrine_Collection_Lazy);
        $this->assertEqual($users->count(), 1);

        $this->assertEqual($users[0]->Account->amount,3000);
    }


    public function testQueryWithComplexAliases() {
        $q = new Doctrine_Query($this->connection);

        $board = new Forum_Board();
        $table = $board->getTable();
        $fk    = $table->getRelation("Threads");

        $this->assertEqual($table->getComponentName(), "Forum_Board");
        $this->assertTrue($fk instanceof Doctrine_Relation_ForeignKey);
        $this->assertEqual($fk->getTable()->getComponentName(), "Forum_Thread");

        $entry = new Forum_Entry();
        $this->assertTrue($entry->getTable()->getRelation("Thread") instanceof Doctrine_Relation_LocalKey);

        $board->name = "Doctrine Forum";

        $board->Threads[0];
        $board->Category->name = "General discussion";
        $this->assertEqual($board->name, "Doctrine Forum");
        $this->assertEqual($board->Category->name, "General discussion");
        $this->assertEqual($board->Category->getState(), Doctrine_Record::STATE_TDIRTY);
        $this->assertEqual($board->Threads[0]->getState(), Doctrine_Record::STATE_TDIRTY);
        $this->assertTrue($board->Threads[0] instanceof Forum_Thread);

        $thread = $board->Threads[0];
        $thread->Entries[0]->topic = "My first topic";
        $thread->Entries[1]->topic = "My second topic";
        $this->assertEqual($thread->Entries[0]->topic, "My first topic");
        $this->assertEqual($thread->Entries[0]->getState(), Doctrine_Record::STATE_TDIRTY);
        $this->assertTrue($thread->Entries[0] instanceof Forum_Entry);

        $this->connection->flush();

        $board->getTable()->clear();

        $board = $board->getTable()->find($board->id);
        $this->assertEqual($board->Threads->count(), 1);
        $this->assertEqual($board->name, "Doctrine Forum");
        $this->assertEqual($board->Category->name, "General discussion");
        $this->assertEqual($board->Category->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($board->Threads[0]->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertTrue($board->Threads[0] instanceof Forum_Thread);
        $this->assertEqual($board->Threads[0]->Entries->count(), 2);


        $q->from("Forum_Board");
        $coll = $q->execute();
        $this->assertEqual($coll->count(), 1);

        $table = $this->connection->getTable("Forum_Board")->setAttribute(Doctrine::ATTR_FETCHMODE, Doctrine::FETCH_LAZY);
        $table = $this->connection->getTable("Forum_Thread")->setAttribute(Doctrine::ATTR_FETCHMODE, Doctrine::FETCH_LAZY);
        $q->from("Forum_Board.Threads");

        $this->assertEqual($q->getQuery(), "SELECT forum__board.id AS forum__board__id, forum__thread.id AS forum__thread__id FROM forum__board LEFT JOIN forum__thread ON forum__board.id = forum__thread.board_id");
        $coll = $q->execute();
        $this->assertEqual($coll->count(), 1);



        $q->from("Forum_Board-l.Threads-l");
        $this->assertEqual($q->getQuery(), "SELECT forum__board.id AS forum__board__id, forum__thread.id AS forum__thread__id FROM forum__board LEFT JOIN forum__thread ON forum__board.id = forum__thread.board_id");

        //$this->connection->clear();

        $q->from("Forum_Board-l.Threads-l.Entries-l");
        $this->assertEqual($q->getQuery(), "SELECT forum__board.id AS forum__board__id, forum__thread.id AS forum__thread__id, forum__entry.id AS forum__entry__id FROM forum__board LEFT JOIN forum__thread ON forum__board.id = forum__thread.board_id LEFT JOIN forum__entry ON forum__thread.id = forum__entry.thread_id");
        $boards = $q->execute();
        $this->assertEqual($boards->count(), 1);
        $count = count($this->dbh);
        $this->assertEqual($boards[0]->Threads->count(), 1);
        $this->assertEqual(count($this->dbh), $count);
        $this->assertEqual($boards[0]->Threads[0]->Entries->count(), 2);


        $q->from("Forum_Board-l.Threads-l.Entries-i");
        $this->assertEqual($boards->count(), 1);
        $count = count($this->dbh);
        $this->assertEqual($boards[0]->Threads->count(), 1);
        $this->assertEqual(count($this->dbh), $count);
        $this->assertEqual($boards[0]->Threads[0]->Entries->count(), 2);

    }

    public function testQueryWithAliases() {
        $query = new Doctrine_Query($this->connection);

        $task = new Task();
        $task->name = "Task 1";
        $task->ResourceAlias[0]->name = "Resource 1";
        $task->ResourceAlias[1]->name = "Resource 2";

        $task->save();


        $coll = $query->query("FROM Task WHERE Task.ResourceAlias.name = 'Resource 1'");

        $this->assertEqual($coll->count(), 1);
        $this->assertTrue($coll[0] instanceof Task);
    }

    public function testQueryArgs() {
        $query = new Doctrine_Query($this->connection);
        $query = $query->from("User-l");

        $this->assertTrue($query instanceof Doctrine_Query);
        $this->assertEqual($query->get("from"), array("entity" => true));

        $query = $query->orderby("User.name");
        $this->assertTrue($query instanceof Doctrine_Query);
        $this->assertEqual($query->get("orderby"), array("entity.name"));

        $query = $query->orderby("User.created");
        $this->assertTrue($query instanceof Doctrine_Query);
        $this->assertEqual($query->get("orderby"), array("entity.created"));

        $query = $query->where("User.name LIKE 'zYne%'");
        $this->assertTrue($query instanceof Doctrine_Query);
        $this->assertEqual($query->get("where"), array("entity.name LIKE 'zYne%'"));

        $query = $query->where("User.name LIKE 'Arnold%'");
        $this->assertTrue($query instanceof Doctrine_Query);
        $this->assertEqual($query->get("where"), array("entity.name LIKE 'Arnold%'"));

        $query = $query->limit(5);
        $this->assertTrue($query instanceof Doctrine_Query);
        $this->assertEqual($query->get("limit"), 5);

        $query = $query->offset(5);
        $this->assertTrue($query instanceof Doctrine_Query);
        $this->assertEqual($query->get("offset"), 5);

        $query->remove('limit')->remove('offset');

        $this->assertFalse(strpos($query->getQuery(),"OFFSET"));

        $this->assertFalse(strpos($query->getQuery(),"LIMIT"));

        $coll = $query->execute();
        $this->assertTrue($coll instanceof Doctrine_Collection_Lazy);
        $this->assertEqual($coll->count(), 1);

        
        $query->where("User.name LIKE ?")->limit(3);
        $this->assertEqual($query->limit, 3);
        $this->assertTrue(strpos($query->getQuery(),"LIMIT"));
    }

    public function testLimit() {
        $query = new Doctrine_Query($this->connection);
        $coll  = $query->query("FROM User(id) LIMIT 3");
        $this->assertEqual($query->limit, 3);
        $this->assertEqual($coll->count(), 3);
    }
    public function testOffset() {
        $query = new Doctrine_Query($this->connection);
        $coll  = $query->query("FROM User LIMIT 3 OFFSET 3");
        $this->assertEqual($query->offset, 3);
        $this->assertEqual($coll->count(), 3);
    }
    public function testPreparedQuery() {
        $coll = $this->connection->query("FROM User WHERE User.name = :name", array(":name" => "zYne"));
        $this->assertEqual($coll->count(), 1);
    }
    public function testOrderBy() {
        $query = new Doctrine_Query($this->connection);
        $query->from("User-b")->orderby("User.name ASC, User.Email.address");
        $users = $query->execute();

        $this->assertEqual(trim($query->getQuery()),
        "SELECT entity.id AS entity__id FROM entity LEFT JOIN email ON entity.email_id = email.id WHERE (entity.type = 0) ORDER BY entity.name ASC, email.address");
        $this->assertEqual($users->count(),8);
        $this->assertTrue($users[0]->name == "Arnold Schwarzenegger");
    }
    public function testBatchFetching() {
        $query = new Doctrine_Query($this->connection);
        $users = $query->query("FROM User-b");
        $this->assertEqual(trim($query->getQuery()),
        "SELECT entity.id AS entity__id FROM entity WHERE (entity.type = 0)");

        $this->assertEqual($users[0]->name, "zYne");
        $this->assertTrue($users instanceof Doctrine_Collection_Batch);
    }
    public function testLazyFetching() {
        $query = new Doctrine_Query($this->connection);
        $users = $query->query("FROM User-l");
        $this->assertEqual(trim($query->getQuery()),
        "SELECT entity.id AS entity__id FROM entity WHERE (entity.type = 0)");

        $this->assertEqual($users[0]->name, "zYne");
        $this->assertTrue($users instanceof Doctrine_Collection_Lazy);

    }


    function testFetchingWithCollectionExpanding() {
        // DYNAMIC COLLECTION EXPANDING
        $query = new Doctrine_Query($this->connection);

        $user = $this->objTable->find(5);
        $user->Group[1]->name = "Tough guys inc.";
        $user->Group[2]->name = "Terminators";
        $this->assertEqual($user->Group[0]->name, "Action Actors");
        $user->save();
        $this->assertEqual($user->Group[0]->name, "Action Actors");
        $this->assertEqual(count($user->Group), 3);

        $user = $this->objTable->find(5);
        $this->assertEqual(count($user->Group), 3);

        //$users = $query->query("FROM User, User.Group WHERE User.Group.name LIKE 'Action Actors'");
        //$this->assertEqual(count($users),1);

        //$this->assertEqual($users[0]->Group[0]->name, "Action Actors");
        //$this->assertEqual(count($users[0]->Group), 1);

        //$this->assertEqual($users[0]->Group[1]->name, "Tough guys inc.");
        //$this->assertEqual($users[0]->Group[2]->name, "Terminators");
        //$this->assertEqual(count($users[0]->Group), 3);

        $this->connection->getTable("User")->clear();
        $this->connection->getTable("Phonenumber")->clear();

        $users = $query->query("FROM User-b.Phonenumber-l WHERE User.Phonenumber.phonenumber LIKE '%123%'");
        $this->assertEqual(trim($query->getQuery()),
        "SELECT entity.id AS entity__id, phonenumber.id AS phonenumber__id FROM entity LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE phonenumber.phonenumber LIKE '%123%' AND (entity.type = 0)");

        $count = $this->connection->getDBH()->count();

        $users[1]->Phonenumber[0]->phonenumber;
        $users[1]->Phonenumber[1]->phonenumber;
        $this->assertEqual($users[1]->Phonenumber[1]->getState(),Doctrine_Record::STATE_CLEAN);

        $users[1]->Phonenumber[2]->phonenumber;
        $this->assertEqual($users[1]->Phonenumber[1]->getState(),Doctrine_Record::STATE_CLEAN);
        $count2 = $this->connection->getDBH()->count();
        $this->assertEqual($count + 4,$count2);



        // DYNAMIC FETCHMODES
        $e = false;
        try {
            $users = $query->query("FROM User-unknown");
        } catch(Exception $e) {
        }
        $this->assertTrue($e instanceof Doctrine_Query_Exception);


        $users = $query->query("FROM User-i");
        $this->assertEqual(trim($query->getQuery()),
        "SELECT entity.id AS entity__id, entity.name AS entity__name, entity.loginname AS entity__loginname, entity.password AS entity__password, entity.type AS entity__type, entity.created AS entity__created, entity.updated AS entity__updated, entity.email_id AS entity__email_id FROM entity WHERE (entity.type = 0)");

        $count = $this->connection->getDBH()->count();
        $this->assertEqual($users[0]->name, "zYne");

        $this->assertTrue($users instanceof Doctrine_Collection_Immediate);
        $count2 = $this->connection->getDBH()->count();






        //$this->clearCache();

        $users = $query->query("FROM User-b.Phonenumber-b");
        $this->assertEqual(trim($query->getQuery()),
        "SELECT entity.id AS entity__id, phonenumber.id AS phonenumber__id FROM entity LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE (entity.type = 0)");

        $this->assertEqual($users->count(),8);

        // EXPECTED THAT ONE NEW QUERY IS NEEDED TO GET THE FIRST USER's PHONENUMBER

        $count = $this->connection->getDBH()->count();
        $users[0]->Phonenumber[0]->phonenumber;
        $count2 = $this->connection->getDBH()->count();
        $this->assertEqual($count + 1,$count2);




        $users = $query->query("FROM User-l:Email-b");

        $this->assertEqual(trim($query->getQuery()),
        "SELECT entity.id AS entity__id, email.id AS email__id FROM entity INNER JOIN email ON entity.email_id = email.id WHERE (entity.type = 0)");

        $this->assertEqual($users->count(),8);

        $users = $query->query("FROM Email-b WHERE Email.address LIKE '%@example%'");

        $this->assertEqual($query->getQuery(),
        "SELECT email.id AS email__id FROM email WHERE email.address LIKE '%@example%'");
        $this->assertEqual($users->count(),8);

        $users = $query->query("FROM User-b WHERE User.name LIKE '%Jack%'");
        $this->assertEqual($query->getQuery(), "SELECT entity.id AS entity__id FROM entity WHERE entity.name LIKE '%Jack%' AND (entity.type = 0)");
        $this->assertEqual($users->count(),0);


        $users = $query->query("FROM User-b WHERE User.Phonenumber.phonenumber LIKE '%123%'");
        $this->assertEqual(trim($query->getQuery()),
        "SELECT entity.id AS entity__id FROM entity LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE phonenumber.phonenumber LIKE '%123%' AND (entity.type = 0)");
        $this->assertEqual($users->count(),5);


        //$values = $query->query("SELECT COUNT(User.name) AS users, MAX(User.name) AS max FROM User");
        //$this->assertEqual(trim($query->getQuery()),"SELECT COUNT(entity.name) AS users, MAX(entity.name) AS max FROM entity WHERE (entity.type = 0)");
        //$this->assertTrue(is_array($values));
        //$this->assertTrue(isset($values['users']));
        //$this->assertTrue(isset($values['max']));

    }

}
?>
