<?php
class Doctrine_Query_Limit_TestCase extends Doctrine_UnitTestCase {
    public function prepareTables() {
        $this->tables[] = "Photo";
        $this->tables[] = "Tag";
        $this->tables[] = "Phototag";

        parent::prepareTables();
    }
    public function testLimitWithOneToOneLeftJoin() {
        $q = new Doctrine_Query($this->session);
        $q->from('User(id).Email')->limit(5);

        $users = $q->execute();
        $this->assertEqual($users->count(), 5);
        $this->assertEqual($q->getQuery(), "SELECT entity.id AS entity__id, email.id AS email__id, email.address AS email__address FROM entity LEFT JOIN email ON entity.email_id = email.id WHERE (entity.type = 0) LIMIT 5");

    }
    public function testLimitWithOneToOneInnerJoin() {
        $q = new Doctrine_Query($this->session);
        $q->from('User(id):Email')->limit(5);

        $users = $q->execute();
        $this->assertEqual($users->count(), 5);
        $this->assertEqual($q->getQuery(), "SELECT entity.id AS entity__id, email.id AS email__id, email.address AS email__address FROM entity INNER JOIN email ON entity.email_id = email.id WHERE (entity.type = 0) LIMIT 5");
    }
    public function testLimitWithOneToManyLeftJoin() {
        $this->query->from("User(id).Phonenumber");
        $this->query->limit(5);

        $sql = $this->query->getQuery();

        $users = $this->query->execute();
        $count = $this->dbh->count();
        $this->assertEqual($users->count(), 5);
        $users[0]->Phonenumber[0];
        $this->assertEqual($count, $this->dbh->count());
        

        $this->assertEqual($this->query->getQuery(), 
        'SELECT entity.id AS entity__id, phonenumber.id AS phonenumber__id, phonenumber.phonenumber AS phonenumber__phonenumber, phonenumber.entity_id AS phonenumber__entity_id FROM entity LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE entity.id IN (SELECT DISTINCT entity.id FROM entity WHERE (entity.type = 0) LIMIT 5) AND (entity.type = 0)');


        $this->query->offset(2);

        $users = $this->query->execute();
        $count = $this->dbh->count();
        $this->assertEqual($users->count(), 5);
        $users[3]->Phonenumber[0];
        $this->assertEqual($count, $this->dbh->count());
    }

    public function testLimitWithOneToManyLeftJoinAndCondition() {
        $q = new Doctrine_Query($this->session);
        $q->from("User(name)")->where("User.Phonenumber.phonenumber LIKE '%123%'")->limit(5);
        $users = $q->execute();
        
        $this->assertEqual($users[0]->name, 'zYne');
        $this->assertEqual($users[1]->name, 'Arnold Schwarzenegger');
        $this->assertEqual($users[2]->name, 'Michael Caine');
        $this->assertEqual($users[3]->name, 'Sylvester Stallone');
        $this->assertEqual($users[4]->name, 'Jean Reno');

        $this->assertEqual($users->count(), 5);

        $this->assertEqual($q->getQuery(),
        "SELECT entity.id AS entity__id, entity.name AS entity__name FROM entity LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE entity.id IN (SELECT DISTINCT entity.id FROM entity LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE phonenumber.phonenumber LIKE '%123%' AND (entity.type = 0) LIMIT 5) AND phonenumber.phonenumber LIKE '%123%' AND (entity.type = 0)");
    }

