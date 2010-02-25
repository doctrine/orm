<?php

namespace Doctrine\Tests\DBAL\Schema;

require_once __DIR__ . '/../../TestInit.php';

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableBuilder;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Types\Type;

class TableTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateWithInvalidTableName()
    {
        $this->setExpectedException('Doctrine\DBAL\DBALException');
        $table = new \Doctrine\DBAL\Schema\Table('');
    }

    public function testGetName()
    {
        $table =  new Table("foo", array(), array(), array());
        $this->assertEquals("foo", $table->getName());
    }

    public function testColumns()
    {
        $type = Type::getType('integer');
        $columns = array();
        $columns[] = new Column("foo", $type);
        $columns[] = new Column("bar", $type);
        $table = new Table("foo", $columns, array(), array());

        $this->assertTrue($table->hasColumn("foo"));
        $this->assertTrue($table->hasColumn("bar"));
        $this->assertFalse($table->hasColumn("baz"));

        $this->assertType('Doctrine\DBAL\Schema\Column', $table->getColumn("foo"));
        $this->assertType('Doctrine\DBAL\Schema\Column', $table->getColumn("bar"));

        $this->assertEquals(2, count($table->getColumns()));
    }

    public function testColumnsCaseInsensitive()
    {
        $table = new Table("foo");
        $column = $table->addColumn('Foo', 'integer');

        $this->assertTrue($table->hasColumn('Foo'));
        $this->assertTrue($table->hasColumn('foo'));
        $this->assertTrue($table->hasColumn('FOO'));

        $this->assertSame($column, $table->getColumn('Foo'));
        $this->assertSame($column, $table->getColumn('foo'));
        $this->assertSame($column, $table->getColumn('FOO'));
    }

    public function testCreateColumn()
    {
        $type = Type::getType('integer');

        $table = new Table("foo");

        $this->assertFalse($table->hasColumn("bar"));
        $table->addColumn("bar", 'integer');
        $this->assertTrue($table->hasColumn("bar"));
        $this->assertSame($type, $table->getColumn("bar")->getType());
    }

    public function testDropColumn()
    {
        $type = Type::getType('integer');
        $columns = array();
        $columns[] = new Column("foo", $type);
        $columns[] = new Column("bar", $type);
        $table = new Table("foo", $columns, array(), array());

        $this->assertTrue($table->hasColumn("foo"));
        $this->assertTrue($table->hasColumn("bar"));

        $table->dropColumn("foo")->dropColumn("bar");

        $this->assertFalse($table->hasColumn("foo"));
        $this->assertFalse($table->hasColumn("bar"));
    }

    public function testGetUnknownColumnThrowsException()
    {
        $this->setExpectedException("Doctrine\DBAL\Schema\SchemaException");

        $table = new Table("foo", array(), array(), array());
        $table->getColumn('unknown');
    }

    public function testAddColumnTwiceThrowsException()
    {
        $this->setExpectedException("Doctrine\DBAL\Schema\SchemaException");

        $type = \Doctrine\DBAL\Types\Type::getType('integer');
        $columns = array();
        $columns[] = new Column("foo", $type);
        $columns[] = new Column("foo", $type);
        $table = new Table("foo", $columns, array(), array());
    }

    public function testCreateIndex()
    {
        $type = \Doctrine\DBAL\Types\Type::getType('integer');
        $columns = array(new Column("foo", $type), new Column("bar", $type), new Column("baz", $type));
        $table = new Table("foo", $columns);
        
        $table->addIndex(array("foo", "bar", "baz"));
        $table->addUniqueIndex(array("foo", "bar", "baz"));

        $this->assertTrue($table->hasIndex("foo_foo_bar_baz_idx"));
        $this->assertTrue($table->hasIndex("foo_foo_bar_baz_uniq"));
    }

    public function testIndexCaseInsensitive()
    {
        $type = \Doctrine\DBAL\Types\Type::getType('integer');
        $columns = array(new Column("foo", $type), new Column("bar", $type), new Column("baz", $type));
        $table = new Table("foo", $columns);

        $table->addIndex(array("foo", "bar", "baz"), "Foo_Idx");

        $this->assertTrue($table->hasIndex('foo_idx'));
        $this->assertTrue($table->hasIndex('Foo_Idx'));
        $this->assertTrue($table->hasIndex('FOO_IDX'));
    }

    public function testAddIndexes()
    {
        $type = \Doctrine\DBAL\Types\Type::getType('integer');
        $columns = array(new Column("foo", $type));
        $indexes = array(
            new Index("the_primary", array("foo"), true, true),
            new Index("foo_idx", array("foo"), false, false),
        );
        $table = new Table("foo", $columns, $indexes, array());

        $this->assertTrue($table->hasIndex("the_primary"));
        $this->assertTrue($table->hasIndex("foo_idx"));
        $this->assertFalse($table->hasIndex("some_idx"));

        $this->assertType('Doctrine\DBAL\Schema\Index', $table->getPrimaryKey());
        $this->assertType('Doctrine\DBAL\Schema\Index', $table->getIndex('the_primary'));
        $this->assertType('Doctrine\DBAL\Schema\Index', $table->getIndex('foo_idx'));
    }

    public function testGetUnknownIndexThrowsException()
    {
        $this->setExpectedException("Doctrine\DBAL\Schema\SchemaException");

        $table = new Table("foo", array(), array(), array());
        $table->getIndex("unknownIndex");
    }

    public function testAddTwoPrimaryThrowsException()
    {
        $this->setExpectedException("Doctrine\DBAL\Schema\SchemaException");

        $type = \Doctrine\DBAL\Types\Type::getType('integer');
        $columns = array(new Column("foo", $type), new Column("bar", $type));
        $indexes = array(
            new Index("the_primary", array("foo"), true, true),
            new Index("other_primary", array("bar"), true, true),
        );
        $table = new Table("foo", $columns, $indexes, array());
    }

    public function testAddTwoIndexesWithSameNameThrowsException()
    {
        $this->setExpectedException("Doctrine\DBAL\Schema\SchemaException");

        $type = \Doctrine\DBAL\Types\Type::getType('integer');
        $columns = array(new Column("foo", $type), new Column("bar", $type));
        $indexes = array(
            new Index("an_idx", array("foo"), false, false),
            new Index("an_idx", array("bar"), false, false),
        );
        $table = new Table("foo", $columns, $indexes, array());
    }

    public function testIdGenerator()
    {
        $tableA = new Table("foo", array(), array(), array(), Table::ID_NONE);
        $this->assertFalse($tableA->isIdGeneratorIdentity());
        $this->assertFalse($tableA->isIdGeneratorSequence());;
        
        $tableB = new Table("foo", array(), array(), array(), Table::ID_IDENTITY);
        $this->assertTrue($tableB->isIdGeneratorIdentity());
        $this->assertFalse($tableB->isIdGeneratorSequence());;

        $tableC = new Table("foo", array(), array(), array(), Table::ID_SEQUENCE);
        $this->assertFalse($tableC->isIdGeneratorIdentity());
        $this->assertTrue($tableC->isIdGeneratorSequence());;
    }

    public function testConstraints()
    {
        $constraint = new ForeignKeyConstraint(array(), "foo", array());

        $tableA = new Table("foo", array(), array(), array($constraint));
        $constraints = $tableA->getForeignKeys();

        $this->assertEquals(1, count($constraints));
        $this->assertSame($constraint, array_shift($constraints));
    }

    public function testOptions()
    {
        $table = new Table("foo", array(), array(), array(), Table::ID_NONE, array("foo" => "bar"));

        $this->assertTrue($table->hasOption("foo"));
        $this->assertEquals("bar", $table->getOption("foo"));
    }

    public function testBuilderSetPrimaryKey()
    {
        $table = new Table("foo");

        $table->addColumn("bar", 'integer');
        $table->setPrimaryKey(array("bar"));

        $this->assertTrue($table->hasIndex("primary"));
        $this->assertType('Doctrine\DBAL\Schema\Index', $table->getPrimaryKey());
        $this->assertTrue($table->getIndex("primary")->isUnique());
        $this->assertTrue($table->getIndex("primary")->isPrimary());
    }

    public function testBuilderAddUniqueIndex()
    {
        $table = new Table("foo");

        $table->addColumn("bar", 'integer');
        $table->addUniqueIndex(array("bar"), "my_idx");

        $this->assertTrue($table->hasIndex("my_idx"));
        $this->assertTrue($table->getIndex("my_idx")->isUnique());
        $this->assertFalse($table->getIndex("my_idx")->isPrimary());
    }

    public function testBuilderAddIndex()
    {
        $table = new Table("foo");

        $table->addColumn("bar", 'integer');
        $table->addIndex(array("bar"), "my_idx");

        $this->assertTrue($table->hasIndex("my_idx"));
        $this->assertFalse($table->getIndex("my_idx")->isUnique());
        $this->assertFalse($table->getIndex("my_idx")->isPrimary());
    }

    public function testBuilderAddIndexWithInvalidNameThrowsException()
    {
        $this->setExpectedException("Doctrine\DBAL\Schema\SchemaException");

        $table = new Table("foo");
        $table->addColumn("bar",'integer');
        $table->addIndex(array("bar"), "invalid name %&/");
    }

    public function testBuilderAddIndexWithUnknownColumnThrowsException()
    {
        $this->setExpectedException("Doctrine\DBAL\Schema\SchemaException");

        $table = new Table("foo");
        $table->addIndex(array("bar"), "invalidName");
    }

    public function testBuilderOptions()
    {
        $table = new Table("foo");
        $table->addOption("foo", "bar");
        $this->assertTrue($table->hasOption("foo"));
        $this->assertEquals("bar", $table->getOption("foo"));
    }

    public function testIdGeneratorType()
    {
        $table = new Table("foo");
        
        $table->setIdGeneratorType(Table::ID_IDENTITY);
        $this->assertTrue($table->isIdGeneratorIdentity());

        $table->setIdGeneratorType(Table::ID_SEQUENCE);
        $this->assertTrue($table->isIdGeneratorSequence());
    }

    public function testAddForeignKeyConstraint_UnknownLocalColumn_ThrowsException()
    {
        $this->setExpectedException("Doctrine\DBAL\Schema\SchemaException");

        $table = new Table("foo");
        $table->addColumn("id", 'int');

        $foreignTable = new Table("bar");
        $foreignTable->addColumn("id", 'int');

        $table->addForeignKeyConstraint($foreignTable, array("foo"), array("id"));
    }

    public function testAddForeignKeyConstraint_UnknownForeignColumn_ThrowsException()
    {
        $this->setExpectedException("Doctrine\DBAL\Schema\SchemaException");

        $table = new Table("foo");
        $table->addColumn("id", 'integer');

        $foreignTable = new Table("bar");
        $foreignTable->addColumn("id", 'integer');

        $table->addForeignKeyConstraint($foreignTable, array("id"), array("foo"));
    }

    public function testAddForeignKeyConstraint()
    {
        $table = new Table("foo");
        $table->addColumn("id", 'integer');

        $foreignTable = new Table("bar");
        $foreignTable->addColumn("id", 'integer');

        $table->addForeignKeyConstraint($foreignTable, array("id"), array("id"), array("foo" => "bar"));

        $constraints = $table->getForeignKeys();
        $this->assertEquals(1, count($constraints));
        $this->assertType('Doctrine\DBAL\Schema\ForeignKeyConstraint', $constraints["foo_id_fk"]);

        $this->assertEquals("foo_id_fk", $constraints["foo_id_fk"]->getName());
        $this->assertTrue($constraints["foo_id_fk"]->hasOption("foo"));
        $this->assertEquals("bar", $constraints["foo_id_fk"]->getOption("foo"));
    }

    public function testAddIndexWithCaseSensitiveColumnProblem()
    {
        $table = new Table("foo");
        $table->addColumn("id", 'integer');

        $table->addIndex(array("ID"), "my_idx");

        $this->assertTrue($table->hasIndex('my_idx'));
        $this->assertEquals(array("ID"), $table->getIndex("my_idx")->getColumns());
    }

    public function testAddPrimaryKey_ColumnsAreExplicitlySetToNotNull()
    {
        $table = new Table("foo");
        $column = $table->addColumn("id", 'integer', array('notnull' => false));

        $this->assertFalse($column->getNotnull());

        $table->setPrimaryKey(array('id'));
        
        $this->assertTrue($column->getNotnull());
    }
}