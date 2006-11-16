<?php
require_once("../draft/DB.php");

class Doctrine_Db_TestLogger implements Doctrine_Overloadable {
    private $messages = array();
    
    public function __call($m, $a) {
        $this->messages[] = $m;
    }
    public function pop() {
        return array_pop($this->messages);
    }
    public function getAll() {
        return $this->messages;
    }
}
class Doctrine_Db_TestValidListener extends Doctrine_Db_EventListener { }
class Doctrine_Db_TestInvalidListener { }

class Doctrine_Db_TestCase extends Doctrine_UnitTestCase {
    protected $dbh;

    public function prepareData() { }
    public function prepareTables() { }
    public function init() { }

    public function testFetchAll() {
        $this->dbh = Doctrine_Db2::getConnection('sqlite::memory:');
        $this->dbh->connect();


        $this->dbh->query('CREATE TABLE entity (id INTEGER, name TEXT)');

        $this->dbh->query("INSERT INTO entity (id, name) VALUES (1, 'zYne')");
        $this->dbh->query("INSERT INTO entity (id, name) VALUES (2, 'John')");

        $a = $this->dbh->fetchAll('SELECT * FROM entity');


        $this->assertEqual($a, array (
                            0 =>
                            array (
                              'id' => '1',
                              'name' => 'zYne',
                            ),
                            1 =>
                            array (
                              'id' => '2',
                              'name' => 'John',
                            ),
                          ));
    }
    public function testFetchOne() {
        $c = $this->dbh->fetchOne('SELECT COUNT(1) FROM entity');
        
        $this->assertEqual($c, 2);
        
        $c = $this->dbh->fetchOne('SELECT COUNT(1) FROM entity WHERE id = ?', array(1));
        
        $this->assertEqual($c, 1);
    }
    
