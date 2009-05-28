<?php

namespace Doctrine\Tests\DBAL\Functional\Schema;

use Doctrine\Tests\TestUtil;
use Doctrine\DBAL\Schema;

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
        $this->_sm = new Schema\SqliteSchemaManager($this->_conn);
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
                'type' => new \Doctrine\DBAL\Types\IntegerType,
                'autoincrement' => true,
                'primary' => true,
                'notnull' => true
            ),
            'test' => array(
                'type' => new \Doctrine\DBAL\Types\StringType,
                'length' => 255
            )
        );

        $options = array();

        $this->_sm->createTable('list_sequences_test', $columns, $options);

        $sequences = $this->_sm->listSequences();
        $this->assertEquals($sequences[0]['name'], 'list_sequences_test');
        $this->assertEquals($sequences[1]['name'], 'sqlite_sequence');
    }

    public function testListTableConstraints()
    {
        // TODO: Implement support for constraints/foreign keys to be specified
        // when creating tables. Sqlite does not support adding them after
        // the table has already been created
        $tableConstraints = $this->_sm->listTableConstraints('list_table_constraints_test');
        $this->assertEquals($tableConstraints, array());
    }

    public function testListTableColumns()
    {
        $columns = array(
            'id' => array(
                'type' => new \Doctrine\DBAL\Types\IntegerType,
                'autoincrement' => true,
                'primary' => true,
                'notnull' => true
            ),
            'test' => array(
                'type' => new \Doctrine\DBAL\Types\StringType,
                'length' => 255
            )
        );

        $options = array();

        $this->_sm->createTable('list_table_columns_test', $columns, $options);

        $tableColumns = $this->_sm->listTableColumns('list_table_columns_test');

        $this->assertEquals($tableColumns[0]['name'], 'id');
        $this->assertEquals($tableColumns[0]['primary'], true);
        $this->assertEquals(get_class($tableColumns[0]['type']), 'Doctrine\DBAL\Types\IntegerType');
        $this->assertEquals($tableColumns[0]['length'], 4);
        $this->assertEquals($tableColumns[0]['unsigned'], false);
        $this->assertEquals($tableColumns[0]['fixed'], false);
        $this->assertEquals($tableColumns[0]['notnull'], true);
        $this->assertEquals($tableColumns[0]['default'], null);

        $this->assertEquals($tableColumns[1]['name'], 'test');
        $this->assertEquals($tableColumns[1]['primary'], false);
        $this->assertEquals(get_class($tableColumns[1]['type']), 'Doctrine\DBAL\Types\StringType');
        $this->assertEquals($tableColumns[1]['length'], 255);
        $this->assertEquals($tableColumns[1]['unsigned'], false);
        $this->assertEquals($tableColumns[1]['fixed'], false);
        $this->assertEquals($tableColumns[1]['notnull'], false);
        $this->assertEquals($tableColumns[1]['default'], null);
    }

    public function testListTableIndexes()
    {
        $columns = array(
            'id' => array(
                'type' => new \Doctrine\DBAL\Types\IntegerType,
                'autoincrement' => true,
                'primary' => true,
                'notnull' => true
            ),
            'test' => array(
                'type' => new \Doctrine\DBAL\Types\StringType,
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
        $this->assertEquals($tableIndexes[0]['name'], 'test');
        $this->assertEquals($tableIndexes[0]['unique'], true);
    }

    public function testListTable()
    {
        $columns = array(
            'id' => array(
                'type' => new \Doctrine\DBAL\Types\IntegerType,
                'autoincrement' => true,
                'primary' => true,
                'notnull' => true
            ),
            'test' => array(
                'type' => new \Doctrine\DBAL\Types\StringType,
                'length' => 255
            )
        );

        $options = array();

        $this->_sm->createTable('list_tables_test', $columns, $options);

        $tables = $this->_sm->listTables();
        $this->assertEquals(in_array('list_tables_test', $tables), true);
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
                'type' => new \Doctrine\DBAL\Types\IntegerType,
                'autoincrement' => true,
                'primary' => true,
                'notnull' => true
            ),
            'test' => array(
                'type' => new \Doctrine\DBAL\Types\StringType,
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

        $this->assertEquals($views[0]['name'], 'test_create_view');
        $this->assertEquals($views[0]['sql'], 'CREATE VIEW test_create_view AS SELECT * from test_views');
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
        $this->assertEquals(file_exists($path), true);
        $sm->dropDatabase();
        $this->assertEquals(file_exists($path), false);
        $sm->createDatabase();
        $this->assertEquals(file_exists($path), true);
        $sm->dropDatabase();
    }

    public function testCreateTable()
    {
        $columns = array(
            'id' => array(
                'type' => new \Doctrine\DBAL\Types\IntegerType,
                'autoincrement' => true,
                'primary' => true,
                'notnull' => true
            ),
            'test' => array(
                'type' => new \Doctrine\DBAL\Types\StringType,
                'length' => 255
            )
        );

        $options = array();

        $this->_sm->createTable('test_create_table', $columns, $options);
        $tables = $this->_sm->listTables();
        $this->assertEquals(in_array('test_create_table', $tables), true);

        $tableColumns = $this->_sm->listTableColumns('test_create_table');

        $this->assertEquals($tableColumns[0]['name'], 'id');
        $this->assertEquals($tableColumns[0]['primary'], true);
        $this->assertEquals(get_class($tableColumns[0]['type']), 'Doctrine\DBAL\Types\IntegerType');
        $this->assertEquals($tableColumns[0]['length'], 4);
        $this->assertEquals($tableColumns[0]['unsigned'], false);
        $this->assertEquals($tableColumns[0]['fixed'], false);
        $this->assertEquals($tableColumns[0]['notnull'], true);
        $this->assertEquals($tableColumns[0]['default'], null);

        $this->assertEquals($tableColumns[1]['name'], 'test');
        $this->assertEquals($tableColumns[1]['primary'], false);
        $this->assertEquals(get_class($tableColumns[1]['type']), 'Doctrine\DBAL\Types\StringType');
        $this->assertEquals($tableColumns[1]['length'], 255);
        $this->assertEquals($tableColumns[1]['unsigned'], false);
        $this->assertEquals($tableColumns[1]['fixed'], false);
        $this->assertEquals($tableColumns[1]['notnull'], false);
        $this->assertEquals($tableColumns[1]['default'], null);
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
                'type' => new \Doctrine\DBAL\Types\IntegerType,
                'autoincrement' => true,
                'primary' => true,
                'notnull' => true
            ),
            'test' => array(
                'type' => new \Doctrine\DBAL\Types\StringType,
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
        $this->assertEquals($tableIndexes[0]['name'], 'test');
        $this->assertEquals($tableIndexes[0]['unique'], true);
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