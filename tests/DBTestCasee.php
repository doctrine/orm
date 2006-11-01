<?php
require_once("../draft/DB.php");

class Doctrine_DB_TestLogger implements Doctrine_Overloadable {
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
class Doctrine_DB_TestValidListener extends Doctrine_DB_EventListener { }
class Doctrine_DB_TestInvalidListener { }

class Doctrine_DB_TestCase extends Doctrine_UnitTestCase {
    public function prepareData() { }
    public function prepareTables() { }
    public function init() { }

    public function testFetchAll() {
        $dbh = Doctrine_DB2::getConnection('sqlite::memory:');
        $dbh->connect();


        $dbh->query('CREATE TABLE entity (id INTEGER, name TEXT)');

        $dbh->query("INSERT INTO entity (id, name) VALUES (1, 'zYne')");
        $dbh->query("INSERT INTO entity (id, name) VALUES (2, 'John')");

        $a = $dbh->fetchAll('SELECT * FROM entity');


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
        $dbh = Doctrine_DB2::getConnection('sqlite::memory:');

        $c = $dbh->fetchOne('SELECT COUNT(1) FROM entity');
        
        $this->assertEqual($c, 2);
        
        $c = $dbh->fetchOne('SELECT COUNT(1) FROM entity WHERE id = ?', array(1));
        
        $this->assertEqual($c, 1);
    }
    
