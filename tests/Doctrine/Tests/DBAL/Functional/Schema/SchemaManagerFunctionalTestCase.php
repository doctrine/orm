<?php

namespace Doctrine\Tests\DBAL\Functional\Schema;

use Doctrine\DBAL\Types\Type,
    Doctrine\DBAL\Schema\AbstractSchemaManager;

require_once __DIR__ . '/../../../TestInit.php';

class SchemaManagerFunctionalTestCase extends \Doctrine\Tests\DbalFunctionalTestCase
{
    public function testListSequences()
    {
        if(!$this->_conn->getDatabasePlatform()->supportsSequences()) {
            $this->markTestSkipped($this->_conn->getDriver()->getName().' does not support sequences.');
        }

        $sequence = new \Doctrine\DBAL\Schema\Sequence('list_sequences_test_seq', 20, 10);
        $this->_sm->createSequence($sequence);
        
        $sequences = $this->_sm->listSequences();
        
        $this->assertType('array', $sequences, 'listSequences() should return an array.');

        $foundSequence = null;
        foreach($sequences AS $sequence) {
            $this->assertType('Doctrine\DBAL\Schema\Sequence', $sequence, 'Array elements of listSequences() should be Sequence instances.');
            if(strtolower($sequence->getName()) == 'list_sequences_test_seq') {
                $foundSequence = $sequence;
            }
        }

        $this->assertNotNull($foundSequence, "Sequence with name 'list_sequences_test_seq' was not found.");
        $this->assertEquals(20, $foundSequence->getAllocationSize(), "Allocation Size is expected to be 20.");
        $this->assertEquals(10, $foundSequence->getInitialValue(), "Initial Value is expected to be 10.");
    }

    public function testListFunctions()
    {
        $funcs = $this->_sm->listFunctions();
        $this->assertType('array', $funcs);
        $this->assertTrue(count($funcs)>=0);
    }

    public function testListTriggers()
    {
        $triggers = $this->_sm->listTriggers();
        $this->assertType('array', $triggers);
        $this->assertTrue(count($triggers) >= 0);
    }

    public function testListDatabases()
    {
        $this->_sm->dropAndCreateDatabase('test_create_database');
        $databases = $this->_sm->listDatabases();

        $databases = \array_map('strtolower', $databases);
        
        $this->assertEquals(true, \in_array('test_create_database', $databases));
    }

    public function testListTables()
    {
        $this->createTestTable('list_tables_test');
        $tables = $this->_sm->listTables();

        $this->assertType('array', $tables);
        $this->assertTrue(count($tables) > 0);

        $foundTable = false;
        foreach ($tables AS $table) {
            $this->assertType('Doctrine\DBAL\Schema\Table', $table);
            if ($table->getName() == 'list_tables_test') {
                $foundTable = true;

                $this->assertTrue($table->hasColumn('id'));
                $this->assertTrue($table->hasColumn('test'));
                $this->assertTrue($table->hasColumn('foreign_key_test'));
            }
        }
    }

