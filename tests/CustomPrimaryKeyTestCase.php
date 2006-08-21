<?php
require_once("UnitTestCase.php");

class Doctrine_CustomPrimaryKeyTestCase extends Doctrine_UnitTestCase {
    public function prepareData() { }
    
    public function prepareTables() { 
        $this->tables = array("CustomPK");
    }
    public function testOperations() {
        $c = new CustomPK();
        $this->assertTrue($c instanceof Doctrine_Record);

        $c->name = "custom pk test";
        $this->assertEqual($c->getID(), array());
        
        $c->save();
        $this->assertEqual($c->getID(), array("uid" => 1));
        $this->connection->clear();
        
        $c = $this->connection->getTable('CustomPK')->find(1);
    
        $this->assertEqual($c->getID(), array("uid" => 1));
    }
}
?>
