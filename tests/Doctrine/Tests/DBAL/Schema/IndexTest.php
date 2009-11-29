<?php

namespace Doctrine\Tests\DBAL\Schema;

require_once __DIR__ . '/../../TestInit.php';

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;

class IndexTest extends \PHPUnit_Framework_TestCase
{
    public function createIndex($unique=false, $primary=false)
    {
        return new Index("foo", array("bar", "baz"), $unique, $primary);
    }

    public function testCreateIndex()
    {
        $idx = $this->createIndex();
        $this->assertEquals("foo", $idx->getName());
        $columns = $idx->getColumns();
        $this->assertEquals(2, count($columns));
        $this->assertEquals(array("bar", "baz"), $columns);
        $this->assertFalse($idx->isUnique());
        $this->assertFalse($idx->isPrimary());
    }

    public function testCreatePrimary()
    {
        $idx = $this->createIndex(false, true);
        $this->assertTrue($idx->isUnique());
        $this->assertTrue($idx->isPrimary());
    }

    public function testCreateUnique()
    {
        $idx = $this->createIndex(true, false);
        $this->assertTrue($idx->isUnique());
        $this->assertFalse($idx->isPrimary());
    }
}