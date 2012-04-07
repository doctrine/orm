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

    /**
     * @var \Doctrine\ORM\Proxy\ProxyFactory
     */
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
        $proxyClass = 'Proxies\__CG__\Doctrine\Tests\Models\ECommerce\ECommerceFeature';
        $persister = $this->_getMockPersister();
        $this->_uowMock->setEntityPersister('Doctrine\Tests\Models\ECommerce\ECommerceFeature', $persister);

        $proxy = $this->_proxyFactory->getProxy('Doctrine\Tests\Models\ECommerce\ECommerceFeature', $identifier);

        $persister->expects($this->atLeastOnce())
                  ->method('load')
                  ->with($this->equalTo($identifier), $this->isInstanceOf($proxyClass))
                  ->will($this->returnValue(new \stdClass())); // fake return of entity instance

        $proxy->getDescription();
    }

    public function testReferenceProxyExecutesLoadingOnlyOnce()
    {
        $identifier = array('id' => 42);
        $proxyClass = 'Proxies\__CG__\Doctrine\Tests\Models\ECommerce\ECommerceFeature';
        $persister = $this->_getMockPersister();
        $this->_uowMock->setEntityPersister('Doctrine\Tests\Models\ECommerce\ECommerceFeature', $persister);
        $proxy = $this->_proxyFactory->getProxy('Doctrine\Tests\Models\ECommerce\ECommerceFeature', $identifier);

        $persister->expects($this->atLeastOnce())
                  ->method('load')
                  ->with($this->equalTo($identifier), $this->isInstanceOf($proxyClass))
                  ->will($this->returnValue(new \stdClass())); // fake return of entity instance
        $proxy->getDescription();
        $proxy->getProduct();
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

    /**
     * Test that the proxy behaves in regard to methods like &foo() correctly
     */
    public function testProxyRespectsMethodsWhichReturnValuesByReference() {
        $proxy = $this->_proxyFactory->getProxy('Doctrine\Tests\Models\Forum\ForumEntry', null);
        $method = new \ReflectionMethod(get_class($proxy), 'getTopicByReference');

        $this->assertTrue($method->returnsReference());
    }

    public function testCreatesAssociationProxyAsSubclassOfTheOriginalOne()
    {
        $proxyClass = 'Proxies\__CG__\Doctrine\Tests\Models\ECommerce\ECommerceFeature';
        $this->assertTrue(is_subclass_of($proxyClass, 'Doctrine\Tests\Models\ECommerce\ECommerceFeature'));
    }


    public function testAllowsConcurrentCreationOfBothProxyTypes()
    {
        $referenceProxyClass = 'Proxies\DoctrineTestsModelsECommerceECommerceFeatureProxy';
        $associationProxyClass = 'Proxies\DoctrineTestsModelsECommerceECommerceFeatureAProxy';
        $this->assertNotEquals($referenceProxyClass, $associationProxyClass);
    }

    public function testNonNamespacedProxyGeneration()
    {
        require_once dirname(__FILE__)."/fixtures/NonNamespacedProxies.php";

        $className = "\DoctrineOrmTestEntity";
        $proxyName = "DoctrineOrmTestEntity";
        $classMetadata = new \Doctrine\ORM\Mapping\ClassMetadata($className);
        $classMetadata->initializeReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);
        $classMetadata->mapField(array('fieldName' => 'id', 'type' => 'integer'));
        $classMetadata->setIdentifier(array('id'));

        $this->_proxyFactory->generateProxyClasses(array($classMetadata));

        $classCode = file_get_contents(dirname(__FILE__)."/generated/__CG__".$proxyName.".php");

        $this->assertNotContains("class DoctrineOrmTestEntity extends \\\\DoctrineOrmTestEntity", $classCode);
        $this->assertContains("class DoctrineOrmTestEntity extends \\DoctrineOrmTestEntity", $classCode);
    }

    public function testClassWithSleepProxyGeneration()
    {
        $className = "\Doctrine\Tests\ORM\Proxy\SleepClass";
        $proxyName = "DoctrineTestsORMProxySleepClass";
        $classMetadata = new \Doctrine\ORM\Mapping\ClassMetadata($className);
        $classMetadata->initializeReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);
        $classMetadata->mapField(array('fieldName' => 'id', 'type' => 'integer'));
        $classMetadata->setIdentifier(array('id'));

        $this->_proxyFactory->generateProxyClasses(array($classMetadata));

        $classCode = file_get_contents(dirname(__FILE__)."/generated/__CG__".$proxyName.".php");

        $this->assertEquals(1, substr_count($classCode, 'function __sleep'));
    }

    /**
     * @group DDC-1771
     */
    public function testSkipAbstractClassesOnGeneration()
    {
        $cm = new \Doctrine\ORM\Mapping\ClassMetadata(__NAMESPACE__ . '\\AbstractClass');
        $cm->initializeReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);
        $this->assertNotNull($cm->reflClass);

        $num = $this->_proxyFactory->generateProxyClasses(array($cm));

        $this->assertEquals(0, $num, "No proxies generated.");
    }

    public function testNoConfigDir_ThrowsException()
    {
        $this->setExpectedException('Doctrine\ORM\Proxy\ProxyException');
        new ProxyFactory($this->_getTestEntityManager(), null, null);
    }

    public function testNoNamespace_ThrowsException()
    {
        $this->setExpectedException('Doctrine\ORM\Proxy\ProxyException');
        new ProxyFactory($this->_getTestEntityManager(), __DIR__ . '/generated', null);
    }

    protected function _getMockPersister()
    {
        $persister = $this->getMock('Doctrine\ORM\Persisters\BasicEntityPersister', array('load'), array(), '', false);
        return $persister;
    }
}

class SleepClass
{
    public $id;

    public function __sleep()
    {
        return array('id');
    }
}

abstract class AbstractClass
{

}
