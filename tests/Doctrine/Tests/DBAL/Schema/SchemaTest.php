<?php

namespace Doctrine\Tests\DBAL\Schema;

require_once __DIR__ . '/../../TestInit.php';

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Sequence;

class SchemaTest extends \PHPUnit_Framework_TestCase
{
    public function testAddTable()
    {
        $tableName = "foo";
        $table = new Table($tableName);

        $schema = new Schema(array($table));

        $this->assertTrue($schema->hasTable($tableName));

        $tables = $schema->getTables();
        $this->assertTrue( isset($tables[$tableName]) );
        $this->assertSame($table, $tables[$tableName]);
        $this->assertSame($table, $schema->getTable($tableName));
        $this->assertTrue($schema->hasTable($tableName));
    }

    public function testTableMatchingCaseInsenstive()
    {
        $table = new Table("Foo");

        $schema = new Schema(array($table));
        $this->assertTrue($schema->hasTable("foo"));
        $this->assertTrue($schema->hasTable("FOO"));

        $this->assertSame($table, $schema->getTable('FOO'));
        $this->assertSame($table, $schema->getTable('foo'));
        $this->assertSame($table, $schema->getTable('Foo'));
    }

    public function testGetUnknownTableThrowsException()
    {
        $this->setExpectedException("Doctrine\DBAL\Schema\SchemaException");

        $schema = new Schema();
        $schema->getTable("unknown");
    }

    public function testCreateTableTwiceThrowsException()
    {
        $this->setExpectedException("Doctrine\DBAL\Schema\SchemaException");

        $tableName = "foo";
        $table = new Table($tableName);
        $tables = array($table, $table);

        $schema = new Schema($tables);
    }

    public function testRenameTable()
    {
        $tableName = "foo";
        $table = new Table($tableName);
        $schema = new Schema(array($table));

        $this->assertTrue($schema->hasTable("foo"));
        $schema->renameTable("foo", "bar");
        $this->assertFalse($schema->hasTable("foo"));
        $this->assertTrue($schema->hasTable("bar"));
        $this->assertSame($table, $schema->getTable("bar"));
    }

    public function testDropTable()
    {
        $tableName = "foo";
        $table = new Table($tableName);
        $schema = new Schema(array($table));

        $this->assertTrue($schema->hasTable("foo"));

        $schema->dropTable("foo");

        $this->assertFalse($schema->hasTable("foo"));
    }

    public function testCreateTable()
    {
        $schema = new Schema();

        $this->assertFalse($schema->hasTable("foo"));

        $table = $schema->createTable("foo");

        $this->assertType('Doctrine\DBAL\Schema\Table', $table);
        $this->assertEquals("foo", $table->getName());
        $this->assertTrue($schema->hasTable("foo"));
    }

    public function testAddSequences()
    {
        $sequence = new Sequence("a_seq", 1, 1);

        $schema = new Schema(array(), array($sequence));

        $this->assertTrue($schema->hasSequence("a_seq"));
        $this->assertType('Doctrine\DBAL\Schema\Sequence', $schema->getSequence("a_seq"));

        $sequences = $schema->getSequences();
        $this->assertArrayHasKey('a_seq', $sequences);
    }

    public function testSequenceAccessCaseInsensitive()
    {
        $sequence = new Sequence("a_Seq");

        $schema = new Schema(array(), array($sequence));
        $this->assertTrue($schema->hasSequence('a_seq'));
        $this->assertTrue($schema->hasSequence('a_Seq'));
        $this->assertTrue($schema->hasSequence('A_SEQ'));

        $this->assertEquals($sequence, $schema->getSequence('a_seq'));
        $this->assertEquals($sequence, $schema->getSequence('a_Seq'));
        $this->assertEquals($sequence, $schema->getSequence('A_SEQ'));
    }

    public function testGetUnknownSequenceThrowsException()
    {
        $this->setExpectedException("Doctrine\DBAL\Schema\SchemaException");

        $schema = new Schema();
        $schema->getSequence("unknown");
    }

