<?php
class Transaction_TestLogger implements Doctrine_Overloadable {
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
}

class Doctrine_Connection_Transaction_TestCase extends Doctrine_UnitTestCase {
    public function prepareData() { }
    public function testInsert() {
        $count = count($this->dbh);


        $listener = new Transaction_TestLogger();

        $user = new User();
        $user->getTable()->getConnection()->setListener($listener);

        $this->connection->beginTransaction();

        $user->name = 'John';

        $user->save();

        $this->assertEqual($listener->pop(), 'onSave');
        $this->assertEqual($listener->pop(), 'onInsert');
        $this->assertEqual($listener->pop(), 'onPreInsert');
        $this->assertEqual($listener->pop(), 'onPreSave');
        $this->assertEqual($listener->pop(), 'onSetProperty');
        $this->assertEqual($listener->pop(), 'onTransactionBegin');
        $this->assertEqual($listener->pop(), 'onPreTransactionBegin');

        $this->assertEqual($user->id, 1);
        
        $this->assertTrue($count < count($this->dbh));

        $this->connection->commit();

        $this->assertEqual($listener->pop(), 'onTransactionCommit');
        $this->assertEqual($listener->pop(), 'onPreTransactionCommit');
    }
    public function testInsertMultiple() {
        $count = count($this->dbh);


        $listener = new Transaction_TestLogger();

        $users = new Doctrine_Collection('User');
        $users->getTable()->getConnection()->setListener($listener);

        $this->connection->beginTransaction();

        $users[0]->name = 'Arnold';
        $users[1]->name = 'Vincent';

        $users[0]->save();
        $users[1]->save();


        $this->assertEqual($listener->pop(), 'onSave');
        $this->assertEqual($listener->pop(), 'onInsert');
        $this->assertEqual($listener->pop(), 'onPreInsert');
        $this->assertEqual($listener->pop(), 'onPreSave');
        $this->assertEqual($listener->pop(), 'onSave');
        $this->assertEqual($listener->pop(), 'onInsert');
        $this->assertEqual($listener->pop(), 'onPreInsert');
        $this->assertEqual($listener->pop(), 'onPreSave');

        $this->assertEqual($users[0]->id, 2);

        $this->assertEqual($users[1]->id, 3);
        
        $this->assertTrue($count < count($this->dbh));

        $this->connection->commit();

        $this->assertEqual($listener->pop(), 'onTransactionCommit');
        $this->assertEqual($listener->pop(), 'onPreTransactionCommit');
    }
    public function testUpdate() {
        $count = count($this->dbh);


        $user = $this->connection->getTable('User')->find(1);
        
        $listener = new Transaction_TestLogger();
        $user->getTable()->getConnection()->setListener($listener);
        $this->connection->beginTransaction();

        $user->name = 'Jack';

        $user->save();

        $this->assertEqual($listener->pop(), 'onSave');
        $this->assertEqual($listener->pop(), 'onUpdate');
        $this->assertEqual($listener->pop(), 'onPreUpdate');
        $this->assertEqual($listener->pop(), 'onPreSave');
        $this->assertEqual($listener->pop(), 'onSetProperty');
        $this->assertEqual($listener->pop(), 'onTransactionBegin');
        $this->assertEqual($listener->pop(), 'onPreTransactionBegin');

        $this->assertEqual($user->id, 1);
        
        $this->assertTrue($count < count($this->dbh));

        $this->connection->commit();

        $this->assertEqual($listener->pop(), 'onTransactionCommit');
        $this->assertEqual($listener->pop(), 'onPreTransactionCommit');
    }
    public function testUpdateMultiple() {
        $count = count($this->dbh);

        $listener = new Transaction_TestLogger();

        $users = $this->connection->query('FROM User');
        $users->getTable()->getConnection()->setListener($listener);

        $this->connection->beginTransaction();

        $users[1]->name = 'Arnold';
        $users[2]->name = 'Vincent';

        $users[1]->save();
        $users[2]->save();


        $this->assertEqual($listener->pop(), 'onSave');
        $this->assertEqual($listener->pop(), 'onUpdate');
        $this->assertEqual($listener->pop(), 'onPreUpdate');
        $this->assertEqual($listener->pop(), 'onPreSave');
        $this->assertEqual($listener->pop(), 'onSave');
        $this->assertEqual($listener->pop(), 'onUpdate');
        $this->assertEqual($listener->pop(), 'onPreUpdate');
        $this->assertEqual($listener->pop(), 'onPreSave');

        $this->assertEqual($users[1]->id, 2);

        $this->assertEqual($users[2]->id, 3);
        
        $this->assertTrue($count < count($this->dbh));

        $this->connection->commit();

        $this->assertEqual($listener->pop(), 'onTransactionCommit');
        $this->assertEqual($listener->pop(), 'onPreTransactionCommit');
    }
    public function testDelete() {
        $count = count($this->dbh);

        $listener = new Transaction_TestLogger();
        $listener->clear();
        $users = $this->connection->query('FROM User');
        $users->getTable()->getConnection()->setListener($listener);

        $this->connection->beginTransaction();

        $users->delete();

        $this->assertEqual($listener->pop(), 'onDelete');

        $this->assertTrue($count, count($this->dbh));

        $this->connection->commit();

        $this->assertTrue(($count + 1), count($this->dbh));

        $this->assertEqual($listener->pop(), 'onTransactionCommit');
        $this->assertEqual($listener->pop(), 'onPreTransactionCommit');
    }
}
