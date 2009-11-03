<?php

namespace Doctrine\Tests\DBAL\Functional\Schema;

use Doctrine\DBAL\Types\Type;

require_once __DIR__ . '/../../../TestInit.php';

class SchemaManagerFunctionalTestCase extends \Doctrine\Tests\DbalFunctionalTestCase
{
    public function testListFunctions()
    {
        $funcs = $this->_sm->listFunctions();
        $this->assertType('array', $funcs);
        $this->assertTrue(count($funcs)>=0);
    }

    public function testListTriggers()
    {
        $triggers = $this->_sm->listTriggers();
        $this->assertType('array', $triggers);
        $this->assertTrue(count($triggers) >= 0);
    }

    public function testListDatabases()
    {
        $this->_sm->dropAndCreateDatabase('test_create_database');
        $databases = $this->_sm->listDatabases();

        $databases = \array_map('strtolower', $databases);
        
        $this->assertEquals(true, in_array('test_create_database', $databases));
    }

    public function testListTables()
    {
        $this->createTestTable('list_tables_test');
        $tables = $this->_sm->listTables();

        $tables = \array_change_key_case($tables, CASE_LOWER);

        $this->assertEquals(true, in_array('list_tables_test', $tables));
    }

    public function testListTableColumns()
    {
        $data = array();
        $data['columns'] = array(
            'id' => array(
                'type' => Type::getType('integer'),
                'autoincrement' => true,
                'primary' => true,
                'notnull' => true
            ),
            'test' => array(
                'type' => Type::getType('string'),
                'length' => 255,
                'notnull' => false,
            ),
            'foo' => array(
                'type' => Type::getType('text'),
                'notnull' => true,
            ),
            'bar' => array(
                'type' => Type::getType('decimal'),
                'precision' => 10,
                'scale' => 4,
            ),
            'baz1' => array(
                'type' => Type::getType('datetime'),
            ),
            'baz2' => array(
                'type' => Type::getType('time'),
            ),
            'baz3' => array(
                'type' => Type::getType('date'),
            ),
        );
        $this->createTestTable('list_table_columns', $data);

        $columns = $this->_sm->listTableColumns('list_table_columns');

        $columns = \array_change_key_case($columns, CASE_LOWER);

        $this->assertArrayHasKey('id', $columns);
        $this->assertEquals('id',   strtolower($columns['id']['name']));
        $this->assertType('Doctrine\DBAL\Types\IntegerType', $columns['id']['type']);
        $this->assertEquals(null,   $columns['id']['length']);
        $this->assertEquals(null,   $columns['id']['precision']);
        $this->assertEquals(null,   $columns['id']['scale']);
        $this->assertEquals(false,  $columns['id']['unsigned']);
        $this->assertEquals(false,  $columns['id']['fixed']);
        $this->assertEquals(true,   $columns['id']['notnull']);
        $this->assertEquals(null,   $columns['id']['default']);
        $this->assertType('array',  $columns['id']['platformDetails']);

        $this->assertArrayHasKey('test', $columns);
        $this->assertEquals('test', strtolower($columns['test']['name']));
        $this->assertType('Doctrine\DBAL\Types\StringType', $columns['test']['type']);
        $this->assertEquals(255,    $columns['test']['length']);
        $this->assertEquals(null,   $columns['test']['precision']);
        $this->assertEquals(null,   $columns['test']['scale']);
        $this->assertEquals(false,  $columns['test']['unsigned']);
        $this->assertEquals(false,  $columns['test']['fixed']);
        $this->assertEquals(false,  $columns['test']['notnull']);
        $this->assertEquals(null,   $columns['test']['default']);
        $this->assertType('array',  $columns['test']['platformDetails']);

        $this->assertEquals('foo', strtolower($columns['foo']['name']));
        $this->assertType('Doctrine\DBAL\Types\TextType', $columns['foo']['type']);
        $this->assertEquals(null,   $columns['foo']['length']);
        $this->assertEquals(null,   $columns['foo']['precision']);
        $this->assertEquals(null,   $columns['foo']['scale']);
        $this->assertEquals(false,  $columns['foo']['unsigned']);
        $this->assertEquals(false,  $columns['foo']['fixed']);
        $this->assertEquals(true,   $columns['foo']['notnull']);
        $this->assertEquals(null,   $columns['foo']['default']);
        $this->assertType('array',  $columns['foo']['platformDetails']);

        $this->assertEquals('bar', strtolower($columns['bar']['name']));
        $this->assertType('Doctrine\DBAL\Types\DecimalType', $columns['bar']['type']);
        $this->assertEquals(null,   $columns['bar']['length']);
        $this->assertEquals(10,   $columns['bar']['precision']);
        $this->assertEquals(4,   $columns['bar']['scale']);
        $this->assertEquals(false,  $columns['bar']['unsigned']);
        $this->assertEquals(false,  $columns['bar']['fixed']);
        $this->assertEquals(false,   $columns['bar']['notnull']);
        $this->assertEquals(null,   $columns['bar']['default']);
        $this->assertType('array',  $columns['bar']['platformDetails']);

        $this->assertEquals('baz1', strtolower($columns['baz1']['name']));
        $this->assertType('Doctrine\DBAL\Types\DateTimeType', $columns['baz1']['type']);
        $this->assertEquals(null,   $columns['baz1']['length']);
        $this->assertEquals(null,   $columns['baz1']['precision']);
        $this->assertEquals(null,   $columns['baz1']['scale']);
        $this->assertEquals(false,  $columns['baz1']['unsigned']);
        $this->assertEquals(false,  $columns['baz1']['fixed']);
        $this->assertEquals(false,   $columns['baz1']['notnull']);
        $this->assertEquals(null,   $columns['baz1']['default']);
        $this->assertType('array',  $columns['baz1']['platformDetails']);

        $this->assertEquals('baz2', strtolower($columns['baz2']['name']));
        $this->assertContains($columns['baz2']['type']->getName(), array('Time', 'Date', 'DateTime'));
        $this->assertEquals(null,   $columns['baz2']['length']);
        $this->assertEquals(null,   $columns['baz2']['precision']);
        $this->assertEquals(null,   $columns['baz2']['scale']);
        $this->assertEquals(false,  $columns['baz2']['unsigned']);
        $this->assertEquals(false,  $columns['baz2']['fixed']);
        $this->assertEquals(false,   $columns['baz2']['notnull']);
        $this->assertEquals(null,   $columns['baz2']['default']);
        $this->assertType('array',  $columns['baz2']['platformDetails']);
        
        $this->assertEquals('baz3', strtolower($columns['baz3']['name']));
        $this->assertContains($columns['baz2']['type']->getName(), array('Time', 'Date', 'DateTime'));
        $this->assertEquals(null,   $columns['baz3']['length']);
        $this->assertEquals(null,   $columns['baz3']['precision']);
        $this->assertEquals(null,   $columns['baz3']['scale']);
        $this->assertEquals(false,  $columns['baz3']['unsigned']);
        $this->assertEquals(false,  $columns['baz3']['fixed']);
        $this->assertEquals(false,   $columns['baz3']['notnull']);
        $this->assertEquals(null,   $columns['baz3']['default']);
        $this->assertType('array',  $columns['baz3']['platformDetails']);
    }

