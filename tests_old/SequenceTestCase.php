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
 * Doctrine_Sequence_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Sequence_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareData() 
    {
    }
    public function prepareTables() 
    {
    }
    public function testSequencesAreSupportedForRecords()
    {       
                 /**
        // the last profiled event is transaction commit
        $this->assertEqual($this->adapter->pop(), 'COMMIT');
        // query execution                                         

        $this->assertEqual($this->adapter->pop(), 'INSERT INTO custom_sequence_record (name, id) VALUES (?, ?)');
        // query prepare
        $this->assertEqual($this->adapter->pop(), 'INSERT INTO custom_sequence_record (name, id) VALUES (?, ?)');

        // sequence generation (first fails)
        $this->assertEqual($this->adapter->pop(), 'INSERT INTO custom_seq_seq (id) VALUES (1)');
        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE custom_seq_seq (id INTEGER PRIMARY KEY DEFAULT 0 NOT NULL)');
        $this->assertEqual($this->adapter->pop(), 'INSERT INTO custom_seq_seq (id) VALUES (NULL)');

        // transaction begin
        $this->assertEqual($this->adapter->pop(), 'BEGIN TRANSACTION');
        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE custom_sequence_record (id INTEGER, name VARCHAR(2147483647), PRIMARY KEY(id))');
        */
    }
}
