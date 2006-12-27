<?php
class Doctrine_Query_Subquery_TestCase extends Doctrine_UnitTestCase {
    public function testSubqueryWithWherePartAndInExpression() {
        $q = new Doctrine_Query();
        $q->from('User')->where("User.id NOT IN (FROM User(id) WHERE User.name = 'zYne')");

        $this->assertEqual($q->getQuery(),
        "SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE e.id NOT IN (SELECT e.id AS e__id FROM entity e WHERE e.name = 'zYne' AND (e.type = 0)) AND (e.type = 0)");

        $users = $q->execute();

        $this->assertEqual($users->count(), 7);
        $this->assertEqual($users[0]->name, 'Arnold Schwarzenegger');
    }
}
?>