    public function testFetchAssoc() {

    }
    public function testFetchColumn() {
        $dbh = Doctrine_DB2::getConnection('sqlite::memory:');

        $a = $dbh->fetchColumn('SELECT * FROM entity');

        $this->assertEqual($a, array (
                              0 => '1',
                              1 => '2',
                            ));

        $a = $dbh->fetchColumn('SELECT * FROM entity WHERE id = ?', array(1));

        $this->assertEqual($a, array (
                              0 => '1',
                            ));
    }
    public function testFetchArray() {
        $dbh = Doctrine_DB2::getConnection('sqlite::memory:');

        $a = $dbh->fetchArray('SELECT * FROM entity');

        $this->assertEqual($a, array (
                              0 => '1',
                              1 => 'zYne',
                            ));

        $a = $dbh->fetchArray('SELECT * FROM entity WHERE id = ?', array(1));

        $this->assertEqual($a, array (
                              0 => '1',
                              1 => 'zYne',
                            ));
    }
    public function testFetchRow() {
        $dbh = Doctrine_DB2::getConnection('sqlite::memory:');

        $c = $dbh->fetchRow('SELECT * FROM entity');

        $this->assertEqual($c, array (
                              'id' => '1',
                              'name' => 'zYne',
                            ));
                            
        $c = $dbh->fetchRow('SELECT * FROM entity WHERE id = ?', array(1));
        
        $this->assertEqual($c, array (
                              'id' => '1',
                              'name' => 'zYne',
                            ));
    }
    public function testFetchPairs() {
                                   	
    }
    public function testAddValidEventListener() {
        $dbh = Doctrine_DB2::getConnection('sqlite::memory:');
        
        $this->assertTrue($dbh->getListener() instanceof Doctrine_DB_EventListener);
        try {
            $ret = $dbh->addListener(new Doctrine_DB_TestLogger());
            $this->pass();
            $this->assertTrue($ret instanceof Doctrine_DB2);
        } catch(Doctrine_DB_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($dbh->getListener() instanceof Doctrine_DB_EventListener_Chain);
        $this->assertTrue($dbh->getListener()->get(0) instanceof Doctrine_DB_TestLogger);
        
        try {
            $ret = $dbh->addListener(new Doctrine_DB_TestValidListener());
            $this->pass();
            $this->assertTrue($ret instanceof Doctrine_DB2);
        } catch(Doctrine_DB_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($dbh->getListener() instanceof Doctrine_DB_EventListener_Chain);
        $this->assertTrue($dbh->getListener()->get(0) instanceof Doctrine_DB_TestLogger);
        $this->assertTrue($dbh->getListener()->get(1) instanceof Doctrine_DB_TestValidListener);
        
        try {
            $ret = $dbh->addListener(new Doctrine_DB_EventListener_Chain(), 'chain');
            $this->pass();
            $this->assertTrue($ret instanceof Doctrine_DB2);
        } catch(Doctrine_DB_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($dbh->getListener() instanceof Doctrine_DB_EventListener_Chain);
        $this->assertTrue($dbh->getListener()->get(0) instanceof Doctrine_DB_TestLogger);
        $this->assertTrue($dbh->getListener()->get(1) instanceof Doctrine_DB_TestValidListener);
        $this->assertTrue($dbh->getListener()->get('chain') instanceof Doctrine_DB_EventListener_Chain);
        
        // replacing

        try {
            $ret = $dbh->addListener(new Doctrine_DB_EventListener_Chain(), 'chain');
            $this->pass();
            $this->assertTrue($ret instanceof Doctrine_DB2);
        } catch(Doctrine_DB_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($dbh->getListener() instanceof Doctrine_DB_EventListener_Chain);
        $this->assertTrue($dbh->getListener()->get(0) instanceof Doctrine_DB_TestLogger);
        $this->assertTrue($dbh->getListener()->get(1) instanceof Doctrine_DB_TestValidListener);
        $this->assertTrue($dbh->getListener()->get('chain') instanceof Doctrine_DB_EventListener_Chain);
    }
    public function testListeningEventsWithSingleListener() {
        $dbh = Doctrine_DB2::getConnection('sqlite::memory:');
        $dbh->connect();
        $dbh->setListener(new Doctrine_DB_TestLogger());
        $listener = $dbh->getListener();
        $stmt = $dbh->prepare('INSERT INTO entity (id) VALUES(?)');

        $this->assertEqual($listener->pop(), 'onPrepare');
        $this->assertEqual($listener->pop(), 'onPrePrepare');
        
        $stmt->execute(array(1));

        $this->assertEqual($listener->pop(), 'onExecute');
        $this->assertEqual($listener->pop(), 'onPreExecute');
        
        $dbh->exec('DELETE FROM entity');

        $this->assertEqual($listener->pop(), 'onExec');
        $this->assertEqual($listener->pop(), 'onPreExec');
        
        $dbh->beginTransaction();

        $this->assertEqual($listener->pop(), 'onBeginTransaction');
        $this->assertEqual($listener->pop(), 'onPreBeginTransaction');

        $dbh->query('INSERT INTO entity (id) VALUES (1)');

        $dbh->commit();
        
        $this->assertEqual($listener->pop(), 'onCommit');
        $this->assertEqual($listener->pop(), 'onPreCommit');
        
        $this->assertEqual($listener->pop(), 'onQuery');
        $this->assertEqual($listener->pop(), 'onPreQuery');
        
        $dbh->query('DROP TABLE entity');
    }
    public function testListeningEventsWithListenerChain() {
        $dbh = Doctrine_DB2::getConnection('sqlite::memory:');
        $dbh->connect();
        $dbh->addListener(new Doctrine_DB_TestLogger());
        $dbh->addListener(new Doctrine_DB_TestLogger());

        $dbh->query('CREATE TABLE entity (id INT)');

        $listener = $dbh->getListener()->get(0);
        $listener2 = $dbh->getListener()->get(1);
        $this->assertEqual($listener->pop(), 'onQuery');
        $this->assertEqual($listener->pop(), 'onPreQuery');

        $this->assertEqual($listener2->pop(), 'onQuery');
        $this->assertEqual($listener2->pop(), 'onPreQuery');


        $stmt = $dbh->prepare('INSERT INTO entity (id) VALUES(?)');

        $this->assertEqual($listener->pop(), 'onPrepare');
        $this->assertEqual($listener->pop(), 'onPrePrepare');

        $this->assertEqual($listener2->pop(), 'onPrepare');
        $this->assertEqual($listener2->pop(), 'onPrePrepare');

        $stmt->execute(array(1));

        $this->assertEqual($listener->pop(), 'onExecute');
        $this->assertEqual($listener->pop(), 'onPreExecute');
        
        $this->assertEqual($listener2->pop(), 'onExecute');
        $this->assertEqual($listener2->pop(), 'onPreExecute');
        
        $dbh->exec('DELETE FROM entity');

        $this->assertEqual($listener->pop(), 'onExec');
        $this->assertEqual($listener->pop(), 'onPreExec');

        $this->assertEqual($listener2->pop(), 'onExec');
        $this->assertEqual($listener2->pop(), 'onPreExec');

        $dbh->beginTransaction();

        $this->assertEqual($listener->pop(), 'onBeginTransaction');
        $this->assertEqual($listener->pop(), 'onPreBeginTransaction');

        $this->assertEqual($listener2->pop(), 'onBeginTransaction');
        $this->assertEqual($listener2->pop(), 'onPreBeginTransaction');

        $dbh->query('INSERT INTO entity (id) VALUES (1)');

        $dbh->commit();

        $this->assertEqual($listener->pop(), 'onCommit');
        $this->assertEqual($listener->pop(), 'onPreCommit');
        
        $this->assertEqual($listener->pop(), 'onQuery');
        $this->assertEqual($listener->pop(), 'onPreQuery');
        
        $dbh->query('DROP TABLE entity');
    }
    public function testSetValidEventListener() {
        $dbh = Doctrine_DB2::getConnection('sqlite::memory:');
        try {
            $dbh->setListener(new Doctrine_DB_TestLogger());
            $this->pass();
        } catch(Doctrine_DB_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($dbh->getListener() instanceof Doctrine_DB_TestLogger);
        try {
            $dbh->setListener(new Doctrine_DB_TestValidListener());
            $this->pass();
        } catch(Doctrine_DB_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($dbh->getListener() instanceof Doctrine_DB_TestValidListener);
        try {
            $dbh->setListener(new Doctrine_DB_EventListener_Chain());
            $this->pass();

        } catch(Doctrine_DB_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($dbh->getListener() instanceof Doctrine_DB_EventListener_Chain);
        try {
            $dbh->setListener(new Doctrine_DB_EventListener());
            $this->pass();
        } catch(Doctrine_DB_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($dbh->getListener() instanceof Doctrine_DB_EventListener);
    }
    public function testSetInvalidEventListener() {
        $dbh = Doctrine_DB2::getConnection('sqlite::memory:');
        try {
            $dbh->setListener(new Doctrine_DB_TestInvalidListener());
            $this->fail();
        } catch(Doctrine_DB_Exception $e) {
            $this->pass();
        }
    }
    public function testInvalidDSN() {
        try {
            $dbh = Doctrine_DB2::getConnection('');
            $this->fail();
        } catch(Doctrine_DB_Exception $e) {
            $this->pass();
        }
        try {
            $dbh = Doctrine_DB2::getConnection('unknown');
            $this->fail();
        } catch(Doctrine_DB_Exception $e) {
            $this->pass();
        }   
        try {
            $dbh = Doctrine_DB2::getConnection(0);
            $this->fail();
        } catch(Doctrine_DB_Exception $e) {
            $this->pass();
        }
    }
    public function testInvalidScheme() {
        try {
            $dbh = Doctrine_DB2::getConnection('unknown://:memory:');
            $this->fail();
        } catch(Doctrine_DB_Exception $e) {
            $this->pass();
        }
    }
    public function testInvalidHost() {
        try {
            $dbh = Doctrine_DB2::getConnection('mysql://user:password@');
            $this->fail();
        } catch(Doctrine_DB_Exception $e) {
            $this->pass();
        }
    }
    public function testInvalidDatabase() {
        try {
            $dbh = Doctrine_DB2::getConnection('mysql://user:password@host/');
            $this->fail();
        } catch(Doctrine_DB_Exception $e) {
            $this->pass();
        }
    }
    public function testGetConnectionPdoLikeDSN() {
        $dbh = Doctrine_DB2::getConnection('mysql:host=localhost;dbname=test', 'root', 'password');
        $this->assertEqual($dbh->getDSN(), 'mysql:host=localhost;dbname=test');
        $this->assertEqual($dbh->getUsername(), 'root');
        $this->assertEqual($dbh->getPassword(), 'password');


        $dbh = Doctrine_DB2::getConnection('sqlite::memory:');

        $this->assertEqual($dbh->getDSN(), 'sqlite::memory:');
        $this->assertEqual($dbh->getUsername(), null);
        $this->assertEqual($dbh->getPassword(), null);
    }
    public function testDriverName() {

    }

    public function testGetConnectionWithPearLikeDSN() {
        $dbh = Doctrine_DB2::getConnection('mysql://zYne:password@localhost/test');
        $this->assertEqual($dbh->getDSN(), 'mysql:host=localhost;dbname=test');
        $this->assertEqual($dbh->getUsername(), 'zYne');
        $this->assertEqual($dbh->getPassword(), 'password');


        $dbh = Doctrine_DB2::getConnection('sqlite://:memory:');

        $this->assertEqual($dbh->getDSN(), 'sqlite::memory:');
        $this->assertEqual($dbh->getUsername(), null);
        $this->assertEqual($dbh->getPassword(), null);
    }

}
?>
