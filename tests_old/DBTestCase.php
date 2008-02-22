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
 * <http://www.phpdoctrine.org>.
 */

/**
 * Doctrine_Db_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Db_TestCase extends Doctrine_UnitTestCase
{

    public function prepareData() 
    { }

    public function prepareTables() 
    { }

    public function init() 
    { }
    
    public function testInitialize() 
    {
        $this->conn = Doctrine_Manager::getInstance()->openConnection(array('sqlite::memory:'));
        $this->conn->exec('CREATE TABLE entity (id INTEGER, name TEXT)');

        $this->conn->exec("INSERT INTO entity (id, name) VALUES (1, 'zYne')");
        $this->conn->exec("INSERT INTO entity (id, name) VALUES (2, 'John')");
        
        
        $this->assertEqual($this->conn->getAttribute(Doctrine::ATTR_DRIVER_NAME), 'sqlite');
    }

    public function testAddValidEventListener() 
    {
        $this->conn->setListener(new Doctrine_EventListener());

        $this->assertTrue($this->conn->getListener() instanceof Doctrine_EventListener);
        try {
            $ret = $this->conn->addListener(new Doctrine_Connection_TestLogger());
            $this->pass();
            $this->assertTrue($ret instanceof Doctrine_Connection);
        } catch(Doctrine_EventListener_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($this->conn->getListener() instanceof Doctrine_EventListener_Chain);
        $this->assertTrue($this->conn->getListener()->get(0) instanceof Doctrine_Connection_TestLogger);

        try {
            $ret = $this->conn->addListener(new Doctrine_Connection_TestValidListener());
            $this->pass();
            $this->assertTrue($ret instanceof Doctrine_Connection);
        } catch(Doctrine_EventListener_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($this->conn->getListener() instanceof Doctrine_EventListener_Chain);
        $this->assertTrue($this->conn->getListener()->get(0) instanceof Doctrine_Connection_TestLogger);
        $this->assertTrue($this->conn->getListener()->get(1) instanceof Doctrine_Connection_TestValidListener);
        
        try {
            $ret = $this->conn->addListener(new Doctrine_EventListener_Chain(), 'chain');
            $this->pass();
            $this->assertTrue($ret instanceof Doctrine_Connection);
        } catch(Doctrine_EventListener_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($this->conn->getListener() instanceof Doctrine_EventListener_Chain);
        $this->assertTrue($this->conn->getListener()->get(0) instanceof Doctrine_Connection_TestLogger);
        $this->assertTrue($this->conn->getListener()->get(1) instanceof Doctrine_Connection_TestValidListener);
        $this->assertTrue($this->conn->getListener()->get('chain') instanceof Doctrine_EventListener_Chain);

        // replacing

        try {
            $ret = $this->conn->addListener(new Doctrine_EventListener_Chain(), 'chain');
            $this->pass();
            $this->assertTrue($ret instanceof Doctrine_Connection);
        } catch(Doctrine_EventListener_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($this->conn->getListener() instanceof Doctrine_EventListener_Chain);
        $this->assertTrue($this->conn->getListener()->get(0) instanceof Doctrine_Connection_TestLogger);
        $this->assertTrue($this->conn->getListener()->get(1) instanceof Doctrine_Connection_TestValidListener);
        $this->assertTrue($this->conn->getListener()->get('chain') instanceof Doctrine_EventListener_Chain);
    }

    public function testListeningEventsWithSingleListener() 
    {
        $this->conn->setListener(new Doctrine_Connection_TestLogger());
        $listener = $this->conn->getListener();
        $stmt = $this->conn->prepare('INSERT INTO entity (id) VALUES(?)');

        $this->assertEqual($listener->pop(), 'postPrepare');
        $this->assertEqual($listener->pop(), 'prePrepare');
        
        $stmt->execute(array(1));

        $this->assertEqual($listener->pop(), 'postStmtExecute');
        $this->assertEqual($listener->pop(), 'preStmtExecute');
        
        $this->conn->exec('DELETE FROM entity');

        $this->assertEqual($listener->pop(), 'postExec');
        $this->assertEqual($listener->pop(), 'preExec');
        
        $this->conn->beginTransaction();

        $this->assertEqual($listener->pop(), 'postTransactionBegin');
        $this->assertEqual($listener->pop(), 'preTransactionBegin');

        $this->conn->exec('INSERT INTO entity (id) VALUES (1)');

        $this->assertEqual($listener->pop(), 'postExec');
        $this->assertEqual($listener->pop(), 'preExec');

        $this->conn->commit();
        
        $this->assertEqual($listener->pop(), 'postTransactionCommit');
        $this->assertEqual($listener->pop(), 'preTransactionCommit');
    }

    public function testListeningQueryEventsWithListenerChain() 
    {
        $this->conn->exec('DROP TABLE entity');

        $this->conn->addListener(new Doctrine_Connection_TestLogger());
        $this->conn->addListener(new Doctrine_Connection_TestLogger());

        $this->conn->exec('CREATE TABLE entity (id INT)');

        $listener = $this->conn->getListener()->get(0);
        $listener2 = $this->conn->getListener()->get(1);
        $this->assertEqual($listener->pop(), 'postExec');
        $this->assertEqual($listener->pop(), 'preExec');

        $this->assertEqual($listener2->pop(), 'postExec');
        $this->assertEqual($listener2->pop(), 'preExec');
    }

    public function testListeningPrepareEventsWithListenerChain() 
    {

        $stmt = $this->conn->prepare('INSERT INTO entity (id) VALUES(?)');
        $listener = $this->conn->getListener()->get(0);
        $listener2 = $this->conn->getListener()->get(1);
        $this->assertEqual($listener->pop(), 'postPrepare');
        $this->assertEqual($listener->pop(), 'prePrepare');

        $this->assertEqual($listener2->pop(), 'postPrepare');
        $this->assertEqual($listener2->pop(), 'prePrepare');

        $stmt->execute(array(1));

        $this->assertEqual($listener->pop(), 'postStmtExecute');
        $this->assertEqual($listener->pop(), 'preStmtExecute');

        $this->assertEqual($listener2->pop(), 'postStmtExecute');
        $this->assertEqual($listener2->pop(), 'preStmtExecute');
    }

    public function testListeningErrorHandlingMethodsOnExec()
    {
        $this->conn->setAttribute(Doctrine::ATTR_THROW_EXCEPTIONS, false);
        $listener = $this->conn->getListener()->get(0);
        $this->conn->exec('DELETE FROM unknown');

        $this->assertEqual($listener->pop(), 'postError');
        $this->assertEqual($listener->pop(), 'preError');

        $this->assertEqual($listener->pop(), 'preExec');
    }

    public function testListeningErrorHandlingMethodsOnQuery()
    {
        $this->conn->setAttribute(Doctrine::ATTR_THROW_EXCEPTIONS, false);
        $listener = $this->conn->getListener()->get(0);
        $this->conn->execute('DELETE FROM unknown');

        $this->assertEqual($listener->pop(), 'postError');
        $this->assertEqual($listener->pop(), 'preError');

        $this->assertEqual($listener->pop(), 'preQuery');
    }

    public function testListeningErrorHandlingMethodsOnPrepare()
    {
        $this->conn->setAttribute(Doctrine::ATTR_THROW_EXCEPTIONS, false);
        $listener = $this->conn->getListener()->get(0);

        $this->conn->prepare('INSERT INTO unknown (id) VALUES (?)');

        $this->assertEqual($listener->pop(), 'postError');
        $this->assertEqual($listener->pop(), 'preError');

        $this->assertEqual($listener->pop(), 'prePrepare');
    }

    public function testListeningErrorHandlingMethodsOnStatementExecute()
    {
        $this->conn->setAttribute(Doctrine::ATTR_THROW_EXCEPTIONS, false);
        $listener = $this->conn->getListener()->get(0);

        $stmt = $this->conn->prepare('INSERT INTO entity (id) VALUES (?)');

        $stmt->execute(array(1, 2, 3));

        $this->assertEqual($listener->pop(), 'postError');
        $this->assertEqual($listener->pop(), 'preError');

        $this->assertEqual($listener->pop(), 'preStmtExecute');
        $this->assertEqual($listener->pop(), 'postPrepare');
        $this->assertEqual($listener->pop(), 'prePrepare');
    }

    public function testListeningExecEventsWithListenerChain()
    {
        $this->conn->exec('DELETE FROM entity');
        $listener = $this->conn->getListener()->get(0);
        $listener2 = $this->conn->getListener()->get(1);
        $this->assertEqual($listener->pop(), 'postExec');
        $this->assertEqual($listener->pop(), 'preExec');

        $this->assertEqual($listener2->pop(), 'postExec');
        $this->assertEqual($listener2->pop(), 'preExec');
    }

    public function testListeningTransactionEventsWithListenerChain() 
    {
        $this->conn->beginTransaction();
        $listener = $this->conn->getListener()->get(0);
        $listener2 = $this->conn->getListener()->get(1);
        $this->assertEqual($listener->pop(), 'postTransactionBegin');
        $this->assertEqual($listener->pop(), 'preTransactionBegin');

        $this->assertEqual($listener2->pop(), 'postTransactionBegin');
        $this->assertEqual($listener2->pop(), 'preTransactionBegin');

        $this->conn->exec('INSERT INTO entity (id) VALUES (1)');

        $this->conn->commit();

        $this->assertEqual($listener->pop(), 'postTransactionCommit');
        $this->assertEqual($listener->pop(), 'preTransactionCommit');
        
        $this->assertEqual($listener->pop(), 'postExec');
        $this->assertEqual($listener->pop(), 'preExec');
        
        $this->conn->exec('DROP TABLE entity');
    }

    public function testSetValidEventListener() 
    {
        try {
            $this->conn->setListener(new Doctrine_Connection_TestLogger());
            $this->pass();
        } catch(Doctrine_EventListener_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($this->conn->getListener() instanceof Doctrine_Connection_TestLogger);
        try {
            $this->conn->setListener(new Doctrine_Connection_TestValidListener());
            $this->pass();
        } catch(Doctrine_EventListener_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($this->conn->getListener() instanceof Doctrine_Connection_TestValidListener);
        try {
            $this->conn->setListener(new Doctrine_EventListener_Chain());
            $this->pass();

        } catch(Doctrine_EventListener_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($this->conn->getListener() instanceof Doctrine_EventListener_Chain);
        try {
            $this->conn->setListener(new Doctrine_EventListener());
            $this->pass();
        } catch(Doctrine_EventListener_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($this->conn->getListener() instanceof Doctrine_EventListener);
    }

    public function testSetInvalidEventListener() 
    {
        try {
            $this->conn->setListener(new Doctrine_Connection_TestInvalidListener());
            $this->fail();
        } catch(Doctrine_EventListener_Exception $e) {
            $this->pass();
        }
    }
    public function testInvalidDSN() 
    {
        $manager = Doctrine_Manager::getInstance();
        try {
            $this->conn = $manager->openConnection('');
            $this->fail();
        } catch(Doctrine_Exception $e) {
            $this->pass();
        }
        try {
            $this->conn = $manager->openConnection('unknown');
            $this->fail();
        } catch(Doctrine_Exception $e) {
            $this->pass();
        }   
        try {
            $this->conn = $manager->openConnection(0);
            $this->fail();
        } catch(Doctrine_Exception $e) {
            $this->pass();
        }
    }
    public function testInvalidScheme() 
    {
        $manager = Doctrine_Manager::getInstance();
        try {
            $this->conn = $manager->openConnection('unknown://:memory:');
            $this->fail();
        } catch(Doctrine_Exception $e) {
            $this->pass();
        }
    }
    public function testInvalidHost() 
    {
        $manager = Doctrine_Manager::getInstance();
        try {
            $this->conn = $manager->openConnection('mysql://user:password@');
            $this->fail();
        } catch(Doctrine_Exception $e) {
            $this->pass();
        }
    }
    public function testInvalidDatabase() 
    {
        $manager = Doctrine_Manager::getInstance();
        try {
            $this->conn = $manager->openConnection('mysql://user:password@host/');
            $this->fail();
        } catch(Doctrine_Exception $e) {
            $this->pass();
        }
    }

    /**
    public function testGetConnectionPdoLikeDSN()
    {
        $this->conn = Doctrine_Manager::openConnection(array('mysql:host=localhost;dbname=test', 'root', 'password'));
        $this->assertEqual($this->conn->getOption('dsn'), 'mysql:host=localhost;dbname=test');
        $this->assertEqual($this->conn->getOption('username'), 'root');
        $this->assertEqual($this->conn->getOption('password'), 'password');


        $this->conn = Doctrine_Connection::getConnection('sqlite::memory:');

        $this->assertEqual($this->conn->getOption('dsn'), 'sqlite::memory:');
        $this->assertEqual($this->conn->getOption('username'), false);
        $this->assertEqual($this->conn->getOption('password'), false);
    }
    public function testDriverName()
    {

    }

    public function testGetConnectionWithPearLikeDSN()
    {
        $this->conn = Doctrine_Connection::getConnection('mysql://zYne:password@localhost/test');
        $this->assertEqual($this->conn->getOption('dsn'), 'mysql:host=localhost;dbname=test');
        $this->assertEqual($this->conn->getOption('username'), 'zYne');
        $this->assertEqual($this->conn->getOption('password'), 'password');


        $this->conn = Doctrine_Connection::getConnection('sqlite://:memory:');

        $this->assertEqual($this->conn->getOption('dsn'), 'sqlite::memory:');
        $this->assertEqual($this->conn->getOption('username'), false);
        $this->assertEqual($this->conn->getOption('password'), false);
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
