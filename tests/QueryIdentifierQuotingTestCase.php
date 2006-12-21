<?php
class Doctrine_Query_IdentifierQuoting_TestCase extends Doctrine_UnitTestCase {
    public function prepareTables() { }
    public function prepareData() { }
    public function testQuerySupportsIdentifierQuoting() {
        $this->connection->setAttribute(Doctrine::ATTR_QUOTE_IDENTIFIER, true);

        $q = new Doctrine_Query();
        
        $q->parseQuery('SELECT MAX(u.id), MIN(u.name) FROM User u');

        $this->assertEqual($q->getQuery(), 'SELECT MAX(e.id) AS e__0, MIN(e.name) AS e__1 FROM "entity" e WHERE (e.type = 0)');

    }
    public function testQuerySupportsIdentifierQuotingWithJoins() {
        $q = new Doctrine_Query();
        
        $q->parseQuery('SELECT u.name FROM User u LEFT JOIN u.Phonenumber p');

        $this->assertEqual($q->getQuery(), 'SELECT e.id AS e__id, e.name AS e__name FROM "entity" e LEFT JOIN "phonenumber" p ON e.id = p.entity_id WHERE (e.type = 0)');
        

    }
    
    public function testLimitSubqueryAlgorithmSupportsIdentifierQuoting() {
        $q = new Doctrine_Query();
        
        $q->parseQuery('SELECT u.name FROM User u INNER JOIN u.Phonenumber p')->limit(5); 
        
        $this->assertEqual($q->getQuery(), 'SELECT e.id AS e__id, e.name AS e__name FROM "entity" e INNER JOIN "phonenumber" p ON e.id = p.entity_id WHERE e.id IN (SELECT DISTINCT e2.id FROM "entity" e2 INNER JOIN "phonenumber" p2 ON e2.id = p2.entity_id WHERE (e2.type = 0) LIMIT 5) AND (e.type = 0)');
        
        $this->connection->setAttribute(Doctrine::ATTR_QUOTE_IDENTIFIER, false);
    }
}
