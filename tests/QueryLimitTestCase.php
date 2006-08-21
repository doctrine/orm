<?php
class Doctrine_Query_Limit_TestCase extends Doctrine_UnitTestCase {

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
                                                   	
    }                                               	
    public function testLimitWithManyToManyLeftJoin() {
        $q = new Doctrine_Query($this->session);
        $q->from("User.Group")->limit(5);
        $users = $q->execute();

        $this->assertEqual($users->count(), 5);
    }

}
?>
