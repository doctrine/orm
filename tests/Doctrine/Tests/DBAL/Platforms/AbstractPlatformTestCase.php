<?php

namespace Doctrine\Tests\DBAL\Platforms;

abstract class AbstractPlatformTestCase extends \Doctrine\Tests\DbalTestCase
{
    /**
     * @var Doctrine\DBAL\Platforms\AbstractPlatform
     */
    protected $_platform;

    abstract public function createPlatform();

    public function setUp()
    {
        $this->_platform = $this->createPlatform();
    }

    public function testCreateWithNoColumns()
    {
        $table = new \Doctrine\DBAL\Schema\Table('test');

        $this->setExpectedException('Doctrine\DBAL\DBALException');
        $sql = $this->_platform->getCreateTableSQL($table);
    }

    public function testGeneratesTableCreationSql()
    {
        $table = new \Doctrine\DBAL\Schema\Table('test');
        $table->addColumn('id', 'integer', array('notnull' => true));
        $table->addColumn('test', 'string', array('notnull' => false, 'length' => 255));
        $table->setPrimaryKey(array('id'));
        $table->setIdGeneratorType(\Doctrine\DBAL\Schema\Table::ID_IDENTITY);

        $sql = $this->_platform->getCreateTableSQL($table);
        $this->assertEquals($this->getGenerateTableSql(), $sql[0]);
    }

    abstract public function getGenerateTableSql();

    public function testGenerateTableWithMultiColumnUniqueIndex()
    {
        $table = new \Doctrine\DBAL\Schema\Table('test');
        $table->addColumn('foo', 'string', array('notnull' => false, 'length' => 255));
        $table->addColumn('bar', 'string', array('notnull' => false, 'length' => 255));
        $table->addUniqueIndex(array("foo", "bar"));

        $sql = $this->_platform->getCreateTableSQL($table);
        $this->assertEquals($this->getGenerateTableWithMultiColumnUniqueIndexSql(), $sql);
    }

    abstract public function getGenerateTableWithMultiColumnUniqueIndexSql();

    public function testGeneratesIndexCreationSql()
    {
        $indexDef = new \Doctrine\DBAL\Schema\Index('my_idx', array('user_name', 'last_login'));

        $this->assertEquals(
            $this->getGenerateIndexSql(),
            $this->_platform->getCreateIndexSQL($indexDef, 'mytable')
        );
    }

    abstract public function getGenerateIndexSql();

    public function testGeneratesUniqueIndexCreationSql()
    {
        $indexDef = new \Doctrine\DBAL\Schema\Index('index_name', array('test', 'test2'), true);

        $sql = $this->_platform->getCreateIndexSQL($indexDef, 'test');
        $this->assertEquals($this->getGenerateUniqueIndexSql(), $sql);
    }

    abstract public function getGenerateUniqueIndexSql();

    public function testGeneratesForeignKeyCreationSql()
    {
        $fk = new \Doctrine\DBAL\Schema\ForeignKeyConstraint(array('fk_name_id'), 'other_table', array('id'), '');

        $sql = $this->_platform->getCreateForeignKeySQL($fk, 'test');
        $this->assertEquals($sql, $this->getGenerateForeignKeySql());
    }

    abstract public function getGenerateForeignKeySql();

    public function testGeneratesConstraintCreationSql()
    {
        $idx = new \Doctrine\DBAL\Schema\Index('constraint_name', array('test'), true, false);
        $sql = $this->_platform->getCreateConstraintSQL($idx, 'test');
        $this->assertEquals($this->getGenerateConstraintUniqueIndexSql(), $sql);

        $pk = new \Doctrine\DBAL\Schema\Index('constraint_name', array('test'), true, true);
        $sql = $this->_platform->getCreateConstraintSQL($pk, 'test');
        $this->assertEquals($this->getGenerateConstraintPrimaryIndexSql(), $sql);

        $fk = new \Doctrine\DBAL\Schema\ForeignKeyConstraint(array('fk_name'), 'foreign', array('id'), 'constraint_fk');
        $sql = $this->_platform->getCreateConstraintSQL($fk, 'test');
        $this->assertEquals($this->getGenerateConstraintForeignKeySql(), $sql);
    }

    public function getGenerateConstraintUniqueIndexSql()
    {
        return 'ALTER TABLE test ADD CONSTRAINT constraint_name UNIQUE (test)';
    }

    public function getGenerateConstraintPrimaryIndexSql()
    {
        return 'ALTER TABLE test ADD CONSTRAINT constraint_name PRIMARY KEY (test)';
    }

    public function getGenerateConstraintForeignKeySql()
    {
        return 'ALTER TABLE test ADD CONSTRAINT constraint_fk FOREIGN KEY (fk_name) REFERENCES foreign (id)';
    }

    abstract public function getGenerateAlterTableSql();

    public function testGeneratesTableAlterationSql()
    {
        $expectedSql = $this->getGenerateAlterTableSql();

        $columnDiff = new \Doctrine\DBAL\Schema\ColumnDiff(
            'bar', new \Doctrine\DBAL\Schema\Column(
                'baz', \Doctrine\DBAL\Types\Type::getType('string'), array('default' => 'def')
            ),
            array('type', 'notnull', 'default')
        );

        $tableDiff = new \Doctrine\DBAL\Schema\TableDiff('mytable');
        $tableDiff->newName = 'userlist';
        $tableDiff->addedColumns['quota'] = new \Doctrine\DBAL\Schema\Column('quota', \Doctrine\DBAL\Types\Type::getType('integer'), array('notnull' => false));
        $tableDiff->removedColumns['foo'] = new \Doctrine\DBAL\Schema\Column('foo', \Doctrine\DBAL\Types\Type::getType('integer'));
        $tableDiff->changedColumns['bar'] = $columnDiff;

        $sql = $this->_platform->getAlterTableSQL($tableDiff);

        $this->assertEquals($expectedSql, $sql);
    }

    public function testGetCustomColumnDeclarationSql()
    {
        $field = array('columnDefinition' => 'MEDIUMINT(6) UNSIGNED');
        $this->assertEquals('foo MEDIUMINT(6) UNSIGNED', $this->_platform->getColumnDeclarationSQL('foo', $field));
    }
}
