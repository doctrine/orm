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
 * Doctrine_Transaction_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Transaction_TestCase extends Doctrine_UnitTestCase
{
    public function testInit()
    {
        $this->transaction = new Doctrine_Transaction_Mock();

        $this->listener = new TransactionListener();

        $this->conn->setListener($this->listener);
    }

    public function testCreateSavepointListenersGetInvoked()
    {
        try {
            $this->transaction->beginTransaction('point');

            $this->pass();
        } catch(Doctrine_Transaction_Exception $e) {
            $this->fail();
        }

        $this->assertEqual($this->listener->pop(), 'postSavepointCreate');
        $this->assertEqual($this->listener->pop(), 'preSavepointCreate');
    }

    public function testCommitSavepointListenersGetInvoked()
    {
        try {
            $this->transaction->commit('point');

            $this->pass();
        } catch(Doctrine_Transaction_Exception $e) {
            $this->fail();
        }

        $this->assertEqual($this->listener->pop(), 'postSavepointCommit');
        $this->assertEqual($this->listener->pop(), 'preSavepointCommit');
        $this->assertEqual($this->transaction->getTransactionLevel(), 0);
    }

    public function testNestedSavepoints()
    {
        $this->assertEqual($this->transaction->getTransactionLevel(), 0);
        $this->transaction->beginTransaction();
        $this->assertEqual($this->transaction->getTransactionLevel(), 1);
        $this->transaction->beginTransaction('point 1');
        $this->assertEqual($this->transaction->getTransactionLevel(), 2);
        $this->transaction->beginTransaction('point 2');
        $this->assertEqual($this->transaction->getTransactionLevel(), 3);
        $this->transaction->commit('point 2');
        $this->assertEqual($this->transaction->getTransactionLevel(), 2);
        $this->transaction->commit('point 1');
        $this->assertEqual($this->transaction->getTransactionLevel(), 1);
        $this->transaction->commit();
        $this->assertEqual($this->transaction->getTransactionLevel(), 0);
    }

    public function testRollbackSavepointListenersGetInvoked()
    {
        try {
            $this->transaction->beginTransaction('point');
            $this->transaction->rollback('point');

            $this->pass();
        } catch(Doctrine_Transaction_Exception $e) {
            $this->fail();
        }

        $this->assertEqual($this->listener->pop(), 'postSavepointRollback');
        $this->assertEqual($this->listener->pop(), 'preSavepointRollback');
        $this->assertEqual($this->listener->pop(), 'postSavepointCreate');
        $this->assertEqual($this->listener->pop(), 'preSavepointCreate');
        $this->assertEqual($this->transaction->getTransactionLevel(), 0);

        $this->listener = new Doctrine_Eventlistener();
        $this->conn->setListener($this->listener);
    }

    public function testCreateSavepointIsOnlyImplementedAtDriverLevel() 
    {
        try {
            $this->transaction->beginTransaction('savepoint');
            $this->fail();
        } catch(Doctrine_Transaction_Exception $e) {
            $this->pass();
        }
    }

    public function testReleaseSavepointIsOnlyImplementedAtDriverLevel()
    {
        try {
            $this->transaction->setTransactionLevel(1);

            $this->transaction->commit('savepoint');
            $this->fail();
        } catch(Doctrine_Transaction_Exception $e) {
            $this->pass();
        }
        $this->transaction->setTransactionLevel(0);
    }

    public function testRollbackSavepointIsOnlyImplementedAtDriverLevel() 
    {
        try {
            $this->transaction->setTransactionLevel(1);

            $this->transaction->rollback('savepoint');
            $this->fail();
        } catch(Doctrine_Transaction_Exception $e) {
            $this->pass();
        }    
        $this->transaction->setTransactionLevel(0);
    }

    public function testSetIsolationIsOnlyImplementedAtDriverLevel() 
    {
        try {
            $this->transaction->setIsolation('READ UNCOMMITTED');
            $this->fail();
        } catch(Doctrine_Transaction_Exception $e) {
            $this->pass();
        }
    }

    public function testGetIsolationIsOnlyImplementedAtDriverLevel()
    {
        try {
            $this->transaction->GetIsolation('READ UNCOMMITTED');
            $this->fail();
        } catch(Doctrine_Transaction_Exception $e) {
            $this->pass();
        }
    }

    public function testTransactionLevelIsInitiallyZero() 
    {
        $this->assertEqual($this->transaction->getTransactionLevel(), 0);
    }

    public function testGetStateReturnsStateConstant() 
    {
        $this->assertEqual($this->transaction->getState(), Doctrine_Transaction::STATE_SLEEP);                                                      
    }

    public function testCommittingNotActiveTransactionReturnsFalse()
    {
        $this->assertEqual($this->transaction->commit(), false);
    }

    public function testExceptionIsThrownWhenUsingRollbackOnNotActiveTransaction() 
    {
        $this->assertEqual($this->transaction->rollback(), false);
    }

    public function testBeginTransactionStartsNewTransaction() 
    {
        $this->transaction->beginTransaction();  

        $this->assertEqual($this->adapter->pop(), 'BEGIN TRANSACTION');                                                         
    }

    public function testCommitMethodCommitsCurrentTransaction()
    {
        $this->transaction->commit();

        $this->assertEqual($this->adapter->pop(), 'COMMIT');
    }

}
class TransactionListener extends Doctrine_EventListener 
{
    protected $_messages = array();

    public function preTransactionCommit(Doctrine_Event $event)
    {
        $this->_messages[] = __FUNCTION__;

        $event->skipOperation();
    }
    public function postTransactionCommit(Doctrine_Event $event)
    {
        $this->_messages[] = __FUNCTION__;
    }

    public function preTransactionRollback(Doctrine_Event $event)
    {
        $this->_messages[] = __FUNCTION__;

        $event->skipOperation();
    }
    public function postTransactionRollback(Doctrine_Event $event)
    {
        $this->_messages[] = __FUNCTION__;
    }

    public function preTransactionBegin(Doctrine_Event $event)
    {
        $this->_messages[] = __FUNCTION__;

        $event->skipOperation();
    }
    public function postTransactionBegin(Doctrine_Event $event)
    { 
        $this->_messages[] = __FUNCTION__;
    }


    public function preSavepointCommit(Doctrine_Event $event)
    {           
        $this->_messages[] = __FUNCTION__;

        $event->skipOperation();
    }
    public function postSavepointCommit(Doctrine_Event $event)
    { 
        $this->_messages[] = __FUNCTION__;
    }

    public function preSavepointRollback(Doctrine_Event $event)
    {
        $this->_messages[] = __FUNCTION__;

        $event->skipOperation();
    }
    public function postSavepointRollback(Doctrine_Event $event)
    { 
        $this->_messages[] = __FUNCTION__;
    }

    public function preSavepointCreate(Doctrine_Event $event)
    { 
        $this->_messages[] = __FUNCTION__;

        $event->skipOperation();
    }

    public function postSavepointCreate(Doctrine_Event $event)
    { 
        $this->_messages[] = __FUNCTION__;
    }
    
    public function pop()
    {
        return array_pop($this->_messages);
    }
}
