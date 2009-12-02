<?php

namespace Doctrine\Tests\DBAL\Platforms;

abstract class AbstractPlatformTestCase extends \Doctrine\Tests\DbalTestCase
{
    protected $_platform;

    abstract public function createPlatform();

    public function setUp()
    {
        $this->_platform = $this->createPlatform();
    }

    public function testGeneratesTableCreationSql()
    {
        $table = new \Doctrine\DBAL\Schema\Table('test');
        $table->createColumn('id', 'integer', array('notnull' => true));
        $table->createColumn('test', 'string', array('notnull' => false, 'length' => 255));
        $table->setPrimaryKey(array('id'));
        $table->setIdGeneratorType(\Doctrine\DBAL\Schema\Table::ID_IDENTITY);

        $sql = $this->_platform->getCreateTableSql($table);
        $this->assertEquals($this->getGenerateTableSql(), $sql[0]);
    }

    abstract public function getGenerateTableSql();

    public function testGeneratesIndexCreationSql()
    {
        $indexDef = new \Doctrine\DBAL\Schema\Index('my_idx', array('user_name', 'last_login'));

        $this->assertEquals(
            $this->getGenerateIndexSql(),
            $this->_platform->getCreateIndexSql($indexDef, 'mytable')
        );
    }

    abstract public function getGenerateIndexSql();

    public function testGeneratesUniqueIndexCreationSql()
    {
        $indexDef = new \Doctrine\DBAL\Schema\Index('index_name', array('test', 'test2'), true);

        $sql = $this->_platform->getCreateIndexSql($indexDef, 'test');
        $this->assertEquals($this->getGenerateUniqueIndexSql(), $sql);
    }

    abstract public function getGenerateUniqueIndexSql();

    public function testGeneratesForeignKeyCreationSql()
    {
        $fk = new \Doctrine\DBAL\Schema\ForeignKeyConstraint(array('fk_name_id'), 'other_table', array('id'), '');

        $sql = $this->_platform->getCreateForeignKeySql($fk, 'test');
        $this->assertEquals($sql, $this->getGenerateForeignKeySql());
    }

    abstract public function getGenerateForeignKeySql();
}
