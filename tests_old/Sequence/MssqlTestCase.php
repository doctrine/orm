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
 * Doctrine_Sequence_Mssql_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Sequence_Mssql_TestCase extends Doctrine_UnitTestCase 
{
    public function testCurrIdExecutesSql()
    {
        $id = $this->sequence->currId('user');

        $this->assertEqual($this->adapter->pop(), 'DELETE FROM user_seq WHERE id < 0');

        $this->assertEqual($this->adapter->pop(), 'SELECT @@IDENTITY');  
        $this->assertEqual($this->adapter->pop(), 'SELECT @@VERSION');
        $this->assertEqual($this->adapter->pop(), 'SET IDENTITY_INSERT user_seq OFF INSERT INTO user_seq DEFAULT VALUES');
        $this->assertEqual($this->adapter->pop(), 'SELECT COUNT(1) FROM user_seq');
    }
    public function testNextIdExecutesSql()
    {
        $id = $this->sequence->nextId('user');

        $this->assertEqual($this->adapter->pop(), 'DELETE FROM user_seq WHERE id < 0');

        $this->assertEqual($this->adapter->pop(), 'SELECT @@IDENTITY');  
        $this->assertEqual($this->adapter->pop(), 'SELECT @@VERSION'); 
        $this->assertEqual($this->adapter->pop(), 'SET IDENTITY_INSERT user_seq OFF INSERT INTO user_seq DEFAULT VALUES');
        $this->assertEqual($this->adapter->pop(), 'SELECT COUNT(1) FROM user_seq');
    }
    public function testLastInsertIdCallsPdoLevelEquivalent() 
    {
        $id = $this->sequence->lastInsertId('user');

        $this->assertEqual($this->adapter->pop(), 'SELECT @@IDENTITY');
    }
}
