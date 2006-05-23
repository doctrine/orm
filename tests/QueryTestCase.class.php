<?php
class Doctrine_QueryTestCase extends Doctrine_UnitTestCase {
    public function prepareTables() {
        $this->tables[] = "Forum_Category";
        $this->tables[] = "Forum_Entry";
        $this->tables[] = "Forum_Board";
        $this->tables[] = "Forum_Thread";
        parent::prepareTables();
    }
    public function testQueryWithComplexAliases() {

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
        $q = new Doctrine_Query($this->session);
        $board->getTable()->clear();

        $board = $board->getTable()->find($board->getID());
        $this->assertEqual($board->Threads->count(), 1);
        $this->assertEqual($board->name, "Doctrine Forum");
        $this->assertEqual($board->Category->name, "General discussion");
        $this->assertEqual($board->Category->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($board->Threads[0]->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertTrue($board->Threads[0] instanceof Forum_Thread);



        $q->from("Forum_Board");
        $coll = $q->execute();
        $this->assertEqual($coll->count(), 1);

        $q->from("Forum_Board, Forum_Board.Threads");
        $coll = $q->execute();
        $this->assertEqual($coll->count(), 1);


        $q->from("Forum_Board-l, Forum_Board.Threads-l");
        $this->assertEqual($q->getQuery(), "SELECT forum_board.id AS Forum_Board__id, forum_thread.id AS Forum_Thread__id FROM forum_board LEFT JOIN forum_thread ON forum_board.id = forum_thread.board_id");

        $q->from("Forum_Board-l, Forum_Board.Threads-l, Forum_Board.Threads.Entries-l");
        $this->assertEqual($q->getQuery(), "SELECT forum_board.id AS Forum_Board__id, forum_thread.id AS Forum_Thread__id, forum_entry.id AS Forum_Entry__id FROM forum_board LEFT JOIN forum_thread ON forum_board.id = forum_thread.board_id LEFT JOIN forum_entry ON forum_thread.id = forum_entry.thread_id");
        $boards = $q->execute();
        $this->assertEqual($boards->count(), 1);
        $count = count($this->dbh);
        $this->assertEqual($boards[0]->Threads->count(), 1);
        $this->assertEqual(count($this->dbh), $count);
        $this->assertEqual($boards[0]->Threads[0]->Entries->count(), 1);
        $this->assertEqual(count($this->dbh), $count);
    }

    public function testQueryWithAliases() {
        $task = new Task();
        $task->name = "Task 1";
        $task->ResourceAlias[0]->name = "Resource 1";
        $task->ResourceAlias[1]->name = "Resource 2";

        $task->save();
        $query = new Doctrine_Query($this->session);
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
    public function testPrepared() {
        $coll = $this->session->query("FROM User WHERE User.name = :name", array(":name" => "zYne"));
        $this->assertEqual($coll->count(), 1);
    }
    public function testOrderBy() {
        $query = new Doctrine_Query($this->session);
        $query->from("User-b")->orderby("User.name ASC, User.Email.address");
        $users = $query->execute();
        $this->assertEqual(trim($query->getQuery()),
        "SELECT entity.id AS User__id FROM entity INNER JOIN email ON entity.email_id = email.id WHERE (entity.type = 0) ORDER BY entity.name ASC, email.address");
        $this->assertEqual($users->count(),8);
        $this->assertTrue($users[0]->name == "Arnold Schwarzenegger");
    }

    public function testQuery() {

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



        // DYNAMIC COLLECTION EXPANDING

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

        $this->clearCache();

        $users = $query->query("FROM User-b, User.Phonenumber-l WHERE User.Phonenumber.phonenumber LIKE '%123%'");
        $this->assertEqual(trim($query->getQuery()),
        "SELECT entity.id AS User__id, phonenumber.id AS Phonenumber__id FROM entity LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE (phonenumber.phonenumber LIKE '%123%') AND (entity.type = 0)");

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
        "SELECT entity.id AS User__id, entity.name AS User__name, entity.loginname AS User__loginname, entity.password AS User__password, entity.type AS User__type, entity.created AS User__created, entity.updated AS User__updated, entity.email_id AS User__email_id FROM entity WHERE (entity.type = 0)");

        $count = $this->session->getDBH()->count();
        $this->assertEqual($users[0]->name, "zYne");

        $this->assertTrue($users instanceof Doctrine_Collection_Immediate);
        $count2 = $this->session->getDBH()->count();


        $users = $query->query("FROM User-b");
        $this->assertEqual(trim($query->getQuery()),
        "SELECT entity.id AS User__id FROM entity WHERE (entity.type = 0)");

        $this->assertEqual($users[0]->name, "zYne");
        $this->assertTrue($users instanceof Doctrine_Collection_Batch);


        $users = $query->query("FROM User-l");
        $this->assertEqual(trim($query->getQuery()),
        "SELECT entity.id AS User__id FROM entity WHERE (entity.type = 0)");

        $this->assertEqual($users[0]->name, "zYne");
        $this->assertTrue($users instanceof Doctrine_Collection_Lazy);


        //$this->clearCache();

        $users = $query->query("FROM User-b, User.Phonenumber-b");
        $this->assertEqual(trim($query->getQuery()),
        "SELECT entity.id AS User__id, phonenumber.id AS Phonenumber__id FROM entity LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE (entity.type = 0)");

        $this->assertEqual($users->count(),8);

        // EXPECTED THAT ONE NEW QUERY IS NEEDED TO GET THE FIRST USER's PHONENUMBER

        $count = $this->session->getDBH()->count();
        $users[0]->Phonenumber[0]->phonenumber;
        $count2 = $this->session->getDBH()->count();
        $this->assertEqual($count + 1,$count2);




        $users = $query->query("FROM User-b, User.Email-b");
        $this->assertEqual(trim($query->getQuery()),
        "SELECT entity.id AS User__id, email.id AS Email__id FROM entity INNER JOIN email ON entity.email_id = email.id WHERE (entity.type = 0)");

        $this->assertEqual($users->count(),8);

        $users = $query->query("FROM Email-b WHERE Email.address LIKE '%@example%'");

        $this->assertEqual($query->getQuery(),
        "SELECT email.id AS Email__id FROM email WHERE (email.address LIKE '%@example%')");
        $this->assertEqual($users->count(),8);

        $users = $query->query("FROM User-b WHERE User.name LIKE '%Jack%'");
        $this->assertEqual($query->getQuery(), "SELECT entity.id AS User__id FROM entity WHERE (entity.name LIKE '%Jack%') AND (entity.type = 0)");
        $this->assertEqual($users->count(),0);


        $users = $query->query("FROM User-b WHERE User.Phonenumber.phonenumber REGEXP '[123]'");
        $this->assertEqual(trim($query->getQuery()),
        "SELECT entity.id AS User__id FROM entity LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE (phonenumber.phonenumber REGEXP '[123]') AND (entity.type = 0)");
        $this->assertEqual($users->count(),8);

        $users = $query->query("FROM User-b WHERE User.Group.name = 'Action Actors'");
        $this->assertEqual(trim($query->getQuery()),
        "SELECT entity.id AS User__id FROM entity WHERE (entity.id IN (SELECT user_id FROM groupuser WHERE group_id IN (SELECT entity.id AS Group__id FROM entity WHERE (entity.name = 'Action Actors') AND (entity.type = 1)))) AND (entity.type = 0)");
        $this->assertTrue($users instanceof Doctrine_Collection);
        $this->assertEqual($users->count(),1);


        $users = $query->query("FROM User-b WHERE User.Group.Phonenumber.phonenumber LIKE '123 123'");
        $this->assertEqual(trim($query->getQuery()),
        "SELECT entity.id AS User__id FROM entity WHERE (entity.id IN (SELECT user_id FROM groupuser WHERE group_id IN (SELECT entity.id AS Group__id FROM entity, phonenumber WHERE (phonenumber.phonenumber LIKE '123 123') AND (entity.type = 1)))) AND (entity.type = 0)");
        $this->assertTrue($users instanceof Doctrine_Collection);
        $this->assertEqual($users->count(),1);

        $values = $query->query("SELECT COUNT(User.name) AS users, MAX(User.name) AS max FROM User");
        $this->assertEqual(trim($query->getQuery()),"SELECT COUNT(entity.name) AS users, MAX(entity.name) AS max FROM entity WHERE (entity.type = 0)");
        $this->assertTrue(is_array($values));
        $this->assertTrue(isset($values['users']));
        $this->assertTrue(isset($values['max']));
    }           

}
?>
