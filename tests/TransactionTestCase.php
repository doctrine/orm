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
    }
    public function testCreateSavepointIsOnlyImplementedAtDriverLevel() {
        try {
            $this->transaction->beginTransaction('point');
            $this->fail();
        } catch(Doctrine_Transaction_Exception $e) {
            $this->pass();
        }
    }
    public function testReleaseSavepointIsOnlyImplementedAtDriverLevel() {
        try {
            $this->transaction->commit('point');
            $this->fail();
        } catch(Doctrine_Transaction_Exception $e) {
            $this->pass();
        }
    }

    public function testRollbackSavepointIsOnlyImplementedAtDriverLevel() {
        try {
            $this->transaction->beginTransaction();

            $this->transaction->rollback('point');
            $this->fail();
        } catch(Doctrine_Transaction_Exception $e) {
            $this->pass();
        }
    }
    public function testSetIsolationIsOnlyImplementedAtDriverLevel() {
        try {
            $this->transaction->setIsolation('READ UNCOMMITTED');
            $this->fail();
        } catch(Doctrine_Transaction_Exception $e) {
            $this->pass();
        }
    }
    public function testGetIsolationIsOnlyImplementedAtDriverLevel() {
        try {
            $this->transaction->GetIsolation('READ UNCOMMITTED');
            $this->fail();
        } catch(Doctrine_Transaction_Exception $e) {
            $this->pass();
        }
    }
    public function testTransactionLevelIsInitiallyZero() {
        $this->assertEqual($this->transaction->getTransactionLevel(), 0);
    }
    public function testGetStateReturnsStateConstant() {
        $this->assertEqual($this->transaction->getState(), Doctrine_Transaction::STATE_SLEEP);                                                  	
    }
    public function testCommittingNotActiveTransactionReturnsFalse() {
        $this->assertEqual($this->transaction->commit(), false);
    }
    public function testExceptionIsThrownWhenUsingRollbackOnNotActiveTransaction() {
        $this->assertEqual($this->transaction->rollback(), false);
    }
    public function testBeginTransactionStartsNewTransaction() {
        $this->transaction->beginTransaction();  

        $this->assertEqual($this->adapter->pop(), 'BEGIN TRANSACTION');                                                     	
    }
    public function testCommitMethodCommitsCurrentTransaction() {
        $this->transaction->commit();

        $this->assertEqual($this->adapter->pop(), 'COMMIT');
    }

}
