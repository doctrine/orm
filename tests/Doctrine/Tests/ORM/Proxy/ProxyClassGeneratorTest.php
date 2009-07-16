<?php

namespace Doctrine\Tests\ORM\Proxy;

use Doctrine\ORM\Proxy\ProxyClassGenerator;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Models\ECommerce\ECommerceCart;
use Doctrine\Tests\Models\ECommerce\ECommerceCustomer;
use Doctrine\Tests\Models\ECommerce\ECommerceFeature;
use Doctrine\Tests\Models\ECommerce\ECommerceShipping;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Test the proxy generator. Its work is generating on-the-fly subclasses of a given model, which implement the Proxy pattern.
 * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 */
class ProxyClassGeneratorTest extends \Doctrine\Tests\OrmTestCase
{
    private $_connectionMock;
    private $_emMock;
    private $_generator;
    
    protected function setUp()
    {
        parent::setUp();
        // SUT
        $this->_connectionMock = new ConnectionMock(array(), new \Doctrine\Tests\Mocks\DriverMock());
        $this->_emMock = EntityManagerMock::create($this->_connectionMock);
        $this->_generator = new ProxyClassGenerator($this->_emMock, __DIR__ . '/generated');
    }
    
    protected function tearDown()
    {
        foreach (new \DirectoryIterator(__DIR__ . '/generated') as $file) {
            if (strstr($file->getFilename(), '.php')) {
                unlink($file->getPathname());
            }
        }
    }

    public function testCreatesASubclassOfTheOriginalOne()
    {
        $proxyClass = $this->_generator->generateReferenceProxyClass('Doctrine\Tests\Models\ECommerce\ECommerceFeature');
        $this->assertTrue(is_subclass_of($proxyClass, '\Doctrine\Tests\Models\ECommerce\ECommerceFeature'));
    }

    public function testCanGuessADefaultTempFolder()
    {
        $generator = new ProxyClassGenerator($this->_emMock);
        $proxyClass = $generator->generateReferenceProxyClass('Doctrine\Tests\Models\ECommerce\ECommerceShipping');
        $this->assertTrue(is_subclass_of($proxyClass, '\Doctrine\Tests\Models\ECommerce\ECommerceShipping'));
    }

    public function testAllowsClassesWithAConstructor()
    {
        $proxyClass = $this->_generator->generateReferenceProxyClass('Doctrine\Tests\Models\ECommerce\ECommerceCart');
        $this->assertTrue(is_subclass_of($proxyClass, '\Doctrine\Tests\Models\ECommerce\ECommerceCart'));
    }

    public function _testGenerateProxiesWhichForwardsToTheModelWithTheGivenIdentifier()
    {
        $feature = new ECommerceFeature;
        $feature->setDescription('An interesting feature');
        $this->_emMock->save($feature);
        $id = $feature->getId();
        $this->_emMock->clear();

        $proxy = $this->_generator->generateReferenceProxyClass('Doctrine\Tests\Models\ECommerce\ECommerceFeature', 1);
        $this->assertEquals('An interesting feature', $proxy->getDescription());
    }
}
