<?php
class Doctrine_Transaction_TestCase extends Doctrine_Driver_UnitTestCase {
    public function __construct() {
        parent::__construct('sqlite', true);
    }
    public function testCreateSavepointIsOnlyImplementedAtDriverLevel() {
        try {
            $this->transaction->createSavePoint('point');
            $this->fail();
        } catch(Doctrine_Transaction_Exception $e) {
            $this->pass();
        }
    }
    public function testReleaseSavepointIsOnlyImplementedAtDriverLevel() {
        try {
            $this->transaction->releaseSavePoint('point');
            $this->fail();
        } catch(Doctrine_Transaction_Exception $e) {
            $this->pass();
        }
    }
    public function testRollbackSavepointIsOnlyImplementedAtDriverLevel() {
        try {
            $this->transaction->rollbackSavePoint('point');
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
    public function testExceptionIsThrownWhenCommittingNotActiveTransaction() {
        try {
            $this->transaction->commit();
            $this->fail();
        } catch(Doctrine_Transaction_Exception $e) {
            $this->pass();
        }
    }
    public function testExceptionIsThrownWhenUsingRollbackOnNotActiveTransaction() {
        try {
            $this->transaction->rollback();
            $this->fail();
        } catch(Doctrine_Transaction_Exception $e) {
            $this->pass();
        }
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
