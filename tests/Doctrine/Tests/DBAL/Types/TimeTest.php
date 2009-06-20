<?php

namespace Doctrine\Tests\DBAL\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\Tests\DBAL\Mocks;

require_once __DIR__ . '/../../TestInit.php';
 
class TimeTest extends \Doctrine\Tests\DbalTestCase
{
    protected
        $_platform,
        $_type;

    protected function setUp()
    {
        $this->_platform = new \Doctrine\Tests\DBAL\Mocks\MockPlatform();
        $this->_type = Type::getType('time');
    }

    public function testTimeConvertsToDatabaseValue()
    {
        $this->assertTrue(
            is_string($this->_type->convertToDatabaseValue(new \DateTime(), $this->_platform))
        );
    }

    public function testTimeConvertsToPHPValue()
    {
        $this->assertTrue(
            $this->_type->convertToPHPValue('5:30:55', $this->_platform)
            instanceof \DateTime
        );
    }
}