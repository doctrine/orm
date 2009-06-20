<?php

namespace Doctrine\Tests\DBAL\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\Tests\DBAL\Mocks;

require_once __DIR__ . '/../../TestInit.php';
 
class DateTimeTest extends \Doctrine\Tests\DbalTestCase
{
    protected
        $_platform,
        $_type;

    protected function setUp()
    {
        $this->_platform = new \Doctrine\Tests\DBAL\Mocks\MockPlatform();
        $this->_type = Type::getType('datetime');
    }

    public function testDateTimeConvertsToDatabaseValue()
    {
        $this->assertTrue(
            is_string($this->_type->convertToDatabaseValue(new \DateTime(), $this->_platform))
        );
    }

    public function testDateTimeConvertsToPHPValue()
    {
        // Birthday of jwage and also birthday of Doctrine. Send him a present ;)
        $this->assertTrue(
            $this->_type->convertToPHPValue('1985-09-01 00:00:00', $this->_platform)
            instanceof \DateTime
        );
    }
}