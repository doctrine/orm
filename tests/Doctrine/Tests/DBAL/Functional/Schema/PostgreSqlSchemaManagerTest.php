<?php

namespace Doctrine\Tests\DBAL\Functional\Schema;

use Doctrine\Tests\TestUtil;
use Doctrine\DBAL\Schema;
use Doctrine\DBAL\Types\Type;

require_once __DIR__ . '/../../../TestInit.php';
 
class PostgreSqlSchemaManagerTest extends \Doctrine\Tests\DbalFunctionalTestCase
{
    private $_conn;

    protected function setUp()
    {
        $this->_conn = TestUtil::getConnection();
        if ($this->_conn->getDatabasePlatform()->getName() !== 'postgresql')
        {
            $this->markTestSkipped('The PostgreSQLSchemaTest requires the use of postgresql');
        }
        $this->_sm = $this->_conn->getSchemaManager();
    }

    public function testListDatabases()
    {
        try {
            $this->_sm->dropDatabase('test_pgsql_create_database');
        } catch (\Exception $e) {}

        $this->_sm->createDatabase('test_pgsql_create_database');

        $databases = $this->_sm->listDatabases();

        $this->assertEquals(in_array('test_pgsql_create_database', $databases), true);
    }

    public function testListFunctions()
    {
        try {
            $this->_sm->listFunctions();
        } catch (\Exception $e) {
            return;
        }
 
        $this->fail('PostgreSql listFunctions() should throw an exception because it is not supported');
    }

    public function testListTriggers()
    {
        $triggers = $this->_sm->listTriggers();
        $this->assertEquals(true, is_array($triggers));
        $this->assertEquals(true, count($triggers) > 0);
    }

    public function testListSequences()
    {
        $columns = array(
            'id' => array(
                'type' => Type::getType('integer'),
                'autoincrement' => true,
                'primary' => true,
                'notnull' => true
            ),
            'test' => array(
                'type' => Type::getType('string'),
                'length' => 255
            )
        );

        $options = array();

        try {
            $this->_sm->dropTable('list_sequences_test');
        } catch (\Exception $e) {}

        $this->_sm->createTable('list_sequences_test', $columns, $options);

        $sequences = $this->_sm->listSequences();

        $this->assertEquals(true, in_array('list_sequences_test_id_seq', $sequences));
    }

    public function testListTableConstraints()
    {
        $columns = array(
            'id' => array(
                'type' => Type::getType('integer'),
                'autoincrement' => true,
                'primary' => true,
                'notnull' => true
            ),
            'test' => array(
                'type' => Type::getType('string'),
                'length' => 255
            )
        );

        $options = array();

        try {
            $this->_sm->dropTable('list_table_constraints_test');
        } catch (\Exception $e) {}

        $this->_sm->createTable('list_table_constraints_test', $columns, $options);

        $tableConstraints = $this->_sm->listTableConstraints('list_table_constraints_test');

        $this->assertEquals(array('list_table_constraints_test_pkey'), $tableConstraints);
    }

    public function testListTableColumns()
    {
        $columns = array(
            'id' => array(
                'type' => Type::getType('integer'),
                'autoincrement' => true,
                'primary' => true,
                'notnull' => true
            ),
            'test' => array(
                'type' => Type::getType('string'),
                'length' => 255
            )
        );

        $options = array();

        try {
            $this->_sm->dropTable('list_tables_test');
        } catch (\Exception $e) {}

        $this->_sm->createTable('list_tables_test', $columns, $options);

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
        $columns = array(
            'id' => array(
                'type' => Type::getType('integer'),
                'autoincrement' => true,
                'primary' => true,
                'notnull' => true
            ),
            'test' => array(
                'type' => Type::getType('string'),
                'length' => 255
            )
        );

        $options = array(
            'indexes' => array(
                'test' => array(
                    'fields' => array(
                        'test' => array()
                    ),
                    'type' => 'unique'
                )
            )
        );

        try {
            $this->_sm->dropTable('list_table_indexes_test');
        } catch (\Exception $e) {}

        $this->_sm->createTable('list_table_indexes_test', $columns, $options);

        $tableIndexes = $this->_sm->listTableIndexes('list_table_indexes_test');

        $this->assertEquals('test', $tableIndexes[0]['name']);
        $this->assertEquals(true, $tableIndexes[0]['unique']);
    }

    public function testListTables()
    {
        $columns = array(
            'id' => array(
                'type' => Type::getType('integer'),
                'autoincrement' => true,
                'primary' => true,
                'notnull' => true
            ),
            'test' => array(
                'type' => Type::getType('string'),
                'length' => 255
            )
        );

        $options = array();

        try {
            $this->_sm->dropTable('list_tables_test');
        } catch (\Exception $e) {}

        $this->_sm->createTable('list_tables_test', $columns, $options);

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
        try {
            $this->_sm->dropView('test_create_view');
        } catch (\Exception $e) {}

        $this->_sm->createView('test_create_view', 'SELECT usename, passwd FROM pg_user');
        $views = $this->_sm->listViews();

        $found = false;
        foreach ($views as $view) {
            if ($view['name'] == 'test_create_view') {
                $found = true;
                break;
            }
        }

        $this->assertEquals(true, $found);
        $this->assertEquals('SELECT pg_user.usename, pg_user.passwd FROM pg_user;', $view['sql']);
    }

    public function testListTableForeignKeys()
    {
        // Create table that has foreign key
        $columns = array(
            'id' => array(
                'type' => Type::getType('integer'),
                'autoincrement' => true,
                'primary' => true,
                'notnull' => true
            ),
            'test' => array(
                'type' => Type::getType('integer'),
                'length' => 4
            )
        );

        $options = array('type' => 'innodb');

        try {
            $this->_sm->dropTable('list_table_foreign_keys_test2');
        } catch (\Exception $e) {}

        $this->_sm->createTable('list_table_foreign_keys_test2', $columns, $options);

        // Create the table that is being referenced in the foreign key
        $columns = array(
            'id' => array(
                'type' => Type::getType('integer'),
                'autoincrement' => true,
                'primary' => true,
                'notnull' => true
            ),
            'whatever' => array(
                'type' => Type::getType('string'),
                'length' => 255
            )
        );

        $options = array('type' => 'innodb');

        try {
            $this->_sm->dropTable('list_table_foreign_keys_test');
        } catch (\Exception $e) {}

        $this->_sm->createTable('list_table_foreign_keys_test', $columns, $options);

        // Create the foreign key between the tables
        $definition = array(
            'name' => 'testing',
            'local' => 'test',
            'foreign' => 'id',
            'foreignTable' => 'list_table_foreign_keys_test'
        );
        $this->_sm->createForeignKey('list_table_foreign_keys_test2', $definition);

        $tableForeignKeys = $this->_sm->listTableForeignKeys('list_table_foreign_keys_test2');
        $this->assertEquals(1, count($tableForeignKeys));
        $this->assertEquals('list_table_foreign_keys_test', $tableForeignKeys[0]['table']);
        $this->assertEquals('test', $tableForeignKeys[0]['local']);
        $this->assertEquals('id', $tableForeignKeys[0]['foreign']);
    }
}