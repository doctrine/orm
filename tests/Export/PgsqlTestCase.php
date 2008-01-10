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
 * Doctrine_Export_Mysql_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Export_Pgsql_TestCase extends Doctrine_UnitTestCase
{
    public function testCreateDatabaseExecutesSql()
    {
        $this->export->createDatabase('db');
        $this->assertEqual($this->adapter->pop(), 'CREATE DATABASE db');
    }
    public function testDropDatabaseExecutesSql()
    {
        $this->export->dropDatabase('db');

        $this->assertEqual($this->adapter->pop(), 'DROP DATABASE db');
    }
    public function testCreateTableSupportsAutoincPks()
    {
        $name = 'mytable';

        $fields  = array('id' => array('type' => 'integer', 'unsigned' => 1, 'autoincrement' => true));
        $options = array('primary' => array('id'));

        $this->export->createTable($name, $fields, $options);

        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE mytable (id SERIAL, PRIMARY KEY(id))');
    }
    public function testQuoteAutoincPks()
    {
        $this->conn->setAttribute(Doctrine::ATTR_QUOTE_IDENTIFIER, true);

        $name = 'mytable';

        $fields  = array('id' => array('type' => 'integer', 'unsigned' => 1, 'autoincrement' => true));
        $options = array('primary' => array('id'));

        $this->export->createTable($name, $fields, $options);

        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE "mytable" ("id" SERIAL, PRIMARY KEY("id"))');

        $name = 'mytable';
        $fields  = array('name' => array('type' => 'char', 'length' => 10),
                         'type' => array('type' => 'integer', 'length' => 3));

        $options = array('primary' => array('name', 'type'));
        $this->export->createTable($name, $fields, $options);

        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE "mytable" ("name" CHAR(10) DEFAULT NULL, "type" INT, PRIMARY KEY("name", "type"))');

        $this->conn->setAttribute(Doctrine::ATTR_QUOTE_IDENTIFIER, false);
    }
    public function testForeignKeyIdentifierQuoting()
    {
        $this->conn->setAttribute(Doctrine::ATTR_QUOTE_IDENTIFIER, true);

        $name = 'mytable';

        $fields = array('id' => array('type' => 'boolean', 'primary' => true),
                        'foreignKey' => array('type' => 'integer')
                        );
        $options = array('foreignKeys' => array(array('local' => 'foreignKey',
                                                      'foreign' => 'id',
                                                      'foreignTable' => 'sometable'))
                         );


        $sql = $this->export->createTableSql($name, $fields, $options);

        $this->assertEqual($sql[0], 'CREATE TABLE "mytable" ("id" BOOLEAN DEFAULT NULL, "foreignKey" INT)');
        $this->assertEqual($sql[1], 'ALTER TABLE "mytable" ADD FOREIGN KEY ("foreignKey") REFERENCES "sometable"("id") NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->conn->setAttribute(Doctrine::ATTR_QUOTE_IDENTIFIER, false);
    }
    public function testCreateTableSupportsDefaultAttribute()
    {
        $name = 'mytable';
        $fields  = array('name' => array('type' => 'char', 'length' => 10, 'default' => 'def'),
                         'type' => array('type' => 'integer', 'length' => 3, 'default' => 12)
                         );

        $options = array('primary' => array('name', 'type'));
        $this->export->createTable($name, $fields, $options);

        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE mytable (name CHAR(10) DEFAULT \'def\', type INT DEFAULT 12, PRIMARY KEY(name, type))');
    }
    public function testCreateTableSupportsMultiplePks()
     {
        $name = 'mytable';
        $fields  = array('name' => array('type' => 'char', 'length' => 10),
                         'type' => array('type' => 'integer', 'length' => 3));

        $options = array('primary' => array('name', 'type'));
        $this->export->createTable($name, $fields, $options);

        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE mytable (name CHAR(10) DEFAULT NULL, type INT, PRIMARY KEY(name, type))');
    }
    public function testExportSql()
    {
        $sql = $this->export->exportClassesSql(array("FooRecord", "FooReferenceRecord", "FooLocallyOwned", "FooForeignlyOwned", "FooForeignlyOwnedWithPK", "FooBarRecord", "BarRecord"));
        //dirname(__FILE__) . DIRECTORY_SEPARATOR . '_files');

        $this->assertEqual($sql, array ( 0 => 'CREATE TABLE foo_reference (foo1 BIGINT, foo2 BIGINT, PRIMARY KEY(foo1, foo2))',
                                         1 => 'CREATE TABLE foo_locally_owned (id BIGSERIAL, name VARCHAR(200) DEFAULT NULL, PRIMARY KEY(id))',
                                         2 => 'CREATE TABLE foo_foreignly_owned_with_pk (id BIGSERIAL, name VARCHAR(200) DEFAULT NULL, PRIMARY KEY(id))',
                                         3 => 'CREATE TABLE foo_foreignly_owned (id BIGSERIAL, name VARCHAR(200) DEFAULT NULL, fooid BIGINT, PRIMARY KEY(id))',
                                         4 => 'CREATE TABLE foo_bar_record (fooid BIGINT, barid BIGINT, PRIMARY KEY(fooid, barid))',
                                         5 => 'CREATE TABLE foo (id BIGSERIAL, name VARCHAR(200) NOT NULL, parent_id BIGINT, local_foo BIGINT, PRIMARY KEY(id))',
                                         6 => 'CREATE TABLE bar (id BIGSERIAL, name VARCHAR(200) DEFAULT NULL, PRIMARY KEY(id))',
                                         7 => 'ALTER TABLE foo_reference ADD FOREIGN KEY (foo1) REFERENCES foo(id) NOT DEFERRABLE INITIALLY IMMEDIATE',
                                         8 => 'ALTER TABLE foo_bar_record ADD FOREIGN KEY (fooId) REFERENCES foo(id) NOT DEFERRABLE INITIALLY IMMEDIATE',
                                         9 => 'ALTER TABLE foo ADD FOREIGN KEY (parent_id) REFERENCES foo(id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE',
                                         10 => 'ALTER TABLE foo ADD FOREIGN KEY (local_foo) REFERENCES foo_locally_owned(id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE',
                                         ));
    }
}
