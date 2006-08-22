<?php
require_once("UnitTestCase.php");

class Doctrine_EventListenerTestCase extends Doctrine_UnitTestCase {
    public function testEvents() {
        $connection = $this->manager->openConnection(Doctrine_DB::getConn("sqlite::memory:"));
        $debug = $this->listener->getMessages();
        $last = end($debug);
        $this->assertTrue($last->getObject() instanceof Doctrine_Connection);
        $this->assertTrue($last->getCode() == Doctrine_EventListener_Debugger::EVENT_OPEN);
    }
    public function prepareData() { }
    public function prepareTables() { }
}
?>
