<?php

namespace Doctrine\Tests\DBAL\Functional\Schema;

use Doctrine\DBAL\Schema;

require_once __DIR__ . '/../../../TestInit.php';
 
class SqliteSchemaManagerTest extends SchemaManagerFunctionalTest
{
    public function testListDatabases()
    {
        return $this->assertUnsupportedMethod('listDatabases');
    }

    public function testListFunctions()
    {
        return $this->assertUnsupportedMethod('listFunctions');
    }

    public function testListTriggers()
    {
        return $this->assertUnsupportedMethod('listTriggers');
    }

    public function testListSequences()
    {
        $this->createTestTable('list_sequences_test');
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
        $this->createTestTable('list_table_columns_test');

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
        $data['options'] = array(
            'indexes' => array(
                'test' => array(
                    'fields' => array(
                        'test' => array()
                    ),
                    'type' => 'unique'
                )
            )
        );

        $this->createTestTable('list_table_indexes_test', $data);

        $tableIndexes = $this->_sm->listTableIndexes('list_table_indexes_test');
        $this->assertEquals('test', $tableIndexes[0]['name']);
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
        return $this->assertUnsupportedMethod('listUsers');
    }

    public function testListViews()
    {
        $this->createTestTable('test_views');
        $this->_sm->dropAndCreateView('test_create_view', 'SELECT * from test_views');
        $views = $this->_sm->listViews();

        $this->assertEquals('test_create_view', $views[0]['name']);
        $this->assertEquals('CREATE VIEW test_create_view AS SELECT * from test_views', $views[0]['sql']);
    }

    /* FIXME: Using ORM in DBAL test suite. Not OK!!
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
    }*/

    public function testCreateTable()
    {
        $this->createTestTable('test_create_table');
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
        return $this->assertUnsupportedMethod('createSequence');
    }

    public function testCreateConstraint()
    {
        return $this->assertUnsupportedMethod('createConstraint');
    }
    
    /* FIXME: See comment in AbstractSchemaManager#dropIndex($table, $name)
    public function testCreateIndex()
    {
        $this->createTestTable('test_create_index');

        $index = array(
            'fields' => array(
                'test' => array()
            ),
            'type' => 'unique'
        );
        
        $this->_sm->dropAndCreateIndex('test_create_index', 'test', $index);
        $tableIndexes = $this->_sm->listTableIndexes('test_create_index');
        $this->assertEquals('test', $tableIndexes[0]['name']);
        $this->assertEquals(true, $tableIndexes[0]['unique']);
    }*/

    public function testCreateForeignKey()
    {
        return $this->assertUnsupportedMethod('createForeignKey');
    }

    public function testRenameTable()
    {
        return $this->assertUnsupportedMethod('renameTable');
    }


    public function testAddTableColumn()
    {
        return $this->assertUnsupportedMethod('addTableColumn');
    }

    public function testRemoveTableColumn()
    {
        return $this->assertUnsupportedMethod('removeTableColumn');
    }

    public function testChangeTableColumn()
    {
        return $this->assertUnsupportedMethod('changeTableColumn');
    }

    public function testRenameTableColumn()
    {
        return $this->assertUnsupportedMethod('renameTableColumn');
    }
}