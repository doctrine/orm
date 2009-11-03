<?php

namespace Doctrine\Tests\DBAL\Functional\Schema;

use Doctrine\DBAL\Types\Type;

require_once __DIR__ . '/../../../TestInit.php';

class SchemaManagerFunctionalTestCase extends \Doctrine\Tests\DbalFunctionalTestCase
{
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