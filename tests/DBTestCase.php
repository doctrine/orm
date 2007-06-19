<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.phpdoctrine.com>.
 */

/**
 * Doctrine_Db_TestCase
 *
 * @package     Doctrine
 * @subpackage  Doctrine_Db
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Db_TestCase extends Doctrine_UnitTestCase
{
    protected $dbh;

    public function prepareData() { }
    public function prepareTables() { }
    public function init() { }
    
    public function testInitialize() 
    {
        $this->dbh = Doctrine_Manager::getInstance()->openConnection(array('sqlite::memory:'));
        $this->dbh->exec('CREATE TABLE entity (id INTEGER, name TEXT)');

        $this->dbh->exec("INSERT INTO entity (id, name) VALUES (1, 'zYne')");
        $this->dbh->exec("INSERT INTO entity (id, name) VALUES (2, 'John')");
        
        
        $this->assertEqual($this->dbh->getAttribute(Doctrine::ATTR_DRIVER_NAME), 'sqlite');
    }

    public function testAddValidEventListener() 
    {
        $this->dbh->setListener(new Doctrine_EventListener());

        $this->assertTrue($this->dbh->getListener() instanceof Doctrine_EventListener);
        try {
            $ret = $this->dbh->addListener(new Doctrine_Connection_TestLogger());
            $this->pass();
            $this->assertTrue($ret instanceof Doctrine_Connection);
        } catch(Doctrine_EventListener_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($this->dbh->getListener() instanceof Doctrine_EventListener_Chain);
        $this->assertTrue($this->dbh->getListener()->get(0) instanceof Doctrine_Connection_TestLogger);

        try {
            $ret = $this->dbh->addListener(new Doctrine_Connection_TestValidListener());
            $this->pass();
            $this->assertTrue($ret instanceof Doctrine_Connection);
        } catch(Doctrine_EventListener_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($this->dbh->getListener() instanceof Doctrine_EventListener_Chain);
        $this->assertTrue($this->dbh->getListener()->get(0) instanceof Doctrine_Connection_TestLogger);
        $this->assertTrue($this->dbh->getListener()->get(1) instanceof Doctrine_Connection_TestValidListener);
        
        try {
            $ret = $this->dbh->addListener(new Doctrine_EventListener_Chain(), 'chain');
            $this->pass();
            $this->assertTrue($ret instanceof Doctrine_Connection);
        } catch(Doctrine_EventListener_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($this->dbh->getListener() instanceof Doctrine_EventListener_Chain);
        $this->assertTrue($this->dbh->getListener()->get(0) instanceof Doctrine_Connection_TestLogger);
        $this->assertTrue($this->dbh->getListener()->get(1) instanceof Doctrine_Connection_TestValidListener);
        $this->assertTrue($this->dbh->getListener()->get('chain') instanceof Doctrine_EventListener_Chain);

        // replacing

        try {
            $ret = $this->dbh->addListener(new Doctrine_EventListener_Chain(), 'chain');
            $this->pass();
            $this->assertTrue($ret instanceof Doctrine_Connection);
        } catch(Doctrine_EventListener_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($this->dbh->getListener() instanceof Doctrine_EventListener_Chain);
        $this->assertTrue($this->dbh->getListener()->get(0) instanceof Doctrine_Connection_TestLogger);
        $this->assertTrue($this->dbh->getListener()->get(1) instanceof Doctrine_Connection_TestValidListener);
        $this->assertTrue($this->dbh->getListener()->get('chain') instanceof Doctrine_EventListener_Chain);
    }

    public function testListeningEventsWithSingleListener() 
    {
        $this->dbh->setListener(new Doctrine_Connection_TestLogger());
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

        $this->assertEqual($listener->pop(), 'onTransactionBegin');
        $this->assertEqual($listener->pop(), 'onPreTransactionBegin');

        $this->dbh->exec('INSERT INTO entity (id) VALUES (1)');

        $this->assertEqual($listener->pop(), 'onExec');
        $this->assertEqual($listener->pop(), 'onPreExec');

        $this->dbh->commit();
        
        $this->assertEqual($listener->pop(), 'onTransactionCommit');
        $this->assertEqual($listener->pop(), 'onPreTransactionCommit');

        

    }
    public function testListeningQueryEventsWithListenerChain() 
    {
        $this->dbh->exec('DROP TABLE entity');

        $this->dbh->addListener(new Doctrine_Connection_TestLogger());
        $this->dbh->addListener(new Doctrine_Connection_TestLogger());

        $this->dbh->exec('CREATE TABLE entity (id INT)');

        $listener = $this->dbh->getListener()->get(0);
        $listener2 = $this->dbh->getListener()->get(1);
        $this->assertEqual($listener->pop(), 'onExec');
        $this->assertEqual($listener->pop(), 'onPreExec');

        $this->assertEqual($listener2->pop(), 'onExec');
        $this->assertEqual($listener2->pop(), 'onPreExec');
    }
    public function testListeningPrepareEventsWithListenerChain() 
    {

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
    public function testListeningExecEventsWithListenerChain() 
    {
        $this->dbh->exec('DELETE FROM entity');
        $listener = $this->dbh->getListener()->get(0);
        $listener2 = $this->dbh->getListener()->get(1);
        $this->assertEqual($listener->pop(), 'onExec');
        $this->assertEqual($listener->pop(), 'onPreExec');

        $this->assertEqual($listener2->pop(), 'onExec');
        $this->assertEqual($listener2->pop(), 'onPreExec');
    }
    public function testListeningTransactionEventsWithListenerChain() 
    {
        $this->dbh->beginTransaction();
        $listener = $this->dbh->getListener()->get(0);
        $listener2 = $this->dbh->getListener()->get(1);
        $this->assertEqual($listener->pop(), 'onTransactionBegin');
        $this->assertEqual($listener->pop(), 'onPreTransactionBegin');

        $this->assertEqual($listener2->pop(), 'onTransactionBegin');
        $this->assertEqual($listener2->pop(), 'onPreTransactionBegin');

        $this->dbh->exec('INSERT INTO entity (id) VALUES (1)');

        $this->dbh->commit();

        $this->assertEqual($listener->pop(), 'onTransactionCommit');
        $this->assertEqual($listener->pop(), 'onPreTransactionCommit');
        
        $this->assertEqual($listener->pop(), 'onExec');
        $this->assertEqual($listener->pop(), 'onPreExec');
        
        $this->dbh->exec('DROP TABLE entity');
    }
    public function testSetValidEventListener() 
    {
        try {
            $this->dbh->setListener(new Doctrine_Connection_TestLogger());
            $this->pass();
        } catch(Doctrine_EventListener_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($this->dbh->getListener() instanceof Doctrine_Connection_TestLogger);
        try {
            $this->dbh->setListener(new Doctrine_Connection_TestValidListener());
            $this->pass();
        } catch(Doctrine_EventListener_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($this->dbh->getListener() instanceof Doctrine_Connection_TestValidListener);
        try {
            $this->dbh->setListener(new Doctrine_EventListener_Chain());
            $this->pass();

        } catch(Doctrine_EventListener_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($this->dbh->getListener() instanceof Doctrine_EventListener_Chain);
        try {
            $this->dbh->setListener(new Doctrine_EventListener());
            $this->pass();
        } catch(Doctrine_EventListener_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($this->dbh->getListener() instanceof Doctrine_EventListener);
    }
    public function testSetInvalidEventListener() 
    {
        try {
            $this->dbh->setListener(new Doctrine_Connection_TestInvalidListener());
            $this->fail();
        } catch(Doctrine_EventListener_Exception $e) {
            $this->pass();
        }
    }
    public function testInvalidDSN() 
    {
    	$manager = Doctrine_Manager::getInstance();
        try {
            $this->dbh = $manager->openConnection('');
            $this->fail();
        } catch(Doctrine_Exception $e) {
            $this->pass();
        }
        try {
            $this->dbh = $manager->openConnection('unknown');
            $this->fail();
        } catch(Doctrine_Exception $e) {
            $this->pass();
        }   
        try {
            $this->dbh = $manager->openConnection(0);
            $this->fail();
        } catch(Doctrine_Exception $e) {
            $this->pass();
        }
    }
    public function testInvalidScheme() 
    {
    	$manager = Doctrine_Manager::getInstance();
        try {
            $this->dbh = $manager->openConnection('unknown://:memory:');
            $this->fail();
        } catch(Doctrine_Exception $e) {
            $this->pass();
        }
    }
    public function testInvalidHost() 
    {
    	$manager = Doctrine_Manager::getInstance();
        try {
            $this->dbh = $manager->openConnection('mysql://user:password@');
            $this->fail();
        } catch(Doctrine_Exception $e) {
            $this->pass();
        }
    }
    public function testInvalidDatabase() 
    {
    	$manager = Doctrine_Manager::getInstance();
        try {
            $this->dbh = $manager->openConnection('mysql://user:password@host/');
            $this->fail();
        } catch(Doctrine_Exception $e) {
            $this->pass();
        }
    }
    /**
    public function testGetConnectionPdoLikeDSN()
    {
        $this->dbh = Doctrine_Manager::openConnection(array('mysql:host=localhost;dbname=test', 'root', 'password'));
        $this->assertEqual($this->dbh->getOption('dsn'), 'mysql:host=localhost;dbname=test');
        $this->assertEqual($this->dbh->getOption('username'), 'root');
        $this->assertEqual($this->dbh->getOption('password'), 'password');


        $this->dbh = Doctrine_Connection::getConnection('sqlite::memory:');

        $this->assertEqual($this->dbh->getOption('dsn'), 'sqlite::memory:');
        $this->assertEqual($this->dbh->getOption('username'), false);
        $this->assertEqual($this->dbh->getOption('password'), false);
    }
    public function testDriverName()
    {

    }

    public function testGetConnectionWithPearLikeDSN()
    {
        $this->dbh = Doctrine_Connection::getConnection('mysql://zYne:password@localhost/test');
        $this->assertEqual($this->dbh->getOption('dsn'), 'mysql:host=localhost;dbname=test');
        $this->assertEqual($this->dbh->getOption('username'), 'zYne');
        $this->assertEqual($this->dbh->getOption('password'), 'password');


        $this->dbh = Doctrine_Connection::getConnection('sqlite://:memory:');

        $this->assertEqual($this->dbh->getOption('dsn'), 'sqlite::memory:');
        $this->assertEqual($this->dbh->getOption('username'), false);
        $this->assertEqual($this->dbh->getOption('password'), false);
    }
    */
}   

class Doctrine_Connection_TestLogger implements Doctrine_Overloadable {
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
class Doctrine_Connection_TestValidListener extends Doctrine_EventListener { }
class Doctrine_Connection_TestInvalidListener { }
