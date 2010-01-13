<?php

namespace Doctrine\Tests\DBAL\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\Tests\DBAL\Mocks;

require_once __DIR__ . '/../../TestInit.php';
 
class DateTest extends \Doctrine\Tests\DbalTestCase
{
    protected
        $_platform,
        $_type;

    protected function setUp()
    {
        $this->_platform = new \Doctrine\Tests\DBAL\Mocks\MockPlatform();
        $this->_type = Type::getType('date');
    }

    public function testDateConvertsToDatabaseValue()
    {
        $this->assertTrue(
            is_string($this->_type->convertToDatabaseValue(new \DateTime(), $this->_platform))
        );
    }

    public function testDateConvertsToPHPValue()
    {
        // Birthday of jwage and also birthday of Doctrine. Send him a present ;)
        $this->assertTrue(
            $this->_type->convertToPHPValue('1985-09-01', $this->_platform)
            instanceof \DateTime
        );
    }

    public function testDateResetsNonDatePartsToZeroUnixTimeValues()
    {
        $date = $this->_type->convertToPHPValue('1985-09-01', $this->_platform);

        $this->assertEquals('00:00:00', $date->format('H:i:s'));
    }
}