    public function testFetchAssoc() {

    }
    public function testFetchColumn() {
        $a = $this->dbh->fetchColumn('SELECT * FROM entity');

        $this->assertEqual($a, array (
                              0 => '1',
                              1 => '2',
                            ));

        $a = $this->dbh->fetchColumn('SELECT * FROM entity WHERE id = ?', array(1));

        $this->assertEqual($a, array (
                              0 => '1',
                            ));
    }
    public function testFetchArray() {
        $a = $this->dbh->fetchArray('SELECT * FROM entity');

        $this->assertEqual($a, array (
                              0 => '1',
                              1 => 'zYne',
                            ));

        $a = $this->dbh->fetchArray('SELECT * FROM entity WHERE id = ?', array(1));

        $this->assertEqual($a, array (
                              0 => '1',
                              1 => 'zYne',
                            ));
    }
    public function testFetchRow() {
        $c = $this->dbh->fetchRow('SELECT * FROM entity');

        $this->assertEqual($c, array (
                              'id' => '1',
                              'name' => 'zYne',
                            ));
                            
        $c = $this->dbh->fetchRow('SELECT * FROM entity WHERE id = ?', array(1));
        
        $this->assertEqual($c, array (
                              'id' => '1',
                              'name' => 'zYne',
                            ));
    }
    public function testFetchPairs() {
                                   	
    }
    public function testAddValidEventListener() {
        $this->dbh->setListener(new Doctrine_Db_EventListener());

        $this->assertTrue($this->dbh->getListener() instanceof Doctrine_Db_EventListener);
        try {
            $ret = $this->dbh->addListener(new Doctrine_Db_TestLogger());
            $this->pass();
            $this->assertTrue($ret instanceof Doctrine_Db2);
        } catch(Doctrine_Db_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($this->dbh->getListener() instanceof Doctrine_Db_EventListener_Chain);
        $this->assertTrue($this->dbh->getListener()->get(0) instanceof Doctrine_Db_TestLogger);

        try {
            $ret = $this->dbh->addListener(new Doctrine_Db_TestValidListener());
            $this->pass();
            $this->assertTrue($ret instanceof Doctrine_Db2);
        } catch(Doctrine_Db_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($this->dbh->getListener() instanceof Doctrine_Db_EventListener_Chain);
        $this->assertTrue($this->dbh->getListener()->get(0) instanceof Doctrine_Db_TestLogger);
        $this->assertTrue($this->dbh->getListener()->get(1) instanceof Doctrine_Db_TestValidListener);
        
        try {
            $ret = $this->dbh->addListener(new Doctrine_Db_EventListener_Chain(), 'chain');
            $this->pass();
            $this->assertTrue($ret instanceof Doctrine_Db2);
        } catch(Doctrine_Db_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($this->dbh->getListener() instanceof Doctrine_Db_EventListener_Chain);
        $this->assertTrue($this->dbh->getListener()->get(0) instanceof Doctrine_Db_TestLogger);
        $this->assertTrue($this->dbh->getListener()->get(1) instanceof Doctrine_Db_TestValidListener);
        $this->assertTrue($this->dbh->getListener()->get('chain') instanceof Doctrine_Db_EventListener_Chain);
        
        // replacing

        try {
            $ret = $this->dbh->addListener(new Doctrine_Db_EventListener_Chain(), 'chain');
            $this->pass();
            $this->assertTrue($ret instanceof Doctrine_Db2);
        } catch(Doctrine_Db_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($this->dbh->getListener() instanceof Doctrine_Db_EventListener_Chain);
        $this->assertTrue($this->dbh->getListener()->get(0) instanceof Doctrine_Db_TestLogger);
        $this->assertTrue($this->dbh->getListener()->get(1) instanceof Doctrine_Db_TestValidListener);
        $this->assertTrue($this->dbh->getListener()->get('chain') instanceof Doctrine_Db_EventListener_Chain);
    }

    public function testListeningEventsWithSingleListener() {
        $this->dbh->setListener(new Doctrine_Db_TestLogger());
        $listener = $this->dbh->getListener();
        $stmt = $this->dbh->prepare('INSERT INTO entity (id) VALUES(?)');

        $this->assertEqual($listener->pop(), 'onPrepare');
        $this->assertEqual($listener->pop(), 'onPrePrepare');
        
        $stmt->execute(array(1));

        $this->assertEqual($listener->pop(), 'onExecute');
        $this->assertEqual($listener->pop(), 'onPreExecute');
        
        $this->dbh->exec('DELETE FROM entity');

        $this->assertEqual($listener->pop(), 'onExec');
        $this->assertEqual($listener->pop(), 'onPreExec');
        
        $this->dbh->beginTransaction();

        $this->assertEqual($listener->pop(), 'onBeginTransaction');
        $this->assertEqual($listener->pop(), 'onPreBeginTransaction');

        $this->dbh->query('INSERT INTO entity (id) VALUES (1)');

        $this->dbh->commit();
        
        $this->assertEqual($listener->pop(), 'onCommit');
        $this->assertEqual($listener->pop(), 'onPreCommit');
        
        $this->assertEqual($listener->pop(), 'onQuery');
        $this->assertEqual($listener->pop(), 'onPreQuery');
        

    }
    public function testListeningQueryEventsWithListenerChain() {
        $this->dbh->query('DROP TABLE entity');

        $this->dbh->addListener(new Doctrine_Db_TestLogger());
        $this->dbh->addListener(new Doctrine_Db_TestLogger());

        $this->dbh->query('CREATE TABLE entity (id INT)');

        $listener = $this->dbh->getListener()->get(0);
        $listener2 = $this->dbh->getListener()->get(1);
        $this->assertEqual($listener->pop(), 'onQuery');
        $this->assertEqual($listener->pop(), 'onPreQuery');

        $this->assertEqual($listener2->pop(), 'onQuery');
        $this->assertEqual($listener2->pop(), 'onPreQuery');
    }
    public function testListeningPrepareEventsWithListenerChain() {

        $stmt = $this->dbh->prepare('INSERT INTO entity (id) VALUES(?)');
        $listener = $this->dbh->getListener()->get(0);
        $listener2 = $this->dbh->getListener()->get(1);
        $this->assertEqual($listener->pop(), 'onPrepare');
        $this->assertEqual($listener->pop(), 'onPrePrepare');

        $this->assertEqual($listener2->pop(), 'onPrepare');
        $this->assertEqual($listener2->pop(), 'onPrePrepare');

        $stmt->execute(array(1));

        $this->assertEqual($listener->pop(), 'onExecute');
        $this->assertEqual($listener->pop(), 'onPreExecute');

        $this->assertEqual($listener2->pop(), 'onExecute');
        $this->assertEqual($listener2->pop(), 'onPreExecute');
    }
    public function testListeningExecEventsWithListenerChain() {
        $this->dbh->exec('DELETE FROM entity');
        $listener = $this->dbh->getListener()->get(0);
        $listener2 = $this->dbh->getListener()->get(1);
        $this->assertEqual($listener->pop(), 'onExec');
        $this->assertEqual($listener->pop(), 'onPreExec');

        $this->assertEqual($listener2->pop(), 'onExec');
        $this->assertEqual($listener2->pop(), 'onPreExec');
    }
    public function testListeningTransactionEventsWithListenerChain() {
        $this->dbh->beginTransaction();
        $listener = $this->dbh->getListener()->get(0);
        $listener2 = $this->dbh->getListener()->get(1);
        $this->assertEqual($listener->pop(), 'onBeginTransaction');
        $this->assertEqual($listener->pop(), 'onPreBeginTransaction');

        $this->assertEqual($listener2->pop(), 'onBeginTransaction');
        $this->assertEqual($listener2->pop(), 'onPreBeginTransaction');

        $this->dbh->query('INSERT INTO entity (id) VALUES (1)');

        $this->dbh->commit();

        $this->assertEqual($listener->pop(), 'onCommit');
        $this->assertEqual($listener->pop(), 'onPreCommit');
        
        $this->assertEqual($listener->pop(), 'onQuery');
        $this->assertEqual($listener->pop(), 'onPreQuery');
        
        $this->dbh->query('DROP TABLE entity');
    }
    public function testSetValidEventListener() {
        try {
            $this->dbh->setListener(new Doctrine_Db_TestLogger());
            $this->pass();
        } catch(Doctrine_Db_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($this->dbh->getListener() instanceof Doctrine_Db_TestLogger);
        try {
            $this->dbh->setListener(new Doctrine_Db_TestValidListener());
            $this->pass();
        } catch(Doctrine_Db_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($this->dbh->getListener() instanceof Doctrine_Db_TestValidListener);
        try {
            $this->dbh->setListener(new Doctrine_Db_EventListener_Chain());
            $this->pass();

        } catch(Doctrine_Db_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($this->dbh->getListener() instanceof Doctrine_Db_EventListener_Chain);
        try {
            $this->dbh->setListener(new Doctrine_Db_EventListener());
            $this->pass();
        } catch(Doctrine_Db_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($this->dbh->getListener() instanceof Doctrine_Db_EventListener);
    }
    public function testSetInvalidEventListener() {
        try {
            $this->dbh->setListener(new Doctrine_Db_TestInvalidListener());
            $this->fail();
        } catch(Doctrine_Db_Exception $e) {
            $this->pass();
        }
    }
    public function testInvalidDSN() {
        try {
            $this->dbh = Doctrine_Db2::getConnection('');
            $this->fail();
        } catch(Doctrine_Db_Exception $e) {
            $this->pass();
        }
        try {
            $this->dbh = Doctrine_Db2::getConnection('unknown');
            $this->fail();
        } catch(Doctrine_Db_Exception $e) {
            $this->pass();
        }   
        try {
            $this->dbh = Doctrine_Db2::getConnection(0);
            $this->fail();
        } catch(Doctrine_Db_Exception $e) {
            $this->pass();
        }
    }
    public function testInvalidScheme() {
        try {
            $this->dbh = Doctrine_Db2::getConnection('unknown://:memory:');
            $this->fail();
        } catch(Doctrine_Db_Exception $e) {
            $this->pass();
        }
    }
    public function testInvalidHost() {
        try {
            $this->dbh = Doctrine_Db2::getConnection('mysql://user:password@');
            $this->fail();
        } catch(Doctrine_Db_Exception $e) {
            $this->pass();
        }
    }
    public function testInvalidDatabase() {
        try {
            $this->dbh = Doctrine_Db2::getConnection('mysql://user:password@host/');
            $this->fail();
        } catch(Doctrine_Db_Exception $e) {
            $this->pass();
        }
    }
    public function testGetConnectionPdoLikeDSN() {
        $this->dbh = Doctrine_Db2::getConnection('mysql:host=localhost;dbname=test', 'root', 'password');
        $this->assertEqual($this->dbh->getOption('dsn'), 'mysql:host=localhost;dbname=test');
        $this->assertEqual($this->dbh->getOption('username'), 'root');
        $this->assertEqual($this->dbh->getOption('password'), 'password');


        $this->dbh = Doctrine_Db2::getConnection('sqlite::memory:');

        $this->assertEqual($this->dbh->getOption('dsn'), 'sqlite::memory:');
        $this->assertEqual($this->dbh->getOption('username'), false);
        $this->assertEqual($this->dbh->getOption('password'), false);
    }
    public function testDriverName() {

    }

    public function testGetConnectionWithPearLikeDSN() {
        $this->dbh = Doctrine_Db2::getConnection('mysql://zYne:password@localhost/test');
        $this->assertEqual($this->dbh->getOption('dsn'), 'mysql:host=localhost;dbname=test');
        $this->assertEqual($this->dbh->getOption('username'), 'zYne');
        $this->assertEqual($this->dbh->getOption('password'), 'password');


        $this->dbh = Doctrine_Db2::getConnection('sqlite://:memory:');

        $this->assertEqual($this->dbh->getOption('dsn'), 'sqlite::memory:');
        $this->assertEqual($this->dbh->getOption('username'), false);
        $this->assertEqual($this->dbh->getOption('password'), false);
    }

}
