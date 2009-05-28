<?php

namespace Doctrine\Tests\DBAL\Functional\Schema;

use Doctrine\Tests\TestUtil;
use Doctrine\DBAL\Schema;
use Doctrine\DBAL\Types\Type;

require_once __DIR__ . '/../../../TestInit.php';
 
class SqliteSchemaManagerTest extends \Doctrine\Tests\DbalFunctionalTestCase
{
    private $_conn;

    protected function setUp()
    {
        $this->_conn = TestUtil::getConnection();
        if ($this->_conn->getDatabasePlatform()->getName() !== 'sqlite')
        {
            $this->markTestSkipped('The SqliteSchemaTest requires the use of sqlite');
        }
        $this->_sm = $this->_conn->getSchemaManager();
    }

    public function testListDatabases()
    {
        try {
            $this->_sm->listDatabases();
        } catch (\Exception $e) {
            return;
        }
 
        $this->fail('Sqlite listDatabases() should throw an exception because it is not supported');
    }

    public function testListFunctions()
    {
        try {
            $this->_sm->listFunctions();
        } catch (\Exception $e) {
            return;
        }
 
        $this->fail('Sqlite listFunctions() should throw an exception because it is not supported');
    }

    public function testListTriggers()
    {
        try {
            $this->_sm->listTriggers();
        } catch (\Exception $e) {
            return;
        }
 
        $this->fail('Sqlite listTriggers() should throw an exception because it is not supported');
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

        $this->_sm->createTable('list_sequences_test', $columns, $options);

        $sequences = $this->_sm->listSequences();
        $this->assertEquals('list_sequences_test', $sequences[0]['name']);
        $this->assertEquals('sqlite_sequence', $sequences[1]['name']);
    }

    public function testListTableConstraints()
    {
        // TODO: Implement support for constraints/foreign keys to be specified
        // when creating tables. Sqlite does not support adding them after
        // the table has already been created
        $tableConstraints = $this->_sm->listTableConstraints('list_table_constraints_test');
        $this->assertEquals(array(), $tableConstraints);
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

        $this->_sm->createTable('list_table_columns_test', $columns, $options);

        $tableColumns = $this->_sm->listTableColumns('list_table_columns_test');

        $this->assertEquals('id', $tableColumns[0]['name']);
        $this->assertEquals(true, $tableColumns[0]['primary']);
        $this->assertEquals('Doctrine\DBAL\Types\IntegerType', get_class($tableColumns[0]['type']));
        $this->assertEquals(4, $tableColumns[0]['length']);
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

        $this->_sm->createTable('list_tables_test', $columns, $options);

        $tables = $this->_sm->listTables();
        $this->assertEquals(true, in_array('list_tables_test', $tables));
    }

    public function testListUsers()
    {
        try {
            $this->_sm->listUsers();
        } catch (\Exception $e) {
            return;
        }
 
        $this->fail('Sqlite listUsers() should throw an exception because it is not supported');
    }

    public function testListViews()
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

        $this->_sm->createTable('test_views', $columns, $options);

        try {
            $this->_sm->dropView('test_create_view');
        } catch (\Exception $e) {}

        $this->_sm->createView('test_create_view', 'SELECT * from test_views');
        $views = $this->_sm->listViews();

        $this->assertEquals('test_create_view', $views[0]['name']);
        $this->assertEquals('CREATE VIEW test_create_view AS SELECT * from test_views', $views[0]['sql']);
    }

    public function testCreateAndDropDatabase()
    {
        $path = dirname(__FILE__).'/test_create_and_drop_sqlite_database.sqlite';
        $config = new \Doctrine\ORM\Configuration();
        $eventManager = new \Doctrine\Common\EventManager();
        $connectionOptions = array(
            'driver' => 'pdo_sqlite',
            'path' => $path
        );
        $em = \Doctrine\ORM\EntityManager::create($connectionOptions, $config, $eventManager);
        $conn = $em->getConnection();
        $sm = $conn->getSchemaManager();

        $sm->createDatabase();
        $this->assertEquals(true, file_exists($path));
        $sm->dropDatabase();
        $this->assertEquals(false, file_exists($path));
        $sm->createDatabase();
        $this->assertEquals(true, file_exists($path));
        $sm->dropDatabase();
    }

    public function testCreateTable()
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

        $this->_sm->createTable('test_create_table', $columns, $options);
        $tables = $this->_sm->listTables();
        $this->assertEquals(true, in_array('test_create_table', $tables));

        $tableColumns = $this->_sm->listTableColumns('test_create_table');

        $this->assertEquals('id', $tableColumns[0]['name']);
        $this->assertEquals(true, $tableColumns[0]['primary']);
        $this->assertEquals('Doctrine\DBAL\Types\IntegerType', get_class($tableColumns[0]['type']));
        $this->assertEquals(4, $tableColumns[0]['length']);
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
    }

    public function testCreateSequence()
    {
        try {
            $this->_sm->createSequence();
        } catch (\Exception $e) {
            return;
        }
 
        $this->fail('Sqlite createSequence() should throw an exception because it is not supported');
    }

    public function testCreateConstraint()
    {
        try {
            $this->_sm->createConstraint();
        } catch (\Exception $e) {
            return;
        }
 
        $this->fail('Sqlite createConstraint() should throw an exception because it is not supported');
    }

    public function testCreateIndex()
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

        $this->_sm->createTable('test_create_index', $columns, $options);
        
        $index = array(
            'fields' => array(
                'test' => array()
            ),
            'type' => 'unique'
        );

        $this->_sm->createIndex('test_create_index', 'test', $index);
        $tableIndexes = $this->_sm->listTableIndexes('test_create_index');
        $this->assertEquals('test', $tableIndexes[0]['name']);
        $this->assertEquals(true, $tableIndexes[0]['unique']);
    }

    public function testCreateForeignKey()
    {
        try {
            $this->_sm->createForeignKey();
        } catch (\Exception $e) {
            return;
        }
 
        $this->fail('Sqlite createForeignKey() should throw an exception because it is not supported');
    }

    public function testRenameTable()
    {
        try {
            $this->_sm->renameTable();
        } catch (\Exception $e) {
            return;
        }
 
        $this->fail('Sqlite renameTable() should throw an exception because it is not supported');
    }


    public function testAddTableColumn()
    {
        try {
            $this->_sm->addTableColumn();
        } catch (\Exception $e) {
            return;
        }
 
        $this->fail('Sqlite addTableColumn() should throw an exception because it is not supported');
    }

    public function testRemoveTableColumn()
    {
        try {
            $this->_sm->removeTableColumn();
        } catch (\Exception $e) {
            return;
        }
 
        $this->fail('Sqlite removeTableColumn() should throw an exception because it is not supported');
    }

    public function testChangeTableColumn()
    {
        try {
            $this->_sm->changeTableColumn();
        } catch (\Exception $e) {
            return;
        }
 
        $this->fail('Sqlite changeTableColumn() should throw an exception because it is not supported');
    }

    public function testRenameTableColumn()
    {
        try {
            $this->_sm->renameTableColumn();
        } catch (\Exception $e) {
            return;
        }
 
        $this->fail('Sqlite renameTableColumn() should throw an exception because it is not supported');
    }
}