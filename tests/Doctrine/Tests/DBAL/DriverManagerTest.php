<?php

namespace Doctrine\Tests\DBAL;

require_once __DIR__ . '/../TestInit.php';
 
class DriverManagerTest extends \Doctrine\Tests\DbalTestCase
{
    /**
     * @expectedException \Doctrine\Common\DoctrineException
     */
    public function testCantInstantiateDriverManager()
    {
        $test = new \Doctrine\DBAL\DriverManager();
    }

    /**
     * @expectedException \Doctrine\Common\DoctrineException
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
     * @expectedException \Doctrine\Common\DoctrineException
     */
    public function testCheckParams()
    {
        $conn = \Doctrine\DBAL\DriverManager::getConnection(array());
    }

    /**
     * @expectedException \Doctrine\Common\DoctrineException
     */
    public function testInvalidDriver()
    {
        $conn = \Doctrine\DBAL\DriverManager::getConnection(array('driver' => 'invalid_driver'));
    }
}