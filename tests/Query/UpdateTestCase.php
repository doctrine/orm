<?php
class Doctrine_Query_Update_TestCase extends Doctrine_UnitTestCase {
    public function testUpdateAllWithColumnAggregationInheritance() {
        $q = new Doctrine_Query();

        $q->parseQuery("UPDATE User u SET u.name = 'someone'");

        $this->assertEqual($q->getQuery(), "UPDATE entity SET name = 'someone' WHERE (type = 0)");

        $q = new Doctrine_Query();

        $q->update('User u')->set('u.name', "'someone'");

        $this->assertEqual($q->getQuery(), "UPDATE entity SET name = 'someone' WHERE (type = 0)");
    }
    public function testUpdateWorksWithMultipleColumns() {
        $q = new Doctrine_Query();

        $q->parseQuery("UPDATE User u SET u.name = 'someone', u.email_id = 5");

        $this->assertEqual($q->getQuery(), "UPDATE entity SET name = 'someone', email_id = 5 WHERE (type = 0)");

        $q = new Doctrine_Query();

        $q->update('User u')->set('u.name', "'someone'")->set('u.email_id', 5);

        $this->assertEqual($q->getQuery(), "UPDATE entity SET name = 'someone', email_id = 5 WHERE (type = 0)");
    }
    public function testUpdateSupportsConditions() {
        $q = new Doctrine_Query();

        $q->parseQuery("UPDATE User u SET u.name = 'someone' WHERE u.id = 5");

        $this->assertEqual($q->getQuery(), "UPDATE entity SET name = 'someone' WHERE id = 5 AND (type = 0)");
    }
}
?>
