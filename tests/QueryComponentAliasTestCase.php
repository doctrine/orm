<?php
class Doctrine_Query_ComponentAlias_TestCase extends Doctrine_UnitTestCase {
    public function testQueryWithSingleAlias() {
        $this->connection->clear();
        $q = new Doctrine_Query();

        $q->from('User u, u.Phonenumber');

        $users = $q->execute();

        $count = count($this->dbh);

        $this->assertEqual($users->count(), 8);
        $this->assertTrue($users[0]->Phonenumber instanceof Doctrine_Collection);
        $this->assertEqual($q->getQuery(), 
        "SELECT entity.id AS entity__id, entity.name AS entity__name, entity.loginname AS entity__loginname, entity.password AS entity__password, entity.type AS entity__type, entity.created AS entity__created, entity.updated AS entity__updated, entity.email_id AS entity__email_id, phonenumber.id AS phonenumber__id, phonenumber.phonenumber AS phonenumber__phonenumber, phonenumber.entity_id AS phonenumber__entity_id FROM entity LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE (entity.type = 0)");
        $this->assertEqual($count, count($this->dbh));
    }

    public function testQueryWithNestedAliases() {
        $this->connection->clear();
        $q = new Doctrine_Query();

        $q->from('User u, u.Group g, g.Phonenumber');

        $users = $q->execute();

        $count = count($this->dbh);

        $this->assertEqual($users->count(), 8);
        $this->assertTrue($users[0]->Phonenumber instanceof Doctrine_Collection);
        $this->assertEqual($q->getQuery(), 
        "SELECT entity.id AS entity__id, entity.name AS entity__name, entity.loginname AS entity__loginname, entity.password AS entity__password, entity.type AS entity__type, entity.created AS entity__created, entity.updated AS entity__updated, entity.email_id AS entity__email_id, entity2.id AS entity2__id, entity2.name AS entity2__name, entity2.loginname AS entity2__loginname, entity2.password AS entity2__password, entity2.type AS entity2__type, entity2.created AS entity2__created, entity2.updated AS entity2__updated, entity2.email_id AS entity2__email_id, phonenumber.id AS phonenumber__id, phonenumber.phonenumber AS phonenumber__phonenumber, phonenumber.entity_id AS phonenumber__entity_id FROM entity LEFT JOIN groupuser ON entity.id = groupuser.user_id LEFT JOIN entity AS entity2 ON entity2.id = groupuser.group_id LEFT JOIN phonenumber ON entity2.id = phonenumber.entity_id WHERE (entity.type = 0 AND (entity2.type = 1 OR entity2.type IS NULL))");

        $this->assertEqual(($count + 1), count($this->dbh));
    }
    public function testQueryWithMultipleNestedAliases() {
        $this->connection->clear();
        $q = new Doctrine_Query();

        $q->from('User u, u.Phonenumber, u.Group g, g.Phonenumber');

        $users = $q->execute();

        $count = count($this->dbh);

        $this->assertEqual($users->count(), 8);
        $this->assertTrue($users[0]->Phonenumber instanceof Doctrine_Collection);
        $this->assertEqual($q->getQuery(), 
        "SELECT entity.id AS entity__id, entity.name AS entity__name, entity.loginname AS entity__loginname, entity.password AS entity__password, entity.type AS entity__type, entity.created AS entity__created, entity.updated AS entity__updated, entity.email_id AS entity__email_id, phonenumber.id AS phonenumber__id, phonenumber.phonenumber AS phonenumber__phonenumber, phonenumber.entity_id AS phonenumber__entity_id, entity2.id AS entity2__id, entity2.name AS entity2__name, entity2.loginname AS entity2__loginname, entity2.password AS entity2__password, entity2.type AS entity2__type, entity2.created AS entity2__created, entity2.updated AS entity2__updated, entity2.email_id AS entity2__email_id, phonenumber2.id AS phonenumber2__id, phonenumber2.phonenumber AS phonenumber2__phonenumber, phonenumber2.entity_id AS phonenumber2__entity_id FROM entity LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id LEFT JOIN groupuser ON entity.id = groupuser.user_id LEFT JOIN entity AS entity2 ON entity2.id = groupuser.group_id LEFT JOIN phonenumber AS phonenumber2 ON entity2.id = phonenumber2.entity_id WHERE (entity.type = 0 AND (entity2.type = 1 OR entity2.type IS NULL))");
        $this->assertEqual($count, count($this->dbh));
    }
}
?>