    public function testListTableColumns()
    {
        $table = new \Doctrine\DBAL\Schema\Table('list_table_columns');
        $table->createColumn('id', 'integer', array('notnull' => true));
        $table->createColumn('test', 'string', array('length' => 255, 'notnull' => false));
        $table->createColumn('foo', 'text', array('notnull' => true));
        $table->createColumn('bar', 'decimal', array('precision' => 10, 'scale' => 4, 'notnull' => false));
        $table->createColumn('baz1', 'datetime');
        $table->createColumn('baz2', 'time');
        $table->createColumn('baz3', 'date');

        $this->_sm->dropAndCreateTable($table);

        $columns = $this->_sm->listTableColumns('list_table_columns');

        foreach ($columns AS $column) {
            $column->setCaseMode("lower");
        }

        $this->assertArrayHasKey('id', $columns);
        $this->assertEquals('id',   $columns['id']->getname());
        $this->assertType('Doctrine\DBAL\Types\IntegerType', $columns['id']->gettype());
        $this->assertEquals(false,  $columns['id']->getunsigned());
        $this->assertEquals(true,   $columns['id']->getnotnull());
        $this->assertEquals(null,   $columns['id']->getdefault());
        $this->assertType('array',  $columns['id']->getPlatformOptions());

        $this->assertArrayHasKey('test', $columns);
        $this->assertEquals('test', $columns['test']->getname());
        $this->assertType('Doctrine\DBAL\Types\StringType', $columns['test']->gettype());
        $this->assertEquals(255,    $columns['test']->getlength());
        $this->assertEquals(false,  $columns['test']->getfixed());
        $this->assertEquals(false,  $columns['test']->getnotnull());
        $this->assertEquals(null,   $columns['test']->getdefault());
        $this->assertType('array',  $columns['test']->getPlatformOptions());

        $this->assertEquals('foo', ($columns['foo']->getname()));
        $this->assertType('Doctrine\DBAL\Types\TextType', $columns['foo']->gettype());
        $this->assertEquals(null,   $columns['foo']->getlength());
        $this->assertEquals(false,  $columns['foo']->getunsigned());
        $this->assertEquals(false,  $columns['foo']->getfixed());
        $this->assertEquals(true,   $columns['foo']->getnotnull());
        $this->assertEquals(null,   $columns['foo']->getdefault());
        $this->assertType('array',  $columns['foo']->getPlatformOptions());

        $this->assertEquals('bar', ($columns['bar']->getname()));
        $this->assertType('Doctrine\DBAL\Types\DecimalType', $columns['bar']->gettype());
        $this->assertEquals(null,   $columns['bar']->getlength());
        $this->assertEquals(10,   $columns['bar']->getprecision());
        $this->assertEquals(4,   $columns['bar']->getscale());
        $this->assertEquals(false,  $columns['bar']->getunsigned());
        $this->assertEquals(false,  $columns['bar']->getfixed());
        $this->assertEquals(false,   $columns['bar']->getnotnull());
        $this->assertEquals(null,   $columns['bar']->getdefault());
        $this->assertType('array',  $columns['bar']->getPlatformOptions());

        $this->assertEquals('baz1', ($columns['baz1']->getname()));
        $this->assertType('Doctrine\DBAL\Types\DateTimeType', $columns['baz1']->gettype());
        $this->assertEquals(true,   $columns['baz1']->getnotnull());
        $this->assertEquals(null,   $columns['baz1']->getdefault());
        $this->assertType('array',  $columns['baz1']->getPlatformOptions());

        $this->assertEquals('baz2', ($columns['baz2']->getname()));
        $this->assertContains($columns['baz2']->gettype()->getName(), array('Time', 'Date', 'DateTime'));
        $this->assertEquals(true,   $columns['baz2']->getnotnull());
        $this->assertEquals(null,   $columns['baz2']->getdefault());
        $this->assertType('array',  $columns['baz2']->getPlatformOptions());
        
        $this->assertEquals('baz3', ($columns['baz3']->getname()));
        $this->assertContains($columns['baz2']->gettype()->getName(), array('Time', 'Date', 'DateTime'));
        $this->assertEquals(true,   $columns['baz3']->getnotnull());
        $this->assertEquals(null,   $columns['baz3']->getdefault());
        $this->assertType('array',  $columns['baz3']->getPlatformOptions());
    }

    public function testListTableIndexes()
    {
        $table = $this->getTestTable('list_table_indexes_test');
        $table->addUniqueIndex(array('test'), 'test_index_name');
        $table->addIndex(array('id', 'test'), 'test_composite_idx');

        $this->_sm->createTable($table);

        $tableIndexes = $this->_sm->listTableIndexes('list_table_indexes_test');

        $this->assertEquals(3, count($tableIndexes));

        $this->assertEquals(array('id'), array_map('strtolower', $tableIndexes['primary']->getColumns()));
        $this->assertTrue($tableIndexes['primary']->isUnique());
        $this->assertTrue($tableIndexes['primary']->isPrimary());

        $this->assertEquals('test_index_name', $tableIndexes['test_index_name']->getName());
        $this->assertEquals(array('test'), array_map('strtolower', $tableIndexes['test_index_name']->getColumns()));
        $this->assertTrue($tableIndexes['test_index_name']->isUnique());
        $this->assertFalse($tableIndexes['test_index_name']->isPrimary());

        $this->assertEquals('test_composite_idx', $tableIndexes['test_composite_idx']->getName());
        $this->assertEquals(array('id', 'test'), array_map('strtolower', $tableIndexes['test_composite_idx']->getColumns()));
        $this->assertFalse($tableIndexes['test_composite_idx']->isUnique());
        $this->assertFalse($tableIndexes['test_composite_idx']->isPrimary());
    }

    public function testDropAndCreateIndex()
    {
        $table = $this->getTestTable('test_create_index');
        $table->addUniqueIndex(array('test'), 'test');
        $this->_sm->dropAndCreateTable($table);

        $this->_sm->dropAndCreateIndex($table->getIndex('test'), $table);
        $tableIndexes = $this->_sm->listTableIndexes('test_create_index');
        $this->assertType('array', $tableIndexes);

        $this->assertEquals('test', $tableIndexes['test']->getName());
        $this->assertEquals(array('test'), array_map('strtolower', $tableIndexes['test']->getColumns()));
        $this->assertTrue($tableIndexes['test']->isUnique());
        $this->assertFalse($tableIndexes['test']->isPrimary());
    }

    public function testCreateTableWithForeignKeys()
    {
        if(!$this->_sm->getDatabasePlatform()->supportsForeignKeyConstraints()) {
            $this->markTestSkipped('Platform does not support foreign keys.');
        }

        $tableB = $this->getTestTable('test_foreign');

        $this->_sm->dropAndCreateTable($tableB);

        $tableA = $this->getTestTable('test_create_fk');
        $tableA->addForeignKeyConstraint('test_foreign', array('foreign_key_test'), array('id'));

        $this->_sm->dropAndCreateTable($tableA);

        $fkConstraints = $this->_sm->listTableForeignKeys('test_create_fk');
        $this->assertEquals(1, count($fkConstraints));

        $fkConstraint = current($fkConstraints);
        $fkConstraint->setCaseMode("lower");
        $this->assertEquals('test_foreign', $fkConstraint->getForeignTableName());
        $this->assertEquals(array('foreign_key_test'), $fkConstraint->getColumns());
        $this->assertEquals(array('id'), $fkConstraint->getForeignColumns());
    }

