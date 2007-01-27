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
        "SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, p.id AS p__id, p.phonenumber AS p__phonenumber, p.entity_id AS p__entity_id FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0)");
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
        "SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, e2.id AS e2__id, e2.name AS e2__name, e2.loginname AS e2__loginname, e2.password AS e2__password, e2.type AS e2__type, e2.created AS e2__created, e2.updated AS e2__updated, e2.email_id AS e2__email_id, p.id AS p__id, p.phonenumber AS p__phonenumber, p.entity_id AS p__entity_id FROM entity e LEFT JOIN groupuser g2 ON e.id = g2.user_id LEFT JOIN entity e2 ON e2.id = g2.group_id LEFT JOIN phonenumber p ON e2.id = p.entity_id WHERE (e.type = 0 AND (e2.type = 1 OR e2.type IS NULL))");
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
        "SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, p.id AS p__id, p.phonenumber AS p__phonenumber, p.entity_id AS p__entity_id, e2.id AS e2__id, e2.name AS e2__name, e2.loginname AS e2__loginname, e2.password AS e2__password, e2.type AS e2__type, e2.created AS e2__created, e2.updated AS e2__updated, e2.email_id AS e2__email_id, p2.id AS p2__id, p2.phonenumber AS p2__phonenumber, p2.entity_id AS p2__entity_id FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id LEFT JOIN groupuser g2 ON e.id = g2.user_id LEFT JOIN entity e2 ON e2.id = g2.group_id LEFT JOIN phonenumber p2 ON e2.id = p2.entity_id WHERE (e.type = 0 AND (e2.type = 1 OR e2.type IS NULL))");
        
        $this->assertEqual($count, count($this->dbh));
    }
}
?>
