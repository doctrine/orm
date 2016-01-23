<?php

namespace Shitty\Tests\ORM\Proxy;

use Shitty\ORM\EntityNotFoundException;
use Shitty\ORM\Mapping\ClassMetadata;
use Shitty\ORM\Proxy\ProxyFactory;
use Shitty\Common\Proxy\ProxyGenerator;
use Shitty\Tests\Mocks\ConnectionMock;
use Shitty\Tests\Mocks\EntityManagerMock;
use Shitty\Tests\Mocks\UnitOfWorkMock;
use Shitty\Tests\Mocks\DriverMock;
use Shitty\Common\Proxy\AbstractProxyFactory;

/**
 * Test the proxy generator. Its work is generating on-the-fly subclasses of a given model, which implement the Proxy pattern.
 * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 */
class ProxyFactoryTest extends \Shitty\Tests\OrmTestCase
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
     * @var \Shitty\ORM\Proxy\ProxyFactory
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
        $cm->initializeReflection(new \Shitty\Common\Persistence\Mapping\RuntimeReflectionService);
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

        /* @var $proxy \Shitty\Common\Proxy\Proxy */
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

        /* @var $proxy \Shitty\Common\Proxy\Proxy */
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
}

abstract class AbstractClass
{

}
