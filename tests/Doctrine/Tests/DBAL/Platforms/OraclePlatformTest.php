<?php

namespace Doctrine\Tests\DBAL\Platforms;

use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Types\Type;

require_once __DIR__ . '/../../TestInit.php';
 
class OraclePlatformTest extends \Doctrine\Tests\DbalTestCase
{
    private $_platform;

    public function setUp()
    {
        $this->_platform = new OraclePlatform;
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
        $this->assertEquals('CREATE TABLE test (id NUMBER(10) NOT NULL, test VARCHAR2(255) NOT NULL, PRIMARY KEY(id))', $sql[0]);
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
            'ALTER TABLE mytable ADD (quota NUMBER(10) DEFAULT NULL)',
            $sql[0]
        );

        $this->assertEquals(
            'ALTER TABLE mytable RENAME TO userlist',
            $sql[1]
        );
    }

    /**
     * @expectedException Doctrine\DBAL\DBALException
     */
    public function testRLike()
    {
        $this->assertEquals('RLIKE', $this->_platform->getRegexpExpression(), 'Regular expression operator is not correct');
    }

    public function testGeneratesSqlSnippets()
    {
        $this->assertEquals('"', $this->_platform->getIdentifierQuoteCharacter(), 'Identifier quote character is not correct');
        $this->assertEquals('dbms_random.value', $this->_platform->getRandomExpression(), 'Random function is not correct');
        $this->assertEquals('column1 || column2 || column3', $this->_platform->getConcatExpression('column1', 'column2', 'column3'), 'Concatenation expression is not correct');
    }

    /**
     * @expectedException Doctrine\DBAL\DBALException
     */
    public function testGetCharsetFieldDeclaration()
    {
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
            'SET TRANSACTION ISOLATION LEVEL SERIALIZABLE',
            $this->_platform->getSetTransactionIsolationSql(\Doctrine\DBAL\Connection::TRANSACTION_REPEATABLE_READ)
        );
        $this->assertEquals(
            'SET TRANSACTION ISOLATION LEVEL SERIALIZABLE',
            $this->_platform->getSetTransactionIsolationSql(\Doctrine\DBAL\Connection::TRANSACTION_SERIALIZABLE)
        );
    }

    /**
     * @expectedException Doctrine\DBAL\DBALException
     */
    public function testShowDatabasesThrowsException()
    {
        $this->assertEquals('SHOW DATABASES', $this->_platform->getShowDatabasesSql());
    }

    /**
     * @expectedException Doctrine\DBAL\DBALException
     */
    public function testCreateDatabaseThrowsException()
    {
        $this->assertEquals('CREATE DATABASE foobar', $this->_platform->getCreateDatabaseSql('foobar'));
    }

    public function testDropDatabaseThrowsException()
    {
        $this->assertEquals('DROP USER foobar CASCADE', $this->_platform->getDropDatabaseSql('foobar'));
    }

    public function testDropTable()
    {
        $this->assertEquals('DROP TABLE foobar', $this->_platform->getDropTableSql('foobar'));        
    }

    public function testGeneratesTypeDeclarationForIntegers()
    {
        $this->assertEquals(
            'NUMBER(10)',
            $this->_platform->getIntegerTypeDeclarationSql(array())
        );
        $this->assertEquals(
            'NUMBER(10)',
            $this->_platform->getIntegerTypeDeclarationSql(array('autoincrement' => true)
        ));
        $this->assertEquals(
            'NUMBER(10)',
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
            'VARCHAR2(50)',
            $this->_platform->getVarcharTypeDeclarationSql(array('length' => 50)),
            'Variable string declaration is not correct'
        );
        $this->assertEquals(
            'VARCHAR2(4000)',
            $this->_platform->getVarcharTypeDeclarationSql(array()),
            'Long string declaration is not correct'
        );
    }

    public function testPrefersIdentityColumns()
    {
        $this->assertFalse($this->_platform->prefersIdentityColumns());
    }

    public function testSupportsIdentityColumns()
    {
        $this->assertFalse($this->_platform->supportsIdentityColumns());
    }

    public function testSupportsSavePoints()
    {
        $this->assertTrue($this->_platform->supportsSavepoints());   
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
            'CREATE INDEX my_idx ON mytable (user_name, last_login)',
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
        $this->assertEquals('SELECT a.* FROM (SELECT * FROM user) a WHERE ROWNUM <= 10', $sql);
    }

    public function testModifyLimitQueryWithEmptyOffset()
    {
        $sql = $this->_platform->modifyLimitQuery('SELECT * FROM user', 10);
        $this->assertEquals('SELECT a.* FROM (SELECT * FROM user) a WHERE ROWNUM <= 10', $sql);
    }

    public function testModifyLimitQueryWithAscOrderBy()
    {
        $sql = $this->_platform->modifyLimitQuery('SELECT * FROM user ORDER BY username ASC', 10);
        $this->assertEquals('SELECT a.* FROM (SELECT * FROM user ORDER BY username ASC) a WHERE ROWNUM <= 10', $sql);
    }

    public function testModifyLimitQueryWithDescOrderBy()
    {
        $sql = $this->_platform->modifyLimitQuery('SELECT * FROM user ORDER BY username DESC', 10);
        $this->assertEquals('SELECT a.* FROM (SELECT * FROM user ORDER BY username DESC) a WHERE ROWNUM <= 10', $sql);
    }
}