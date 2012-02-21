<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Proxy\ProxyClassGenerator;
use Doctrine\Tests\Models\ECommerce\ECommerceProduct;
use Doctrine\Tests\Models\ECommerce\ECommerceShipping;
use Doctrine\Tests\Models\Company\CompanyAuction;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Tests the generation of a proxy object for lazy loading.
 * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class ReferenceProxyTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('ecommerce');
        parent::setUp();
        $this->_factory = new ProxyFactory(
                $this->_em,
                __DIR__ . '/../../Proxies',
                'Doctrine\Tests\Proxies',
                true);
    }

    public function createProduct()
    {
        $product = new ECommerceProduct();
        $product->setName('Doctrine Cookbook');
        $this->_em->persist($product);

        $this->_em->flush();
        $this->_em->clear();

        return $product->getId();
    }

    public function createAuction()
    {
        $event = new CompanyAuction();
        $event->setData('Doctrine Cookbook');
        $this->_em->persist($event);

        $this->_em->flush();
        $this->_em->clear();

        return $event->getId();
    }

    public function testLazyLoadsFieldValuesFromDatabase()
    {
        $id = $this->createProduct();

        $productProxy = $this->_em->getReference('Doctrine\Tests\Models\ECommerce\ECommerceProduct', array('id' => $id));
        $this->assertEquals('Doctrine Cookbook', $productProxy->getName());
    }

    /**
     * @group DDC-727
     */
    public function testAccessMetatadaForProxy()
    {
        $id = $this->createProduct();

        $entity = $this->_em->getReference('Doctrine\Tests\Models\ECommerce\ECommerceProduct' , $id);
        $class = $this->_em->getClassMetadata(get_class($entity));

        $this->assertEquals('Doctrine\Tests\Models\ECommerce\ECommerceProduct', $class->name);
    }

    /**
     * @group DDC-1033
     */
    public function testReferenceFind()
    {
        $id = $this->createProduct();

        $entity = $this->_em->getReference('Doctrine\Tests\Models\ECommerce\ECommerceProduct' , $id);
        $entity2 = $this->_em->find('Doctrine\Tests\Models\ECommerce\ECommerceProduct' , $id);

        $this->assertSame($entity, $entity2);
        $this->assertEquals('Doctrine Cookbook', $entity2->getName());
    }

    /**
     * @group DDC-1033
     */
    public function testCloneProxy()
    {
        $id = $this->createProduct();

        /* @var $entity Doctrine\Tests\Models\ECommerce\ECommerceProduct */
        $entity = $this->_em->getReference('Doctrine\Tests\Models\ECommerce\ECommerceProduct' , $id);

        /* @var $clone Doctrine\Tests\Models\ECommerce\ECommerceProduct */
        $clone = clone $entity;

        $this->assertEquals($id, $entity->getId());
        $this->assertEquals('Doctrine Cookbook', $entity->getName());

        $this->assertFalse($this->_em->contains($clone), "Cloning a reference proxy should return an unmanaged/detached entity.");
        $this->assertEquals($id, $clone->getId(), "Cloning a reference proxy should return same id.");
        $this->assertEquals('Doctrine Cookbook', $clone->getName(), "Cloning a reference proxy should return same product name.");

        // domain logic, Product::__clone sets isCloned public property
        $this->assertTrue($clone->isCloned);
        $this->assertFalse($entity->isCloned);
    }

    /**
     * @group DDC-733
     */
    public function testInitializeProxy()
    {
        $id = $this->createProduct();

        /* @var $entity Doctrine\Tests\Models\ECommerce\ECommerceProduct */
        $entity = $this->_em->getReference('Doctrine\Tests\Models\ECommerce\ECommerceProduct' , $id);

        $this->assertFalse($entity->__isInitialized__, "Pre-Condition: Object is unitialized proxy.");
        $this->_em->getUnitOfWork()->initializeObject($entity);
        $this->assertTrue($entity->__isInitialized__, "Should be initialized after called UnitOfWork::initializeObject()");
    }

    /**
     * @group DDC-1163
     */
    public function testInitializeChangeAndFlushProxy()
    {
        $id = $this->createProduct();

        /* @var $entity Doctrine\Tests\Models\ECommerce\ECommerceProduct */
        $entity = $this->_em->getReference('Doctrine\Tests\Models\ECommerce\ECommerceProduct' , $id);
        $entity->setName('Doctrine 2 Cookbook');

        $this->_em->flush();
        $this->_em->clear();

        $entity = $this->_em->getReference('Doctrine\Tests\Models\ECommerce\ECommerceProduct' , $id);
        $this->assertEquals('Doctrine 2 Cookbook', $entity->getName());
    }

    /**
     * @group DDC-1022
     */
    public function testWakeupCalledOnProxy()
    {
        $id = $this->createProduct();

        /* @var $entity Doctrine\Tests\Models\ECommerce\ECommerceProduct */
        $entity = $this->_em->getReference('Doctrine\Tests\Models\ECommerce\ECommerceProduct' , $id);

        $this->assertFalse($entity->wakeUp);

        $entity->setName('Doctrine 2 Cookbook');

        $this->assertTrue($entity->wakeUp, "Loading the proxy should call __wakeup().");
    }

    public function testDoNotInitializeProxyOnGettingTheIdentifier()
    {
        $id = $this->createProduct();

        /* @var $entity Doctrine\Tests\Models\ECommerce\ECommerceProduct */
        $entity = $this->_em->getReference('Doctrine\Tests\Models\ECommerce\ECommerceProduct' , $id);

        $this->assertFalse($entity->__isInitialized__, "Pre-Condition: Object is unitialized proxy.");
        $this->assertEquals($id, $entity->getId());
        $this->assertFalse($entity->__isInitialized__, "Getting the identifier doesn't initialize the proxy.");
    }

    /**
     * @group DDC-1625
     */
    public function testDoNotInitializeProxyOnGettingTheIdentifier_DDC_1625()
    {
        $id = $this->createAuction();

        /* @var $entity Doctrine\Tests\Models\Company\CompanyAuction */
        $entity = $this->_em->getReference('Doctrine\Tests\Models\Company\CompanyAuction' , $id);

        $this->assertFalse($entity->__isInitialized__, "Pre-Condition: Object is unitialized proxy.");
        $this->assertEquals($id, $entity->getId());
        $this->assertFalse($entity->__isInitialized__, "Getting the identifier doesn't initialize the proxy when extending.");
    }
    
    public function testDoNotInitializeProxyOnGettingTheIdentifierAndReturnTheRightType()
    {
        $product = new ECommerceProduct();
        $product->setName('Doctrine Cookbook');

        $shipping = new ECommerceShipping();
        $shipping->setDays(1);
        $product->setShipping($shipping);
        $this->_em->persist($product);
        $this->_em->flush();
        $this->_em->clear();

        $id = $shipping->getId();

        $product = $this->_em->getRepository('Doctrine\Tests\Models\ECommerce\ECommerceProduct')->find($product->getId());

        $entity = $product->getShipping();
        $this->assertFalse($entity->__isInitialized__, "Pre-Condition: Object is unitialized proxy.");
        $this->assertEquals($id, $entity->getId());
        $this->assertSame($id, $entity->getId(), "Check that the id's are the same value, and type.");
        $this->assertFalse($entity->__isInitialized__, "Getting the identifier doesn't initialize the proxy.");
    }

    public function testInitializeProxyOnGettingSomethingOtherThanTheIdentifier()
    {
        $id = $this->createProduct();

        /* @var $entity Doctrine\Tests\Models\ECommerce\ECommerceProduct */
        $entity = $this->_em->getReference('Doctrine\Tests\Models\ECommerce\ECommerceProduct' , $id);

        $this->assertFalse($entity->__isInitialized__, "Pre-Condition: Object is unitialized proxy.");
        $this->assertEquals('Doctrine Cookbook', $entity->getName());
        $this->assertTrue($entity->__isInitialized__, "Getting something other than the identifier initializes the proxy.");
    }

    /**
     * @group DDC-1604
     */
    public function testCommonPersistenceProxy()
    {
        $id = $this->createProduct();

        /* @var $entity Doctrine\Tests\Models\ECommerce\ECommerceProduct */
        $entity = $this->_em->getReference('Doctrine\Tests\Models\ECommerce\ECommerceProduct' , $id);
        $className = \Doctrine\Common\Util\ClassUtils::getClass($entity);

        $this->assertInstanceOf('Doctrine\Common\Persistence\Proxy', $entity);
        $this->assertFalse($entity->__isInitialized());
        $this->assertEquals('Doctrine\Tests\Models\ECommerce\ECommerceProduct', $className);

        $restName = str_replace($this->_em->getConfiguration()->getProxyNamespace(), "", get_class($entity));
        $restName = substr(get_class($entity), strlen($this->_em->getConfiguration()->getProxyNamespace()) +1);
        $proxyFileName = $this->_em->getConfiguration()->getProxyDir() . DIRECTORY_SEPARATOR . str_replace("\\", "", $restName) . ".php";
        $this->assertTrue(file_exists($proxyFileName), "Proxy file name cannot be found generically.");

        $entity->__load();
        $this->assertTrue($entity->__isInitialized());
    }
}
