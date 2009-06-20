<?php

namespace Doctrine\Tests\DBAL\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\Tests\DBAL\Mocks;

require_once __DIR__ . '/../../TestInit.php';
 
class ObjectTest extends \Doctrine\Tests\DbalTestCase
{
    protected
        $_platform,
        $_type;

    protected function setUp()
    {
        $this->_platform = new \Doctrine\Tests\DBAL\Mocks\MockPlatform();
        $this->_type = Type::getType('object');
    }

    public function testObjectConvertsToDatabaseValue()
    {
        $this->assertTrue(
            is_string($this->_type->convertToDatabaseValue(new \stdClass(), $this->_platform))
        );
    }

    public function testObjectConvertsToPHPValue()
    {
        $this->assertTrue(
            is_object($this->_type->convertToPHPValue(serialize(new \stdClass), $this->_platform))
        );
    }
}