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
 * <http://www.phpdoctrine.org>.
 */

/**
 * Doctrine_Connection_Profiler_TestCase
 *
 * @package     Doctrine
 * @subpackage  Doctrine_Db
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Connection_Profiler_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareTables()
    {}
    public function prepareData() 
    {}
    
    public function testQuery() 
    {
        $this->conn = Doctrine_Manager::getInstance()->openConnection(array('sqlite::memory:'));

        $this->profiler = new Doctrine_Connection_Profiler();

        $this->conn->setListener($this->profiler);

        $this->conn->exec('CREATE TABLE test (id INT)');
        
        $this->assertEqual($this->profiler->lastEvent()->getQuery(), 'CREATE TABLE test (id INT)');
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEqual($this->profiler->lastEvent()->getCode(), Doctrine_Db_Event::EXEC);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));
        
        $this->assertEqual($this->conn->count(), 1);
    }
    public function testPrepareAndExecute() 
    {

        $stmt  = $this->conn->prepare('INSERT INTO test (id) VALUES (?)');
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

        $this->assertEqual($this->conn->count(), 2);
    }
    public function testMultiplePrepareAndExecute() 
    {

        $stmt = $this->conn->prepare('INSERT INTO test (id) VALUES (?)');
        $this->assertEqual($this->profiler->lastEvent()->getQuery(), 'INSERT INTO test (id) VALUES (?)');
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEqual($this->profiler->lastEvent()->getCode(), Doctrine_Db_Event::PREPARE);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));

        $stmt2 = $this->conn->prepare('INSERT INTO test (id) VALUES (?)');
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

        $this->assertEqual($this->conn->count(), 4);

    }
    public function testExecuteStatementMultipleTimes()
    {
        try {
            $stmt = $this->conn->prepare('INSERT INTO test (id) VALUES (?)');
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
    public function testTransactionRollback() 
    {
        try {
            $this->conn->beginTransaction();
            $this->pass();
        } catch(Doctrine_Db_Exception $e) {
            $this->fail($e->__toString());
        }
        $this->assertEqual($this->profiler->lastEvent()->getQuery(), null);
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEqual($this->profiler->lastEvent()->getCode(), Doctrine_Db_Event::BEGIN);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));
        
        try {
            $this->conn->rollback();
            $this->pass();
        } catch(Doctrine_Db_Exception $e) {
            $this->fail($e->__toString());
        }

        $this->assertEqual($this->profiler->lastEvent()->getQuery(), null);
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEqual($this->profiler->lastEvent()->getCode(), Doctrine_Db_Event::ROLLBACK);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));
    }
    public function testTransactionCommit() 
    {
        try {
            $this->conn->beginTransaction();
            $this->pass();
        } catch(Doctrine_Db_Exception $e) {
            $this->fail($e->__toString());
        }
        $this->assertEqual($this->profiler->lastEvent()->getQuery(), null);
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEqual($this->profiler->lastEvent()->getCode(), Doctrine_Db_Event::BEGIN);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));
        
        try {
            $this->conn->commit();
            $this->pass();
        } catch(Doctrine_Db_Exception $e) {
            $this->fail($e->__toString());
            $this->conn->rollback();
        }

        $this->assertEqual($this->profiler->lastEvent()->getQuery(), null);
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEqual($this->profiler->lastEvent()->getCode(), Doctrine_Db_Event::COMMIT);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));
    }
}
