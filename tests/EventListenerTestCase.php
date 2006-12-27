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
 * Doctrine_EventListener_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
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

class Doctrine_EventListener_TestCase extends Doctrine_UnitTestCase {
    private $logger;


    public function testSetListener() {
        $this->logger = new Doctrine_EventListener_TestLogger();
    
        $e = new EventListenerTest;
        
        $e->getTable()->setListener($this->logger);

        $e->name = 'listener';
        $e->save();

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
