<?php

namespace Doctrine\Tests\DBAL;

require_once __DIR__ . '/../TestInit.php';
 
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
        $this->assertEquals('Doctrine\DBAL\Driver\PDOMySql\Driver', get_class($this->_conn->getDriver()));
    }

    public function testGetEventManager()
    {
        $this->assertEquals('Doctrine\Common\EventManager', get_class($this->_conn->getEventManager()));
    }
}