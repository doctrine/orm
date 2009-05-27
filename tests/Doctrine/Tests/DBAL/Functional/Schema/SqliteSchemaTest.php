<?php

namespace Doctrine\Tests\DBAL\Functional\Schema;

use Doctrine\Tests\TestUtil;
use Doctrine\DBAL\Schema;

require_once __DIR__ . '/../../../TestInit.php';
 
class SqliteSchemaTest extends \Doctrine\Tests\DbalFunctionalTestCase
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

        $this->_sm->createTable('list_tables_test', $columns, $options);

        $columns = $this->_sm->listTableColumns('list_tables_test');

        $this->assertEquals($columns[0]['name'], 'id');
        $this->assertEquals($columns[0]['primary'], true);
        $this->assertEquals(get_class($columns[0]['type']), 'Doctrine\DBAL\Types\IntegerType');
        $this->assertEquals($columns[0]['length'], 4);
        $this->assertEquals($columns[0]['unsigned'], false);
        $this->assertEquals($columns[0]['fixed'], false);
        $this->assertEquals($columns[0]['notnull'], true);
        $this->assertEquals($columns[0]['default'], null);

        $this->assertEquals($columns[1]['name'], 'test');
        $this->assertEquals($columns[1]['primary'], false);
        $this->assertEquals(get_class($columns[1]['type']), 'Doctrine\DBAL\Types\StringType');
        $this->assertEquals($columns[1]['length'], 255);
        $this->assertEquals($columns[1]['unsigned'], false);
        $this->assertEquals($columns[1]['fixed'], false);
        $this->assertEquals($columns[1]['notnull'], false);
        $this->assertEquals($columns[1]['default'], null);
    }
}