<?php
require_once("UnitTestCase.php");
class EventListenerTest extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("name", "string", 100);
        $this->hasColumn("password", "string", 8);
    }
    public function setUp() {
        $this->setAttribute(Doctrine::ATTR_LISTENER, new Doctrine_EventListener_AccessorInvoker());
    }
    public function getName($name) {
        return strtoupper($name);
    }
    public function setPassword($password) {
        return md5($password);
    }
}

class Doctrine_EventListenerTestCase extends Doctrine_UnitTestCase {
    public function testEvents() {
        $connection = $this->manager->openConnection(Doctrine_DB::getConn("sqlite::memory:"));
        $debug = $this->listener->getMessages();
        $last = end($debug);
        $this->assertTrue($last->getObject() instanceof Doctrine_Connection);
        $this->assertTrue($last->getCode() == Doctrine_EventListener_Debugger::EVENT_OPEN);
    }
    public function testAccessorInvoker() {
        $e = new EventListenerTest;
        $e->name = "something";
        $e->password = "123";


        $this->assertEqual($e->get('name'), 'SOMETHING');         
        // test repeated calls
        $this->assertEqual($e->get('name'), 'SOMETHING');

        $this->assertEqual($e->rawGet('name'), 'something');
        $this->assertEqual($e->password, '202cb962ac59075b964b07152d234b70');

        $e->save();

        $this->assertEqual($e->name, 'SOMETHING');
        $this->assertEqual($e->rawGet('name'), 'something');
        $this->assertEqual($e->password, '202cb962ac59075b964b07152d234b70');

        $this->connection->clear();

        $e->refresh();

        $this->assertEqual($e->name, 'SOMETHING');
        $this->assertEqual($e->rawGet('name'), 'something');
        $this->assertEqual($e->password, '202cb962ac59075b964b07152d234b70');

        $this->connection->clear();

        $e = $e->getTable()->find($e->id);

        $this->assertEqual($e->name, 'SOMETHING');
        $this->assertEqual($e->rawGet('name'), 'something');
        $this->assertEqual($e->password, '202cb962ac59075b964b07152d234b70');
    }
    public function prepareData() { }
    public function prepareTables() {
        $this->tables = array('EventListenerTest');
        parent::prepareTables();
    }
}
?>