    public function testLimitWithOneToManyLeftJoinAndOrderBy() {
        $q = new Doctrine_Query($this->session);
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
        $this->query->from("User(id):Phonenumber");
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
        'SELECT entity.id AS entity__id, phonenumber.id AS phonenumber__id, phonenumber.phonenumber AS phonenumber__phonenumber, phonenumber.entity_id AS phonenumber__entity_id FROM entity INNER JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE entity.id IN (SELECT DISTINCT entity.id FROM entity INNER JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE (entity.type = 0) LIMIT 5 OFFSET 2) AND (entity.type = 0)');
    }
    public function testLimitWithPreparedQueries() {
        $q = new Doctrine_Query();
        $q->from("User(id).Phonenumber(id)");
        $q->where("User.name = ?");
        $q->limit(5);
        $users = $q->execute(array('zYne'));
        
        $this->assertEqual($users->count(), 1);
        $count = $this->dbh->count();
        $users[0]->Phonenumber[0];
        $this->assertEqual($count, $this->dbh->count());

        $this->assertEqual($q->getQuery(),
        'SELECT entity.id AS entity__id, phonenumber.id AS phonenumber__id FROM entity LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE entity.id IN (SELECT DISTINCT entity.id FROM entity WHERE entity.name = ? AND (entity.type = 0) LIMIT 5) AND entity.name = ? AND (entity.type = 0)');

        $q = new Doctrine_Query();
        $q->from("User(id).Phonenumber(id)");
        $q->where("User.name LIKE ? || User.name LIKE ?");
        $q->limit(5);
        $users = $q->execute(array('%zYne%', '%Arnold%'));
        $this->assertEqual($users->count(), 2);


        $count = $this->dbh->count();
        $users[0]->Phonenumber[0];
        $this->assertEqual($count, $this->dbh->count());

        $this->assertEqual($q->getQuery(),
        "SELECT entity.id AS entity__id, phonenumber.id AS phonenumber__id FROM entity LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE entity.id IN (SELECT DISTINCT entity.id FROM entity WHERE (entity.name LIKE ? OR entity.name LIKE ?) AND (entity.type = 0) LIMIT 5) AND (entity.name LIKE ? OR entity.name LIKE ?) AND (entity.type = 0)");

    }    

    public function testConnectionFlushing() {
        $q = new Doctrine_Query();
        $q->from("User(id).Phonenumber(id)");
        $q->where("User.name = ?");
        $q->limit(5);
        $users = $q->execute(array('zYne'));
        
        $this->assertEqual($users->count(), 1);
        $this->session->flush();
    }

    public function testLimitWithManyToManyColumnAggInheritanceLeftJoin() {
        $q = new Doctrine_Query($this->session);
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
        
        $this->session->flush();

        $this->assertEqual($user->Group[0]->name, "Action Actors");
        $this->assertEqual(count($user->Group), 3);



        $q = new Doctrine_Query();
        $q->from("User")->where("User.Group.id = ?")->orderby("User.id DESC")->limit(5);
        $users = $q->execute(array($user->Group[1]->id));

        $this->assertEqual($users->count(), 3);

        $this->session->clear();
        $q = new Doctrine_Query();
        $q->from("User")->where("User.Group.id = ?")->orderby("User.id DESC");
        $users = $q->execute(array($user->Group[1]->id));

        $this->assertEqual($users->count(), 3);
    }
    public function testLimitWithNormalManyToMany() {
        $coll = new Doctrine_Collection($this->session->getTable("Photo"));
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
        $this->session->flush();

        $q = new Doctrine_Query();
        $q->from("Photo")->where("Photo.Tag.id = ?")->orderby("Photo.id DESC")->limit(100);
        $photos = $q->execute(array(1));
        $this->assertEqual($photos->count(), 3);
        $this->assertEqual($q->getQuery(), 
        "SELECT photo.id AS photo__id, photo.name AS photo__name FROM photo LEFT JOIN phototag ON photo.id = phototag.photo_id LEFT JOIN tag ON tag.id = phototag.tag_id WHERE photo.id IN (SELECT DISTINCT photo.id FROM photo LEFT JOIN phototag ON photo.id = phototag.photo_id LEFT JOIN tag ON tag.id = phototag.tag_id WHERE tag.id = ? LIMIT 100) AND tag.id = ? ORDER BY photo.id DESC");
    }

}
?>
