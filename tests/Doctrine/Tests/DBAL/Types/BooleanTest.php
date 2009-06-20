<?php

namespace Doctrine\Tests\DBAL\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\Tests\DBAL\Mocks;

require_once __DIR__ . '/../../TestInit.php';
 
class BooleanTest extends \Doctrine\Tests\DbalTestCase
{
    protected
        $_platform,
        $_type;

    protected function setUp()
    {
        $this->_platform = new \Doctrine\Tests\DBAL\Mocks\MockPlatform();
        $this->_type = Type::getType('boolean');
    }

    public function testBooleanConvertsToDatabaseValue()
    {
        $this->assertTrue(
            is_integer($this->_type->convertToDatabaseValue(1, $this->_platform))
        );
    }

    public function testBooleanConvertsToPHPValue()
    {
        $this->assertTrue(
            is_bool($this->_type->convertToPHPValue(0, $this->_platform))
        );
    }
}