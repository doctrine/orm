<?php
class Doctrine_RawSql_TestCase extends Doctrine_UnitTestCase {
    public function testAsteriskOperator() {
        // Selecting with *

        $query = new Doctrine_RawSql($this->session);
        $query->parseQuery("SELECT {entity.*} FROM entity");
        $fields = $query->getFields();

        $this->assertEqual($fields, array("entity.*"));

        $query->addComponent("entity", "Entity");

        $coll = $query->execute();

        $this->assertEqual($coll->count(), 11);
    }

    public function testLazyPropertyLoading() {
        $query = new Doctrine_RawSql($this->session);
        $this->session->clear();

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
        $query = new Doctrine_RawSql($this->session);
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
        $query = new Doctrine_RawSql($this->session);
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
        
        $query = new Doctrine_RawSql($this->session);

        $query->parseQuery("SELECT {entity.name} FROM entity");
        
        $coll = $query->execute();
        
        $this->assertEqual($coll->count(), 11);
        $this->assertTrue(is_numeric($coll[0]->id));
        $this->assertTrue(is_numeric($coll[3]->id));
        $this->assertTrue(is_numeric($coll[7]->id));
    }
    public function testMethodOverloading() {
        $query = new Doctrine_RawSql($this->session);
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
        
        $query = new Doctrine_RawSql($this->session);

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
