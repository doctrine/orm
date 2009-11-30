<?php

namespace Doctrine\Tests\DBAL\Functional\Schema;

use Doctrine\DBAL\Schema;

require_once __DIR__ . '/../../../TestInit.php';
 
class OracleSchemaManagerTest extends SchemaManagerFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        if(!isset($GLOBALS['db_username'])) {
            $this->markTestSkipped('Foo');
        }

        $username = $GLOBALS['db_username'];

        $query = "GRANT ALL PRIVILEGES TO ".$username;

        $conn = \Doctrine\Tests\TestUtil::getTempConnection();
        $conn->executeUpdate($query);
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
        $this->createTestTable('test_constraints');
        $tableConstraints = $this->_sm->listTableConstraints('test_constraints');
        $this->assertEquals(2, count($tableConstraints));
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

    public function testRenameTable()
    {
        $this->_sm->tryMethod('DropTable', 'list_tables_test');
        $this->_sm->tryMethod('DropTable', 'list_tables_test_new_name');

        $this->createTestTable('list_tables_test');
        $this->_sm->renameTable('list_tables_test', 'list_tables_test_new_name');

        $tables = $this->_sm->listTables();
        $this->assertEquals(true, in_array('LIST_TABLES_TEST_NEW_NAME', $tables));
    }
}