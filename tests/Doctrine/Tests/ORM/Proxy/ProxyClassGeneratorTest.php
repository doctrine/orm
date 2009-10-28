<?php

namespace Doctrine\Tests\ORM\Proxy;

use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Mocks\UnitOfWorkMock;
use Doctrine\Tests\Models\ECommerce\ECommerceCart;
use Doctrine\Tests\Models\ECommerce\ECommerceCustomer;
use Doctrine\Tests\Models\ECommerce\ECommerceFeature;
use Doctrine\Tests\Models\ECommerce\ECommerceProduct;
use Doctrine\Tests\Models\ECommerce\ECommerceShipping;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Test the proxy generator. Its work is generating on-the-fly subclasses of a given model, which implement the Proxy pattern.
 * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 */
class ProxyClassGeneratorTest extends \Doctrine\Tests\OrmTestCase
{
    private $_connectionMock;
    private $_uowMock;
    private $_emMock;
    private $_proxyFactory;
    
    protected function setUp()
    {
        parent::setUp();
        $this->_connectionMock = new ConnectionMock(array(), new \Doctrine\Tests\Mocks\DriverMock());
        $this->_emMock = EntityManagerMock::create($this->_connectionMock);
        $this->_uowMock = new UnitOfWorkMock($this->_emMock);
        $this->_emMock->setUnitOfWork($this->_uowMock);
        // SUT
        $this->_proxyFactory = new ProxyFactory($this->_emMock, __DIR__ . '/generated', 'Proxies', true);
    }
    
    protected function tearDown()
    {
        foreach (new \DirectoryIterator(__DIR__ . '/generated') as $file) {
            if (strstr($file->getFilename(), '.php')) {
                unlink($file->getPathname());
            }
        }
    }

    public function testReferenceProxyDelegatesLoadingToThePersister()
    {
        $identifier = array('id' => 42);
        $proxyClass = 'Proxies\DoctrineTestsModelsECommerceECommerceFeatureProxy';
        $persister = $this->_getMockPersister();
        $this->_uowMock->setEntityPersister('Doctrine\Tests\Models\ECommerce\ECommerceFeature', $persister);
        
        $proxy = $this->_proxyFactory->getProxy('Doctrine\Tests\Models\ECommerce\ECommerceFeature', $identifier);
        
        $persister->expects($this->atLeastOnce())
                  ->method('load')
                  ->with($this->equalTo($identifier), $this->isInstanceOf($proxyClass));
        
        $proxy->getDescription();
    }

    public function testReferenceProxyExecutesLoadingOnlyOnce()
    {
        $identifier = array('id' => 42);
        $proxyClass = 'Proxies\DoctrineTestsModelsECommerceECommerceFeatureProxy';
        $persister = $this->_getMockPersister();
        $this->_uowMock->setEntityPersister('Doctrine\Tests\Models\ECommerce\ECommerceFeature', $persister);
        $proxy = $this->_proxyFactory->getProxy('Doctrine\Tests\Models\ECommerce\ECommerceFeature', $identifier);
        $persister->expects($this->atLeastOnce())
                  ->method('load')
                  ->with($this->equalTo($identifier), $this->isInstanceOf($proxyClass));
        $proxy->getId();
        $proxy->getDescription();
    }

    public function testReferenceProxyRespectsMethodsParametersTypeHinting()
    {
        $proxyClass = 'Proxies\DoctrineTestsModelsECommerceECommerceFeatureProxy';
        $persister = $this->_getMockPersister();
        $this->_uowMock->setEntityPersister('Doctrine\Tests\Models\ECommerce\ECommerceFeature', $persister);
        $proxy = $this->_proxyFactory->getProxy('Doctrine\Tests\Models\ECommerce\ECommerceFeature', null);
        
        $method = new \ReflectionMethod(get_class($proxy), 'setProduct');
        $params = $method->getParameters();
        
        $this->assertEquals(1, count($params));
        $this->assertEquals('Doctrine\Tests\Models\ECommerce\ECommerceProduct', $params[0]->getClass()->getName());
    }

    public function testCreatesAssociationProxyAsSubclassOfTheOriginalOne()
    {
        $proxyClass = 'Proxies\DoctrineTestsModelsECommerceECommerceFeatureProxy';
        $this->assertTrue(is_subclass_of($proxyClass, 'Doctrine\Tests\Models\ECommerce\ECommerceFeature'));
    }


    public function testAllowsConcurrentCreationOfBothProxyTypes()
    {
        $referenceProxyClass = 'Proxies\DoctrineTestsModelsECommerceECommerceFeatureProxy';
        $associationProxyClass = 'Proxies\DoctrineTestsModelsECommerceECommerceFeatureAProxy';
        $this->assertNotEquals($referenceProxyClass, $associationProxyClass);
    }

    protected function _getAssociationMock()
    {
        $assoc = $this->getMock('Doctrine\ORM\Mapping\AssociationMapping', array('load'), array(), '', false, false, false);
        return $assoc;
    }
    
    protected function _getMockPersister()
    {
        $persister = $this->getMock('Doctrine\ORM\Persisters\StandardEntityPersister', array('load'), array(), '', false, false, false);
        return $persister;
    }
}
