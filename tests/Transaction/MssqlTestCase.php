<?php
class Doctrine_Transaction_Mssql_TestCase extends Doctrine_Driver_UnitTestCase {
    public function __construct() {
        parent::__construct('mssql');
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

        $this->assertEqual($this->adapter->pop(), 'SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        $this->assertEqual($this->adapter->pop(), 'SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $this->assertEqual($this->adapter->pop(), 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
        $this->assertEqual($this->adapter->pop(), 'SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED');
    }
    public function testSetIsolationSupportsSnapshotMode() {
        $this->transaction->setIsolation('SNAPSHOT');

        $this->assertEqual($this->adapter->pop(), 'SET TRANSACTION ISOLATION LEVEL SNAPSHOT');
    }
}
