<?php
require_once("UnitTestCase.php");
class EventListenerClainTest extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("name", "string", 100);
    }
    public function setUp() {
        $chain = new Doctrine_EventListener_Chain();
        $chain->add(new Doctrine_EventListener_TestA());
        $chain->add(new Doctrine_EventListener_TestB());
        $this->setAttribute(Doctrine::ATTR_LISTENER, $chain);
    }
}

class Doctrine_EventListener_TestA extends Doctrine_EventListener {
  public function onGetProperty(Doctrine_Record $record, $property, $value) {
    return $value . 'TestA';
  }
}
class Doctrine_EventListener_TestB extends Doctrine_EventListener {
  public function onGetProperty(Doctrine_Record $record, $property, $value) {
    return $value . 'TestB';
  }
}

class Doctrine_EventListenerChainTestCase extends Doctrine_UnitTestCase {
    public function testEvents() {
        $connection = $this->manager->openConnection(Doctrine_DB::getConn("sqlite::memory:"));
        $debug = $this->listener->getMessages();
        $last = end($debug);
        $this->assertTrue($last->getObject() instanceof Doctrine_Connection);
        $this->assertTrue($last->getCode() == Doctrine_EventListener_Debugger::EVENT_OPEN);
    }
    public function testAccessorInvokerChain() {
        $e = new EventListenerTest;
        $e->name = "something";


        $this->assertEqual($e->get('name'), 'SOMETHING');
        // test repeated calls
        $this->assertEqual($e->get('name'), 'SOMETHING');
        $this->assertEqual($e->id, null);
        $this->assertEqual($e->rawGet('name'), 'something');

        $e->save();

        $this->assertEqual($e->id, 1);
        $this->assertEqual($e->name, 'SOMETHING');
        $this->assertEqual($e->rawGet('name'), 'something');

        $this->connection->clear();

        $e->refresh();

        $this->assertEqual($e->id, 1);
        $this->assertEqual($e->name, 'SOMETHING');
        $this->assertEqual($e->rawGet('name'), 'something');

        $this->connection->clear();

        $e = $e->getTable()->find($e->id);

        $this->assertEqual($e->id, 1);
        $this->assertEqual($e->name, 'SOMETHING');
        $this->assertEqual($e->rawGet('name'), 'something');
    }
    public function prepareData() { }
    public function prepareTables() {
        $this->tables = array('EventListenerChainTest');
        parent::prepareTables();
    }
}
?>
