<?php
class Doctrine_QueryTestCase extends Doctrine_UnitTestCase {
    public function prepareTables() {
        $this->tables[] = "Forum_Category";
        $this->tables[] = "Forum_Entry";
        $this->tables[] = "Forum_Board";
        $this->tables[] = "Forum_Thread";

        $this->tables[] = "ORM_TestEntry";
        $this->tables[] = "ORM_TestItem";
        $this->tables[] = "Log_Status";
        $this->tables[] = "Log_Entry";

        try {
            $this->dbh->query("DROP TABLE test_items");
        } catch(PDOException $e) {

        }
        try {
            $this->dbh->query("DROP TABLE test_entries");
        } catch(PDOException $e) {

        }
        parent::prepareTables();

    }
    /**
    public function testQueryPart() {
        $this->query->from[] = "User.Phonenumber";
        $this->query->from[] = "User.Email";

        $users = $this->query->execute();

        $this->assertEqual($users->count(), 8);
    }
    */
    public function testMultipleFetching() {
        $count = $this->dbh->count();
        $this->session->getTable('User')->clear();
        $this->session->getTable('Email')->clear();
        $this->session->getTable('Phonenumber')->clear();

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

    public function testConditionParser() {
        $query = new Doctrine_Query($this->session);

        $query->from("User(id)")->where("User.name LIKE 'z%' || User.name LIKE 's%'");

        $sql = "SELECT entity.id AS entity__id FROM entity WHERE (entity.name LIKE 'z%' OR entity.name LIKE 's%') AND (entity.type = 0)";
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(User.name LIKE 'z%') || (User.name LIKE 's%')");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("((User.name LIKE 'z%') || (User.name LIKE 's%'))");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(((User.name LIKE 'z%') || (User.name LIKE 's%')))");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(((User.name LIKE 'z%') || User.name LIKE 's%'))");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(User.name LIKE 'z%') || User.name LIKE 's%' && User.name LIKE 'a%'");

