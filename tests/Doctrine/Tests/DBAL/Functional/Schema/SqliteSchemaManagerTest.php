<?php

namespace Doctrine\Tests\DBAL\Functional\Schema;

use Doctrine\DBAL\Schema;

require_once __DIR__ . '/../../../TestInit.php';
 
class SqliteSchemaManagerTest extends SchemaManagerFunctionalTestCase
{
    /**
     * SQLITE does not support databases.
     * 
     * @expectedException \Exception
     */
    public function testListDatabases()
    {
        $this->_sm->listDatabases();
    }

    /**
     * SQLITE does not support databases.
     *
     * @expectedException \Exception
     */
    public function testListFunctions()
    {
        $this->_sm->listFunctions();
    }

    /**
     * @expectedException \Exception
     */
    public function testListTriggers()
    {
        $this->_sm->listTriggers();
    }

    public function testListTableConstraints()
    {
        // TODO: Implement support for constraints/foreign keys to be specified
        // when creating tables. Sqlite does not support adding them after
        // the table has already been created
        $tableConstraints = $this->_sm->listTableConstraints('list_table_constraints_test');
        $this->assertEquals(array(), $tableConstraints);
    }

    /*public function testListTableColumns()
    {
        $this->createTestTable('list_table_columns_test');

        $tableColumns = $this->_sm->listTableColumns('list_table_columns_test');

        $this->assertEquals('id', $tableColumns[0]['name']);
        $this->assertEquals(true, $tableColumns[0]['primary']);
        $this->assertEquals('Doctrine\DBAL\Types\IntegerType', get_class($tableColumns[0]['type']));
        $this->assertEquals(null, $tableColumns[0]['length']);
        $this->assertEquals(false, $tableColumns[0]['unsigned']);
        $this->assertEquals(false, $tableColumns[0]['fixed']);
        $this->assertEquals(true, $tableColumns[0]['notnull']);
        $this->assertEquals(null, $tableColumns[0]['default']);

        $this->assertEquals('test', $tableColumns[1]['name']);
        $this->assertEquals(false, $tableColumns[1]['primary']);
        $this->assertEquals('Doctrine\DBAL\Types\StringType', get_class($tableColumns[1]['type']));
        $this->assertEquals(255, $tableColumns[1]['length']);
        $this->assertEquals(false, $tableColumns[1]['unsigned']);
        $this->assertEquals(false, $tableColumns[1]['fixed']);
        $this->assertEquals(false, $tableColumns[1]['notnull']);
        $this->assertEquals(null, $tableColumns[1]['default']);
    }*/

    /**
     * @expectedException \Exception
     */
    public function testListUsers()
    {
        $this->_sm->listUsers();
    }

    protected function getCreateExampleViewSql()
    {
        $this->createTestTable('test_views');
        return 'SELECT * from test_views';
    }

    public function testCreateAndDropDatabase()
    {
        $path = dirname(__FILE__).'/test_create_and_drop_sqlite_database.sqlite';

        $this->_sm->createDatabase($path);
        $this->assertEquals(true, file_exists($path));
        $this->_sm->dropDatabase($path);
        $this->assertEquals(false, file_exists($path));
    }

    /**
     * @expectedException \Exception
     */
    public function testCreateSequence()
    {
        $this->_sm->createSequence('seqname', 1, 1);
    }

    /**
     * @expectedException \Exception
     */
    public function testCreateForeignKey()
    {
        $this->_sm->createForeignKey('table', array());
    }

    /**
     * @expectedException \Exception
     */
    public function testRenameTable()
    {
        $this->_sm->renameTable('oldname', 'newname');
    }

    /**
     * @expectedException \Exception
     */
    public function testAddTableColumn()
    {
        return $this->_sm->addTableColumn('table', 'column', array());
    }

    /**
     * @expectedException \Exception
     */
    public function testRemoveTableColumn()
    {
        $this->_sm->removeTableColumn('table', 'column');
    }

    /**
     * @expectedException \Exception
     */
    public function testChangeTableColumn()
    {
        $this->_sm->changeTableColumn('name', 'type', null, array());
    }
    
    /**
     * @expectedException \Exception
     */
    public function testRenameTableColumn()
    {
        $this->_sm->renameTableColumn('table', 'old', 'new', array());
    }
}