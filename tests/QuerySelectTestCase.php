<?php
class Doctrine_Query_Select_TestCase extends Doctrine_UnitTestCase {
    public function prepareTables() { }

    public function testAggregateFunction() {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT COUNT(u.id) FROM User u');

        $this->assertEqual($q->getQuery(), 'SELECT COUNT(entity.id) AS entity__0 FROM entity WHERE (entity.type = 0)');
    }
    public function testMultipleAggregateFunctions() {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT MAX(u.id), MIN(u.name) FROM User u');

        $this->assertEqual($q->getQuery(), 'SELECT MAX(entity.id) AS entity__0, MIN(entity.name) AS entity__1 FROM entity WHERE (entity.type = 0)');
    }
    public function testMultipleAggregateFunctionsWithMultipleComponents() {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT MAX(u.id), MIN(u.name), COUNT(p.id) FROM User u, u.Phonenumber p');

        $this->assertEqual($q->getQuery(), 'SELECT MAX(entity.id) AS entity__0, MIN(entity.name) AS entity__1, COUNT(phonenumber.id) AS phonenumber__2 FROM entity LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE (entity.type = 0)');
    }

    public function testAggregateFunctionValueHydration() {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT u.id, COUNT(p.id) FROM User u, u.Phonenumber p GROUP BY u.id');

        $users = $q->execute();
        
        $this->assertEqual($users[0]->Phonenumber->getAggregateValue('COUNT'), 1);
        $this->assertEqual($users[1]->Phonenumber->getAggregateValue('COUNT'), 3);
        $this->assertEqual($users[2]->Phonenumber->getAggregateValue('COUNT'), 1);
        $this->assertEqual($users[3]->Phonenumber->getAggregateValue('COUNT'), 1);
        $this->assertEqual($users[4]->Phonenumber->getAggregateValue('COUNT'), 3);

    }

    public function testSingleComponentWithAsterisk() {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT u.* FROM User u');

        $this->assertEqual($q->getQuery(),'SELECT entity.id AS entity__id, entity.name AS entity__name, entity.loginname AS entity__loginname, entity.password AS entity__password, entity.type AS entity__type, entity.created AS entity__created, entity.updated AS entity__updated, entity.email_id AS entity__email_id FROM entity WHERE (entity.type = 0)');
    }
    public function testSingleComponentWithMultipleColumns() {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT u.name, u.type FROM User u'); 
        
        $this->assertEqual($q->getQuery(),'SELECT entity.id AS entity__id, entity.name AS entity__name, entity.type AS entity__type FROM entity WHERE (entity.type = 0)');
    }
    public function testMultipleComponentsWithAsterisk() {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT u.*, p.* FROM User u, u.Phonenumber p');

        $this->assertEqual($q->getQuery(),'SELECT entity.id AS entity__id, entity.name AS entity__name, entity.loginname AS entity__loginname, entity.password AS entity__password, entity.type AS entity__type, entity.created AS entity__created, entity.updated AS entity__updated, entity.email_id AS entity__email_id, phonenumber.id AS phonenumber__id, phonenumber.phonenumber AS phonenumber__phonenumber, phonenumber.entity_id AS phonenumber__entity_id FROM entity LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE (entity.type = 0)');
    }
    public function testMultipleComponentsWithMultipleColumns() {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT u.id, u.name, p.id FROM User u, u.Phonenumber p');

        $this->assertEqual($q->getQuery(),'SELECT entity.id AS entity__id, entity.name AS entity__name, phonenumber.id AS phonenumber__id FROM entity LEFT JOIN phonenumber ON entity.id = phonenumber.entity_id WHERE (entity.type = 0)');
    }

}
?>
