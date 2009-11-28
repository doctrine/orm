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
}