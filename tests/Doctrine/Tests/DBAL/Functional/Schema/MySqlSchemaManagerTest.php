<?php

namespace Doctrine\Tests\DBAL\Functional\Schema;

use Doctrine\DBAL\Schema;

require_once __DIR__ . '/../../../TestInit.php';
 
class MySqlSchemaManagerTest extends SchemaManagerFunctionalTestCase
{
    public function testListTableConstraints()
    {
        $this->createTestTable('list_table_constraints_test');
        $tableConstraints = $this->_sm->listTableConstraints('list_table_constraints_test');
        $this->assertEquals(array('PRIMARY'), $tableConstraints);
    }

    public function testListUsers()
    {
        $users = $this->_sm->listUsers();
        $this->assertEquals(true, is_array($users));
        $params = $this->_conn->getParams();
        $testUser = $params['user'];
        $found = false;
        foreach ($users as $user) {
            if ($user['user'] == $testUser) {
                $found = true;
            }
        }
        $this->assertEquals(true, $found);
    }

    protected function getCreateExampleViewSql()
    {
        return 'SELECT * from mysql.user';
    }
}