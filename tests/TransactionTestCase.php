<?php
class Doctrine_Transaction_TestCase extends Doctrine_Driver_UnitTestCase {
    public function __construct() {
        parent::__construct('sqlite', true);
    }
/**
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
*/
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
