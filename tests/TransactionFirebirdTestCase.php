<?php
class Doctrine_Transaction_Firebird_TestCase extends Doctrine_Driver_UnitTestCase {
    public function __construct() {
        parent::__construct('firebird');
    }
    public function testCreateSavePointExecutesSql() {
        $this->transaction->beginTransaction('mypoint');

        $this->assertEqual($this->adapter->pop(), 'SAVEPOINT mypoint');
    }
    public function testReleaseSavePointExecutesSql() {
        $this->transaction->commit('mypoint');

        $this->assertEqual($this->adapter->pop(), 'RELEASE SAVEPOINT mypoint');
    }
    public function testRollbackSavePointExecutesSql() {
        $this->transaction->rollback('mypoint');

        $this->assertEqual($this->adapter->pop(), 'ROLLBACK TO SAVEPOINT mypoint');
    }
    public function testSetIsolationThrowsExceptionOnUnknownIsolationMode() {
        try {
            $this->transaction->setIsolation('unknown');
            $this->fail();
        } catch(Doctrine_Transaction_Exception $e) {
            $this->pass();
        }
    }
    public function testSetIsolationThrowsExceptionOnUnknownWaitMode() {
        try {
            $this->transaction->setIsolation('READ UNCOMMITTED', array('wait' => 'unknown'));
            $this->fail();
        } catch(Doctrine_Transaction_Exception $e) {
            $this->pass();
        }
    }
    public function testSetIsolationThrowsExceptionOnUnknownReadWriteMode() {
        try {
            $this->transaction->setIsolation('READ UNCOMMITTED', array('rw' => 'unknown'));
            $this->fail();
        } catch(Doctrine_Transaction_Exception $e) {
            $this->pass();
        }
    }
    public function testSetIsolationExecutesSql() {
        $this->transaction->setIsolation('READ UNCOMMITTED');
        $this->transaction->setIsolation('READ COMMITTED');
        $this->transaction->setIsolation('REPEATABLE READ');
        $this->transaction->setIsolation('SERIALIZABLE');

        $this->assertEqual($this->adapter->pop(), 'SET TRANSACTION ISOLATION LEVEL SNAPSHOT TABLE STABILITY');
        $this->assertEqual($this->adapter->pop(), 'SET TRANSACTION ISOLATION LEVEL SNAPSHOT');
        $this->assertEqual($this->adapter->pop(), 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED NO RECORD_VERSION');
        $this->assertEqual($this->adapter->pop(), 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED RECORD_VERSION');
    }
    public function testSetIsolationSupportsReadWriteOptions() {
        $this->transaction->setIsolation('SERIALIZABLE', array('rw' => 'READ ONLY'));

        $this->assertEqual($this->adapter->pop(), 'SET TRANSACTION READ ONLY ISOLATION LEVEL SNAPSHOT TABLE STABILITY');

        $this->transaction->setIsolation('SERIALIZABLE', array('rw' => 'READ WRITE'));

        $this->assertEqual($this->adapter->pop(), 'SET TRANSACTION READ WRITE ISOLATION LEVEL SNAPSHOT TABLE STABILITY');
    }
    public function testSetIsolationSupportsWaitOptions() {
        $this->transaction->setIsolation('SERIALIZABLE', array('wait' => 'NO WAIT'));

        $this->assertEqual($this->adapter->pop(), 'SET TRANSACTION NO WAIT ISOLATION LEVEL SNAPSHOT TABLE STABILITY');

        $this->transaction->setIsolation('SERIALIZABLE', array('wait' => 'WAIT'));

        $this->assertEqual($this->adapter->pop(), 'SET TRANSACTION WAIT ISOLATION LEVEL SNAPSHOT TABLE STABILITY');
    }
}
