<?php

namespace Doctrine\Tests\DBAL\Platforms;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Connection;

require_once __DIR__ . '/../../TestInit.php';
 
class PostgreSqlPlatformTest extends \Doctrine\Tests\DbalTestCase
{
    private $_platform;

    public function setUp()
    {
        $this->_platform = new PostgreSqlPlatform;
    }

    public function testGeneratesTableCreationSql()
    {
        $columns = array(
            'id' => array(
                'type' => Type::getType('integer'),
                'primary' => true,
                'notnull' => true
            ),
            'test' => array(
                'type' => Type::getType('string'),
                'length' => 255,
                'notnull' => true
            )
        );

        $options = array(
            'primary' => array('id')
        );

        $sql = $this->_platform->getCreateTableSql('test', $columns, $options);
        $this->assertEquals('CREATE TABLE test (id INT NOT NULL, test VARCHAR(255) NOT NULL, PRIMARY KEY(id))', $sql[0]);
    
    }

    public function testGeneratesTableAlterationSqlForAddingAndRenaming()
    {
        $changes = array(
            'name' => 'userlist',
            'add' => array(
                'quota' => array(
                'type' => Type::getType('integer')
                )
            ));

        $sql = $this->_platform->getAlterTableSql('mytable', $changes);

        $this->assertEquals(
            'ALTER TABLE mytable ADD quota INT DEFAULT NULL',
            $sql[0]
        );
        $this->assertEquals(
            'ALTER TABLE mytable RENAME TO userlist',
            $sql[1]
        );
    }

    public function testGeneratesIndexCreationSql()
    {
        $indexDef = array(
            'fields' => array(
                'user_name',
                'last_login'
            )
        );

        $sql = $this->_platform->getCreateIndexSql('mytable', 'my_idx', $indexDef);

        $this->assertEquals(
            'CREATE INDEX my_idx ON mytable (user_name, last_login)',
            $sql
        );
    }

    public function testGeneratesSqlSnippets()
    {
        $this->assertEquals('SIMILAR TO', $this->_platform->getRegexpExpression(), 'Regular expression operator is not correct');
        $this->assertEquals('"', $this->_platform->getIdentifierQuoteCharacter(), 'Identifier quote character is not correct');
        $this->assertEquals('RANDOM()', $this->_platform->getRandomExpression(), 'Random function is not correct');
        $this->assertEquals('column1 || column2 || column3', $this->_platform->getConcatExpression('column1', 'column2', 'column3'), 'Concatenation expression is not correct');
    }

    public function testGeneratesTransactionCommands()
    {
        $this->assertEquals(
            'SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL READ UNCOMMITTED',
            $this->_platform->getSetTransactionIsolationSql(Connection::TRANSACTION_READ_UNCOMMITTED)
        );
        $this->assertEquals(
            'SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL READ COMMITTED',
            $this->_platform->getSetTransactionIsolationSql(Connection::TRANSACTION_READ_COMMITTED)
        );
        $this->assertEquals(
            'SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL REPEATABLE READ',
            $this->_platform->getSetTransactionIsolationSql(Connection::TRANSACTION_REPEATABLE_READ)
        );
        $this->assertEquals(
            'SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL SERIALIZABLE',
            $this->_platform->getSetTransactionIsolationSql(Connection::TRANSACTION_SERIALIZABLE)
        );
    }

    public function testGeneratesDDLSnippets()
    {
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
            'SERIAL',
            $this->_platform->getIntegerTypeDeclarationSql(array('autoincrement' => true)
        ));
        $this->assertEquals(
            'SERIAL',
            $this->_platform->getIntegerTypeDeclarationSql(
                array('autoincrement' => true, 'primary' => true)
        ));
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

    public function testGeneratesSequenceSqlCommands()
    {
        $this->assertEquals(
            'CREATE SEQUENCE myseq INCREMENT BY 20 START 1',
            $this->_platform->getCreateSequenceSql('myseq', 1, 20)
        );
        $this->assertEquals(
            'DROP SEQUENCE myseq',
            $this->_platform->getDropSequenceSql('myseq')
        );
        $this->assertEquals(
            "SELECT NEXTVAL('myseq')",
            $this->_platform->getSequenceNextValSql('myseq')
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
}
