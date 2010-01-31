<?php

namespace Doctrine\Tests\DBAL;

require_once __DIR__ . '/../TestInit.php';

use Doctrine\DBAL\Connection;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Events;
 
class ConnectionTest extends \Doctrine\Tests\DbalTestCase
{
    public function setUp()
    {
        $params = array(
            'driver' => 'pdo_mysql',
            'host' => 'localhost',
            'user' => 'root',
            'password' => 'password',
            'port' => '1234'
        );
        $this->_conn = \Doctrine\DBAL\DriverManager::getConnection($params);
    }

    public function testGetHost()
    {
        $this->assertEquals('localhost', $this->_conn->getHost());
    }

    public function testGetPort()
    {
        $this->assertEquals('1234', $this->_conn->getPort());
    }

    public function testGetUsername()
    {
        $this->assertEquals('root', $this->_conn->getUsername());
    }

    public function testGetPassword()
    {
        $this->assertEquals('password', $this->_conn->getPassword());
    }

    public function testGetDriver()
    {
        $this->assertType('Doctrine\DBAL\Driver\PDOMySql\Driver', $this->_conn->getDriver());
    }

    public function testGetEventManager()
    {
        $this->assertType('Doctrine\Common\EventManager', $this->_conn->getEventManager());
    }

    public function testConnectDispatchEvent()
    {
        $listenerMock = $this->getMock('ConnectDispatchEventListener', array('postConnect'));
        $listenerMock->expects($this->once())->method('postConnect');

        $eventManager = new EventManager();
        $eventManager->addEventListener(array(Events::postConnect), $listenerMock);

        $driverMock = $this->getMock('Doctrine\DBAL\Driver');
        $driverMock->expects(($this->at(0)))
                   ->method('connect');
        $platform = new Mocks\MockPlatform();

        $conn = new Connection(array('platform' => $platform), $driverMock, new Configuration(), $eventManager);
        $conn->connect();
    }
}