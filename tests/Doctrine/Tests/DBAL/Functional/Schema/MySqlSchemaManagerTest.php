<?php

namespace Doctrine\Tests\DBAL\Functional\Schema;

use Doctrine\DBAL\Schema;

require_once __DIR__ . '/../../../TestInit.php';
 
class MySqlSchemaManagerTest extends SchemaManagerFunctionalTestCase
{
    public function testListDatabases()
    {
        $this->_sm->dropAndCreateDatabase('test_create_database');
        $databases = $this->_sm->listDatabases();
        $this->assertEquals(true, in_array('test_create_database', $databases));
    }

    /**
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

    public function testListTableColumns()
    {
        $this->createTestTable('list_tables_test');

        $columns = $this->_sm->listTableColumns('list_tables_test');

        $this->assertEquals('id', $columns[0]['name']);
        $this->assertEquals(true, $columns[0]['primary']);
        $this->assertEquals('Doctrine\DBAL\Types\IntegerType', get_class($columns[0]['type']));
        $this->assertEquals(4, $columns[0]['length']);
        $this->assertEquals(false, $columns[0]['unsigned']);
        $this->assertEquals(false, $columns[0]['fixed']);
        $this->assertEquals(true, $columns[0]['notnull']);
        $this->assertEquals(null, $columns[0]['default']);

        $this->assertEquals('test', $columns[1]['name']);
        $this->assertEquals(false, $columns[1]['primary']);
        $this->assertEquals('Doctrine\DBAL\Types\StringType', get_class($columns[1]['type']));
        $this->assertEquals(255, $columns[1]['length']);
        $this->assertEquals(false, $columns[1]['unsigned']);
        $this->assertEquals(false, $columns[1]['fixed']);
        $this->assertEquals(false, $columns[1]['notnull']);
        $this->assertEquals(null, $columns[1]['default']);
    }

    public function testListTableIndexes()
    {
        $data['options'] = array(
            'indexes' => array(
                'test_index_name' => array(
                    'fields' => array(
                        'test' => array()
                    ),
                    'type' => 'unique'
                )
            )
        );

        $this->createTestTable('list_table_indexes_test', $data);

        $tableIndexes = $this->_sm->listTableIndexes('list_table_indexes_test');

        $this->assertEquals('test_index_name', $tableIndexes[0]['name']);
        $this->assertEquals('test', $tableIndexes[0]['column']);
        $this->assertEquals(true, $tableIndexes[0]['unique']);
    }

    public function testListTables()
    {
        $this->createTestTable('list_tables_test');
        $tables = $this->_sm->listTables();
        $this->assertEquals(true, in_array('list_tables_test', $tables));
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

    public function testListViews()
    {
        $this->_sm->dropAndCreateView('test_create_view', 'SELECT * from mysql.user');
        $views = $this->_sm->listViews();
        $this->assertEquals('test_create_view', $views[0]);
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

    public function testDropAndCreate()
    {
        $this->_sm->dropAndCreateView('testing_a_new_view', 'SELECT * from mysql.user');
        $this->_sm->dropAndCreateView('testing_a_new_view', 'SELECT * from mysql.user');
    }
}