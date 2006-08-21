<?php
class Doctrine_RawSql_TestCase extends Doctrine_UnitTestCase {
    public function testQueryParser() {
        $sql = "SELECT {p.*} FROM photos p";
        $query = new Doctrine_RawSql($this->connection);
        $query->parseQuery($sql);
        
        $this->assertEqual($query->from, array('photos p'));


        $sql = "SELECT {p.*} FROM (SELECT p.* FROM photos p LEFT JOIN photos_tags t ON t.photo_id = p.id WHERE t.tag_id = 65) p LEFT JOIN photos_tags t ON t.photo_id = p.id WHERE p.can_see = -1 AND t.tag_id = 62 LIMIT 200";
        $query->parseQuery($sql);

        $this->assertEqual($query->from, array("(SELECT p.* FROM photos p LEFT JOIN photos_tags t ON t.photo_id = p.id WHERE t.tag_id = 65) p LEFT JOIN photos_tags t ON t.photo_id = p.id"));
        $this->assertEqual($query->where, array('p.can_see = -1 AND t.tag_id = 62'));
        $this->assertEqual($query->limit, array(200));
    }

    public function testAsteriskOperator() {
        // Selecting with *

        $query = new Doctrine_RawSql($this->connection);
        $query->parseQuery("SELECT {entity.*} FROM entity");
        $fields = $query->getFields();

        $this->assertEqual($fields, array("entity.*"));

        $query->addComponent("entity", "Entity");

        $coll = $query->execute();

        $this->assertEqual($coll->count(), 11);
    }

    public function testLazyPropertyLoading() {
        $query = new Doctrine_RawSql($this->connection);
        $this->connection->clear();

        // selecting proxy objects (lazy property loading)

        $query->parseQuery("SELECT {entity.name}, {entity.id} FROM entity");
        $fields = $query->getFields();

        $this->assertEqual($fields, array("entity.name", "entity.id"));
        $query->addComponent("entity", "Entity");

        $coll = $query->execute();

        $this->assertEqual($coll->count(), 11);

        $this->assertEqual($coll[0]->getState(), Doctrine_Record::STATE_PROXY);
        $this->assertEqual($coll[3]->getState(), Doctrine_Record::STATE_PROXY); 
    }

    public function testSmartMapping() {
        $query = new Doctrine_RawSql($this->connection);
        // smart component mapping (no need for additional addComponent call
        
        $query->parseQuery("SELECT {entity.name}, {entity.id} FROM entity");
        $fields = $query->getFields();

        $this->assertEqual($fields, array("entity.name", "entity.id"));

        $coll = $query->execute();

        $this->assertEqual($coll->count(), 11);

        $this->assertEqual($coll[0]->getState(), Doctrine_Record::STATE_PROXY);
        $this->assertEqual($coll[3]->getState(), Doctrine_Record::STATE_PROXY);
    }

    public function testMultipleComponents() {
        $query = new Doctrine_RawSql($this->connection);
        // multi component fetching

        $query->parseQuery("SELECT {entity.name}, {entity.id}, {phonenumber.*} FROM entity LEFT JOIN phonenumber ON phonenumber.entity_id = entity.id");

        $query->addComponent("entity", "Entity");
        $query->addComponent("phonenumber", "Entity.Phonenumber");
        
        $coll = $query->execute();
        $this->assertEqual($coll->count(), 11);

        $count = $this->dbh->count();
        
        $coll[4]->Phonenumber[0]->phonenumber;
        $this->assertEqual($count, $this->dbh->count());

        $coll[5]->Phonenumber[0]->phonenumber;
        $this->assertEqual($count, $this->dbh->count());
    }
    public function testPrimaryKeySelectForcing() {
        // forcing the select of primary key fields
        
        $query = new Doctrine_RawSql($this->connection);

        $query->parseQuery("SELECT {entity.name} FROM entity");
        
        $coll = $query->execute();
        
        $this->assertEqual($coll->count(), 11);
        $this->assertTrue(is_numeric($coll[0]->id));
        $this->assertTrue(is_numeric($coll[3]->id));
        $this->assertTrue(is_numeric($coll[7]->id));
    }
    public function testMethodOverloading() {
        $query = new Doctrine_RawSql($this->connection);
        $query->select('{entity.name}')->from('entity');
        $query->addComponent("entity", "User");
        $coll = $query->execute();

        $this->assertEqual($coll->count(), 8);
        $this->assertTrue(is_numeric($coll[0]->id));
        $this->assertTrue(is_numeric($coll[3]->id));
        $this->assertTrue(is_numeric($coll[7]->id));
    }
    public function testColumnAggregationInheritance() {
        // forcing the select of primary key fields
        
        $query = new Doctrine_RawSql($this->connection);

        $query->parseQuery("SELECT {entity.name} FROM entity");
        $query->addComponent("entity", "User");
        $coll = $query->execute();

        $this->assertEqual($coll->count(), 8);
        $this->assertTrue(is_numeric($coll[0]->id));
        $this->assertTrue(is_numeric($coll[3]->id));
        $this->assertTrue(is_numeric($coll[7]->id));
    }
}
?>