        $sql = "SELECT entity.id AS entity__id FROM entity WHERE ((entity.name LIKE 'z%' OR entity.name LIKE 's%') AND entity.name LIKE 'a%') AND (entity.type = 0)";

        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(((User.name LIKE 'z%') || User.name LIKE 's%')) && User.name LIKE 'a%'");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("((((User.name LIKE 'z%') || User.name LIKE 's%')) && User.name LIKE 'a%')");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(((((User.name LIKE 'z%') || User.name LIKE 's%')) && User.name LIKE 'a%'))");
        $this->assertEqual($query->getQuery(), $sql);
    }

    public function testSelfReferencing() {
        $query = new Doctrine_Query($this->session);

        $category = new Forum_Category();

        $category->name = "Root";
        $category->Subcategory[0]->name = "Sub 1";
        $category->Subcategory[1]->name = "Sub 2";
        $category->Subcategory[0]->Subcategory[0]->name = "Sub 1 Sub 1";
        $category->Subcategory[0]->Subcategory[1]->name = "Sub 1 Sub 2";
        $category->Subcategory[1]->Subcategory[0]->name = "Sub 2 Sub 1";
        $category->Subcategory[1]->Subcategory[1]->name = "Sub 2 Sub 2";

        $this->session->flush();
        $this->session->clear();

        $category = $category->getTable()->find($category->id);

        $this->assertEqual($category->name, "Root");
        $this->assertEqual($category->Subcategory[0]->name, "Sub 1");
        $this->assertEqual($category->Subcategory[1]->name, "Sub 2");
        $this->assertEqual($category->Subcategory[0]->Subcategory[0]->name, "Sub 1 Sub 1");
        $this->assertEqual($category->Subcategory[0]->Subcategory[1]->name, "Sub 1 Sub 2");
        $this->assertEqual($category->Subcategory[1]->Subcategory[0]->name, "Sub 2 Sub 1");
        $this->assertEqual($category->Subcategory[1]->Subcategory[1]->name, "Sub 2 Sub 2");

        $this->session->clear();



        $query->from("Forum_Category.Subcategory.Subcategory");
        $coll = $query->execute();
        $category = $coll[0];

        $count = count($this->dbh);

        $this->assertEqual($category->name, "Root");
        
        $this->assertEqual($count, count($this->dbh));
        $this->assertEqual($category->Subcategory[0]->name, "Sub 1");
        $this->assertEqual($category->Subcategory[1]->name, "Sub 2");

        $this->assertEqual($count, count($this->dbh));
        $this->assertEqual($category->Subcategory[0]->Subcategory[0]->name, "Sub 1 Sub 1");
        $this->assertEqual($category->Subcategory[0]->Subcategory[1]->name, "Sub 1 Sub 2");
        $this->assertEqual($category->Subcategory[1]->Subcategory[0]->name, "Sub 2 Sub 1");
        $this->assertEqual($category->Subcategory[1]->Subcategory[1]->name, "Sub 2 Sub 2");
        $this->assertEqual($count, count($this->dbh));
        
        $this->session->clear();
        $query->from("Forum_Category.Parent.Parent")->where("Forum_Category.name LIKE 'Sub%Sub%'");
        $coll = $query->execute();

        $count = count($this->dbh);
        $this->assertEqual($coll->count(), 4);
        $this->assertEqual($coll[0]->name, "Sub 1 Sub 1");
        $this->assertEqual($coll[1]->name, "Sub 1 Sub 2");
        $this->assertEqual($coll[2]->name, "Sub 2 Sub 1");
        $this->assertEqual($coll[3]->name, "Sub 2 Sub 2");

        $this->assertEqual($count, count($this->dbh));

        $this->assertEqual($coll[0]->Parent->name, "Sub 1");
        $this->assertEqual($coll[1]->Parent->name, "Sub 1");
        $this->assertEqual($coll[2]->Parent->name, "Sub 2");
        $this->assertEqual($coll[3]->Parent->name, "Sub 2");
        
        $this->assertEqual($count, count($this->dbh));

        $this->assertEqual($coll[0]->Parent->Parent->name, "Root");
        $this->assertEqual($coll[1]->Parent->Parent->name, "Root");
        $this->assertEqual($coll[2]->Parent->Parent->name, "Root");
        $this->assertEqual($coll[3]->Parent->Parent->name, "Root");
        
        $this->assertEqual($count, count($this->dbh));

        $query->from("Forum_Category.Parent, Forum_Category.Subcategory")->where("Forum_Category.name = 'Sub 1' OR Forum_Category.name = 'Sub 2'");
        
        $coll = $query->execute();
        
        $count = count($this->dbh);
        
        $this->assertEqual($coll->count(), 2);
        $this->assertEqual($coll[0]->name, "Sub 1");
        $this->assertEqual($coll[1]->name, "Sub 2");
        
        $this->assertEqual($count, count($this->dbh));

        $this->assertEqual($coll[0]->Subcategory[0]->name, "Sub 1 Sub 1");
        $this->assertEqual($coll[0]->Subcategory[1]->name, "Sub 1 Sub 2");
        $this->assertEqual($coll[1]->Subcategory[0]->name, "Sub 2 Sub 1");
        $this->assertEqual($coll[1]->Subcategory[1]->name, "Sub 2 Sub 2");

        $this->assertEqual($count, count($this->dbh));

        $this->assertEqual($coll[0]->Parent->name, "Root");
        $this->assertEqual($coll[1]->Parent->name, "Root");

        $this->assertEqual($count, count($this->dbh));
        
        $this->session->clear();

        $query->from("Forum_Category.Subcategory.Subcategory")->where("Forum_Category.parent_category_id IS NULL");
        $coll = $query->execute();
        $this->assertEqual($coll->count(), 1);
        
        $count = count($this->dbh);

        $this->assertEqual($category->name, "Root");
        
        $this->assertEqual($count, count($this->dbh));
        $this->assertEqual($category->Subcategory[0]->name, "Sub 1");
        $this->assertEqual($category->Subcategory[1]->name, "Sub 2");

        $this->assertEqual($count, count($this->dbh));
        $this->assertEqual($category->Subcategory[0]->Subcategory[0]->name, "Sub 1 Sub 1");
        $this->assertEqual($category->Subcategory[0]->Subcategory[1]->name, "Sub 1 Sub 2");
        $this->assertEqual($category->Subcategory[1]->Subcategory[0]->name, "Sub 2 Sub 1");
        $this->assertEqual($category->Subcategory[1]->Subcategory[1]->name, "Sub 2 Sub 2");
        $this->assertEqual($count, count($this->dbh));
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
        $this->session->clear();

        $query = new Doctrine_Query($this->session);

        $query->from("User.Email, User.Phonenumber");
        

        $users = $query->execute();

        $count = count($this->dbh);

        $this->assertEqual($users->count(), 8);
        $this->assertTrue($users[0]->Email instanceof Email);
        $this->assertEqual($users[0]->Phonenumber->count(), 1);
        $this->assertEqual($count, count($this->dbh));
    }

    public function testHaving() {
        $this->session->clear();


        $query = new Doctrine_Query($this->session);
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

        $this->session->clear();
        $query->from('User-l.Phonenumber-l');
        $query->having("COUNT(User.Phonenumber.phonenumber) > 2");
        $query->groupby('User.id');

        $users = $this->session->query("FROM User-l.Phonenumber-l GROUP BY User.id HAVING COUNT(User.Phonenumber.phonenumber) > 2");

        $this->assertEqual($users->count(), 3);
        
        // test that users are in right order
        $this->assertEqual($users[0]->id, 5);
        $this->assertEqual($users[1]->id, 8);
        $this->assertEqual($users[2]->id, 10);
    }

    public function testManyToManyFetchingWithColumnAggregationInheritance() {
        $query = new Doctrine_Query($this->session);

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

        $this->session->flush();

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

        $this->session->flush();

        $this->session->clear();

        $query = new Doctrine_Query($this->session);
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

        $this->session->clear();
        
        $query->from("Task")->where("Task.ResourceAlias.Type.type = 'TY2' || Task.ResourceAlias.Type.type = 'TY1'");
        $tasks = $query->execute();

        $this->assertEqual($tasks->count(),2);
        $this->assertEqual(count($this->dbh->query($query->getQuery())->fetchAll(PDO::FETCH_ASSOC)),2);
        $this->assertEqual($tasks[0]->name, 'T1');
        $this->assertEqual($tasks[1]->name, 'T2');
    }


    public function testManyToManyFetchingWithColonOperator() {
        $query = new Doctrine_Query($this->session);

        $task = new Task();

        // clear identity maps
        $this->session->getTable('Task')->clear();
        $this->session->getTable('Assignment')->clear();
        $this->session->getTable('Resource')->clear();

        $tasks[1] = $task->getTable()->find(2);
        $this->assertEqual($tasks[1]->ResourceAlias[0]->name, "R3");
        $this->assertEqual($tasks[1]->ResourceAlias[1]->name, "R4");
        $this->assertEqual($tasks[1]->ResourceAlias[2]->name, "R5");
        $this->assertEqual($tasks[1]->ResourceAlias[3]->name, "R6");

        // clear identity maps
        $task->getTable()->clear();
        $this->session->getTable('Assignment')->clear();
        $this->session->getTable('Resource')->clear();

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
        $query = new Doctrine_Query($this->session);

        $this->session->getTable('Task')->clear();
        $this->session->getTable('Assignment')->clear();
        $this->session->getTable('Resource')->clear();

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
        $query = new Doctrine_Query($this->session);

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



        $this->session->flush();
        
        // clear the identity maps

        $entries[0]->Log_Status->getTable()->clear();
        $entries[0]->getTable()->clear();

        $entries = $this->session->query("FROM Log_Entry-I.Log_Status-i");

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

        $entries = $this->session->query("FROM Log_Entry-I.Log_Status-i");
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
        
        $this->session->flush();
        
        $count = $this->dbh->count();

        $entries = $this->session->query("FROM ORM_TestEntry-i.ORM_TestItem-i");

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
        $this->session->getTable('User')->clear();
        $this->session->getTable('Email')->clear();
        $this->session->getTable('Phonenumber')->clear();

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

        $q = new Doctrine_Query($this->session);
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

    public function testValidLazyPropertyFetching() {
        $q = new Doctrine_Query($this->session);
        $q->from("User-l(name)");
        $users = $q->execute();
        $this->assertEqual($users->count(), 8);
        $this->assertTrue($users instanceof Doctrine_Collection_Lazy);
        $count = count($this->dbh);
        $this->assertTrue(is_string($users[0]->name));
        $this->assertEqual($count, count($this->dbh));
        $count = count($this->dbh);
        $this->assertTrue(is_numeric($users[0]->email_id));
        $this->assertEqual($count + 1, count($this->dbh));
        
        $users[0]->getTable()->clear();

        $q->from("User-b(name)");
        $users = $q->execute();
        $this->assertEqual($users->count(), 8);
        $this->assertTrue($users instanceof Doctrine_Collection_Batch);
        $count = count($this->dbh);
        $this->assertTrue(is_string($users[0]->name));
        $this->assertEqual($count, count($this->dbh));
        $count = count($this->dbh);
        $this->assertTrue(is_numeric($users[0]->email_id));
        $this->assertEqual($count + 1, count($this->dbh));
        $this->assertTrue(is_numeric($users[1]->email_id));
        $this->assertEqual($count + 1, count($this->dbh));
        $this->assertTrue(is_numeric($users[2]->email_id));
        $this->assertEqual($count + 1, count($this->dbh));
    }

    public function testQueryWithComplexAliases() {
        $q = new Doctrine_Query($this->session);

        $board = new Forum_Board();
        $table = $board->getTable();
        $fk    = $table->getForeignKey("Threads");

        $this->assertEqual($table->getComponentName(), "Forum_Board");
        $this->assertTrue($fk instanceof Doctrine_ForeignKey);
        $this->assertEqual($fk->getTable()->getComponentName(), "Forum_Thread");

        $entry = new Forum_Entry();
        $this->assertTrue($entry->getTable()->getForeignKey("Thread") instanceof Doctrine_LocalKey);

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

        $this->session->flush();

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

        $table = $this->session->getTable("Forum_Board")->setAttribute(Doctrine::ATTR_FETCHMODE, Doctrine::FETCH_LAZY);
        $table = $this->session->getTable("Forum_Thread")->setAttribute(Doctrine::ATTR_FETCHMODE, Doctrine::FETCH_LAZY);
        $q->from("Forum_Board.Threads");

        $this->assertEqual($q->getQuery(), "SELECT forum_board.id AS forum_board__id, forum_thread.id AS forum_thread__id FROM forum_board LEFT JOIN forum_thread ON forum_board.id = forum_thread.board_id");
        $coll = $q->execute();
        $this->assertEqual($coll->count(), 1);



        $q->from("Forum_Board-l.Threads-l");
        $this->assertEqual($q->getQuery(), "SELECT forum_board.id AS forum_board__id, forum_thread.id AS forum_thread__id FROM forum_board LEFT JOIN forum_thread ON forum_board.id = forum_thread.board_id");

        //$this->session->clear();

        $q->from("Forum_Board-l.Threads-l.Entries-l");
        $this->assertEqual($q->getQuery(), "SELECT forum_board.id AS forum_board__id, forum_thread.id AS forum_thread__id, forum_entry.id AS forum_entry__id FROM forum_board LEFT JOIN forum_thread ON forum_board.id = forum_thread.board_id LEFT JOIN forum_entry ON forum_thread.id = forum_entry.thread_id");
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
        $query = new Doctrine_Query($this->session);

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
        $query = new Doctrine_Query($this->session);
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

        $query->offset = 7;
        $this->assertEqual($query->get("offset"), 7);
        
        $query->limit = 10;
        $this->assertEqual($query->limit, 10);
        $this->assertTrue(strpos($query->getQuery(),"LIMIT"));

        $query->limit = null;
        $this->assertFalse(strpos($query->getQuery(),"LIMIT"));

        $coll = $query->execute();
        $this->assertTrue($coll instanceof Doctrine_Collection_Lazy);
        $this->assertEqual($coll->count(), 1);

        
        $query->where("User.name LIKE ?")->limit(3);
        $this->assertEqual($query->limit, 3);
        $this->assertTrue(strpos($query->getQuery(),"LIMIT"));
    }

    public function testLimit() {
        $query = new Doctrine_Query($this->session);
        $coll  = $query->query("FROM User LIMIT 3");
        $this->assertEqual($query->limit, 3);
        $this->assertEqual($coll->count(), 3);
    }
    public function testOffset() {
        $query = new Doctrine_Query($this->session);
        $coll  = $query->query("FROM User LIMIT 3 OFFSET 3");
        $this->assertEqual($query->offset, 3);
        $this->assertEqual($coll->count(), 3);
    }
    public function testPreparedQuery() {
        $coll = $this->session->query("FROM User WHERE User.name = :name", array(":name" => "zYne"));
        $this->assertEqual($coll->count(), 1);
    }
    public function testOrderBy() {
        $query = new Doctrine_Query($this->session);
        $query->from("User-b")->orderby("User.name ASC, User.Email.address");
        $users = $query->execute();

        $this->assertEqual(trim($query->getQuery()),
        "SELECT entity.id AS entity__id FROM entity LEFT JOIN email ON entity.email_id = email.id WHERE (entity.type = 0) ORDER BY entity.name ASC, email.address");
        $this->assertEqual($users->count(),8);
        $this->assertTrue($users[0]->name == "Arnold Schwarzenegger");
    }
    public function testBatchFetching() {
        $query = new Doctrine_Query($this->session);
        $users = $query->query("FROM User-b");
        $this->assertEqual(trim($query->getQuery()),
        "SELECT entity.id AS entity__id FROM entity WHERE (entity.type = 0)");

        $this->assertEqual($users[0]->name, "zYne");
        $this->assertTrue($users instanceof Doctrine_Collection_Batch);
    }
    public function testLazyFetching() {
        $query = new Doctrine_Query($this->session);
        $users = $query->query("FROM User-l");
        $this->assertEqual(trim($query->getQuery()),
        "SELECT entity.id AS entity__id FROM entity WHERE (entity.type = 0)");

        $this->assertEqual($users[0]->name, "zYne");
        $this->assertTrue($users instanceof Doctrine_Collection_Lazy);

    }

    public function testAlbumManager() {

        $query = new Doctrine_Query($this->session);

        $this->graph = $query;

        $user = $this->objTable->find(5);


        $album = $this->session->create("Album");
        $album->Song[0];

        $user->Album[0]->name = "Damage Done";
        $user->Album[1]->name = "Haven";

        $user->Album[0]->Song[0]->title = "Damage Done";


        $user->Album[0]->Song[1]->title = "The Treason Wall";
        $user->Album[0]->Song[2]->title = "Monochromatic Stains";
        $this->assertEqual(count($user->Album[0]->Song), 3);


        $user->Album[1]->Song[0]->title = "Not Built To Last";
        $user->Album[1]->Song[1]->title = "The Wonders At Your Feet";
        $user->Album[1]->Song[2]->title = "Feast Of Burden";
        $user->Album[1]->Song[3]->title = "Fabric";
        $this->assertEqual(count($user->Album[1]->Song), 4);

        $user->save();

        $user = $this->objTable->find(5);

        $this->assertEqual(count($user->Album[0]->Song), 3);
        $this->assertEqual(count($user->Album[1]->Song), 4);

        $users = $query->query("FROM User WHERE User.Album.name like '%Damage%'");
    }

    function testQuery() {
        // DYNAMIC COLLECTION EXPANDING
        $query = new Doctrine_Query($this->session);

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

        $this->session->getTable("User")->clear();
        $this->session->getTable("Phonenumber")->clear();

        $users = $query->query("FROM User-b.Phonenumber-l WHERE User.Phonenumber.phonenumber LIKE '%123%'");
        $this->assertEqual(trim($query->getQuery()),
        "SELECT entity.id AS entity__id, phonenumber.id AS phonenumber__id FROM entity LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE phonenumber.phonenumber LIKE '%123%' AND (entity.type = 0)");

        $count = $this->session->getDBH()->count();

        $users[1]->Phonenumber[0]->phonenumber;
        $users[1]->Phonenumber[1]->phonenumber;
        $this->assertEqual($users[1]->Phonenumber[1]->getState(),Doctrine_Record::STATE_CLEAN);

        $users[1]->Phonenumber[2]->phonenumber;
        $this->assertEqual($users[1]->Phonenumber[1]->getState(),Doctrine_Record::STATE_CLEAN);
        $count2 = $this->session->getDBH()->count();
        $this->assertEqual($count + 4,$count2);



        // DYNAMIC FETCHMODES
        $e = false;
        try {
            $users = $query->query("FROM User-unknown");
        } catch(Exception $e) {
        }
        $this->assertTrue($e instanceof DQLException);


        $users = $query->query("FROM User-i");
        $this->assertEqual(trim($query->getQuery()),
        "SELECT entity.id AS entity__id, entity.name AS entity__name, entity.loginname AS entity__loginname, entity.password AS entity__password, entity.type AS entity__type, entity.created AS entity__created, entity.updated AS entity__updated, entity.email_id AS entity__email_id FROM entity WHERE (entity.type = 0)");

        $count = $this->session->getDBH()->count();
        $this->assertEqual($users[0]->name, "zYne");

        $this->assertTrue($users instanceof Doctrine_Collection_Immediate);
        $count2 = $this->session->getDBH()->count();






        //$this->clearCache();

        $users = $query->query("FROM User-b.Phonenumber-b");
        $this->assertEqual(trim($query->getQuery()),
        "SELECT entity.id AS entity__id, phonenumber.id AS phonenumber__id FROM entity LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE (entity.type = 0)");

        $this->assertEqual($users->count(),8);

        // EXPECTED THAT ONE NEW QUERY IS NEEDED TO GET THE FIRST USER's PHONENUMBER

        $count = $this->session->getDBH()->count();
        $users[0]->Phonenumber[0]->phonenumber;
        $count2 = $this->session->getDBH()->count();
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
