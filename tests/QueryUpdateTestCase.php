<?php
class Doctrine_Query_Update_TestCase extends Doctrine_UnitTestCase {
    public function testUpdateAllWithColumnAggregationInheritance() {
        $q = new Doctrine_Query();

        $q->parseQuery("UPDATE User u SET u.name = 'someone'");

        $this->assertEqual($q->getQuery(), "UPDATE entity SET e.name = 'someone' WHERE (e.type = 0)");

        $q = new Doctrine_Query();

        $q->update('User u')->set('u.name', 'someone');

        $this->assertEqual($q->getQuery(), "UPDATE entity SET e.name = 'someone' WHERE (e.type = 0)");
    }
}
?>
