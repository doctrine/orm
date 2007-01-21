<?php
class Doctrine_Db_Profiler_TestCase extends Doctrine_UnitTestCase {
    protected $dbh;
    
    protected $profiler;
    public function prepareTables() {}
    public function prepareData() {} 
    
    public function testQuery() {
        $this->dbh = Doctrine_Db::getConnection('sqlite::memory:');

        $this->profiler = new Doctrine_Db_Profiler();

        $this->dbh->setListener($this->profiler);

        $this->dbh->query('CREATE TABLE test (id INT)');
        
        $this->assertEqual($this->profiler->lastEvent()->getQuery(), 'CREATE TABLE test (id INT)');
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEqual($this->profiler->lastEvent()->getCode(), Doctrine_Db_Event::QUERY);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));
        
        $this->assertEqual($this->dbh->count(), 1);
    }
    public function testPrepareAndExecute() {

        $stmt  = $this->dbh->prepare('INSERT INTO test (id) VALUES (?)');
        $event = $this->profiler->lastEvent();

        $this->assertEqual($event->getQuery(), 'INSERT INTO test (id) VALUES (?)');
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEqual($this->profiler->lastEvent()->getCode(), Doctrine_Db_Event::PREPARE);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));

        $stmt->execute(array(1));

        $this->assertEqual($this->profiler->lastEvent()->getQuery(), 'INSERT INTO test (id) VALUES (?)');
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEqual($this->profiler->lastEvent()->getCode(), Doctrine_Db_Event::EXECUTE);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));

        $this->assertEqual($this->dbh->count(), 2);
    }
    public function testMultiplePrepareAndExecute() {

        $stmt = $this->dbh->prepare('INSERT INTO test (id) VALUES (?)');
        $this->assertEqual($this->profiler->lastEvent()->getQuery(), 'INSERT INTO test (id) VALUES (?)');
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEqual($this->profiler->lastEvent()->getCode(), Doctrine_Db_Event::PREPARE);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));

        $stmt2 = $this->dbh->prepare('INSERT INTO test (id) VALUES (?)');
        $this->assertEqual($this->profiler->lastEvent()->getQuery(), 'INSERT INTO test (id) VALUES (?)');
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEqual($this->profiler->lastEvent()->getCode(), Doctrine_Db_Event::PREPARE);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));

        $stmt->execute(array(1));
        $stmt2->execute(array(1));

        $this->assertEqual($this->profiler->lastEvent()->getQuery(), 'INSERT INTO test (id) VALUES (?)');
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEqual($this->profiler->lastEvent()->getCode(), Doctrine_Db_Event::EXECUTE);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));

        $this->assertEqual($this->dbh->count(), 4);
    }
    public function testExecuteStatementMultipleTimes() {
        try {
            $stmt = $this->dbh->prepare('INSERT INTO test (id) VALUES (?)');
            $stmt->execute(array(1));
            $stmt->execute(array(1));
            $this->pass();
        } catch(Doctrine_Db_Exception $e) {

            $this->fail($e->__toString());
        }
        $this->assertEqual($this->profiler->lastEvent()->getQuery(), 'INSERT INTO test (id) VALUES (?)');
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEqual($this->profiler->lastEvent()->getCode(), Doctrine_Db_Event::EXECUTE);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));

        $this->assertEqual($this->profiler->lastEvent()->getQuery(), 'INSERT INTO test (id) VALUES (?)');
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEqual($this->profiler->lastEvent()->getCode(), Doctrine_Db_Event::EXECUTE);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));
    }
    public function testTransactionRollback() {
        try {
            $this->dbh->beginTransaction();
            $this->pass();
        } catch(Doctrine_Db_Exception $e) {
            $this->fail($e->__toString());
        }
        $this->assertEqual($this->profiler->lastEvent()->getQuery(), null);
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEqual($this->profiler->lastEvent()->getCode(), Doctrine_Db_Event::BEGIN);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));
        
        try {
            $this->dbh->rollback();
            $this->pass();
        } catch(Doctrine_Db_Exception $e) {
            $this->fail($e->__toString());
        }

        $this->assertEqual($this->profiler->lastEvent()->getQuery(), null);
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEqual($this->profiler->lastEvent()->getCode(), Doctrine_Db_Event::ROLLBACK);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));
    }
    public function testTransactionCommit() {
        try {
            $this->dbh->beginTransaction();
            $this->pass();
        } catch(Doctrine_Db_Exception $e) {
            $this->fail($e->__toString());
        }
        $this->assertEqual($this->profiler->lastEvent()->getQuery(), null);
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEqual($this->profiler->lastEvent()->getCode(), Doctrine_Db_Event::BEGIN);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));
        
        try {
            $this->dbh->commit();
            $this->pass();
        } catch(Doctrine_Db_Exception $e) {
            $this->fail($e->__toString());
            $this->dbh->rollback();
        }

        $this->assertEqual($this->profiler->lastEvent()->getQuery(), null);
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEqual($this->profiler->lastEvent()->getCode(), Doctrine_Db_Event::COMMIT);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));
    }
}
?>
