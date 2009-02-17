<?php

namespace Doctrine\Tests\DBAL\Platforms;

require_once __DIR__ . '/../../TestInit.php';
 
class AbstractPlatformTest extends \Doctrine\Tests\DbalTestCase
{
    private $_conn;

    public function setUp()
    {
        $this->_config = new \Doctrine\DBAL\Configuration;
        $this->_eventManager = new \Doctrine\Common\EventManager;
        $options = array(
            'driver' => 'pdo_sqlite',
            'memory' => true
        );
        $this->_conn = \Doctrine\DBAL\DriverManager::getConnection($options, $this->_config, $this->_eventManager);
        $this->_platform = $this->_conn->getDatabasePlatform();
        $this->_sm = $this->_conn->getSchemaManager();
    }

    public function testGetCreateTableSql()
    {
        $columns = array(
            'id' => array(
                'type' => new \Doctrine\DBAL\Types\IntegerType,
                'autoincrement' => true
            ),
            'test' => array(
                'type' => new \Doctrine\DBAL\Types\VarcharType,
                'length' => 255
            )
        );

        $options = array(
            'primary' => array('id')
        );

        $sql = $this->_platform->getCreateTableSql('test', $columns, $options);
        $this->assertEquals($sql[0], 'CREATE TABLE test (id INTEGER AUTOINCREMENT, test VARCHAR(255))');
    }

    public function testGetCreateConstraintSql()
    {
        $sql = $this->_platform->getCreateConstraintSql('test', 'constraint_name', array('fields' => array('test' => array())));
        $this->assertEquals($sql, 'ALTER TABLE test ADD CONSTRAINT constraint_name (test)');
    }

    public function testGetCreateIndexSql()
    {
        $sql = $this->_platform->getCreateIndexSql('test', 'index_name', array('type' => 'unique', 'fields' => array('test', 'test2')));
        $this->assertEquals($sql, 'CREATE UNIQUE INDEX index_name ON test (test, test2)');
    }

    public function testGetCreateForeignKeySql()
    {
        $sql = $this->_platform->getCreateForeignKeySql('test', array('foreignTable' => 'other_table', 'local' => 'fk_name_id', 'foreign' => 'id'));
        $this->assertEquals($sql, 'ALTER TABLE test ADD FOREIGN KEY (fk_name_id) REFERENCES other_table(id)');
    }
}