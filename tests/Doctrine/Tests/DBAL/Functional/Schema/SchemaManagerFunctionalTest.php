<?php

namespace Doctrine\Tests\DBAL\Functional\Schema;

use Doctrine\DBAL\Types\Type;

class SchemaManagerFunctionalTest extends \Doctrine\Tests\DbalFunctionalTestCase
{
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

    public function createTestTable($name = 'test_table', $data = array())
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

    public function assertUnsupportedMethod($method)
    {
        try {
            $this->_sm->$method();
        } catch (\Exception $e) {
            return;
        }
 
        $this->fail($method . '() should throw an exception because it is not supported in ' . $this->_conn->getDatabasePlatform()->getName());
    }
}