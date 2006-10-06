<?php
class Doctrine_Query_Subquery_TestCase extends Doctrine_UnitTestCase {
    public function testSubqueryWithWherePartAndInExpression() {
        $q = new Doctrine_Query();
        $q->from('User')->where("User.id NOT IN (FROM User(id) WHERE User.name = 'zYne')");

        $this->assertEqual($q->getQuery(),
        "SELECT entity.id AS entity__id, entity.name AS entity__name, entity.loginname AS entity__loginname, entity.password AS entity__password, entity.type AS entity__type, entity.created AS entity__created, entity.updated AS entity__updated, entity.email_id AS entity__email_id FROM entity WHERE entity.id NOT IN (SELECT entity.id AS entity__id FROM entity WHERE entity.name = 'zYne' AND (entity.type = 0)) AND (entity.type = 0)");

        $users = $q->execute();

        $this->assertEqual($users->count(), 7);
        $this->assertEqual($users[0]->name, 'Arnold Schwarzenegger');
    }
}
?>
