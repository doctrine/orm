<?php

namespace Doctrine\Tests\ORM\Proxy;

use Doctrine\ORM\Proxy\ProxyClassGenerator;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Mocks\EntityManagerMock;
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

    public function testCanGuessADefaultTempFolder()
    {
        $generator = new ProxyClassGenerator($this->_emMock);
        $proxyClass = $generator->generateReferenceProxyClass('Doctrine\Tests\Models\ECommerce\ECommerceShipping');
        $this->assertTrue(is_subclass_of($proxyClass, '\Doctrine\Tests\Models\ECommerce\ECommerceShipping'));
    }

    public function testCreatesReferenceProxyAsSubclassOfTheOriginalOne()
    {
        $proxyClass = $this->_generator->generateReferenceProxyClass('Doctrine\Tests\Models\ECommerce\ECommerceFeature');
        $this->assertTrue(is_subclass_of($proxyClass, '\Doctrine\Tests\Models\ECommerce\ECommerceFeature'));
    }

    public function testAllowsReferenceProxyForClassesWithAConstructor()
    {
        $proxyClass = $this->_generator->generateReferenceProxyClass('Doctrine\Tests\Models\ECommerce\ECommerceCart');
        $this->assertTrue(is_subclass_of($proxyClass, '\Doctrine\Tests\Models\ECommerce\ECommerceCart'));
    }

    public function testAllowsIdempotentCreationOfReferenceProxyClass()
    {
        $originalClassName = 'Doctrine\Tests\Models\ECommerce\ECommerceFeature';
        $proxyClass = $this->_generator->generateReferenceProxyClass($originalClassName);
        $theSameProxyClass = $this->_generator->generateReferenceProxyClass($originalClassName);
        $this->assertEquals($proxyClass, $theSameProxyClass);
    }

    public function testRegeneratesMetadataAfterIdempotentCreation()
    {
        $originalClassName = 'Doctrine\Tests\Models\ECommerce\ECommerceFeature';
        $metadataFactory = $this->_emMock->getMetadataFactory();
        $proxyClass = $this->_generator->generateReferenceProxyClass($originalClassName);
        $metadataFactory->setMetadataFor($proxyClass, null);
        $theSameProxyClass = $this->_generator->generateReferenceProxyClass($originalClassName);
        $this->assertNotNull($metadataFactory->getMetadataFor($theSameProxyClass));
    }

    public function testReferenceProxyRequiresPersisterInTheConstructor()
    {
        $proxyClass = $this->_generator->generateReferenceProxyClass('Doctrine\Tests\Models\ECommerce\ECommerceFeature');
        $proxy = new $proxyClass($this->_getMockPersister(), null);
    }

    public function testReferenceProxyDelegatesLoadingToThePersister()
    {
        $identifier = array('id' => 42);
        $proxyClass = $this->_generator->generateReferenceProxyClass('Doctrine\Tests\Models\ECommerce\ECommerceFeature');
        $persister = $this->_getMockPersister();
        $proxy = new $proxyClass($persister, $identifier);
        $persister->expects($this->atLeastOnce())
                  ->method('load')
                  ->with($this->equalTo($identifier), $this->isInstanceOf($proxyClass));
        $proxy->getDescription();
    }

    public function testReferenceProxyExecutesLoadingOnlyOnce()
    {
        $identifier = array('id' => 42);
        $proxyClass = $this->_generator->generateReferenceProxyClass('Doctrine\Tests\Models\ECommerce\ECommerceFeature');
        $persister = $this->_getMockPersister();
        $proxy = new $proxyClass($persister, $identifier);
        $persister->expects($this->atLeastOnce())
                  ->method('load')
                  ->with($this->equalTo($identifier), $this->isInstanceOf($proxyClass));
        $proxy->getId();
        $proxy->getDescription();
    }

    public function testReferenceProxyRespectsMethodsParametersTypeHinting()
    {
        $proxyClass = $this->_generator->generateReferenceProxyClass('Doctrine\Tests\Models\ECommerce\ECommerceFeature');
        $proxy = new $proxyClass($this->_getMockPersister(), null);
        
        $method = new \ReflectionMethod(get_class($proxy), 'setProduct');
        $params = $method->getParameters();
        
        $this->assertEquals(1, count($params));
        $this->assertEquals('Doctrine\Tests\Models\ECommerce\ECommerceProduct', $params[0]->getClass()->getName());
    }

    protected function _getMockPersister()
    {
        $persister = $this->getMock('Doctrine\ORM\Persisters\StandardEntityPersister', array('load'), array(), '', false, false, false);
        return $persister;
    }

    public function testCreatesAssociationProxyAsSubclassOfTheOriginalOne()
    {
        $proxyClass = $this->_generator->generateAssociationProxyClass('Doctrine\Tests\Models\ECommerce\ECommerceFeature');
        $this->assertTrue(is_subclass_of($proxyClass, '\Doctrine\Tests\Models\ECommerce\ECommerceFeature'));
    }

    public function testAllowsAssociationProxyOfClassesWithAConstructor()
    {
        $proxyClass = $this->_generator->generateAssociationProxyClass('Doctrine\Tests\Models\ECommerce\ECommerceCart');
        $this->assertTrue(is_subclass_of($proxyClass, '\Doctrine\Tests\Models\ECommerce\ECommerceCart'));
    }

    public function testAllowsIdempotentCreationOfAssociationProxyClass()
    {
        $proxyClass = $this->_generator->generateAssociationProxyClass('Doctrine\Tests\Models\ECommerce\ECommerceFeature');
        $theSameProxyClass = $this->_generator->generateAssociationProxyClass('Doctrine\Tests\Models\ECommerce\ECommerceFeature');
        $this->assertEquals($proxyClass, $theSameProxyClass);
    }

    public function testAllowsConcurrentCreationOfBothProxyTypes()
    {
        $referenceProxyClass = $this->_generator->generateReferenceProxyClass('Doctrine\Tests\Models\ECommerce\ECommerceFeature');
        $associationProxyClass = $this->_generator->generateAssociationProxyClass('Doctrine\Tests\Models\ECommerce\ECommerceFeature');
        $this->assertNotEquals($referenceProxyClass, $associationProxyClass);
    }

    public function testAssociationProxyRequiresEntityManagerAssociationOwnerAndForeignKeysInTheConstructor()
    {
        $proxyClass = $this->_generator->generateAssociationProxyClass('Doctrine\Tests\Models\ECommerce\ECommerceFeature');
        $product = new ECommerceProduct;
        $proxy = new $proxyClass($this->_emMock, $this->_getAssociationMock(), $product, array());
    }

    public function testAssociationProxyDelegatesLoadingToTheAssociation()
    {
        $proxyClass = $this->_generator->generateAssociationProxyClass('Doctrine\Tests\Models\ECommerce\ECommerceFeature');
        $product = new ECommerceProduct;
        $foreignKeys = array('customer_id' => 42);
        $assoc = $this->_getAssociationMock();
        $proxy = new $proxyClass($this->_emMock, $assoc, $product, $foreignKeys);
        $assoc->expects($this->atLeastOnce())
              ->method('load')
              ->with($product, $this->isInstanceOf($proxyClass), $this->isInstanceOf('Doctrine\Tests\Mocks\EntityManagerMock'), $foreignKeys);
        $proxy->getDescription();
    }

    public function testAssociationProxyExecutesLoadingOnlyOnce()
    {
        $proxyClass = $this->_generator->generateAssociationProxyClass('Doctrine\Tests\Models\ECommerce\ECommerceFeature');
        $assoc = $this->_getAssociationMock();
        $proxy = new $proxyClass($this->_emMock, $assoc, null, array());
        $assoc->expects($this->once())
              ->method('load');

        $proxy->getDescription();
        $proxy->getDescription();
    }

    protected function _getAssociationMock()
    {
        $assoc = $this->getMock('Doctrine\ORM\Mapping\AssociationMapping', array('load'), array(), '', false, false, false);
        return $assoc;
    }
}
