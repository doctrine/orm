<?php
class Doctrine_Db_Profiler_TestCase extends Doctrine_UnitTestCase {
    protected $dbh;
    
    protected $profiler;
    public function prepareTables() {}
    public function prepareData() {} 
    
    public function testQuery() {
        $this->dbh = Doctrine_DB2::getConnection('sqlite::memory:');

        $this->profiler = new Doctrine_DB_Profiler();

        $this->dbh->setListener($this->profiler);

        $this->dbh->query('CREATE TABLE test (id INT)');
        
        $this->assertEqual($this->profiler->lastQuery()->getQuery(), 'CREATE TABLE test (id INT)');
        $this->assertTrue($this->profiler->lastQuery()->hasEnded());
        $this->assertTrue(is_numeric($this->profiler->lastQuery()->getElapsedSecs()));
        
        $this->assertEqual($this->dbh->count(), 1);
    }
    public function testPrepareAndExecute() {

        $stmt = $this->dbh->prepare('INSERT INTO test (id) VALUES (?)');

        $this->assertEqual($this->profiler->lastQuery()->getQuery(), 'INSERT INTO test (id) VALUES (?)');
        $this->assertFalse($this->profiler->lastQuery()->hasEnded());
        $this->assertTrue(is_numeric($this->profiler->lastQuery()->getElapsedSecs()));

        $stmt->execute(array(1));

        $this->assertEqual($this->profiler->lastQuery()->getQuery(), 'INSERT INTO test (id) VALUES (?)');
        $this->assertTrue($this->profiler->lastQuery()->hasEnded());
        $this->assertTrue(is_numeric($this->profiler->lastQuery()->getElapsedSecs()));

        $this->assertEqual($this->dbh->count(), 2);
    }

    public function testMultiplePrepareAndExecute() {

        $stmt = $this->dbh->prepare('INSERT INTO test (id) VALUES (?)');
        $this->assertEqual($this->profiler->lastQuery()->getQuery(), 'INSERT INTO test (id) VALUES (?)');
        $this->assertFalse($this->profiler->lastQuery()->hasEnded());
        $this->assertTrue(is_numeric($this->profiler->lastQuery()->getElapsedSecs()));

        $stmt2 = $this->dbh->prepare('INSERT INTO test (id) VALUES (?)');
        $this->assertEqual($this->profiler->lastQuery()->getQuery(), 'INSERT INTO test (id) VALUES (?)');
        $this->assertFalse($this->profiler->lastQuery()->hasEnded());
        $this->assertTrue(is_numeric($this->profiler->lastQuery()->getElapsedSecs()));

        $stmt->execute(array(1));
        $stmt2->execute(array(1));

        $this->assertEqual($this->profiler->lastQuery()->getQuery(), 'INSERT INTO test (id) VALUES (?)');
        $this->assertTrue($this->profiler->lastQuery()->hasEnded());
        $this->assertTrue(is_numeric($this->profiler->lastQuery()->getElapsedSecs()));

        $this->assertEqual($this->dbh->count(), 4);
    }
    /**
    public function testExecuteStatementMultipleTimes() {
        try {
            $stmt = $this->dbh->prepare('INSERT INTO test (id) VALUES (?)');
            $stmt->execute(array(1));
            $stmt->execute(array(1));
            $this->pass();
        } catch(Doctrine_Db_Exception $e) {
            $this->fail();
        }
        $this->assertEqual($this->profiler->lastQuery()->getQuery(), 'INSERT INTO test (id) VALUES (?)');
        $this->assertTrue($this->profiler->lastQuery()->hasEnded());
        $this->assertTrue(is_numeric($this->profiler->lastQuery()->getElapsedSecs()));

        $this->assertEqual($this->profiler->lastQuery()->getQuery(), 'INSERT INTO test (id) VALUES (?)');
        $this->assertTrue($this->profiler->lastQuery()->hasEnded());
        $this->assertTrue(is_numeric($this->profiler->lastQuery()->getElapsedSecs()));
    }  */
}
?>
