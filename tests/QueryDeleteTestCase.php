<?php
class Doctrine_Query_Delete_TestCase extends Doctrine_UnitTestCase {
    public function testDeleteAllWithColumnAggregationInheritance() {
        $q = new Doctrine_Query();

        $q->parseQuery('DELETE FROM User');

        $this->assertEqual($q->getQuery(), 'DELETE FROM entity WHERE (entity.type = 0)');

        $q = new Doctrine_Query();

        $q->delete()->from('User');
        
        $this->assertEqual($q->getQuery(), 'DELETE FROM entity WHERE (entity.type = 0)');
    }
    public function testDeleteAll() {
        $q = new Doctrine_Query();

        $q->parseQuery('DELETE FROM Entity');

        $this->assertEqual($q->getQuery(), 'DELETE FROM entity');
        
        $q = new Doctrine_Query();

        $q->delete()->from('Entity');
        
        $this->assertEqual($q->getQuery(), 'DELETE FROM entity');
    }
    public function testDeleteWithCondition() {
        $q = new Doctrine_Query();

        $q->parseQuery('DELETE FROM Entity WHERE id = 3');

        $this->assertEqual($q->getQuery(), 'DELETE FROM entity WHERE id = 3');
        
        $q = new Doctrine_Query();

        $q->delete()->from('Entity')->where('id = 3');
        
        $this->assertEqual($q->getQuery(), 'DELETE FROM entity WHERE id = 3');
    }
    public function testDeleteWithLimit() {
        $q = new Doctrine_Query();

        $q->parseQuery('DELETE FROM Entity LIMIT 20');

        $this->assertEqual($q->getQuery(), 'DELETE FROM entity LIMIT 20');
        
        $q = new Doctrine_Query();

        $q->delete()->from('Entity')->limit(20);
        
        $this->assertEqual($q->getQuery(), 'DELETE FROM entity LIMIT 20');
    }
    public function testDeleteWithLimitAndOffset() {
        $q = new Doctrine_Query();

        $q->parseQuery('DELETE FROM Entity LIMIT 10 OFFSET 20');

        $this->assertEqual($q->getQuery(), 'DELETE FROM entity LIMIT 10 OFFSET 20');

        $q = new Doctrine_Query();

        $q->delete()->from('Entity')->limit(10)->offset(20);
        
        $this->assertEqual($q->getQuery(), 'DELETE FROM entity LIMIT 10 OFFSET 20');
    }
}
?>
