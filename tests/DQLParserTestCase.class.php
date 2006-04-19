<?php
require_once("UnitTestCase.class.php");
class Doctrine_DQL_ParserTestCase extends Doctrine_UnitTestCase {

    public function testLimit() {
        $graph = new Doctrine_DQL_Parser($this->session);
        $coll  = $graph->query("FROM User LIMIT 3");
        $this->assertEqual($graph->getLimit(), 3);
        $this->assertEqual($coll->count(), 3);
    }
    public function testOffset() {
        $graph = new Doctrine_DQL_Parser($this->session);
        $coll  = $graph->query("FROM User LIMIT 3 OFFSET 3");
        $this->assertEqual($graph->getOffset(), 3);
        $this->assertEqual($coll->count(), 3);
    }

    public function testPrepared() {
        $coll = $this->session->query("FROM User WHERE User.name = :name", array(":name" => "zYne"));
        $this->assertEqual($coll->count(), 1);
    }

    public function testQuery() {
        $graph = new Doctrine_DQL_Parser($this->session);

        $this->graph = $graph;

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

        $users = $graph->query("FROM User WHERE User.Album.name like '%Damage%'");



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

        //$users = $graph->query("FROM User, User.Group WHERE User.Group.name LIKE 'Action Actors'");
        //$this->assertEqual(count($users),1);

        //$this->assertEqual($users[0]->Group[0]->name, "Action Actors");
        //$this->assertEqual(count($users[0]->Group), 1);

        //$this->assertEqual($users[0]->Group[1]->name, "Tough guys inc.");
        //$this->assertEqual($users[0]->Group[2]->name, "Terminators");
        //$this->assertEqual(count($users[0]->Group), 3);

        $this->clearCache();

        $users = $graph->query("FROM User-b, User.Phonenumber-l WHERE User.Phonenumber.phonenumber LIKE '%123%'");
        $this->assertEqual(trim($graph->getQuery()),
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
            $users = $graph->query("FROM User-unknown");
        } catch(Exception $e) {
        }
        $this->assertTrue($e instanceof DQLException);


        $users = $graph->query("FROM User-i");
        $this->assertEqual(trim($graph->getQuery()),
        "SELECT entity.id AS User__id, entity.name AS User__name, entity.loginname AS User__loginname, entity.password AS User__password, entity.type AS User__type, entity.created AS User__created, entity.updated AS User__updated, entity.email_id AS User__email_id FROM entity WHERE (entity.type = 0)");

        $count = $this->session->getDBH()->count();
        $this->assertEqual($users[0]->name, "zYne");

        $this->assertTrue($users instanceof Doctrine_Collection_Immediate);
        $count2 = $this->session->getDBH()->count();
        $this->assertEqual($count,$count2);


        $users = $graph->query("FROM User-b");
        $this->assertEqual(trim($graph->getQuery()),
        "SELECT entity.id AS User__id FROM entity WHERE (entity.type = 0)");

        $this->assertEqual($users[0]->name, "zYne");
        $this->assertTrue($users instanceof Doctrine_Collection_Batch);


        $users = $graph->query("FROM User-l");
        $this->assertEqual(trim($graph->getQuery()),
        "SELECT entity.id AS User__id FROM entity WHERE (entity.type = 0)");

        $this->assertEqual($users[0]->name, "zYne");
        $this->assertTrue($users instanceof Doctrine_Collection_Lazy);


        //$this->clearCache();

        $users = $graph->query("FROM User-b, User.Phonenumber-b");
        $this->assertEqual(trim($graph->getQuery()),
        "SELECT entity.id AS User__id, phonenumber.id AS Phonenumber__id FROM entity LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE (entity.type = 0)");

        $this->assertEqual($users->count(),8);

        // EXPECTED THAT ONE NEW QUERY IS NEEDED TO GET THE FIRST USER's PHONENUMBER

        $count = $this->session->getDBH()->count();
        $users[0]->Phonenumber[0]->phonenumber;
        $count2 = $this->session->getDBH()->count();
        $this->assertEqual($count + 1,$count2);




        $users = $graph->query("FROM User-b, User.Email-b");
        $this->assertEqual(trim($graph->getQuery()),
        "SELECT entity.id AS User__id, email.id AS Email__id FROM entity, email WHERE (entity.email_id = email.id) AND (entity.type = 0)");

        $this->assertEqual($users->count(),8);

        $users = $graph->query("FROM Email-b WHERE Email.address LIKE '%@example%'");
        $this->assertEqual($graph->getQuery(),
        "SELECT email.id AS Email__id FROM email WHERE (email.address LIKE '%@example%')");
        $this->assertEqual($users->count(),8);

        $users = $graph->query("FROM User-b WHERE User.name LIKE '%Jack%'");
        $this->assertTrue($graph->getQuery() == "SELECT entity.id AS User__id FROM entity WHERE (entity.name LIKE '%Jack%') AND (entity.type = 0)");
        $this->assertEqual($users->count(),0);


        $users = $graph->query("FROM User-b ORDER BY User.name ASC, User.Email.address");
        $this->assertEqual(trim($graph->getQuery()),
        "SELECT entity.id AS User__id FROM entity, email WHERE (entity.email_id = email.id) AND (entity.type = 0) ORDER BY entity.name ASC, email.address");
        $this->assertEqual($users->count(),8);
        $this->assertTrue($users[0]->name == "Arnold Schwarzenegger");

        $users = $graph->query("FROM User-b WHERE User.Phonenumber.phonenumber REGEXP '[123]'");
        $this->assertEqual(trim($graph->getQuery()),
        "SELECT entity.id AS User__id FROM entity LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE (phonenumber.phonenumber REGEXP '[123]') AND (entity.type = 0)");
        $this->assertEqual($users->count(),8);

        $users = $graph->query("FROM User-b WHERE User.Group.name = 'Action Actors'");
        $this->assertEqual(trim($graph->getQuery()),
        "SELECT entity.id AS User__id FROM entity WHERE (entity.id IN (SELECT user_id FROM groupuser WHERE group_id IN (SELECT entity.id AS Group__id FROM entity WHERE (entity.name = 'Action Actors') AND (entity.type = 1)))) AND (entity.type = 0)");
        $this->assertTrue($users instanceof Doctrine_Collection);
        $this->assertEqual($users->count(),1);


        $users = $graph->query("FROM User-b WHERE User.Group.Phonenumber.phonenumber LIKE '123 123'");
        $this->assertEqual(trim($graph->getQuery()),
        "SELECT entity.id AS User__id FROM entity WHERE (entity.id IN (SELECT user_id FROM groupuser WHERE group_id IN (SELECT entity.id AS Group__id FROM entity, phonenumber WHERE (phonenumber.phonenumber LIKE '123 123') AND (entity.type = 1)))) AND (entity.type = 0)");
        $this->assertTrue($users instanceof Doctrine_Collection);
        $this->assertEqual($users->count(),1);



        $values = $graph->query("SELECT COUNT(User.name) AS users, MAX(User.name) AS max FROM User");
        $this->assertEqual(trim($graph->getQuery()),"SELECT COUNT(entity.name) AS users, MAX(entity.name) AS max FROM entity WHERE (entity.type = 0)");
        $this->assertTrue(is_array($values));
        $this->assertTrue(isset($values['users']));
        $this->assertTrue(isset($values['max']));
    }

}
?>
