<?php
class Doctrine_Query_From_TestCase extends Doctrine_UnitTestCase {
    public function testLeftJoin() {
        $q = new Doctrine_Query();

        $q->from('User u LEFT JOIN u.Group');

        $this->assertEqual($q->getQuery(), "SELECT entity.id AS entity__id, entity.name AS entity__name, entity.loginname AS entity__loginname, entity.password AS entity__password, entity.type AS entity__type, entity.created AS entity__created, entity.updated AS entity__updated, entity.email_id AS entity__email_id, entity2.id AS entity2__id, entity2.name AS entity2__name, entity2.loginname AS entity2__loginname, entity2.password AS entity2__password, entity2.type AS entity2__type, entity2.created AS entity2__created, entity2.updated AS entity2__updated, entity2.email_id AS entity2__email_id FROM entity LEFT JOIN groupuser ON entity.id = groupuser.user_id LEFT JOIN entity AS entity2 ON entity2.id = groupuser.group_id WHERE (entity.type = 0 AND (entity2.type = 1 OR entity2.type IS NULL))");

        $users = $q->execute();
        
        $this->assertEqual($users->count(), 8);
    }
    public function testDefaultLeftJoin() {
        $q = new Doctrine_Query();

        $q->from('User u JOIN u.Group');

        $this->assertEqual($q->getQuery(), "SELECT entity.id AS entity__id, entity.name AS entity__name, entity.loginname AS entity__loginname, entity.password AS entity__password, entity.type AS entity__type, entity.created AS entity__created, entity.updated AS entity__updated, entity.email_id AS entity__email_id, entity2.id AS entity2__id, entity2.name AS entity2__name, entity2.loginname AS entity2__loginname, entity2.password AS entity2__password, entity2.type AS entity2__type, entity2.created AS entity2__created, entity2.updated AS entity2__updated, entity2.email_id AS entity2__email_id FROM entity LEFT JOIN groupuser ON entity.id = groupuser.user_id LEFT JOIN entity AS entity2 ON entity2.id = groupuser.group_id WHERE (entity.type = 0 AND (entity2.type = 1 OR entity2.type IS NULL))");

        $users = $q->execute();
        
        $this->assertEqual($users->count(), 8);
    }
    public function testInnerJoin() {
        $q = new Doctrine_Query();

        $q->from('User u INNER JOIN u.Group');

        $this->assertEqual($q->getQuery(), "SELECT entity.id AS entity__id, entity.name AS entity__name, entity.loginname AS entity__loginname, entity.password AS entity__password, entity.type AS entity__type, entity.created AS entity__created, entity.updated AS entity__updated, entity.email_id AS entity__email_id, entity2.id AS entity2__id, entity2.name AS entity2__name, entity2.loginname AS entity2__loginname, entity2.password AS entity2__password, entity2.type AS entity2__type, entity2.created AS entity2__created, entity2.updated AS entity2__updated, entity2.email_id AS entity2__email_id FROM entity INNER JOIN groupuser ON entity.id = groupuser.user_id INNER JOIN entity AS entity2 ON entity2.id = groupuser.group_id WHERE (entity.type = 0 AND (entity2.type = 1 OR entity2.type IS NULL))");

        $users = $q->execute();
        
        $this->assertEqual($users->count(), 1);
    }
    public function testMultipleLeftJoin() {
        $q = new Doctrine_Query();

        $q->from('User u LEFT JOIN u.Group LEFT JOIN u.Phonenumber');

        $this->assertEqual($q->getQuery(), "SELECT entity.id AS entity__id, entity.name AS entity__name, entity.loginname AS entity__loginname, entity.password AS entity__password, entity.type AS entity__type, entity.created AS entity__created, entity.updated AS entity__updated, entity.email_id AS entity__email_id, entity2.id AS entity2__id, entity2.name AS entity2__name, entity2.loginname AS entity2__loginname, entity2.password AS entity2__password, entity2.type AS entity2__type, entity2.created AS entity2__created, entity2.updated AS entity2__updated, entity2.email_id AS entity2__email_id, phonenumber.id AS phonenumber__id, phonenumber.phonenumber AS phonenumber__phonenumber, phonenumber.entity_id AS phonenumber__entity_id FROM entity LEFT JOIN groupuser ON entity.id = groupuser.user_id LEFT JOIN entity AS entity2 ON entity2.id = groupuser.group_id LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE (entity.type = 0 AND (entity2.type = 1 OR entity2.type IS NULL))");
        $users = $q->execute();

        $this->assertEqual($users->count(), 8);
    }
    public function testMultipleLeftJoin2() {
        $q = new Doctrine_Query();

        $q->from('User u LEFT JOIN u.Group LEFT JOIN u.Phonenumber');

        $this->assertEqual($q->getQuery(), "SELECT entity.id AS entity__id, entity.name AS entity__name, entity.loginname AS entity__loginname, entity.password AS entity__password, entity.type AS entity__type, entity.created AS entity__created, entity.updated AS entity__updated, entity.email_id AS entity__email_id, entity2.id AS entity2__id, entity2.name AS entity2__name, entity2.loginname AS entity2__loginname, entity2.password AS entity2__password, entity2.type AS entity2__type, entity2.created AS entity2__created, entity2.updated AS entity2__updated, entity2.email_id AS entity2__email_id, phonenumber.id AS phonenumber__id, phonenumber.phonenumber AS phonenumber__phonenumber, phonenumber.entity_id AS phonenumber__entity_id FROM entity LEFT JOIN groupuser ON entity.id = groupuser.user_id LEFT JOIN entity AS entity2 ON entity2.id = groupuser.group_id LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE (entity.type = 0 AND (entity2.type = 1 OR entity2.type IS NULL))");
        $users = $q->execute();

        $this->assertEqual($users->count(), 8);
    }
    public function testMultipleInnerJoin() {
        $q = new Doctrine_Query();

        $q->from('User u INNER JOIN u.Group INNER JOIN u.Phonenumber');

        $this->assertEqual($q->getQuery(), "SELECT entity.id AS entity__id, entity.name AS entity__name, entity.loginname AS entity__loginname, entity.password AS entity__password, entity.type AS entity__type, entity.created AS entity__created, entity.updated AS entity__updated, entity.email_id AS entity__email_id, entity2.id AS entity2__id, entity2.name AS entity2__name, entity2.loginname AS entity2__loginname, entity2.password AS entity2__password, entity2.type AS entity2__type, entity2.created AS entity2__created, entity2.updated AS entity2__updated, entity2.email_id AS entity2__email_id, phonenumber.id AS phonenumber__id, phonenumber.phonenumber AS phonenumber__phonenumber, phonenumber.entity_id AS phonenumber__entity_id FROM entity INNER JOIN groupuser ON entity.id = groupuser.user_id INNER JOIN entity AS entity2 ON entity2.id = groupuser.group_id INNER JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE (entity.type = 0 AND (entity2.type = 1 OR entity2.type IS NULL))");
        $users = $q->execute();

        $this->assertEqual($users->count(), 1);
    }
    public function testMultipleInnerJoin2() {
        $q = new Doctrine_Query();

        $q->from('User u INNER JOIN u.Group, u.Phonenumber');

        $this->assertEqual($q->getQuery(), "SELECT entity.id AS entity__id, entity.name AS entity__name, entity.loginname AS entity__loginname, entity.password AS entity__password, entity.type AS entity__type, entity.created AS entity__created, entity.updated AS entity__updated, entity.email_id AS entity__email_id, entity2.id AS entity2__id, entity2.name AS entity2__name, entity2.loginname AS entity2__loginname, entity2.password AS entity2__password, entity2.type AS entity2__type, entity2.created AS entity2__created, entity2.updated AS entity2__updated, entity2.email_id AS entity2__email_id, phonenumber.id AS phonenumber__id, phonenumber.phonenumber AS phonenumber__phonenumber, phonenumber.entity_id AS phonenumber__entity_id FROM entity INNER JOIN groupuser ON entity.id = groupuser.user_id INNER JOIN entity AS entity2 ON entity2.id = groupuser.group_id INNER JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE (entity.type = 0 AND (entity2.type = 1 OR entity2.type IS NULL))");
        $users = $q->execute();

        $this->assertEqual($users->count(), 1);
    }
    public function testMixingOfJoins() {
        $q = new Doctrine_Query();

        $q->from('User u INNER JOIN u.Group LEFT JOIN u.Phonenumber');

        $this->assertEqual($q->getQuery(), "SELECT entity.id AS entity__id, entity.name AS entity__name, entity.loginname AS entity__loginname, entity.password AS entity__password, entity.type AS entity__type, entity.created AS entity__created, entity.updated AS entity__updated, entity.email_id AS entity__email_id, entity2.id AS entity2__id, entity2.name AS entity2__name, entity2.loginname AS entity2__loginname, entity2.password AS entity2__password, entity2.type AS entity2__type, entity2.created AS entity2__created, entity2.updated AS entity2__updated, entity2.email_id AS entity2__email_id, phonenumber.id AS phonenumber__id, phonenumber.phonenumber AS phonenumber__phonenumber, phonenumber.entity_id AS phonenumber__entity_id FROM entity INNER JOIN groupuser ON entity.id = groupuser.user_id INNER JOIN entity AS entity2 ON entity2.id = groupuser.group_id LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE (entity.type = 0 AND (entity2.type = 1 OR entity2.type IS NULL))");
        $users = $q->execute();

        $this->assertEqual($users->count(), 1);
    }
}
?>
