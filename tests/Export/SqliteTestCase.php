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
 * Doctrine_Export_Sqlite_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Export_Sqlite_TestCase extends Doctrine_UnitTestCase 
{
    public function testCreateDatabaseDoesNotExecuteSql() 
    {
        try {
            $this->export->createDatabase('db');
            $this->fail();
        } catch(Doctrine_Export_Exception $e) {
            $this->pass();
        }
    }
    public function testDropDatabaseDoesNotExecuteSql() 
    {
        try {
            $this->export->dropDatabase('db');
            $this->fail();
        } catch(Doctrine_Export_Exception $e) {
            $this->pass();
        }
    }
    public function testCreateTableSupportsAutoincPks() 
    {
        $name = 'mytable';
        
        $fields  = array('id' => array('type' => 'integer', 'unsigned' => 1, 'autoincrement' => true));

        $this->export->createTable($name, $fields);

        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE mytable (id INTEGER PRIMARY KEY AUTOINCREMENT)');
    }
    public function testCreateTableSupportsDefaultAttribute() 
    {
        $name = 'mytable';
        $fields  = array('name' => array('type' => 'char', 'length' => 10, 'default' => 'def'),
                         'type' => array('type' => 'integer', 'length' => 3, 'default' => 12)
                         );

        $options = array('primary' => array('name', 'type'));
        $this->export->createTable($name, $fields, $options);

        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE mytable (name CHAR(10) DEFAULT \'def\', type INTEGER DEFAULT 12, PRIMARY KEY(name, type))');
    }
    public function testCreateTableSupportsMultiplePks() 
    {
        $name = 'mytable';
        $fields  = array('name' => array('type' => 'char', 'length' => 10),
                         'type' => array('type' => 'integer', 'length' => 3));
                         
        $options = array('primary' => array('name', 'type'));
        $this->export->createTable($name, $fields, $options);
        
        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE mytable (name CHAR(10), type INTEGER, PRIMARY KEY(name, type))');
    }
    public function testCreateTableSupportsIndexes()
    {
        $fields  = array('id' => array('type' => 'integer', 'unsigned' => 1, 'autoincrement' => true, 'unique' => true),
                         'name' => array('type' => 'string', 'length' => 4),
                         );

        $options = array('primary' => array('id'),
                         'indexes' => array('myindex' => array('fields' => array('id', 'name')))
                         );

        $this->export->createTable('sometable', $fields, $options);

        //this was the old line, but it looks like the table is created first 
        //and then the index so i replaced it with the ones below
        //$this->assertEqual($var, 'CREATE TABLE sometable (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(4), INDEX myindex (id, name))');

        $this->assertEqual($this->adapter->pop(),"CREATE INDEX myindex_idx ON sometable (id, name)");

        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE sometable (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(4))');
    }
    public function testUnknownIndexSortingAttributeThrowsException()
    {
        $fields = array('id' => array('sorting' => 'ASC'),
                        'name' => array('sorting' => 'unknown'));

        try {
            $this->export->getIndexFieldDeclarationList($fields);
            $this->fail();
        } catch(Doctrine_Export_Exception $e) {
            $this->pass();
        }
    }
    public function testCreateTableSupportsIndexesWithCustomSorting()
    {
        $fields  = array('id' => array('type' => 'integer', 'unsigned' => 1, 'autoincrement' => true, 'unique' => true),
                         'name' => array('type' => 'string', 'length' => 4),
                         );

        $options = array('primary' => array('id'),
                         'indexes' => array('myindex' => array(
                                                    'fields' => array(
                                                            'id' => array('sorting' => 'ASC'), 
                                                            'name' => array('sorting' => 'DESC')
                                                                )
                                                            ))
                         );

        $this->export->createTable('sometable', $fields, $options);
        
        //removed this assertion and inserted the two below
//        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE sometable (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(4), INDEX myindex (id ASC, name DESC))');

        $this->assertEqual($this->adapter->pop(),"CREATE INDEX myindex_idx ON sometable (id ASC, name DESC)");

        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE sometable (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(4))');

    }
    /**
    public function testExportSupportsEmulationOfCascadingDeletes()
    {
        $r = new ForeignKeyTest;

        $this->assertEqual($this->adapter->pop(), 'COMMIT');
        $this->assertEqual($this->adapter->pop(), 'CREATE TRIGGER doctrine_foreign_key_test_cscd_delete AFTER DELETE ON foreign_key_test BEGIN DELETE FROM foreign_key_test WHERE parent_id = old.id;END;');
        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE foreign_key_test (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(2147483647), code INTEGER, content VARCHAR(4000), parent_id INTEGER)');
        $this->assertEqual($this->adapter->pop(), 'BEGIN TRANSACTION');
    }
    */
}
?>
