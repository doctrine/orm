<?php

namespace Doctrine\Tests\DBAL\Platforms;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Types\Type;

require_once __DIR__ . '/../../TestInit.php';
 
class SqlitePlatformTest extends \Doctrine\Tests\DbalTestCase
{
    private $_platform;

    public function setUp()
    {
        $this->_platform = new SqlitePlatform;
    }

    public function testGetCreateTableSql()
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

        $sql = $this->_platform->getCreateTableSql('test', $columns, $options);
        $this->assertEquals('CREATE TABLE test (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, test VARCHAR(255) DEFAULT NULL)', $sql[0]);
    }

    public function testGetCreateConstraintSql()
    {
        $sql = $this->_platform->getCreateConstraintSql('test', 'constraint_name', array('fields' => array('test' => array())));
        $this->assertEquals('ALTER TABLE test ADD CONSTRAINT constraint_name (test)', $sql);
    }

    public function testGetCreateIndexSql()
    {
        $sql = $this->_platform->getCreateIndexSql('test', 'index_name', array('type' => 'unique', 'fields' => array('test', 'test2')));
        $this->assertEquals('CREATE UNIQUE INDEX index_name ON test (test, test2)', $sql);
    }

    public function testGetCreateForeignKeySql()
    {
        $sql = $this->_platform->getCreateForeignKeySql('test', array('foreignTable' => 'other_table', 'local' => 'fk_name_id', 'foreign' => 'id'));
        $this->assertEquals('ALTER TABLE test ADD FOREIGN KEY (fk_name_id) REFERENCES other_table(id)', $sql);
    }

    public function testExpressionsSql()
    {
        $this->assertEquals('RLIKE', $this->_platform->getRegexpExpression());
        $this->assertEquals('SUBSTR(column, 5, LENGTH(column))', $this->_platform->getSubstringExpression('column', 5));
        $this->assertEquals('SUBSTR(column, 0, 5)', $this->_platform->getSubstringExpression('column', 0, 5));
        $this->assertEquals('PRAGMA read_uncommitted = 0', $this->_platform->getSetTransactionIsolationSql(\Doctrine\DBAL\Connection::TRANSACTION_READ_UNCOMMITTED));
        $this->assertEquals('PRAGMA read_uncommitted = 1', $this->_platform->getSetTransactionIsolationSql(\Doctrine\DBAL\Connection::TRANSACTION_READ_COMMITTED));
        $this->assertEquals('PRAGMA read_uncommitted = 1', $this->_platform->getSetTransactionIsolationSql(\Doctrine\DBAL\Connection::TRANSACTION_REPEATABLE_READ));
        $this->assertEquals('PRAGMA read_uncommitted = 1', $this->_platform->getSetTransactionIsolationSql(\Doctrine\DBAL\Connection::TRANSACTION_SERIALIZABLE));
    }

    public function testPreferences()
    {
        $this->assertTrue($this->_platform->prefersIdentityColumns());
    }

    public function testTypeDeclarationSql()
    {
        $this->assertEquals(
            'INTEGER',
            $this->_platform->getIntegerTypeDeclarationSql(array())
        );
        $this->assertEquals(
            'INTEGER AUTOINCREMENT',
            $this->_platform->getIntegerTypeDeclarationSql(array('autoincrement' => true)
        ));
        $this->assertEquals(
            'INTEGER PRIMARY KEY AUTOINCREMENT',
            $this->_platform->getIntegerTypeDeclarationSql(
                array('autoincrement' => true, 'primary' => true)
        ));
        $this->assertEquals(
            'CHAR(10)',
            $this->_platform->getVarcharTypeDeclarationSql(
                array('length' => 10, 'fixed' => true)
        ));
        $this->assertEquals(
            'VARCHAR(50)',
            $this->_platform->getVarcharTypeDeclarationSql(array('length' => 50))
        );
        $this->assertEquals(
            'TEXT',
            $this->_platform->getVarcharTypeDeclarationSql(array())
        );
    }
    
}