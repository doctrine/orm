<?php

namespace Doctrine\Tests\DBAL\Platforms;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\Type;

require_once __DIR__ . '/../../TestInit.php';
 
class MySqlPlatformTest extends \Doctrine\Tests\DbalTestCase
{
    private $_platform;

    public function setUp()
    {
        $this->_platform = new MySqlPlatform;
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
                'length' => 255,
                'notnull' => true
            )
        );

        $options = array(
            'primary' => array('id')
        );

        $sql = $this->_platform->getCreateTableSql('test', $columns, $options);
        $this->assertEquals('CREATE TABLE test (id INT AUTO_INCREMENT NOT NULL, test VARCHAR(255) NOT NULL, PRIMARY KEY(id)) ENGINE = InnoDB', $sql[0]);
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
        $this->assertEquals('`', $this->_platform->getIdentifierQuoteCharacter(), 'Quote character is not correct');
        $this->assertEquals('RAND()', $this->_platform->getRandomExpression(), 'Random function is not correct');
        $this->assertEquals('CONCAT(column1, column2, column3)', $this->_platform->getConcatExpression('column1', 'column2', 'column3'), 'Concatenation function is not correct');
        $this->assertEquals('CHARACTER SET utf8', $this->_platform->getCharsetFieldDeclaration('utf8'), 'Charset declaration is not correct');
    }

    public function testGeneratesTransactionsCommands()
    {
        $this->assertEquals(
            'SET SESSION TRANSACTION ISOLATION LEVEL READ UNCOMMITTED',
            $this->_platform->getSetTransactionIsolationSql(\Doctrine\DBAL\Connection::TRANSACTION_READ_UNCOMMITTED),
            ''
        );
        $this->assertEquals(
            'SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED',
            $this->_platform->getSetTransactionIsolationSql(\Doctrine\DBAL\Connection::TRANSACTION_READ_COMMITTED)
        );
        $this->assertEquals(
            'SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ',
            $this->_platform->getSetTransactionIsolationSql(\Doctrine\DBAL\Connection::TRANSACTION_REPEATABLE_READ)
        );
        $this->assertEquals(
            'SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE',
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

    public function testGeneratesTypeDeclarationForStrings()
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
            'VARCHAR(255)',
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

    public function testGeneratesIndexCreationSql()
    {
        $indexDef = array(
            'columns' => array(
                'user_name' => array(
                    'sorting' => 'ASC',
                    'length' => 10
                ),
                'last_login' => array()
            )
        );

        $this->assertEquals(
            'CREATE INDEX my_idx ON mytable (user_name(10) ASC, last_login)',
            $this->_platform->getCreateIndexSql('mytable', 'my_idx', $indexDef)
        );
    }

    public function testGeneratesUniqueIndexCreationSql()
    {
        $sql = $this->_platform->getCreateIndexSql('test', 'index_name', array('type' => 'unique', 'columns' => array('test', 'test2')));
        $this->assertEquals($sql, 'CREATE UNIQUE INDEX index_name ON test (test, test2)');
    }

    public function testGeneratesForeignKeyCreationSql()
    {
        $sql = $this->_platform->getCreateForeignKeySql('test', array('foreignTable' => 'other_table', 'local' => 'fk_name_id', 'foreign' => 'id'));
        $this->assertEquals($sql, 'ALTER TABLE test ADD FOREIGN KEY (fk_name_id) REFERENCES other_table(id)');
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

    /**
     * @group DDC-118
     */
    public function testGetDateTimeTypeDeclarationSql()
    {
        $this->assertEquals("DATETIME", $this->_platform->getDateTimeTypeDeclarationSql(array('version' => false)));
        $this->assertEquals("TIMESTAMP", $this->_platform->getDateTimeTypeDeclarationSql(array('version' => true)));
        $this->assertEquals("DATETIME", $this->_platform->getDateTimeTypeDeclarationSql(array()));
    }
}