    public function testListForeignKeys()
    {
        if(!$this->_conn->getDatabasePlatform()->supportsForeignKeyConstraints()) {
            $this->markTestSkipped('Does not support foreign key constraints.');
        }

        $this->createTestTable('test_create_fk1');
        $this->createTestTable('test_create_fk2');

        $foreignKey = new \Doctrine\DBAL\Schema\ForeignKeyConstraint(
            array('foreign_key_test'), 'test_create_fk2', array('id'), 'foreign_key_test_fk', array('onUpdate' => 'CASCADE', 'onDelete' => 'CASCADE')
        );

        $this->_sm->createForeignKey($foreignKey, 'test_create_fk1');

        $fkeys = $this->_sm->listTableForeignKeys('test_create_fk1');

        foreach ($fkeys AS $fkey) {
            $fkey->setCaseMode("lower");
        }

        $this->assertEquals(1, count($fkeys));
        $this->assertType('Doctrine\DBAL\Schema\ForeignKeyConstraint', $fkeys[0]);
        $this->assertEquals(array('foreign_key_test'), array_map('strtolower', $fkeys[0]->getLocalColumns()));
        $this->assertEquals(array('id'), array_map('strtolower', $fkeys[0]->getForeignColumns()));
        $this->assertEquals('test_create_fk2', ($fkeys[0]->getForeignTableName()));

        if($fkeys[0]->hasOption('onUpdate')) {
            $this->assertEquals('CASCADE', $fkeys[0]->getOption('onUpdate'));
        }
        if($fkeys[0]->hasOption('onDelete')) {
            $this->assertEquals('CASCADE', $fkeys[0]->getOption('onDelete'));
        }
    }

    protected function getCreateExampleViewSql()
    {
        $this->markTestSkipped('No Create Example View SQL was defined for this SchemaManager');
    }

    public function testListViews()
    {
        $this->_sm->dropAndCreateView('test_create_view', $this->getCreateExampleViewSql());
        $views = $this->_sm->listViews();
        $this->assertTrue(count($views) >= 1, "There should be at least the fixture view created in the database, but none were found.");
        
        $found = false;
        foreach($views AS $view) {
            if(!isset($view['name']) || !isset($view['sql'])) {
                $this->fail(
                    "listViews() has to return entries with both name ".
                    "and sql keys, but only ".implode(", ", array_keys($view))." are present."
                );
            }

            if($view['name'] == 'test_create_view') {
                $found = true;
            }
        }
        $this->assertTrue($found, "'test_create_view' View was not found in listViews().");
    }

    public function testCreateSchema()
    {
        $this->createTestTable('test_table');

        $schema = $this->_sm->createSchema();

        $this->assertTrue($schema->hasTable('test_table'));
    }

    /**
     * @var \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    protected $_sm;

    protected function setUp()
    {
        parent::setUp();

        $class = get_class($this);
        $e = explode('\\', $class);
        $testClass = end($e);
        $dbms = strtolower(str_replace('SchemaManagerTest', null, $testClass));

        if ($this->_conn->getDatabasePlatform()->getName() !== $dbms)
        {
            $this->markTestSkipped('The ' . $testClass .' requires the use of ' . $dbms);
        }

        $this->_sm = $this->_conn->getSchemaManager();
    }

    protected function createTestTable($name = 'test_table', $data = array())
    {
        $options = array();
        if (isset($data['options'])) {
            $options = $data['options'];
        }

        $table = $this->getTestTable($name, $options);

        $this->_sm->dropAndCreateTable($table);
    }

    protected function getTestTable($name, $options=array())
    {
        $table = new \Doctrine\DBAL\Schema\Table($name, array(), array(), array(), \Doctrine\DBAL\Schema\Table::ID_NONE, $options);
        $table->setIdGeneratorType(\Doctrine\DBAL\Schema\Table::ID_IDENTITY);
        $table->createColumn('id', 'integer', array('notnull' => true));
        $table->setPrimaryKey(array('id'));
        $table->createColumn('test', 'string', array('length' => 255));
        $table->createColumn('foreign_key_test', 'integer');
        return $table;
    }

    protected function assertHasTable($tables, $tableName)
    {
        $foundTable = false;
        foreach ($tables AS $table) {
            $this->assertType('Doctrine\DBAL\Schema\Table', $table, 'No Table instance was found in tables array.');
            if (strtolower($table->getName()) == 'list_tables_test_new_name') {
                $foundTable = true;
            }
        }
        $this->assertTrue($foundTable, "Could not find new table");
    }
}