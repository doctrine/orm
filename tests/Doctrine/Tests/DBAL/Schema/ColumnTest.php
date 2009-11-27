<?php

namespace Doctrine\Tests\DBAL\Schema;

require_once __DIR__ . '/../../TestInit.php';

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;

class ColumnTest extends \PHPUnit_Framework_TestCase
{
    public function testGet()
    {
        $options = array(
            'length' => 200,
            'precision' => 5,
            'scale' => 2,
            'unsigned' => true,
            'notnull' => false,
            'fixed' => true,
            'default' => 'baz',
            'platformOptions' => array('foo' => 'bar'),
        );

        $string = Type::getType('string');
        $column = new Column("foo", $string, $options);

        $this->assertEquals("foo", $column->getName());
        $this->assertSame($string, $column->getType());

        $this->assertEquals(200, $column->getLength());
        $this->assertEquals(5, $column->getPrecision());
        $this->assertEquals(2, $column->getScale());
        $this->assertTrue($column->getUnsigned());
        $this->assertFalse($column->getNotNull());
        $this->assertTrue($column->getFixed());
        $this->assertEquals("baz", $column->getDefault());

        $this->assertEquals(array('foo' => 'bar'), $column->getPlatformOptions());
        $this->assertTrue($column->hasPlatformOption('foo'));
        $this->assertEquals('bar', $column->getPlatformOption('foo'));
        $this->assertFalse($column->hasPlatformOption('bar'));
    }
}