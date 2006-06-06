<?php
require_once("UnitTestCase.php");
class Doctrine_ManagerTestCase extends Doctrine_UnitTestCase {
    public function testGetInstance() {
        $this->assertTrue(Doctrine_Manager::getInstance() instanceOf Doctrine_Manager);
    }
    public function testOpenSession() {
        $this->assertTrue($this->session instanceOf Doctrine_Session);
    }
    public function testGetIterator() {
        $this->assertTrue($this->manager->getIterator() instanceof ArrayIterator);
    }
    public function testCount() {
        $this->assertEqual(count($this->manager),1);
    }
    public function testGetCurrentSession() {
        $this->assertEqual($this->manager->getCurrentSession(), $this->session);
    }
    public function testGetSessions() {
        $this->assertEqual(count($this->manager->getSessions()),1);
    }
    public function prepareData() { }
    public function prepareTables() { }
}
?>
