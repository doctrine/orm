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
 * Doctrine_EventListener_Chain_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */   
class Doctrine_EventListener_Chain_TestCase extends Doctrine_UnitTestCase {

    public function testAccessorInvokerChain() {
        $e = new EventListenerChainTest;
        $e->name = "something";


        $this->assertEqual($e->get('name'), 'somethingTestATestB');
        // test repeated calls
        $this->assertEqual($e->get('name'), 'somethingTestATestB');
        $this->assertEqual($e->id, null);
        $this->assertEqual($e->rawGet('name'), 'something');

        $e->save();

        $this->assertEqual($e->id, 1);
        $this->assertEqual($e->name, 'somethingTestATestB');
        $this->assertEqual($e->rawGet('name'), 'something');

        $this->connection->clear();

        $e->refresh();

        $this->assertEqual($e->id, 1);
        $this->assertEqual($e->name, 'somethingTestATestB');
        $this->assertEqual($e->rawGet('name'), 'something');

        $this->connection->clear();

        $e = $e->getTable()->find($e->id);

        $this->assertEqual($e->id, 1);
        $this->assertEqual($e->name, 'somethingTestATestB');
        $this->assertEqual($e->rawGet('name'), 'something');
    }
    public function prepareData() { }
    public function prepareTables() {
        $this->tables = array('EventListenerChainTest');
        parent::prepareTables();
    }
}
class EventListenerChainTest extends Doctrine_Record {
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

