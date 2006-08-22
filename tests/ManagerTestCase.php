<?php
require_once("UnitTestCase.php");
class Doctrine_ManagerTestCase extends Doctrine_UnitTestCase {
    public function testGetInstance() {
        $this->assertTrue(Doctrine_Manager::getInstance() instanceOf Doctrine_Manager);
    }
    public function testOpenConnection() {
        $this->assertTrue($this->connection instanceOf Doctrine_Connection);
    }
    public function testGetIterator() {
        $this->assertTrue($this->manager->getIterator() instanceof ArrayIterator);
    }
    public function testCount() {
        $this->assertEqual(count($this->manager),1);
    }
    public function testGetCurrentConnection() {
        $this->assertEqual($this->manager->getCurrentConnection(), $this->connection);
    }
    public function testGetConnections() {
        $this->assertEqual(count($this->manager->getConnections()),1);
    }
    public function testClassifyTableize() {
        $name = "Forum_Category";
        $this->assertEqual(Doctrine::tableize($name), "forum__category");
        $this->assertEqual(Doctrine::classify(Doctrine::tableize($name)), $name);
        
        
    }
    public function prepareData() { }
    public function prepareTables() { }
}
?>