    public function testCreateSequence()
    {
        $schema = new Schema();
        $sequence = $schema->createSequence('a_seq', 10, 20);

        $this->assertEquals('a_seq', $sequence->getName());
        $this->assertEquals(10, $sequence->getAllocationSize());
        $this->assertEquals(20, $sequence->getInitialValue());

        $this->assertTrue($schema->hasSequence("a_seq"));
        $this->assertType('Doctrine\DBAL\Schema\Sequence', $schema->getSequence("a_seq"));

        $sequences = $schema->getSequences();
        $this->assertArrayHasKey('a_seq', $sequences);
    }

    public function testDropSequence()
    {
        $sequence = new Sequence("a_seq", 1, 1);

        $schema = new Schema(array(), array($sequence));

        $schema->dropSequence("a_seq");
        $this->assertFalse($schema->hasSequence("a_seq"));
    }

    public function testAddSequenceTwiceThrowsException()
    {
        $this->setExpectedException("Doctrine\DBAL\Schema\SchemaException");

        $sequence = new Sequence("a_seq", 1, 1);

        $schema = new Schema(array(), array($sequence, $sequence));
    }

    public function testFixSchema_AddExplicitIndexForForeignKey()
    {
        $schema = new Schema();
        $tableA = $schema->createTable('foo');
        $tableA->addColumn('id', 'integer');

        $tableB = $schema->createTable('bar');
        $tableB->addColumn('id', 'integer');
        $tableB->addColumn('foo_id', 'integer');
        $tableB->addForeignKeyConstraint($tableA, array('foo_id'), array('id'));

        $this->assertEquals(0, count($tableB->getIndexes()));

        $schema->visit(new \Doctrine\DBAL\Schema\Visitor\FixSchema(true));

        $this->assertEquals(1, count($tableB->getIndexes()));
        $indexes = $tableB->getIndexes();
        $index = current($indexes);
        $this->assertTrue($index->hasColumnAtPosition('foo_id', 0));
    }

    public function testConfigHasExplicitForeignKeyIndex()
    {
        $schemaConfig = new \Doctrine\DBAL\Schema\SchemaConfig();
        $schemaConfig->setExplicitForeignKeyIndexes(false);

        $schema = new Schema(array(), array(), array(), array(), $schemaConfig);
        $this->assertFalse($schema->hasExplicitForeignKeyIndexes());

        $schemaConfig->setExplicitForeignKeyIndexes(true);
        $this->assertTrue($schema->hasExplicitForeignKeyIndexes());
    }

    public function testConfigMaxIdentifierLength()
    {
        $schemaConfig = new \Doctrine\DBAL\Schema\SchemaConfig();
        $schemaConfig->setMaxIdentifierLength(10);

        $schema = new Schema(array(), array(), array(), array(), $schemaConfig);
        $table = $schema->createTable("smalltable");
        $table->addColumn('long_id', 'integer');
        $table->addIndex(array('long_id'));

        $this->assertTrue($table->hasIndex('le_id_idx'));
    }

    public function testDeepClone()
    {
        $schema = new Schema();
        $sequence = $schema->createSequence('baz');

        $tableA = $schema->createTable('foo');
        $tableA->addColumn('id', 'integer');

        $tableB = $schema->createTable('bar');
        $tableB->addColumn('id', 'integer');
        $tableB->addColumn('foo_id', 'integer');
        $tableB->addForeignKeyConstraint($tableA, array('foo_id'), array('id'));

        $schemaNew = clone $schema;

        $this->assertNotSame($sequence, $schemaNew->getSequence('baz'));

        $this->assertNotSame($tableA, $schemaNew->getTable('foo'));
        $this->assertNotSame($tableA->getColumn('id'), $schemaNew->getTable('foo')->getColumn('id'));

        $this->assertNotSame($tableB, $schemaNew->getTable('bar'));
        $this->assertNotSame($tableB->getColumn('id'), $schemaNew->getTable('bar')->getColumn('id'));

        $fk = current( $schemaNew->getTable('bar')->getForeignKeys() );
        $this->assertSame($schemaNew->getTable('bar'), $this->readAttribute($fk, '_localTable'));
    }
}