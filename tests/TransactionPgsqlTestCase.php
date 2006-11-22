<?php
class Doctrine_Transaction_Pgsql_TestCase extends Doctrine_Driver_UnitTestCase {
    public function __construct() {
        parent::__construct('pgsql');
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
    public function testSetIsolationExecutesSql() {
        $this->transaction->setIsolation('READ UNCOMMITTED');

        $this->assertEqual($this->adapter->pop(), 'SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL READ UNCOMMITTED');
    }
}
