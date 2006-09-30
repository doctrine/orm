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
class Doctrine_EventListener_TestLogger implements Doctrine_Overloadable, Countable {
    private $messages = array();

    public function __call($m, $a) {

        $this->messages[] = $m;
    }
    public function pop() {
        return array_pop($this->messages);
    }
    public function clear() {
        $this->messages = array();
    }
    public function getAll() {
        return $this->messages;
    }
    public function count() {
        return count($this->messages);
    }
}

class Doctrine_EventListenerTestCase extends Doctrine_UnitTestCase {
    private $logger;

    public function testAccessorInvoker() {
        $e = new EventListenerTest;
        $e->name = "something";
        $e->password = "123";


        $this->assertEqual($e->get('name'), 'SOMETHING');
        // test repeated calls
        $this->assertEqual($e->get('name'), 'SOMETHING');
        $this->assertEqual($e->id, null);
        $this->assertEqual($e->rawGet('name'), 'something');
        $this->assertEqual($e->password, '202cb962ac59075b964b07152d234b70');

        $e->save();

        $this->assertEqual($e->id, 1);
        $this->assertEqual($e->name, 'SOMETHING');
        $this->assertEqual($e->rawGet('name'), 'something');
        $this->assertEqual($e->password, '202cb962ac59075b964b07152d234b70');

        $this->connection->clear();

        $e->refresh();

        $this->assertEqual($e->id, 1);
        $this->assertEqual($e->name, 'SOMETHING');
        $this->assertEqual($e->rawGet('name'), 'something');
        $this->assertEqual($e->password, '202cb962ac59075b964b07152d234b70');

        $this->connection->clear();

        $e = $e->getTable()->find($e->id);

        $this->assertEqual($e->id, 1);
        $this->assertEqual($e->name, 'SOMETHING');
        $this->assertEqual($e->rawGet('name'), 'something');
        $this->assertEqual($e->password, '202cb962ac59075b964b07152d234b70');

    }
    public function testSetListener() {
        $this->logger = new Doctrine_EventListener_TestLogger();
    
        $e = new EventListenerTest;
        
        $e->getTable()->setListener($this->logger);

        $this->assertEqual($e->getTable()->getListener(), $this->logger);
    }
    public function testOnLoad() {
        $this->logger->clear();
        $this->assertEqual($this->connection->getTable('EventListenerTest')->getListener(), $this->logger);
        $this->connection->clear();

        $e = $this->connection->getTable('EventListenerTest')->find(1);

        $this->assertEqual($e->getTable()->getListener(), $this->logger);

        $this->assertEqual($this->logger->pop(), 'onLoad');
        $this->assertEqual($this->logger->pop(), 'onPreLoad');
    }

    public function testOnCreate() {
        $e = new EventListenerTest;
        

        $e->setListener($this->logger);
        $this->logger->clear();
        $e = new EventListenerTest;

        $this->assertEqual($this->logger->pop(), 'onCreate');
        $this->assertEqual($this->logger->pop(), 'onPreCreate');
        $this->assertEqual($this->logger->count(), 0);
    }
    public function testOnSleepAndOnWakeUp() {
        $e = new EventListenerTest;

        $this->logger->clear();

        $s = serialize($e);

        $this->assertEqual($this->logger->pop(), 'onSleep');
        $this->assertEqual($this->logger->count(), 0);

        $e = unserialize($s);

        $this->assertEqual($this->logger->pop(), 'onWakeUp');
        $this->assertEqual($this->logger->count(), 0);
    }
    public function testTransaction() {
        $e = new EventListenerTest();
        $e->name = "test 1";
        
        $this->logger->clear();

        $e->save();

        $this->assertEqual($this->logger->pop(), 'onSave');
        $this->assertEqual($this->logger->pop(), 'onInsert');
        $this->assertEqual($this->logger->pop(), 'onPreInsert');
        $this->assertEqual($this->logger->pop(), 'onPreSave');
        
        $e->name = "test 2";

        $e->save();

        $this->assertEqual($this->logger->pop(), 'onSave');
        $this->assertEqual($this->logger->pop(), 'onUpdate');
        $this->assertEqual($this->logger->pop(), 'onPreUpdate');
        $this->assertEqual($this->logger->pop(), 'onPreSave');
        
        $this->logger->clear();

        $e->delete();

        $this->assertEqual($this->logger->pop(), 'onDelete');
        $this->assertEqual($this->logger->pop(), 'onPreDelete');
    }
    public function testTransactionWithConnectionListener() {
        $e = new EventListenerTest();
        $e->getTable()->getConnection()->setListener($this->logger);
        
        $e->name = "test 2";
        
        $this->logger->clear();
        
        $e->save();

        $this->assertEqual($this->logger->pop(), 'onTransactionCommit');
        $this->assertEqual($this->logger->pop(), 'onPreTransactionCommit');
        $this->assertEqual($this->logger->pop(), 'onSave');
        $this->assertEqual($this->logger->pop(), 'onInsert');
        $this->assertEqual($this->logger->pop(), 'onPreInsert');
        $this->assertEqual($this->logger->pop(), 'onPreSave');

        $this->assertEqual($this->logger->pop(), 'onTransactionBegin');
        $this->assertEqual($this->logger->pop(), 'onPreTransactionBegin');

        $e->name = "test 1";

        $e->save();

        $this->assertEqual($this->logger->pop(), 'onTransactionCommit');
        $this->assertEqual($this->logger->pop(), 'onPreTransactionCommit');
        $this->assertEqual($this->logger->pop(), 'onSave');
        $this->assertEqual($this->logger->pop(), 'onUpdate');
        $this->assertEqual($this->logger->pop(), 'onPreUpdate');
        $this->assertEqual($this->logger->pop(), 'onPreSave');

        $this->assertEqual($this->logger->pop(), 'onTransactionBegin');
        $this->assertEqual($this->logger->pop(), 'onPreTransactionBegin');

        $this->logger->clear();

        $e->delete();

        $this->assertEqual($this->logger->pop(), 'onTransactionCommit');
        $this->assertEqual($this->logger->pop(), 'onPreTransactionCommit');
        $this->assertEqual($this->logger->pop(), 'onDelete');

        $this->assertEqual($this->logger->pop(), 'onPreDelete');
        $this->assertEqual($this->logger->pop(), 'onTransactionBegin');
        $this->assertEqual($this->logger->pop(), 'onPreTransactionBegin');
    
        $this->connection->setListener(new Doctrine_EventListener());
    }



    public function prepareData() { }
    public function prepareTables() {
        $this->tables = array('EventListenerTest');
        parent::prepareTables();
    }
}
?>
