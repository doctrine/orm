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
 * Doctrine_Connection_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Connection_TestCase extends Doctrine_UnitTestCase 
{

    public function testUnknownModule() 
    {
        try {
            $this->connection->unknown;
            $this->fail();
        } catch(Doctrine_Connection_Exception $e) {
            $this->pass();
        }
    }

    public function testGetModule() 
    {
        $this->assertTrue($this->connection->unitOfWork instanceof Doctrine_Connection_UnitOfWork);
        //$this->assertTrue($this->connection->dataDict instanceof Doctrine_DataDict);
        $this->assertTrue($this->connection->expression instanceof Doctrine_Expression_Driver);
        $this->assertTrue($this->connection->transaction instanceof Doctrine_Transaction);
        $this->assertTrue($this->connection->export instanceof Doctrine_Export);
    }

    public function testFetchAll() 
    {
        $this->conn->exec('DROP TABLE entity');
        $this->conn->exec('CREATE TABLE entity (id INT, name TEXT)');

        $this->conn->exec("INSERT INTO entity (id, name) VALUES (1, 'zYne')");
        $this->conn->exec("INSERT INTO entity (id, name) VALUES (2, 'John')");

        $a = $this->conn->fetchAll('SELECT * FROM entity');


        $this->assertEqual($a, array (
                            0 =>
                            array (
                              'id' => '1',
                              'name' => 'zYne',
                            ),
                            1 =>
                            array (
                              'id' => '2',
                              'name' => 'John',
                            ),
                          ));
    }

    public function testFetchOne()
    {
        $c = $this->conn->fetchOne('SELECT COUNT(1) FROM entity');
        
        $this->assertEqual($c, 2);
        
        $c = $this->conn->fetchOne('SELECT COUNT(1) FROM entity WHERE id = ?', array(1));
        
        $this->assertEqual($c, 1);
    }
    

    public function testFetchColumn() 
    {
        $a = $this->conn->fetchColumn('SELECT * FROM entity');

        $this->assertEqual($a, array (
                              0 => '1',
                              1 => '2',
                            ));

        $a = $this->conn->fetchColumn('SELECT * FROM entity WHERE id = ?', array(1));

        $this->assertEqual($a, array (
                              0 => '1',
                            ));
    }

    public function testFetchArray() 
    {
        $a = $this->conn->fetchArray('SELECT * FROM entity');

        $this->assertEqual($a, array (
                              0 => '1',
                              1 => 'zYne',
                            ));

        $a = $this->conn->fetchArray('SELECT * FROM entity WHERE id = ?', array(1));

        $this->assertEqual($a, array (
                              0 => '1',
                              1 => 'zYne',
                            ));
    }

    public function testFetchRow() 
    {
        $c = $this->conn->fetchRow('SELECT * FROM entity');

        $this->assertEqual($c, array (
                              'id' => '1',
                              'name' => 'zYne',
                            ));

        $c = $this->conn->fetchRow('SELECT * FROM entity WHERE id = ?', array(1));
        
        $this->assertEqual($c, array (
                              'id' => '1',
                              'name' => 'zYne',
                            ));
    }

    public function testFetchPairs() 
    {
        $this->conn->exec('DROP TABLE entity');
    }

    public function testGetManager() 
    {
        $this->assertTrue($this->connection->getManager() === $this->manager);
    }

    public function testDelete() 
    {
        //$user = $this->connection->create('User');
        //$this->connection->unitOfWork->delete($user);
        //$this->assertEqual($user->state(),Doctrine_Entity::STATE_TCLEAN);
    }

    public function testGetTable() 
    {
        $table = $this->connection->getClassMetadata('Group');
        $this->assertTrue($table instanceof Doctrine_ClassMetadata);
        try {
            $table = $this->connection->getClassMetadata('Unknown');
            $f = false;
        } catch(Doctrine_Exception $e) {
            $f = true;
        }
        $this->assertTrue($f);
    }

    public function testCreate() 
    {
        $email = $this->connection->create('Email');
        $this->assertTrue($email instanceof Email);
    }

    public function testGetDbh() 
    {
        $this->assertTrue($this->connection->getDbh() instanceof PDO);
    }

    public function testCount() 
    {
        $this->assertTrue(is_integer(count($this->connection)));
    }

    public function testGetIterator() 
    {
        $this->assertTrue($this->connection->getIterator() instanceof ArrayIterator);
    }

    public function testGetState()
    {
        $this->assertEqual($this->connection->transaction->getState(),Doctrine_Transaction::STATE_SLEEP);
        $this->assertEqual(Doctrine_Lib::getConnectionStateAsString($this->connection->transaction->getState()), 'open');
    }

    public function testRollback() 
    {
        $this->connection->beginTransaction();
        $this->assertEqual($this->connection->transaction->getTransactionLevel(),1);
        $this->assertEqual($this->connection->transaction->getState(), Doctrine_Transaction::STATE_ACTIVE);
        $this->connection->rollback();
        $this->assertEqual($this->connection->transaction->getState(), Doctrine_Transaction::STATE_SLEEP);
        $this->assertEqual($this->connection->transaction->getTransactionLevel(),0);
    }

    public function testNestedTransactions()
    {
        $this->assertEqual($this->connection->transaction->getTransactionLevel(),0);
        $this->connection->beginTransaction();
        $this->assertEqual($this->connection->transaction->getTransactionLevel(),1);
        $this->assertEqual($this->connection->transaction->getState(), Doctrine_Transaction::STATE_ACTIVE);
        $this->connection->beginTransaction();
        $this->assertEqual($this->connection->transaction->getState(), Doctrine_Transaction::STATE_BUSY);
        $this->assertEqual($this->connection->transaction->getTransactionLevel(),2);
        $this->connection->commit();
        $this->assertEqual($this->connection->transaction->getState(), Doctrine_Transaction::STATE_ACTIVE);
        $this->assertEqual($this->connection->transaction->getTransactionLevel(),1);
        $this->connection->commit();
        $this->assertEqual($this->connection->transaction->getState(), Doctrine_Transaction::STATE_SLEEP);
        $this->assertEqual($this->connection->transaction->getTransactionLevel(),0);
    }

    public function testSqliteDsn()
    {
        $conn = Doctrine_Manager::connection('sqlite:foo.sq3');

        try {
            $conn->connect();

            $conn->close();
            $this->pass();
        } catch (Doctrine_Exception $e) {
            $this->fail();
        }
        unlink('foo.sq3');
    }
}
