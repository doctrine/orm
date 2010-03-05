<?php

namespace Doctrine\Tests\DBAL\Platforms;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Connection;

require_once __DIR__ . '/../../TestInit.php';
 
class PostgreSqlPlatformTest extends AbstractPlatformTestCase
{
    public function createPlatform()
    {
        return new PostgreSqlPlatform;
    }

    public function getGenerateTableSql()
    {
        return 'CREATE TABLE test (id SERIAL NOT NULL, test VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))';
    }

    public function getGenerateTableWithMultiColumnUniqueIndexSql()
    {
        return array(
            'CREATE TABLE test (foo VARCHAR(255) DEFAULT NULL, bar VARCHAR(255) DEFAULT NULL)',
            'CREATE UNIQUE INDEX test_foo_bar_uniq ON test (foo, bar)'
        );
    }

    public function getGenerateAlterTableSql()
    {
        return array(
            'ALTER TABLE mytable ADD quota INT DEFAULT NULL',
            'ALTER TABLE mytable DROP foo',
            'ALTER TABLE mytable ALTER bar TYPE VARCHAR(255)',
            "ALTER TABLE mytable ALTER bar SET  DEFAULT 'def'",
            'ALTER TABLE mytable ALTER bar SET NOT NULL',
            'ALTER TABLE mytable RENAME TO userlist',
        );
    }

    public function getGenerateIndexSql()
    {
        return 'CREATE INDEX my_idx ON mytable (user_name, last_login)';
    }

    public function getGenerateForeignKeySql()
    {
        return 'ALTER TABLE test ADD FOREIGN KEY (fk_name_id) REFERENCES other_table(id) NOT DEFERRABLE INITIALLY IMMEDIATE';
    }

    public function testGeneratesForeignKeySqlForNonStandardOptions()
    {
        $foreignKey = new \Doctrine\DBAL\Schema\ForeignKeyConstraint(
                array('foreign_id'), 'my_table', array('id'), 'my_fk', array('onDelete' => 'CASCADE')
        );
        $this->assertEquals(
            "CONSTRAINT my_fk FOREIGN KEY (foreign_id) REFERENCES my_table(id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE",
            $this->_platform->getForeignKeyDeclarationSQL($foreignKey)
        );
    }

    public function testGeneratesSqlSnippets()
    {
        $this->assertEquals('SIMILAR TO', $this->_platform->getRegexpExpression(), 'Regular expression operator is not correct');
        $this->assertEquals('"', $this->_platform->getIdentifierQuoteCharacter(), 'Identifier quote character is not correct');
        $this->assertEquals('column1 || column2 || column3', $this->_platform->getConcatExpression('column1', 'column2', 'column3'), 'Concatenation expression is not correct');
        $this->assertEquals('SUBSTR(column, 5)', $this->_platform->getSubstringExpression('column', 5), 'Substring expression without length is not correct');
        $this->assertEquals('SUBSTR(column, 0, 5)', $this->_platform->getSubstringExpression('column', 0, 5), 'Substring expression with length is not correct');
    }

    public function testGeneratesTransactionCommands()
    {
        $this->assertEquals(
            'SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL READ UNCOMMITTED',
            $this->_platform->getSetTransactionIsolationSQL(Connection::TRANSACTION_READ_UNCOMMITTED)
        );
        $this->assertEquals(
            'SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL READ COMMITTED',
            $this->_platform->getSetTransactionIsolationSQL(Connection::TRANSACTION_READ_COMMITTED)
        );
        $this->assertEquals(
            'SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL REPEATABLE READ',
            $this->_platform->getSetTransactionIsolationSQL(Connection::TRANSACTION_REPEATABLE_READ)
        );
        $this->assertEquals(
            'SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL SERIALIZABLE',
            $this->_platform->getSetTransactionIsolationSQL(Connection::TRANSACTION_SERIALIZABLE)
        );
    }

    public function testGeneratesDDLSnippets()
    {
        $this->assertEquals('CREATE DATABASE foobar', $this->_platform->getCreateDatabaseSQL('foobar'));
        $this->assertEquals('DROP DATABASE foobar', $this->_platform->getDropDatabaseSQL('foobar'));
        $this->assertEquals('DROP TABLE foobar', $this->_platform->getDropTableSQL('foobar'));
    }

    public function testGeneratesTypeDeclarationForIntegers()
    {
        $this->assertEquals(
            'INT',
            $this->_platform->getIntegerTypeDeclarationSQL(array())
        );
        $this->assertEquals(
            'SERIAL',
            $this->_platform->getIntegerTypeDeclarationSQL(array('autoincrement' => true)
        ));
        $this->assertEquals(
            'SERIAL',
            $this->_platform->getIntegerTypeDeclarationSQL(
                array('autoincrement' => true, 'primary' => true)
        ));
    }

    public function testGeneratesTypeDeclarationForStrings()
    {
        $this->assertEquals(
            'CHAR(10)',
            $this->_platform->getVarcharTypeDeclarationSQL(
                array('length' => 10, 'fixed' => true))
        );
        $this->assertEquals(
            'VARCHAR(50)',
            $this->_platform->getVarcharTypeDeclarationSQL(array('length' => 50)),
            'Variable string declaration is not correct'
        );
        $this->assertEquals(
            'TEXT',
            $this->_platform->getVarcharTypeDeclarationSQL(array()),
            'Long string declaration is not correct'
        );
    }

    public function getGenerateUniqueIndexSql()
    {
        return 'CREATE UNIQUE INDEX index_name ON test (test, test2)';
    }

    public function testGeneratesSequenceSqlCommands()
    {
        $sequence = new \Doctrine\DBAL\Schema\Sequence('myseq', 20, 1);
        $this->assertEquals(
            'CREATE SEQUENCE myseq INCREMENT BY 20 MINVALUE 1 START 1',
            $this->_platform->getCreateSequenceSQL($sequence)
        );
        $this->assertEquals(
            'DROP SEQUENCE myseq',
            $this->_platform->getDropSequenceSQL('myseq')
        );
        $this->assertEquals(
            "SELECT NEXTVAL('myseq')",
            $this->_platform->getSequenceNextValSQL('myseq')
        );
    }

    public function testDoesNotPreferIdentityColumns()
    {
        $this->assertFalse($this->_platform->prefersIdentityColumns());
    }

    public function testPrefersSequences()
    {
        $this->assertTrue($this->_platform->prefersSequences());
    }

    public function testSupportsIdentityColumns()
    {
        $this->assertTrue($this->_platform->supportsIdentityColumns());
    }

    public function testSupportsSavePoints()
    {
        $this->assertTrue($this->_platform->supportsSavepoints());
    }

    public function testSupportsSequences()
    {
        $this->assertTrue($this->_platform->supportsSequences());
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