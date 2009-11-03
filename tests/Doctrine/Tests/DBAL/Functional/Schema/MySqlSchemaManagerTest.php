<?php

namespace Doctrine\Tests\DBAL\Functional\Schema;

use Doctrine\DBAL\Schema;

require_once __DIR__ . '/../../../TestInit.php';
 
class MySqlSchemaManagerTest extends SchemaManagerFunctionalTestCase
{
    public function testListSequences()
    {
        $this->createTestTable('list_sequences_test');
        $sequences = $this->_sm->listSequences();
        $this->assertEquals(true, in_array('list_sequences_test', $sequences));
    }

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
    
    public function testListTableForeignKeys()
    {
        $data['options'] = array('type' => 'innodb');
        $this->createTestTable('list_table_foreign_keys_test1', $data);
        $this->createTestTable('list_table_foreign_keys_test2', $data);
        
        $definition = array(
            'name' => 'testing',
            'local' => 'foreign_key_test',
            'foreign' => 'id',
            'foreignTable' => 'list_table_foreign_keys_test2'
        );
        $this->_sm->createForeignKey('list_table_foreign_keys_test1', $definition);
        
        $tableForeignKeys = $this->_sm->listTableForeignKeys('list_table_foreign_keys_test1');
        $this->assertEquals(1, count($tableForeignKeys));
        $this->assertEquals('list_table_foreign_keys_test2', $tableForeignKeys[0]['table']);
        $this->assertEquals('foreign_key_test', $tableForeignKeys[0]['local']);
        $this->assertEquals('id', $tableForeignKeys[0]['foreign']);
    }
}