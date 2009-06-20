<?php

namespace Doctrine\Tests\DBAL\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\Tests\DBAL\Mocks;

require_once __DIR__ . '/../../TestInit.php';
 
class ArrayTest extends \Doctrine\Tests\DbalTestCase
{
    protected
        $_platform,
        $_type;

    protected function setUp()
    {
        $this->_platform = new \Doctrine\Tests\DBAL\Mocks\MockPlatform();
        $this->_type = Type::getType('array');
    }

    public function testArrayConvertsToDatabaseValue()
    {
        $this->assertTrue(
            is_string($this->_type->convertToDatabaseValue(array(), $this->_platform))
        );
    }

    public function testArrayConvertsToPHPValue()
    {
        $this->assertTrue(
            is_array($this->_type->convertToPHPValue(serialize(array()), $this->_platform))
        );
    }
}