<?php

namespace Doctrine\Tests\DBAL\Platforms;

use Doctrine\DBAL\Platforms\MsSqlPlatform;
use Doctrine\DBAL\Types\Type;

require_once __DIR__ . '/../../TestInit.php';
 
class MsSqlPlatformTest extends AbstractPlatformTestCase
{
    public function createPlatform()
    {
        return new MsSqlPlatform;
    }

    public function getGenerateTableSql()
    {
        return 'CREATE TABLE test (id INT AUTO_INCREMENT NOT NULL, test VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))';
    }

    public function testGeneratesTableAlterationSql()
    {
        $changes = array(
            'name' => 'userlist',
            'add' => array(
                'quota' => array(
                'type' => Type::getType('integer'),
                'unsigned' => 1
                )
            ));

        $sql = $this->_platform->getAlterTableSql('mytable', $changes);
        $this->assertEquals(
            'ALTER TABLE mytable RENAME TO userlist, ADD quota INT UNSIGNED DEFAULT NULL',
            $sql[0]
        );
    }

    public function testGeneratesSqlSnippets()
    {
        $this->assertEquals('RLIKE', $this->_platform->getRegexpExpression(), 'Regular expression operator is not correct');
        $this->assertEquals('"', $this->_platform->getIdentifierQuoteCharacter(), 'Identifier quote character is not correct');
        $this->assertEquals('RAND()', $this->_platform->getRandomExpression(), 'Random function is not correct');
        $this->assertEquals('(column1 + column2 + column3)', $this->_platform->getConcatExpression('column1', 'column2', 'column3'), 'Concatenation expression is not correct');
        $this->assertEquals('CHARACTER SET utf8', $this->_platform->getCharsetFieldDeclaration('utf8'), 'Charset declaration is not correct');
    }

    public function testGeneratesTransactionsCommands()
    {
        $this->assertEquals(
            'SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED',
            $this->_platform->getSetTransactionIsolationSql(\Doctrine\DBAL\Connection::TRANSACTION_READ_UNCOMMITTED)
        );
        $this->assertEquals(
            'SET TRANSACTION ISOLATION LEVEL READ COMMITTED',
            $this->_platform->getSetTransactionIsolationSql(\Doctrine\DBAL\Connection::TRANSACTION_READ_COMMITTED)
        );
        $this->assertEquals(
            'SET TRANSACTION ISOLATION LEVEL REPEATABLE READ',
            $this->_platform->getSetTransactionIsolationSql(\Doctrine\DBAL\Connection::TRANSACTION_REPEATABLE_READ)
        );
        $this->assertEquals(
            'SET TRANSACTION ISOLATION LEVEL SERIALIZABLE',
            $this->_platform->getSetTransactionIsolationSql(\Doctrine\DBAL\Connection::TRANSACTION_SERIALIZABLE)
        );
    }

    public function testGeneratesDDLSnippets()
    {
        $this->assertEquals('SHOW DATABASES', $this->_platform->getShowDatabasesSql());
        $this->assertEquals('CREATE DATABASE foobar', $this->_platform->getCreateDatabaseSql('foobar'));
        $this->assertEquals('DROP DATABASE foobar', $this->_platform->getDropDatabaseSql('foobar'));
        $this->assertEquals('DROP TABLE foobar', $this->_platform->getDropTableSql('foobar'));
    }

    public function testGeneratesTypeDeclarationForIntegers()
    {
        $this->assertEquals(
            'INT',
            $this->_platform->getIntegerTypeDeclarationSql(array())
        );
        $this->assertEquals(
            'INT AUTO_INCREMENT',
            $this->_platform->getIntegerTypeDeclarationSql(array('autoincrement' => true)
        ));
        $this->assertEquals(
            'INT AUTO_INCREMENT',
            $this->_platform->getIntegerTypeDeclarationSql(
                array('autoincrement' => true, 'primary' => true)
        ));
    }

    public function testGeneratesTypeDeclarationsForStrings()
    {
        $this->assertEquals(
            'CHAR(10)',
            $this->_platform->getVarcharTypeDeclarationSql(
                array('length' => 10, 'fixed' => true)
        ));
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

    public function testPrefersIdentityColumns()
    {
        $this->assertTrue($this->_platform->prefersIdentityColumns());
    }

    public function testSupportsIdentityColumns()
    {
        $this->assertTrue($this->_platform->supportsIdentityColumns());
    }

    public function testDoesNotSupportSavePoints()
    {
        $this->assertFalse($this->_platform->supportsSavepoints());   
    }

    public function testGeneratesConstraintCreationSql()
    {
        $sql = $this->_platform->getCreateConstraintSql('test', 'constraint_name', array('columns' => array('test' => array())));
        $this->assertEquals($sql, 'ALTER TABLE test ADD CONSTRAINT constraint_name (test)');
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
        return  'ALTER TABLE test ADD FOREIGN KEY (fk_name_id) REFERENCES other_table(id)';
    }

    public function testModifyLimitQuery()
    {
        $sql = $this->_platform->modifyLimitQuery('SELECT * FROM user', 10, 0);
        $this->assertEquals('SELECT * FROM (SELECT TOP 10 * FROM (SELECT TOP 10 * FROM user) AS inner_tbl) AS outer_tbl', $sql);
    }

    public function testModifyLimitQueryWithEmptyOffset()
    {
        $sql = $this->_platform->modifyLimitQuery('SELECT * FROM user', 10);
        $this->assertEquals('SELECT * FROM (SELECT TOP 10 * FROM (SELECT TOP 10 * FROM user) AS inner_tbl) AS outer_tbl', $sql);
    }

    public function testModifyLimitQueryWithAscOrderBy()
    {
        $sql = $this->_platform->modifyLimitQuery('SELECT * FROM user ORDER BY username ASC', 10);
        $this->assertEquals('SELECT * FROM (SELECT TOP 10 * FROM (SELECT TOP 10 * FROM user ORDER BY username ASC) AS inner_tbl ORDER BY inner_tbl.u DESC) AS outer_tbl ORDER BY outer_tbl.u ASC', $sql);
    }

    public function testModifyLimitQueryWithDescOrderBy()
    {
        $sql = $this->_platform->modifyLimitQuery('SELECT * FROM user ORDER BY username DESC', 10);
        $this->assertEquals('SELECT * FROM (SELECT TOP 10 * FROM (SELECT TOP 10 * FROM user ORDER BY username DESC) AS inner_tbl ORDER BY inner_tbl.u ASC) AS outer_tbl ORDER BY outer_tbl.u DESC', $sql);
    }
}