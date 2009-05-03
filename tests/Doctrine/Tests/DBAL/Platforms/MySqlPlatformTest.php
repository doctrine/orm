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

    public function testCreateTableSql()
    {
        $columns = array(
            'id' => array(
                'type' => Type::getType('integer'),
                'autoincrement' => true,
                'primary' => true,
                'notnull' => true
            ),
            'test' => array(
                'type' => Type::getType('varchar'),
                'length' => 255,
                'notnull' => true
            )
        );

        $options = array(
            'primary' => array('id')
        );

        $sql = $this->_platform->getCreateTableSql('test', $columns, $options);
        $this->assertEquals('CREATE TABLE test (id INT AUTO_INCREMENT NOT NULL, test VARCHAR(255) NOT NULL, PRIMARY KEY(id))', $sql[0]);
    }

    public function testAlterTableSql()
    {
        $changes = array(
            'name' => 'userlist',
            'add' => array(
                'quota' => array(
                'type' => Type::getType('integer'),
                'unsigned' => 1
                )
            ));
        
        $this->assertEquals(
            'ALTER TABLE mytable RENAME TO userlist, ADD quota INT UNSIGNED DEFAULT NULL',
            $this->_platform->getAlterTableSql('mytable', $changes)
        );
    }

    public function testCreateIndexSql()
    {
        $indexDef = array(
            'fields' => array(
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

    public function testSqlSnippets()
    {
        $this->assertEquals('RLIKE', $this->_platform->getRegexpExpression());
        $this->assertEquals('`', $this->_platform->getIdentifierQuoteCharacter());
        $this->assertEquals('RAND()', $this->_platform->getRandomExpression());
        $this->assertEquals('CONCAT(column1, column2, column3)', $this->_platform->getConcatExpression('column1', 'column2', 'column3'));
        $this->assertEquals('CHARACTER SET utf8', $this->_platform->getCharsetFieldDeclaration('utf8'));
        $this->assertEquals(
            'SET SESSION TRANSACTION ISOLATION LEVEL READ UNCOMMITTED',
            $this->_platform->getSetTransactionIsolationSql(\Doctrine\DBAL\Connection::TRANSACTION_READ_UNCOMMITTED)
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

    public function testDDLSnippets()
    {
        $this->assertEquals('SHOW DATABASES', $this->_platform->getShowDatabasesSql());
        $this->assertEquals('CREATE DATABASE foobar', $this->_platform->getCreateDatabaseSql('foobar'));
        $this->assertEquals('DROP DATABASE foobar', $this->_platform->getDropDatabaseSql('foobar'));
        $this->assertEquals('DROP TABLE foobar', $this->_platform->getDropTableSql('foobar'));
    }

    public function testTypeDeclarationSql()
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

    public function testPreferences()
    {
        $this->assertTrue($this->_platform->prefersIdentityColumns());
        $this->assertTrue($this->_platform->supportsIdentityColumns());
        $this->assertFalse($this->_platform->supportsSavepoints());
        
    }

/*
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


    */
}