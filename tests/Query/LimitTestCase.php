<?php
class Doctrine_Query_Limit_TestCase extends Doctrine_UnitTestCase {
    public function prepareTables() {
        $this->tables[] = "Photo";
        $this->tables[] = "Tag";
        $this->tables[] = "Phototag";

        parent::prepareTables();
    }

    public function testLimitWithOneToOneLeftJoin() {
        $q = new Doctrine_Query($this->connection);
        $q->select('u.id, e.*')->from('User u, u.Email e')->limit(5);

        $users = $q->execute();
        $this->assertEqual($users->count(), 5);
        $this->assertEqual($q->getQuery(), "SELECT e.id AS e__id, e2.id AS e2__id, e2.address AS e2__address FROM entity e LEFT JOIN email e2 ON e.email_id = e2.id WHERE (e.type = 0) LIMIT 5");

    }
    public function testLimitWithOneToOneInnerJoin() {
        $q = new Doctrine_Query($this->connection);
        $q->select('u.id, e.*')->from('User u, u:Email e')->limit(5);

        $users = $q->execute();
        $this->assertEqual($users->count(), 5);
        $this->assertEqual($q->getQuery(), "SELECT e.id AS e__id, e2.id AS e2__id, e2.address AS e2__address FROM entity e INNER JOIN email e2 ON e.email_id = e2.id WHERE (e.type = 0) LIMIT 5");
    }
    public function testLimitWithOneToManyLeftJoin() {
        $this->query->select('u.id, p.*')->from('User u, u.Phonenumber p')->limit(5);

        $sql = $this->query->getQuery();
        $this->assertEqual($this->query->getQuery(), 
        'SELECT e.id AS e__id, p.id AS p__id, p.phonenumber AS p__phonenumber, p.entity_id AS p__entity_id FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE e.id IN (SELECT DISTINCT e2.id FROM entity e2 WHERE (e2.type = 0) LIMIT 5) AND (e.type = 0)');

        $users = $this->query->execute();
        $count = $this->dbh->count();
        $this->assertEqual($users->count(), 5);
        $users[0]->Phonenumber[0];
        $this->assertEqual($count, $this->dbh->count());
        



        $this->query->offset(2);

        $users = $this->query->execute();
        $count = $this->dbh->count();
        $this->assertEqual($users->count(), 5);
        $users[3]->Phonenumber[0];
        $this->assertEqual($count, $this->dbh->count());
    }

    public function testLimitWithOneToManyLeftJoinAndCondition() {
        $q = new Doctrine_Query($this->connection);
        $q->from("User(name)")->where("User.Phonenumber.phonenumber LIKE '%123%'")->limit(5);

        $users = $q->execute();

        $this->assertEqual($users->count(), 5);

        $this->assertEqual($users[0]->name, 'zYne');
        $this->assertEqual($users[1]->name, 'Arnold Schwarzenegger');
        $this->assertEqual($users[2]->name, 'Michael Caine');
        $this->assertEqual($users[3]->name, 'Sylvester Stallone');
        $this->assertEqual($users[4]->name, 'Jean Reno');

        $this->assertEqual($q->getQuery(),
        "SELECT e.id AS e__id, e.name AS e__name FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE e.id IN (SELECT DISTINCT e2.id FROM entity e2 LEFT JOIN phonenumber p2 ON e2.id = p2.entity_id WHERE p2.phonenumber LIKE '%123%' AND (e2.type = 0) LIMIT 5) AND p.phonenumber LIKE '%123%' AND (e.type = 0)");
    }


    public function testLimitWithOneToManyLeftJoinAndOrderBy() {
        $q = new Doctrine_Query($this->connection);
        $q->from("User(name)")->where("User.Phonenumber.phonenumber LIKE '%123%'")->orderby("User.Email.address")->limit(5);
        

        $users = $q->execute();

        $this->assertEqual($users[0]->name, 'Arnold Schwarzenegger');
        $this->assertEqual($users[1]->name, 'Michael Caine');
        $this->assertEqual($users[2]->name, 'Jean Reno');
        $this->assertEqual($users[3]->name, 'Sylvester Stallone');
        $this->assertEqual($users[4]->name, 'zYne');

        $this->assertEqual($users->count(), 5);
    }

    public function testLimitWithOneToManyInnerJoin() {
        $this->query->select('u.id, p.*')->from('User u INNER JOIN u.Phonenumber p');
        $this->query->limit(5);


        $sql = $this->query->getQuery();

        $users = $this->query->execute();
        $count = $this->dbh->count();
        $this->assertEqual($users->count(), 5);
        $users[0]->Phonenumber[0];
        $this->assertEqual($count, $this->dbh->count());


        $this->query->offset(2);

        $users = $this->query->execute();
        $count = $this->dbh->count();
        $this->assertEqual($users->count(), 5);
        $users[3]->Phonenumber[0];
        $this->assertEqual($count, $this->dbh->count());
        
        $this->assertEqual($this->query->getQuery(),
        'SELECT e.id AS e__id, p.id AS p__id, p.phonenumber AS p__phonenumber, p.entity_id AS p__entity_id FROM entity e INNER JOIN phonenumber p ON e.id = p.entity_id WHERE e.id IN (SELECT DISTINCT e2.id FROM entity e2 INNER JOIN phonenumber p2 ON e2.id = p2.entity_id WHERE (e2.type = 0) LIMIT 5 OFFSET 2) AND (e.type = 0)');
    }

