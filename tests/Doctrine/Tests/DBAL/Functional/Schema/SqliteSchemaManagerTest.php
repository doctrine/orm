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