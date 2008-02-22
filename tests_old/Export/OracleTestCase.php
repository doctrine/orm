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
 * Doctrine_Export_Oracle_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Export_Oracle_TestCase extends Doctrine_UnitTestCase
{
    public function testCreateSequenceExecutesSql()
    {
        $sequenceName = 'sequence';
        $start = 1;
        $query = 'CREATE SEQUENCE ' . $sequenceName . '_seq START WITH ' . $start . ' INCREMENT BY 1 NOCACHE';

        $this->export->createSequence($sequenceName, $start);

        $this->assertEqual($this->adapter->pop(), $query);
    }

    public function testDropSequenceExecutesSql()
    {
        $sequenceName = 'sequence';

        $query = 'DROP SEQUENCE ' . $sequenceName;

        $this->export->dropSequence($sequenceName);

        $this->assertEqual($this->adapter->pop(), $query . '_seq');
    }
    public function testCreateTableExecutesSql()
    {
        $name = 'mytable';

        $fields  = array('id' => array('type' => 'integer'));
        $options = array('type' => 'MYISAM');

        $this->export->createTable($name, $fields);

        $this->assertEqual($this->adapter->pop(), 'COMMIT');
        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE mytable (id INT DEFAULT NULL)');
        $this->assertEqual($this->adapter->pop(), 'BEGIN TRANSACTION');
    }
    public function testCreateTableSupportsDefaultAttribute()
    {
        $name = 'mytable';
        $fields  = array('name' => array('type' => 'char', 'length' => 10, 'default' => 'def'),
                         'type' => array('type' => 'integer', 'length' => 3, 'default' => 12)
                         );

        $options = array('primary' => array('name', 'type'));
        $this->export->createTable($name, $fields, $options);


        $this->assertEqual($this->adapter->pop(), 'COMMIT');
        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE mytable (name CHAR(10) DEFAULT \'def\', type NUMBER(3) DEFAULT 12, PRIMARY KEY(name, type))');
        $this->assertEqual($this->adapter->pop(), 'BEGIN TRANSACTION');
    }
    public function testCreateTableSupportsMultiplePks()
    {
        $name = 'mytable';
        $fields  = array('name' => array('type' => 'char', 'length' => 10),
                         'type' => array('type' => 'integer', 'length' => 3));

        $options = array('primary' => array('name', 'type'));
        $this->export->createTable($name, $fields, $options);


        $this->assertEqual($this->adapter->pop(), 'COMMIT');
        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE mytable (name CHAR(10) DEFAULT NULL, type NUMBER(3) DEFAULT NULL, PRIMARY KEY(name, type))');
        $this->assertEqual($this->adapter->pop(), 'BEGIN TRANSACTION');
    }
    public function testCreateTableSupportsAutoincPks()
    {
        $name = 'mytable';

        $fields  = array('id' => array('type' => 'integer', 'autoincrement' => true));


        $this->export->createTable($name, $fields);

        $this->assertEqual($this->adapter->pop(), 'COMMIT');
        $this->assertEqual(substr($this->adapter->pop(),0, 14), 'CREATE TRIGGER');
        $this->assertEqual($this->adapter->pop(), 'CREATE SEQUENCE MYTABLE_seq START WITH 1 INCREMENT BY 1 NOCACHE');
        $this->assertEqual($this->adapter->pop(), 'ALTER TABLE MYTABLE ADD CONSTRAINT MYTABLE_AI_PK_idx PRIMARY KEY (id)');
        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE mytable (id INT DEFAULT NULL)');
        $this->assertEqual($this->adapter->pop(), 'BEGIN TRANSACTION');
    }

    public function testCreateTableSupportsCharType()
    {
        $name = 'mytable';

        $fields  = array('id' => array('type' => 'char', 'length' => 3));

        $this->export->createTable($name, $fields);

        $this->adapter->pop();
        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE mytable (id CHAR(3) DEFAULT NULL)');
    }
    public function testCreateTableSupportsUniqueConstraint()
    {
        $fields  = array('id' => array('type' => 'integer', 'unsigned' => 1, 'autoincrement' => true, 'unique' => true),
                         'name' => array('type' => 'string', 'length' => 4),
                         );

        $options = array('primary' => array('id'),
                         );

        $sql = $this->export->createTableSql('sometable', $fields, $options);

        $this->assertEqual($sql[0], 'CREATE TABLE sometable (id INT DEFAULT NULL UNIQUE, name VARCHAR2(4) DEFAULT NULL, PRIMARY KEY(id))');
    }
    public function testCreateTableSupportsIndexes()
    {
        $fields  = array('id' => array('type' => 'integer', 'unsigned' => 1, 'autoincrement' => true, 'unique' => true),
                         'name' => array('type' => 'string', 'length' => 4),
                         );

        $options = array('primary' => array('id'),
                         'indexes' => array('myindex' => array('fields' => array('id', 'name')))
                         );

        $sql = $this->export->createTableSql('sometable', $fields, $options);

        $this->assertEqual($sql[0], 'CREATE TABLE sometable (id INT DEFAULT NULL UNIQUE, name VARCHAR2(4) DEFAULT NULL, PRIMARY KEY(id), INDEX myindex (id, name))');
    }
}
