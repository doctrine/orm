<?php
class Doctrine_Transaction_Oracle_TestCase extends Doctrine_Driver_UnitTestCase {
    public function __construct() {
        parent::__construct('oci');
    }
    public function testCreateSavePointExecutesSql() {
        $this->transaction->beginTransaction('mypoint');
        
        $this->assertEqual($this->adapter->pop(), 'SAVEPOINT mypoint');
    }
    public function testReleaseSavePointAlwaysReturnsTrue() {
        $this->assertEqual($this->transaction->commit('mypoint'), true);
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
    public function testSetIsolationExecutesSql() {
        $this->transaction->setIsolation('READ UNCOMMITTED');
        $this->transaction->setIsolation('READ COMMITTED');
        $this->transaction->setIsolation('REPEATABLE READ');
        $this->transaction->setIsolation('SERIALIZABLE');

        $this->assertEqual($this->adapter->pop(), 'ALTER SESSION ISOLATION LEVEL SERIALIZABLE');
        $this->assertEqual($this->adapter->pop(), 'ALTER SESSION ISOLATION LEVEL SERIALIZABLE');
        $this->assertEqual($this->adapter->pop(), 'ALTER SESSION ISOLATION LEVEL SERIALIZABLE');
        $this->assertEqual($this->adapter->pop(), 'ALTER SESSION ISOLATION LEVEL READ COMMITTED');
    }
}
