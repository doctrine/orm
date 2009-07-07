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

    public function testGeneratesTableCreationSql()
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

    public function testGeneratesSqlSnippets()
    {
        $this->assertEquals('RLIKE', $this->_platform->getRegexpExpression(), 'Regular expression operator is not correct');
        $this->assertEquals('SUBSTR(column, 5, LENGTH(column))', $this->_platform->getSubstringExpression('column', 5), 'Substring expression without length is not correct');
        $this->assertEquals('SUBSTR(column, 0, 5)', $this->_platform->getSubstringExpression('column', 0, 5), 'Substring expression with length is not correct');
    }

    public function testGeneratesTransactionCommands()
    {
        $this->assertEquals('PRAGMA read_uncommitted = 0', $this->_platform->getSetTransactionIsolationSql(\Doctrine\DBAL\Connection::TRANSACTION_READ_UNCOMMITTED));
        $this->assertEquals('PRAGMA read_uncommitted = 1', $this->_platform->getSetTransactionIsolationSql(\Doctrine\DBAL\Connection::TRANSACTION_READ_COMMITTED));
        $this->assertEquals('PRAGMA read_uncommitted = 1', $this->_platform->getSetTransactionIsolationSql(\Doctrine\DBAL\Connection::TRANSACTION_REPEATABLE_READ));
        $this->assertEquals('PRAGMA read_uncommitted = 1', $this->_platform->getSetTransactionIsolationSql(\Doctrine\DBAL\Connection::TRANSACTION_SERIALIZABLE));
    }

    public function testPrefersIdentityColumns()
    {
        $this->assertTrue($this->_platform->prefersIdentityColumns());
    }

    public function testGeneratesTypeDeclarationForIntegers()
    {
        $this->assertEquals(
            'INTEGER',
            $this->_platform->getIntegerTypeDeclarationSql(array())
        );
        $this->assertEquals(
            'INTEGER AUTOINCREMENT',
            $this->_platform->getIntegerTypeDeclarationSql(array('autoincrement' => true))
        );
        $this->assertEquals(
            'INTEGER PRIMARY KEY AUTOINCREMENT',
            $this->_platform->getIntegerTypeDeclarationSql(
                array('autoincrement' => true, 'primary' => true))
        );
    }

    public function testGeneratesTypeDeclarationForStrings()
    {
        $this->assertEquals(
            'CHAR(10)',
            $this->_platform->getVarcharTypeDeclarationSql(
                array('length' => 10, 'fixed' => true))
        );
        $this->assertEquals(
            'VARCHAR(50)',
            $this->_platform->getVarcharTypeDeclarationSql(array('length' => 50)),
            'Variable string declaration is not correct'
        );
        $this->assertEquals(
            'TEXT',
            $this->_platform->getVarcharTypeDeclarationSql(array()),
            'Long string declaration is not correct'
        );
    }

    public function testGeneratesConstraintCreationSql()
    {
        $sql = $this->_platform->getCreateConstraintSql('test', 'constraint_name', array('fields' => array('test' => array())));
        $this->assertEquals('ALTER TABLE test ADD CONSTRAINT constraint_name (test)', $sql);
    }

    public function testGeneratesIndexCreationSql()
    {
        $sql = $this->_platform->getCreateIndexSql('test', 'index_name', array('type' => 'unique', 'fields' => array('test', 'test2')));
        $this->assertEquals('CREATE UNIQUE INDEX index_name ON test (test, test2)', $sql);
    }

    public function testGeneratesForeignKeyCreationSql()
    {
        $sql = $this->_platform->getCreateForeignKeySql('test', array('foreignTable' => 'other_table', 'local' => 'fk_name_id', 'foreign' => 'id'));
        $this->assertEquals('ALTER TABLE test ADD FOREIGN KEY (fk_name_id) REFERENCES other_table(id)', $sql);
    }
}
