<?php
class Doctrine_Query_Condition_TestCase extends Doctrine_UnitTestCase {
    public function prepareData() { }
    public function prepareTables() { }

    public function testBracktExplode() {
        $str   = "item OR item || item";
        $parts = Doctrine_Query::bracketExplode($str, array(' \|\| ', ' OR '), "(", ")");

        $this->assertEqual($parts, array('item','item','item'));

    }
    public function testConditionParser() {
        $query = new Doctrine_Query($this->connection);

        $query->from("User(id)")->where("User.name LIKE 'z%' || User.name LIKE 's%'");

        $sql = "SELECT entity.id AS entity__id FROM entity WHERE (entity.name LIKE 'z%' OR entity.name LIKE 's%') AND (entity.type = 0)";
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(User.name LIKE 'z%') || (User.name LIKE 's%')");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("((User.name LIKE 'z%') || (User.name LIKE 's%'))");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(((User.name LIKE 'z%') || (User.name LIKE 's%')))");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(((User.name LIKE 'z%') || User.name LIKE 's%'))");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(User.name LIKE 'z%') || User.name LIKE 's%' && User.name LIKE 'a%'");

        $sql = "SELECT entity.id AS entity__id FROM entity WHERE ((entity.name LIKE 'z%' OR entity.name LIKE 's%') AND entity.name LIKE 'a%') AND (entity.type = 0)";

        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(((User.name LIKE 'z%') || User.name LIKE 's%')) && User.name LIKE 'a%'");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("((((User.name LIKE 'z%') || User.name LIKE 's%')) && User.name LIKE 'a%')");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(((((User.name LIKE 'z%') || User.name LIKE 's%')) && User.name LIKE 'a%'))");
        $this->assertEqual($query->getQuery(), $sql);

    }

    public function testConditionParser2() {
        $query = new Doctrine_Query($this->connection);

        $query->from("User(id)")->where("User.name LIKE 'z%' || User.name LIKE 's%'");

        $sql = "SELECT entity.id AS entity__id FROM entity WHERE (entity.name LIKE 'z%' OR entity.name LIKE 's%') AND (entity.type = 0)";
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(User.name LIKE 'z%') OR (User.name LIKE 's%')");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("((User.name LIKE 'z%') OR (User.name LIKE 's%'))");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(((User.name LIKE 'z%') OR (User.name LIKE 's%')))");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(((User.name LIKE 'z%') OR User.name LIKE 's%'))");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(User.name LIKE 'z%') OR User.name LIKE 's%' AND User.name LIKE 'a%'");

        $sql = "SELECT entity.id AS entity__id FROM entity WHERE ((entity.name LIKE 'z%' OR entity.name LIKE 's%') AND entity.name LIKE 'a%') AND (entity.type = 0)";

        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(((User.name LIKE 'z%') OR User.name LIKE 's%')) AND User.name LIKE 'a%'");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("((((User.name LIKE 'z%') OR User.name LIKE 's%')) AND User.name LIKE 'a%')");
        $this->assertEqual($query->getQuery(), $sql);

        $query->where("(((((User.name LIKE 'z%') OR User.name LIKE 's%')) AND User.name LIKE 'a%'))");
        $this->assertEqual($query->getQuery(), $sql);
    }
}
?>