    public function testLimitWithPreparedQueries() {
        $q = new Doctrine_Query();
        $q->select('u.id, p.id')->from('User u LEFT JOIN u.Phonenumber p');
        $q->where("u.name = ?");
        $q->limit(5);
        $users = $q->execute(array('zYne'));
        
        $this->assertEqual($users->count(), 1);
        $count = $this->dbh->count();
        $users[0]->Phonenumber[0];
        $this->assertEqual($count, $this->dbh->count());

        $this->assertEqual($q->getQuery(),
        'SELECT e.id AS e__id, p.id AS p__id FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE e.id IN (SELECT DISTINCT e2.id FROM entity e2 WHERE e2.name = ? AND (e2.type = 0) LIMIT 5) AND e.name = ? AND (e.type = 0)');

        $q = new Doctrine_Query();
        $q->select('u.id, p.id')->from('User u LEFT JOIN u.Phonenumber p');
        $q->where("User.name LIKE ? || User.name LIKE ?");
        $q->limit(5);
        $users = $q->execute(array('%zYne%', '%Arnold%'));
        $this->assertEqual($users->count(), 2);


        $count = $this->dbh->count();
        $users[0]->Phonenumber[0];
        $this->assertEqual($count, $this->dbh->count());

        $this->assertEqual($q->getQuery(),
        "SELECT e.id AS e__id, p.id AS p__id FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE e.id IN (SELECT DISTINCT e2.id FROM entity e2 WHERE (e2.name LIKE ? OR e2.name LIKE ?) AND (e2.type = 0) LIMIT 5) AND (e.name LIKE ? OR e.name LIKE ?) AND (e.type = 0)");

    } 



    public function testConnectionFlushing() {
        $q = new Doctrine_Query();
        $q->from("User(id).Phonenumber(id)");
        $q->where("User.name = ?");
        $q->limit(5);
        $users = $q->execute(array('zYne'));
        
        $this->assertEqual($users->count(), 1);
        $this->connection->flush();
    }


    public function testLimitWithManyToManyColumnAggInheritanceLeftJoin() {
        $q = new Doctrine_Query($this->connection);
        $q->from("User.Group")->limit(5);

        $users = $q->execute();
 

        $this->assertEqual($users->count(), 5);
        
        $user = $this->objTable->find(5);
        $user->Group[1]->name = "Tough guys inc.";
        $user->Group[2]->name = "Terminators";
        
        $user2 = $this->objTable->find(4);
        $user2->Group = $user->Group;
        
        $user3 = $this->objTable->find(6);
        $user3->Group = $user->Group;

        $this->assertEqual($user->Group[0]->name, "Action Actors");
        
        $this->connection->flush();

        $this->assertEqual($user->Group[0]->name, "Action Actors");
        $this->assertEqual(count($user->Group), 3);



        $q = new Doctrine_Query();
        $q->from("User")->where("User.Group.id = ?")->orderby("User.id ASC")->limit(5);


        $users = $q->execute(array($user->Group[1]->id));

        $this->assertEqual($users->count(), 3);

        $this->connection->clear();
        $q = new Doctrine_Query();
        $q->from("User")->where("User.Group.id = ?")->orderby("User.id DESC");
        $users = $q->execute(array($user->Group[1]->id));

        $this->assertEqual($users->count(), 3);
    }

    public function testLimitAttribute() {
        $this->manager->setAttribute(Doctrine::ATTR_QUERY_LIMIT, Doctrine::LIMIT_ROWS);
        
        $this->connection->clear();
        $q = new Doctrine_Query();
        $q->from("User")->where("User.Group.id = ?")->orderby("User.id DESC")->limit(5);
        $users = $q->execute(array(3));

        $this->assertEqual($users->count(), 3);

        $this->assertEqual($q->getQuery(), "SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e LEFT JOIN groupuser g ON e.id = g.user_id LEFT JOIN entity e2 ON e2.id = g.group_id WHERE e2.id = ? AND (e.type = 0 AND (e2.type = 1 OR e2.type IS NULL)) ORDER BY e.id DESC LIMIT 5");
        $this->manager->setAttribute(Doctrine::ATTR_QUERY_LIMIT, Doctrine::LIMIT_RECORDS);
    }

    public function testLimitWithManyToManyAndColumnAggregationInheritance() {
        $q = new Doctrine_Query();
        $q->from('User u, u.Group g')->where('g.id > 1')->orderby('u.name DESC')->limit(10); 

    }
    public function testLimitWithNormalManyToMany() {
        $coll = new Doctrine_Collection($this->connection->getTable("Photo"));
        $tag = new Tag();
        $tag->tag = "Some tag";
        $coll[0]->Tag[0] = $tag;
        $coll[0]->name = "photo 1";
        $coll[1]->Tag[0] = $tag;
        $coll[1]->name = "photo 2";
        $coll[2]->Tag[0] = $tag;
        $coll[2]->name = "photo 3";
        $coll[3]->Tag[0]->tag = "Other tag";
        $coll[3]->name = "photo 4";
        $this->connection->flush();

        $q = new Doctrine_Query();
        $q->from("Photo")->where("Photo.Tag.id = ?")->orderby("Photo.id DESC")->limit(100);

        $photos = $q->execute(array(1));
        $this->assertEqual($photos->count(), 3);            
        $this->assertEqual($q->getQuery(), 
        "SELECT p.id AS p__id, p.name AS p__name FROM photo p LEFT JOIN phototag p2 ON p.id = p2.photo_id LEFT JOIN tag t ON t.id = p2.tag_id WHERE p.id IN (SELECT DISTINCT p3.id FROM photo p3 LEFT JOIN phototag p4 ON p3.id = p4.photo_id LEFT JOIN tag t2 ON t2.id = p4.tag_id WHERE t2.id = ? ORDER BY p3.id DESC LIMIT 100) AND t.id = ? ORDER BY p.id DESC");
    }

}
?>
