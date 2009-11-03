<?php

namespace Doctrine\Tests\DBAL\Functional\Schema;

use Doctrine\DBAL\Schema;

require_once __DIR__ . '/../../../TestInit.php';
 
class PostgreSqlSchemaManagerTest extends SchemaManagerFunctionalTestCase
{
    public function testListSequences()
    {
        $this->createTestTable('list_sequences_test');
        $sequences = $this->_sm->listSequences();
        $this->assertEquals(true, in_array('list_sequences_test_id_seq', $sequences));
    }

    public function testListTableConstraints()
    {
        $this->createTestTable('list_table_constraints_test');
        $tableConstraints = $this->_sm->listTableConstraints('list_table_constraints_test');
        $this->assertEquals(array('list_table_constraints_test_pkey'), $tableConstraints);
    }

    /*public function testListTableColumns()
    {
        $this->createTestTable('list_tables_test');
        $columns = $this->_sm->listTableColumns('list_tables_test');

        $this->assertEquals('id', $columns[0]['name']);
        $this->assertEquals(true, $columns[0]['primary']);
        $this->assertEquals('Doctrine\DBAL\Types\IntegerType', get_class($columns[0]['type']));
        $this->assertEquals(null, $columns[0]['length']);
        $this->assertEquals(false, $columns[0]['fixed']);
        $this->assertEquals(true, $columns[0]['notnull']);
        $this->assertEquals(null, $columns[0]['default']);

        $this->assertEquals('test', $columns[1]['name']);
        $this->assertEquals(false, $columns[1]['primary']);
        $this->assertEquals('Doctrine\DBAL\Types\StringType', get_class($columns[1]['type']));
        $this->assertEquals(255, $columns[1]['length']);
        $this->assertEquals(false, $columns[1]['fixed']);
        $this->assertEquals(false, $columns[1]['notnull']);
        $this->assertEquals(null, $columns[1]['default']);
    }*/

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
        return 'SELECT usename, passwd FROM pg_user';
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