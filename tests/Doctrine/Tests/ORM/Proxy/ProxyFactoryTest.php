<?php

namespace Doctrine\Tests\ORM\Proxy;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Mocks\DriverMock;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Mocks\UnitOfWorkMock;
use Doctrine\Tests\OrmTestCase;
use Doctrine\Tests\Models\Company\CompanyEmployee;

/**
 * Test the proxy generator. Its work is generating on-the-fly subclasses of a given model, which implement the Proxy pattern.
 * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 */
class ProxyFactoryTest extends \Doctrine\Tests\OrmTestCase
{
    /**
     * @var ConnectionMock
     */
    private $connectionMock;

    /**
     * @var UnitOfWorkMock
     */
    private $uowMock;

    /**
     * @var EntityManagerMock
     */
    private $emMock;

    /**
     * @var \Doctrine\ORM\Proxy\ProxyFactory
     */
    private $proxyFactory;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->connectionMock = new ConnectionMock(array(), new DriverMock());
        $this->emMock = EntityManagerMock::create($this->connectionMock);
        $this->uowMock = new UnitOfWorkMock($this->emMock);
        $this->emMock->setUnitOfWork($this->uowMock);
        $this->proxyFactory = new ProxyFactory($this->emMock, sys_get_temp_dir(), 'Proxies', AbstractProxyFactory::AUTOGENERATE_ALWAYS);
    }

    public function testReferenceProxyDelegatesLoadingToThePersister()
    {
        $identifier = array('id' => 42);
        $proxyClass = 'Proxies\__CG__\Doctrine\Tests\Models\ECommerce\ECommerceFeature';
        $persister = $this->getMock('Doctrine\ORM\Persisters\Entity\BasicEntityPersister', array('load'), array(), '', false);
        $this->uowMock->setEntityPersister('Doctrine\Tests\Models\ECommerce\ECommerceFeature', $persister);

        $proxy = $this->proxyFactory->getProxy('Doctrine\Tests\Models\ECommerce\ECommerceFeature', $identifier);

        $persister
            ->expects($this->atLeastOnce())
            ->method('load')
            ->with($this->equalTo($identifier), $this->isInstanceOf($proxyClass))
            ->will($this->returnValue(new \stdClass()));

        $proxy->getDescription();
    }

    /**
     * @group DDC-1771
     */
    public function testSkipAbstractClassesOnGeneration()
    {
        $cm = new ClassMetadata(__NAMESPACE__ . '\\AbstractClass');
        $cm->initializeReflection(new RuntimeReflectionService);
        $this->assertNotNull($cm->reflClass);

        $num = $this->proxyFactory->generateProxyClasses(array($cm));

        $this->assertEquals(0, $num, "No proxies generated.");
    }

    /**
     * @group DDC-2432
     */
    public function testFailedProxyLoadingDoesNotMarkTheProxyAsInitialized()
    {
        $persister = $this->getMock('Doctrine\ORM\Persisters\Entity\BasicEntityPersister', array('load'), array(), '', false);
        $this->uowMock->setEntityPersister('Doctrine\Tests\Models\ECommerce\ECommerceFeature', $persister);

        /* @var $proxy \Doctrine\Common\Proxy\Proxy */
        $proxy = $this->proxyFactory->getProxy('Doctrine\Tests\Models\ECommerce\ECommerceFeature', array('id' => 42));

        $persister
            ->expects($this->atLeastOnce())
            ->method('load')
            ->will($this->returnValue(null));

        try {
            $proxy->getDescription();
            $this->fail('An exception was expected to be raised');
        } catch (EntityNotFoundException $exception) {
        }

        $this->assertFalse($proxy->__isInitialized());
        $this->assertInstanceOf('Closure', $proxy->__getInitializer(), 'The initializer wasn\'t removed');
        $this->assertInstanceOf('Closure', $proxy->__getCloner(), 'The cloner wasn\'t removed');
    }

    /**
     * @group DDC-2432
     */
    public function testFailedProxyCloningDoesNotMarkTheProxyAsInitialized()
    {
        $persister = $this->getMock('Doctrine\ORM\Persisters\Entity\BasicEntityPersister', array('load'), array(), '', false);
        $this->uowMock->setEntityPersister('Doctrine\Tests\Models\ECommerce\ECommerceFeature', $persister);

        /* @var $proxy \Doctrine\Common\Proxy\Proxy */
        $proxy = $this->proxyFactory->getProxy('Doctrine\Tests\Models\ECommerce\ECommerceFeature', array('id' => 42));

        $persister
            ->expects($this->atLeastOnce())
            ->method('load')
            ->will($this->returnValue(null));

        try {
            $cloned = clone $proxy;
            $this->fail('An exception was expected to be raised');
        } catch (EntityNotFoundException $exception) {
        }

        $this->assertFalse($proxy->__isInitialized());
        $this->assertInstanceOf('Closure', $proxy->__getInitializer(), 'The initializer wasn\'t removed');
        $this->assertInstanceOf('Closure', $proxy->__getCloner(), 'The cloner wasn\'t removed');
    }

    public function testProxyClonesParentFields()
    {
        $companyEmployee = new CompanyEmployee();
        $companyEmployee->setSalary(1000); // A property on the CompanyEmployee
        $companyEmployee->setName("Bob"); // A property on the parent class, CompanyPerson

        // Set the id of the CompanyEmployee (which is in the parent CompanyPerson)
        $class = new \ReflectionClass('Doctrine\Tests\Models\Company\CompanyPerson');

        $property = $class->getProperty('id');
        $property->setAccessible(true);

        $property->setValue($companyEmployee, 42);

        $classMetaData = $this->emMock->getClassMetadata('Doctrine\Tests\Models\Company\CompanyEmployee');

        $persister = $this->getMock('Doctrine\ORM\Persisters\Entity\BasicEntityPersister', array('load', 'getClassMetadata'), array(), '', false);
        $this->uowMock->setEntityPersister('Doctrine\Tests\Models\Company\CompanyEmployee', $persister);

        /* @var $proxy \Doctrine\Common\Proxy\Proxy */
        $proxy = $this->proxyFactory->getProxy('Doctrine\Tests\Models\Company\CompanyEmployee', array('id' => 42));

        $persister
            ->expects($this->atLeastOnce())
            ->method('load')
            ->will($this->returnValue($companyEmployee));

        $persister
            ->expects($this->atLeastOnce())
            ->method('getClassMetadata')
            ->will($this->returnValue($classMetaData));

        $cloned = clone $proxy;

        $this->assertEquals(42, $cloned->getId(), "Expected the Id to be cloned");
        $this->assertEquals(1000, $cloned->getSalary(), "Expect properties on the CompanyEmployee class to be cloned");
        $this->assertEquals("Bob", $cloned->getName(), "Expect properties on the CompanyPerson class to be cloned");
    }
}

abstract class AbstractClass
{

}
