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
 * Doctrine_Export_Record_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Export_Record_TestCase extends Doctrine_UnitTestCase
{
    public function prepareTables() 
    { }
    public function prepareData() 
    { }
    public function setUp() {
        $this->driverName = 'mysql';
        if ( ! $this->init) {
            $this->init();
        }

        $this->init    = true;
    }

    public function testExportSupportsForeignKeys()
    {
        $sql = $this->conn->export->exportClassesSql(array('ForeignKeyTest'));

        $this->assertEqual($sql[0], 'CREATE TABLE foreign_key_test (id BIGINT AUTO_INCREMENT, name TEXT, code INT, content TEXT, parent_id BIGINT, INDEX parent_id_idx (parent_id), PRIMARY KEY(id)) ENGINE = INNODB');
        if (isset($sql[1])) {
            $this->assertEqual($sql[1], 'ALTER TABLE foreign_key_test ADD FOREIGN KEY (parent_id) REFERENCES foreign_key_test(id) ON UPDATE RESTRICT ON DELETE CASCADE');
        } else {
            $this->fail('$sql should contain ALTER TABLE statement');
        }
    }

    public function testExportSupportsIndexes()
    {
        $sql = $this->conn->export->exportClassesSql(array('MysqlIndexTestRecord'));

        $this->assertEqual($sql[0], 'CREATE TABLE mysql_index_test_record (id BIGINT AUTO_INCREMENT, name TEXT, code INT, content TEXT, FULLTEXT INDEX content_idx (content), UNIQUE INDEX namecode_idx (name, code), PRIMARY KEY(id)) ENGINE = MYISAM');
    }

    public function testRecordDefinitionsSupportTableOptions()
    {
        $sql = $this->conn->export->exportClassesSql(array('MysqlTestRecord'));

        $this->assertEqual($sql[0], 'CREATE TABLE mysql_test_record (name TEXT, code BIGINT, PRIMARY KEY(name, code)) ENGINE = INNODB');
    }

    public function testExportSupportsForeignKeysWithoutAttributes()
    {
        $sql = $this->conn->export->exportClassesSql(array('ForeignKeyTest'));

        $this->assertEqual($sql[0], 'CREATE TABLE foreign_key_test (id BIGINT AUTO_INCREMENT, name TEXT, code INT, content TEXT, parent_id BIGINT, INDEX parent_id_idx (parent_id), PRIMARY KEY(id)) ENGINE = INNODB');
        if (isset($sql[1])) {
            $this->assertEqual($sql[1], 'ALTER TABLE foreign_key_test ADD FOREIGN KEY (parent_id) REFERENCES foreign_key_test(id) ON UPDATE RESTRICT ON DELETE CASCADE');
        } else {
            $this->fail('$sql should contain ALTER TABLE statement');
        }
    }

    public function testExportSupportsForeignKeysForManyToManyRelations()
    {
        $sql = $this->conn->export->exportClassesSql(array('MysqlUser'));

        $this->assertEqual($sql[0], 'CREATE TABLE mysql_user (id BIGINT AUTO_INCREMENT, name TEXT, PRIMARY KEY(id)) ENGINE = INNODB');

        $sql = $this->conn->export->exportClassesSql(array('MysqlGroup'));

        $this->assertEqual($sql[0], 'CREATE TABLE mysql_group (id BIGINT AUTO_INCREMENT, name TEXT, PRIMARY KEY(id)) ENGINE = INNODB');
    }

    public function testExportModelFromDirectory()
    {
        
        Doctrine::createTablesFromModels(dirname(__FILE__) . DIRECTORY_SEPARATOR .'..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'export');
        $this->assertEqual($this->adapter->pop(), 'COMMIT');
        $this->assertEqual($this->adapter->pop(), 'ALTER TABLE cms__category_languages ADD FOREIGN KEY (category_id) REFERENCES cms__category(id) ON DELETE CASCADE');
        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE cms__category_languages (id BIGINT AUTO_INCREMENT, name TEXT, category_id BIGINT, language_id BIGINT, INDEX index_category_idx (category_id), INDEX index_language_idx (language_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = INNODB');
        $this->assertEqual($this->adapter->pop(), 'CREATE TABLE cms__category (id BIGINT AUTO_INCREMENT, created DATETIME, parent BIGINT, position MEDIUMINT, active BIGINT, INDEX index_parent_idx (parent), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = INNODB');   
        $this->assertEqual($this->adapter->pop(), 'BEGIN TRANSACTION');
    }

}
