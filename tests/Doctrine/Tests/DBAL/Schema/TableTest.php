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

    public function testCreateColumn()
    {
        $type = Type::getType('integer');

        $table = new Table("foo");

        $this->assertFalse($table->hasColumn("bar"));
        $table->createColumn("bar", 'integer');
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
        $columns = array(new Column("foo", $type));
        $indexes = array(
            new Index("the_primary", array("foo"), true, true),
            new Index("other_primary", array("foo"), true, true),
        );
        $table = new Table("foo", $columns, $indexes, array());
    }

    public function testAddTwoIndexesWithSameNameThrowsException()
    {
        $this->setExpectedException("Doctrine\DBAL\Schema\SchemaException");

        $type = \Doctrine\DBAL\Types\Type::getType('integer');
        $columns = array(new Column("foo", $type));
        $indexes = array(
            new Index("an_idx", array("foo"), false, false),
            new Index("an_idx", array("foo"), false, false),
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
        $constraints = $tableA->getConstraints();

        $this->assertEquals(1, count($constraints));
        $this->assertSame($constraint, $constraints[0]);
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

        $table->createColumn("bar", 'integer');
        $table->setPrimaryKey(array("bar"));

        $this->assertTrue($table->hasIndex("primary"));
        $this->assertType('Doctrine\DBAL\Schema\Index', $table->getPrimaryKey());
        $this->assertTrue($table->getIndex("primary")->isUnique());
        $this->assertTrue($table->getIndex("primary")->isPrimary());
    }

    public function testBuilderAddUniqueIndex()
    {
        $table = new Table("foo");

        $table->createColumn("bar", 'integer');
        $table->addUniqueIndex(array("bar"), "my_idx");

        $this->assertTrue($table->hasIndex("my_idx"));
        $this->assertTrue($table->getIndex("my_idx")->isUnique());
        $this->assertFalse($table->getIndex("my_idx")->isPrimary());
    }

    public function testBuilderAddIndex()
    {
        $table = new Table("foo");

        $table->createColumn("bar", 'integer');
        $table->addIndex(array("bar"), "my_idx");

        $this->assertTrue($table->hasIndex("my_idx"));
        $this->assertFalse($table->getIndex("my_idx")->isUnique());
        $this->assertFalse($table->getIndex("my_idx")->isPrimary());
    }

    public function testBuilderAddIndexWithInvalidNameThrowsException()
    {
        $this->setExpectedException("Doctrine\DBAL\Schema\SchemaException");

        $table = new Table("foo");
        $table->createColumn("bar",'integer');
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
        $table->createColumn("id", 'int');

        $foreignTable = new Table("bar");
        $foreignTable->createColumn("id", 'int');

        $table->addForeignKeyConstraint($foreignTable, array("foo"), array("id"), array());
    }

    public function testAddForeignKeyConstraint_UnknownForeignColumn_ThrowsException()
    {
        $this->setExpectedException("Doctrine\DBAL\Schema\SchemaException");

        $table = new Table("foo");
        $table->createColumn("id", 'integer');

        $foreignTable = new Table("bar");
        $foreignTable->createColumn("id", 'integer');

        $table->addForeignKeyConstraint($foreignTable, array("id"), array("foo"), array());
    }

    public function testAddForeignKeyConstraint()
    {
        $table = new Table("foo");
        $table->createColumn("id", 'integer');

        $foreignTable = new Table("bar");
        $foreignTable->createColumn("id", 'integer');

        $table->addForeignKeyConstraint($foreignTable, array("id"), array("id"), "fkName", array("foo" => "bar"));

        $constraints = $table->getConstraints();
        $this->assertEquals(1, count($constraints));
        $this->assertType('Doctrine\DBAL\Schema\ForeignKeyConstraint', $constraints[0]);

        $this->assertEquals("fkName", $constraints[0]->getName());
        $this->assertTrue($constraints[0]->hasOption("foo"));
        $this->assertEquals("bar", $constraints[0]->getOption("foo"));
    }
    
}