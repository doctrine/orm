<?php

namespace Doctrine\Tests\DBAL;

require_once __DIR__ . '/../TestInit.php';
 
class DriverManagerTest extends \Doctrine\Tests\DbalTestCase
{
    /**
     * @expectedException \Doctrine\DBAL\DBALException
     */
    public function testInvalidPdoInstance()
    {
        $options = array(
            'pdo' => 'test'
        );
        $test = \Doctrine\DBAL\DriverManager::getConnection($options);
    }

    public function testValidPdoInstance()
    {
        $options = array(
            'pdo' => new \PDO('sqlite::memory:')
        );
        $conn = \Doctrine\DBAL\DriverManager::getConnection($options);
        $this->assertEquals('sqlite', $conn->getDatabasePlatform()->getName());
    }

    /**
     * @expectedException \Doctrine\DBAL\DBALException
     */
    public function testCheckParams()
    {
        $conn = \Doctrine\DBAL\DriverManager::getConnection(array());
    }

    /**
     * @expectedException \Doctrine\DBAL\DBALException
     */
    public function testInvalidDriver()
    {
        $conn = \Doctrine\DBAL\DriverManager::getConnection(array('driver' => 'invalid_driver'));
    }

    public function testCustomPlatform()
    {
        $mockPlatform = new \Doctrine\Tests\DBAL\Mocks\MockPlatform();
        $options = array(
            'pdo' => new \PDO('sqlite::memory:'),
            'platform' => $mockPlatform
        );

        $conn = \Doctrine\DBAL\DriverManager::getConnection($options);
        $this->assertSame($mockPlatform, $conn->getDatabasePlatform());
    }

    public function testCustomWrapper()
    {
        $wrapperMock = $this->getMock('\Doctrine\DBAL\Connection', array(), array(), '', false);
        $wrapperClass = get_class($wrapperMock);

        $options = array(
            'pdo' => new \PDO('sqlite::memory:'),
            'wrapperClass' => $wrapperClass
        );

        $conn = \Doctrine\DBAL\DriverManager::getConnection($options);
        $this->assertType($wrapperClass, $conn);
    }

    public function testInvalidWrapperClass()
    {
        $this->setExpectedException('\Doctrine\DBAL\DBALException');

        $options = array(
            'pdo' => new \PDO('sqlite::memory:'),
            'wrapperClass' => 'stdClass',
        );

        $conn = \Doctrine\DBAL\DriverManager::getConnection($options);
    }

    public function testInvalidDriverClass()
    {
        $this->setExpectedException('\Doctrine\DBAL\DBALException');

        $options = array(
            'driverClass' => 'stdClass'
        );

        $conn = \Doctrine\DBAL\DriverManager::getConnection($options);
    }

    public function testValidDriverClass()
    {
        $options = array(
            'driverClass' => 'Doctrine\DBAL\Driver\PDOMySql\Driver',
        );

        $conn = \Doctrine\DBAL\DriverManager::getConnection($options);
        $this->assertType('Doctrine\DBAL\Driver\PDOMySql\Driver', $conn->getDriver());
    }
}