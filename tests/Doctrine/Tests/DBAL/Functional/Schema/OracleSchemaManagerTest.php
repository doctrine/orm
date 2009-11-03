<?php

namespace Doctrine\Tests\DBAL\Functional\Schema;

use Doctrine\DBAL\Schema;

require_once __DIR__ . '/../../../TestInit.php';
 
class OracleSchemaManagerTest extends SchemaManagerFunctionalTestCase
{
    public function testListDatabases()
    {
        $this->_sm->dropAndCreateDatabase('test_oracle_create_database');
        $databases = $this->_sm->listDatabases();
        $this->assertEquals(true, in_array('TEST_ORACLE_CREATE_DATABASE', $databases));
    }

    public function testListFunctions()
    {
        $functions = $this->_sm->listFunctions();
        $this->assertEquals(array(), $functions);
    }

    /**
     * @expectedException \Exception
     */
    public function testListTriggers()
    {
        $this->_sm->listTriggers();
    }

    public function testListSequences()
    {
        $this->createTestTable('list_sequences_test');
        $sequences = $this->_sm->listSequences();
        $this->assertEquals(true, in_array('LIST_SEQUENCES_TEST_SEQ', $sequences));
    }

    public function testListTableConstraints()
    {
        $this->createTestTable('test_constraints');
        $tableConstraints = $this->_sm->listTableConstraints('test_constraints');
        $this->assertEquals(2, count($tableConstraints));
    }

    public function testListTableColumns()
    {
        $this->createTestTable('list_tables_test');

        $columns = $this->_sm->listTableColumns('list_tables_test');

        $this->assertEquals('ID', $columns[1]['name']);
        $this->assertEquals('Doctrine\DBAL\Types\IntegerType', get_class($columns[1]['type']));
        $this->assertEquals(22, $columns[1]['length']);
        $this->assertEquals(false, $columns[1]['unsigned']);
        $this->assertEquals(false, $columns[1]['fixed']);
        $this->assertEquals(true, $columns[1]['notnull']);
        $this->assertEquals(null, $columns[1]['default']);

        $this->assertEquals('TEST', $columns[2]['name']);
        $this->assertEquals('Doctrine\DBAL\Types\StringType', get_class($columns[2]['type']));
        $this->assertEquals(255, $columns[2]['length']);
        $this->assertEquals(false, $columns[2]['unsigned']);
        $this->assertEquals(false, $columns[2]['fixed']);
        $this->assertEquals(false, $columns[2]['notnull']);
        $this->assertEquals(null, $columns[2]['default']);
    }

    public function testListTables()
    {
        $this->createTestTable('list_tables_test');
        $tables = $this->_sm->listTables();
        $this->assertEquals(true, in_array('LIST_TABLES_TEST', $tables));
    }

    public function testListUsers()
    {
        $users = $this->_sm->listUsers();
        $this->assertEquals(true, is_array($users));
        $params = $this->_conn->getParams();
        $testUser = strtoupper($params['user']);
        $found = false;
        foreach ($users as $user) {
            if ($user['user'] == $testUser) {
                $found = true;
            }
        }
        $this->assertEquals(true, $found);
    }

    public function testListViews()
    {
        $this->_sm->dropAndCreateView('test_create_view', 'SELECT * FROM sys.user_tables');

        $views = $this->_sm->listViews();
        $view = end($views);

        $this->assertEquals('TEST_CREATE_VIEW', $view['name']);
    }

    public function testListTableForeignKeys()
    {
        return $this->assertUnsupportedMethod('listTableForeignKeys');
    }

    public function testRenameTable()
    {
        $this->_sm->tryDropTable('list_tables_test');
        $this->_sm->tryDropTable('list_tables_test_new_name');

        $this->createTestTable('list_tables_test');
        $this->_sm->renameTable('list_tables_test', 'list_tables_test_new_name');

        $tables = $this->_sm->listTables();
        $this->assertEquals(true, in_array('LIST_TABLES_TEST_NEW_NAME', $tables));
    }

    public function testDropAndCreate()
    {
        $this->_sm->dropAndCreateView('testing_a_new_view', 'SELECT * FROM sys.user_tables');
        $this->_sm->dropAndCreateView('testing_a_new_view', 'SELECT * FROM sys.user_tables');
    }
}