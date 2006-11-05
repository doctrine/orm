<?php
class Doctrine_Query_ShortAliases_TestCase extends Doctrine_UnitTestCase {
    public function testShortAliasesWithSingleComponent() {
        $q = new Doctrine_Query();
        
        $q->select('u.name')->from('User u');

        $this->assertEqual($q->getQuery(), 'SELECT e.id AS e__id, e.name AS e__name FROM entity e WHERE (e.type = 0)');
    }
    public function testShortAliasesWithOneToManyLeftJoin() {
        $q = new Doctrine_Query();
        
        $q->select('u.name, p.id')->from('User u LEFT JOIN u.Phonenumber p');

        $this->assertEqual($q->getQuery(), 'SELECT e.id AS e__id, e.name AS e__name, p.id AS p__id FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0)');

        $users = $q->execute();
        
        $this->assertEqual($users->count(), 8);

    }
}
