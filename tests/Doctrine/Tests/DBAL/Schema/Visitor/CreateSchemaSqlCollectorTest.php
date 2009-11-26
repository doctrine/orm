<?php

namespace Doctrine\Tests\DBAL\Schema\Visitor;

require_once __DIR__ . '/../../../TestInit.php';

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

class CreateSchemaSqlCollectorTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateSchema()
    {
        $platformMock = $this->getMock(
            'Doctrine\DBAL\Platforms\MySqlPlatform',
            array('getCreateTableSql', 'getCreateSequenceSql', 'getCreateForeignKeySql')
        );
        $platformMock->expects($this->exactly(2))
                     ->method('getCreateTableSql')
                     ->will($this->returnValue(array("foo" => "bar")));
        $platformMock->expects($this->exactly(1))
                     ->method('getCreateSequenceSql')
                     ->will($this->returnValue(array("bar" => "baz")));
        $platformMock->expects($this->exactly(1))
                     ->method('getCreateForeignKeySql')
                     ->will($this->returnValue(array("baz" => "foo")));

        $schema = new Schema();
        $tableA = $schema->createTable("foo");
        $tableA->createColumn("id", 'integer');
        $tableA->createColumn("bar", 'string', array('length' => 255));
        $tableA->setPrimaryKey(array("id"));
        $tableA->setIdGeneratorType(Table::ID_SEQUENCE);

        $schema->createSequence("foo_seq");

        $tableB = $schema->createTable("bar");
        $tableB->createColumn("id", 'integer');
        $tableB->setPrimaryKey(array("id"));

        $tableA->addForeignKeyConstraint($tableB, array("bar"), array("id"));

        $sql = $schema->toSql($platformMock);

        $this->assertEquals(array("foo" => "bar", "bar" => "baz", "baz" => "foo"), $sql);
    }
}