<?php
require_once("UnitTestCase.class.php");

class Doctrine_EventListenerTestCase extends Doctrine_UnitTestCase {
    public function testEvents() {
        $session = $this->manager->openSession(Doctrine_DB::getConnection());
        $debug = $this->listener->getMessages();
        $last = end($debug);
        $this->assertTrue($last->getObject() instanceof Doctrine_Session);
        $this->assertTrue($last->getCode() == Doctrine_EventListener_Debugger::EVENT_OPEN);
    }
    public function prepareData() { }
    public function prepareTables() { }
}
?>
