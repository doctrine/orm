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
 * <http://www.phpdoctrine.com>.
 */

/**
 * Doctrine_Transaction_Firebird_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Transaction_Firebird_TestCase extends Doctrine_UnitTestCase 
{
    public function testCreateSavePointExecutesSql() 
    {
        $this->transaction->beginTransaction('mypoint');

        $this->assertEqual($this->adapter->pop(), 'SAVEPOINT mypoint');
    }
    public function testReleaseSavePointExecutesSql() 
    {
        $this->transaction->commit('mypoint');

        $this->assertEqual($this->adapter->pop(), 'RELEASE SAVEPOINT mypoint');
    }
    public function testRollbackSavePointExecutesSql() 
    {
        $this->transaction->beginTransaction('mypoint');
        $this->transaction->rollback('mypoint');

        $this->assertEqual($this->adapter->pop(), 'ROLLBACK TO SAVEPOINT mypoint');
    }
    public function testSetIsolationThrowsExceptionOnUnknownIsolationMode() 
    {
        try {
            $this->transaction->setIsolation('unknown');
            $this->fail();
        } catch(Doctrine_Transaction_Exception $e) {
            $this->pass();
        }
    }
    public function testSetIsolationThrowsExceptionOnUnknownWaitMode() 
    {
        try {
            $this->transaction->setIsolation('READ UNCOMMITTED', array('wait' => 'unknown'));
            $this->fail();
        } catch(Doctrine_Transaction_Exception $e) {
            $this->pass();
        }
    }
    public function testSetIsolationThrowsExceptionOnUnknownReadWriteMode() 
    {
        try {
            $this->transaction->setIsolation('READ UNCOMMITTED', array('rw' => 'unknown'));
            $this->fail();
        } catch(Doctrine_Transaction_Exception $e) {
            $this->pass();
        }
    }
    public function testSetIsolationExecutesSql() 
    {
        $this->transaction->setIsolation('READ UNCOMMITTED');
        $this->transaction->setIsolation('READ COMMITTED');
        $this->transaction->setIsolation('REPEATABLE READ');
        $this->transaction->setIsolation('SERIALIZABLE');

        $this->assertEqual($this->adapter->pop(), 'SET TRANSACTION ISOLATION LEVEL SNAPSHOT TABLE STABILITY');
        $this->assertEqual($this->adapter->pop(), 'SET TRANSACTION ISOLATION LEVEL SNAPSHOT');
        $this->assertEqual($this->adapter->pop(), 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED NO RECORD_VERSION');
        $this->assertEqual($this->adapter->pop(), 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED RECORD_VERSION');
    }
    public function testSetIsolationSupportsReadWriteOptions() 
    {
        $this->transaction->setIsolation('SERIALIZABLE', array('rw' => 'READ ONLY'));

        $this->assertEqual($this->adapter->pop(), 'SET TRANSACTION READ ONLY ISOLATION LEVEL SNAPSHOT TABLE STABILITY');

        $this->transaction->setIsolation('SERIALIZABLE', array('rw' => 'READ WRITE'));

        $this->assertEqual($this->adapter->pop(), 'SET TRANSACTION READ WRITE ISOLATION LEVEL SNAPSHOT TABLE STABILITY');
    }
    public function testSetIsolationSupportsWaitOptions() 
    {
        $this->transaction->setIsolation('SERIALIZABLE', array('wait' => 'NO WAIT'));

        $this->assertEqual($this->adapter->pop(), 'SET TRANSACTION NO WAIT ISOLATION LEVEL SNAPSHOT TABLE STABILITY');

        $this->transaction->setIsolation('SERIALIZABLE', array('wait' => 'WAIT'));

        $this->assertEqual($this->adapter->pop(), 'SET TRANSACTION WAIT ISOLATION LEVEL SNAPSHOT TABLE STABILITY');
    }
}
