<?php
class Doctrine_Query_Select_TestCase extends Doctrine_UnitTestCase {
    public function prepareTables() { }
    public function testAggregateFunctionWithDistinctKeyword() {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT COUNT(DISTINCT u.name) FROM User u');

        $this->assertEqual($q->getQuery(), 'SELECT COUNT(DISTINCT e.name) AS e__0 FROM entity e WHERE (e.type = 0)');
    }

    public function testAggregateFunction() {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT COUNT(u.id) FROM User u');

        $this->assertEqual($q->getQuery(), 'SELECT COUNT(e.id) AS e__0 FROM entity e WHERE (e.type = 0)');
    }

    public function testMultipleAggregateFunctions() {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT MAX(u.id), MIN(u.name) FROM User u');

        $this->assertEqual($q->getQuery(), 'SELECT MAX(e.id) AS e__0, MIN(e.name) AS e__1 FROM entity e WHERE (e.type = 0)');
    }
    public function testMultipleAggregateFunctionsWithMultipleComponents() {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT MAX(u.id), MIN(u.name), COUNT(p.id) FROM User u, u.Phonenumber p');

        $this->assertEqual($q->getQuery(), 'SELECT MAX(e.id) AS e__0, MIN(e.name) AS e__1, COUNT(p.id) AS p__2 FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0)');
    }
    public function testEmptySelectPart() {
        $q = new Doctrine_Query();
        
        try {
            $q->select();
            
            $this->fail();
        } catch(Doctrine_Query_Exception $e) {
            $this->pass();
        }
    }
    public function testUnknownAggregateFunction() {
        $q = new Doctrine_Query();
        
        try {
            $q->parseQuery('SELECT UNKNOWN(u.id) FROM User');
            $this->fail();
        } catch(Doctrine_Query_Exception $e) {
            $this->pass();
        }
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

    public function testAggregateFunctionValueHydrationWithAliases() {

        $q = new Doctrine_Query();

        $q->parseQuery('SELECT u.id, COUNT(p.id) count FROM User u, u.Phonenumber p GROUP BY u.id');

        $users = $q->execute();
        
        $this->assertEqual($users[0]->Phonenumber->getAggregateValue('count'), 1);
        $this->assertEqual($users[1]->Phonenumber->getAggregateValue('count'), 3);
        $this->assertEqual($users[2]->Phonenumber->getAggregateValue('count'), 1);
        $this->assertEqual($users[3]->Phonenumber->getAggregateValue('count'), 1);
        $this->assertEqual($users[4]->Phonenumber->getAggregateValue('count'), 3);
    }
    public function testMultipleAggregateFunctionValueHydrationWithAliases() {

        $q = new Doctrine_Query();

        $q->parseQuery('SELECT u.id, COUNT(p.id) count, MAX(p.phonenumber) max FROM User u, u.Phonenumber p GROUP BY u.id');

        $users = $q->execute();

        $this->assertEqual($users[0]->Phonenumber->getAggregateValue('count'), 1);
        $this->assertEqual($users[1]->Phonenumber->getAggregateValue('count'), 3);
        $this->assertEqual($users[2]->Phonenumber->getAggregateValue('count'), 1);
        $this->assertEqual($users[3]->Phonenumber->getAggregateValue('count'), 1);
        $this->assertEqual($users[4]->Phonenumber->getAggregateValue('count'), 3);

        $this->assertEqual($users[0]->Phonenumber->getAggregateValue('max'), '123 123');
        $this->assertEqual($users[1]->Phonenumber->getAggregateValue('max'), '789 789');
        $this->assertEqual($users[2]->Phonenumber->getAggregateValue('max'), '123 123');
        $this->assertEqual($users[3]->Phonenumber->getAggregateValue('max'), '111 222 333');
        $this->assertEqual($users[4]->Phonenumber->getAggregateValue('max'), '444 555');
    }
    public function testSingleComponentWithAsterisk() {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT u.* FROM User u');

        $this->assertEqual($q->getQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.type = 0)');
    }
    public function testSingleComponentWithMultipleColumns() {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT u.name, u.type FROM User u'); 
        
        $this->assertEqual($q->getQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.type AS e__type FROM entity e WHERE (e.type = 0)');
    }
    public function testMultipleComponentsWithAsterisk() {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT u.*, p.* FROM User u, u.Phonenumber p');

        $this->assertEqual($q->getQuery(),'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, p.id AS p__id, p.phonenumber AS p__phonenumber, p.entity_id AS p__entity_id FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0)');
    }
    public function testMultipleComponentsWithMultipleColumns() {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT u.id, u.name, p.id FROM User u, u.Phonenumber p');

        $this->assertEqual($q->getQuery(),'SELECT e.id AS e__id, e.name AS e__name, p.id AS p__id FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0)');
    }

}
?>
