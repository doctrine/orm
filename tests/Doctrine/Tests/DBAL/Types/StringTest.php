<?php

namespace Doctrine\Tests\DBAL\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\Tests\DBAL\Mocks;

require_once __DIR__ . '/../../TestInit.php';
 
class StringTest extends \Doctrine\Tests\DbalTestCase
{
    protected
        $_platform,
        $_type;

    protected function setUp()
    {
        $this->_platform = new \Doctrine\Tests\DBAL\Mocks\MockPlatform();
        $this->_type = Type::getType('string');
    }

    public function testReturnsSqlDeclarationFromPlatformVarchar()
    {
        $this->assertEquals("DUMMYVARCHAR()", $this->_type->getSqlDeclaration(array(), $this->_platform));
    }

    public function testReturnsDefaultLengthFromPlatformVarchar()
    {
        $this->assertEquals(255, $this->_type->getDefaultLength($this->_platform));
    }
}
