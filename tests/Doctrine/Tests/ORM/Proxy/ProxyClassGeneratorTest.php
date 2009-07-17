<?php

namespace Doctrine\Tests\ORM\Proxy;

use Doctrine\ORM\Proxy\ProxyClassGenerator;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Models\ECommerce\ECommerceCart;
use Doctrine\Tests\Models\ECommerce\ECommerceCustomer;
use Doctrine\Tests\Models\ECommerce\ECommerceFeature;
use Doctrine\Tests\Models\ECommerce\ECommerceShipping;
use Doctrine\ORM\Persisters\StandardEntityPersister;

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

    public function testAllowsIdempotentCreationOfProxyClass()
    {
        $proxyClass = $this->_generator->generateReferenceProxyClass('Doctrine\Tests\Models\ECommerce\ECommerceFeature');
        $theSameProxyClass = $this->_generator->generateReferenceProxyClass('Doctrine\Tests\Models\ECommerce\ECommerceFeature');
        $this->assertEquals($proxyClass, $theSameProxyClass);
    }

    public function testCreatesClassesThatRequirePersisterInTheConstructor()
    {
        $proxyClass = $this->_generator->generateReferenceProxyClass('Doctrine\Tests\Models\ECommerce\ECommerceFeature');
        $proxy = new $proxyClass($this->_getMockPersister(), null);
    }

    public function testCreatesClassesThatDelegateLoadingToThePersister()
    {
        $identifier = array('id' => 42);
        $proxyClass = $this->_generator->generateReferenceProxyClass('Doctrine\Tests\Models\ECommerce\ECommerceFeature');
        $persister = $this->_getMockPersister();
        $proxy = new $proxyClass($persister, $identifier);
        $persister->expects($this->any())
                  ->method('load')
                  ->with($this->equalTo($identifier), $this->isInstanceOf($proxyClass));
        $proxy->getDescription();
    }

    public function testCreatesClassesThatExecutesLoadingOnlyOnce()
    {
        $identifier = array('id' => 42);
        $proxyClass = $this->_generator->generateReferenceProxyClass('Doctrine\Tests\Models\ECommerce\ECommerceFeature');
        $persister = $this->_getMockPersister();
        $proxy = new $proxyClass($persister, $identifier);
        $persister->expects($this->once())
                  ->method('load')
                  ->with($this->equalTo($identifier), $this->isInstanceOf($proxyClass));
        $proxy->getId();
        $proxy->getDescription();
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testRespectsMethodsParametersTypeHinting()
    {
        $proxyClass = $this->_generator->generateReferenceProxyClass('Doctrine\Tests\Models\ECommerce\ECommerceFeature');
        $proxy = new $proxyClass($this->_getMockPersister(), null);
        $proxy->setProduct(array('invalid parameter'));
    }

    protected function _getMockPersister()
    {
        $persister = $this->getMock('Doctrine\ORM\Persisters\StandardEntityPersister', array('load'), array(), '', false);
        return $persister;
    }
}