    public function testListTableIndexes()
    {
        $data['options'] = array(
            'indexes' => array(
                'test_index_name' => array(
                    'columns' => array(
                        'test' => array()
                    ),
                    'type' => 'unique'
                ),
                'test_composite_idx' => array(
                    'columns' => array(
                        'id' => array(), 'test' => array(),
                    )
                ),
            )
        );

        $this->createTestTable('list_table_indexes_test', $data);

        $tableIndexes = $this->_sm->listTableIndexes('list_table_indexes_test');
        
        $this->assertEquals(3, count($tableIndexes));

        $this->assertEquals(array('id'), $tableIndexes['primary']['columns']);
        $this->assertTrue($tableIndexes['primary']['unique']);
        $this->assertTrue($tableIndexes['primary']['primary']);

        $this->assertEquals('test_index_name', $tableIndexes['test_index_name']['name']);
        $this->assertEquals(array('test'), $tableIndexes['test_index_name']['columns']);
        $this->assertTrue($tableIndexes['test_index_name']['unique']);
        $this->assertFalse($tableIndexes['test_index_name']['primary']);

        $this->assertEquals('test_composite_idx', $tableIndexes['test_composite_idx']['name']);
        $this->assertEquals(array('id', 'test'), $tableIndexes['test_composite_idx']['columns']);
        $this->assertFalse($tableIndexes['test_composite_idx']['unique']);
        $this->assertFalse($tableIndexes['test_composite_idx']['primary']);
    }

    public function testDropAndCreateIndex()
    {
        $this->createTestTable('test_create_index');

        $index = array(
            'columns' => array(
                'test' => array()
            ),
            'type' => 'unique'
        );

        $this->_sm->dropAndCreateIndex('test_create_index', 'test', $index);
        $tableIndexes = $this->_sm->listTableIndexes('test_create_index');

        $this->assertEquals('test', $tableIndexes['test']['name']);
        $this->assertEquals(array('test'), $tableIndexes['test']['columns']);
        $this->assertTrue($tableIndexes['test']['unique']);
        $this->assertFalse($tableIndexes['test']['primary']);
    }

    protected function getCreateExampleViewSql()
    {
        $this->markTestSkipped('No Create Example View SQL was defined for this SchemaManager');
    }

    public function testListViews()
    {
        $this->_sm->dropAndCreateView('test_create_view', $this->getCreateExampleViewSql());
        $views = $this->_sm->listViews();
        $this->assertTrue(count($views) >= 1, "There should be at least the fixture view created in the database, but none were found.");
        
        $found = false;
        foreach($views AS $view) {
            if(!isset($view['name']) || !isset($view['sql'])) {
                $this->fail(
                    "listViews() has to return entries with both name ".
                    "and sql keys, but only ".implode(", ", array_keys($view))." are present."
                );
            }

            if($view['name'] == 'test_create_view') {
                $found = true;
            }
        }
        $this->assertTrue($found, "'test_create_view' View was not found in listViews().");
    }

    /**
     * @var \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    protected $_sm;

    protected function setUp()
    {
        parent::setUp();

        $class = get_class($this);
        $e = explode('\\', $class);
        $testClass = end($e);
        $dbms = strtolower(str_replace('SchemaManagerTest', null, $testClass));

        if ($this->_conn->getDatabasePlatform()->getName() !== $dbms)
        {
            $this->markTestSkipped('The ' . $testClass .' requires the use of ' . $dbms);
        }

        $this->_sm = $this->_conn->getSchemaManager();
    }

    protected function createTestTable($name = 'test_table', $data = array())
    {
        if ( ! isset($data['columns'])) {
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
                ),
                'foreign_key_test' => array(
                    'type' => Type::getType('integer')
                )
            );
        } else {
            $columns = $data['columns'];
        }

        $options = array();
        if (isset($data['options'])) {
            $options = $data['options'];
        }

        $this->_sm->dropAndCreateTable($name, $columns, $options);
    }
}