<?php
class Doctrine_Transaction_Pgsql_TestCase extends Doctrine_Driver_UnitTestCase {
    public function __construct() {
        parent::__construct('firebird');
    }
    public function testCreateSavePointExecutesSql() {
        $this->transaction->createSavePoint('mypoint');
        
        $this->assertEqual($this->adapter->pop(), 'SAVEPOINT mypoint');
    }
    public function testReleaseSavePointExecutesSql() {
        $this->transaction->releaseSavePoint('mypoint');

        $this->assertEqual($this->adapter->pop(), 'RELEASE SAVEPOINT mypoint');
    }
    public function testRollbackSavePointExecutesSql() {
        $this->transaction->rollbackSavePoint('mypoint');

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

        $this->assertEqual($this->adapter->pop(), 'ALTER SESSION ISOLATION LEVEL READ COMMITTED RECORD_VERSION');
        $this->assertEqual($this->adapter->pop(), 'ALTER SESSION ISOLATION LEVEL READ COMMITTED NO RECORD_VERSION');
        $this->assertEqual($this->adapter->pop(), 'ALTER SESSION ISOLATION LEVEL SNAPSHOT');
        $this->assertEqual($this->adapter->pop(), 'ALTER SESSION ISOLATION LEVEL SNAPSHOT TABLE STABILITY');
    }
    public function testSetIsolationSupportsReadWriteOptions() {
        $this->transaction->setIsolation('SERIALIZABLE', array('rw' => 'READ ONLY'));

        $this->assertEqual($this->adapter->pop(), 'ALTER SESSION READ ONLY ISOLATION LEVEL SNAPSHOT TABLE STABILITY');

        $this->transaction->setIsolation('SERIALIZABLE', array('rw' => 'READ WRITE'));

        $this->assertEqual($this->adapter->pop(), 'ALTER SESSION READ WRITE ISOLATION LEVEL SNAPSHOT TABLE STABILITY');
    }
    public function testSetIsolationSupportsWaitOptions() {
        $this->transaction->setIsolation('SERIALIZABLE', array('wait' => 'NO WAIT'));

        $this->assertEqual($this->adapter->pop(), 'ALTER SESSION NO WAIT ISOLATION LEVEL SNAPSHOT TABLE STABILITY');

        $this->transaction->setIsolation('SERIALIZABLE', array('wait' => 'WAIT'));

        $this->assertEqual($this->adapter->pop(), 'ALTER SESSION WAIT ISOLATION LEVEL SNAPSHOT TABLE STABILITY');
    }
}
