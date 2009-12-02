<?php

namespace Doctrine\Tests\DBAL\Platforms;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Types\Type;

require_once __DIR__ . '/../../TestInit.php';
 
class SqlitePlatformTest extends AbstractPlatformTestCase
{
    public function createPlatform()
    {
        return new SqlitePlatform;
    }

    public function getGenerateTableSql()
    {
        return 'CREATE TABLE test (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, test VARCHAR(255) DEFAULT NULL)';
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
        $sql = $this->_platform->getCreateConstraintSql('test', 'constraint_name', array('columns' => array('test' => array())));
        $this->assertEquals('ALTER TABLE test ADD CONSTRAINT constraint_name (test)', $sql);
    }

    public function getGenerateIndexSql()
    {
        return 'CREATE INDEX my_idx ON mytable (user_name, last_login)';
    }

    public function getGenerateUniqueIndexSql()
    {
        return 'CREATE UNIQUE INDEX index_name ON test (test, test2)';
    }

    public function getGenerateForeignKeySql()
    {
        $this->markTestSkipped('SQLite does not support ForeignKeys.');
    }

    public function testModifyLimitQuery()
    {
        $sql = $this->_platform->modifyLimitQuery('SELECT * FROM user', 10, 0);
        $this->assertEquals('SELECT * FROM user LIMIT 10 OFFSET 0', $sql);
    }

    public function testModifyLimitQueryWithEmptyOffset()
    {
        $sql = $this->_platform->modifyLimitQuery('SELECT * FROM user', 10);
        $this->assertEquals('SELECT * FROM user LIMIT 10', $sql);
    }
}