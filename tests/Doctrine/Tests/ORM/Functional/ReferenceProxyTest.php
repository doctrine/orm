<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Persistence\Proxy;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Configuration\ProxyConfiguration;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\Tests\Models\Company\CompanyAuction;
use Doctrine\Tests\Models\ECommerce\ECommerceProduct;
use Doctrine\Tests\Models\ECommerce\ECommerceShipping;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Tests the generation of a proxy object for lazy loading.
 * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class ReferenceProxyTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('ecommerce');
        $this->useModelSet('company');

        parent::setUp();

        $proxyConfiguration = new ProxyConfiguration();

        $proxyConfiguration->setDirectory(__DIR__ . '/../../Proxies');
        $proxyConfiguration->setNamespace('Doctrine\Tests\Proxies');
        $proxyConfiguration->setAutoGenerate(AbstractProxyFactory::AUTOGENERATE_ALWAYS);

        $this->factory = new ProxyFactory($this->em, $proxyConfiguration);
    }

    public function createProduct()
    {
        $product = new ECommerceProduct();
        $product->setName('Doctrine Cookbook');
        $this->em->persist($product);

        $this->em->flush();
        $this->em->clear();

        return $product->getId();
    }

    public function createAuction()
    {
        $event = new CompanyAuction();
        $event->setData('Doctrine Cookbook');
        $this->em->persist($event);

        $this->em->flush();
        $this->em->clear();

        return $event->getId();
    }

    public function testLazyLoadsFieldValuesFromDatabase()
    {
        $id = $this->createProduct();

        $productProxy = $this->em->getReference(ECommerceProduct::class, ['id' => $id]);
        self::assertEquals('Doctrine Cookbook', $productProxy->getName());
    }

    /**
     * @group DDC-727
     */
    public function testAccessMetatadaForProxy()
    {
        $id = $this->createProduct();

        $entity = $this->em->getReference(ECommerceProduct::class , $id);
        $class = $this->em->getClassMetadata(get_class($entity));

        self::assertEquals(ECommerceProduct::class, $class->name);
    }

    /**
     * @group DDC-1033
     */
    public function testReferenceFind()
    {
        $id = $this->createProduct();

        $entity = $this->em->getReference(ECommerceProduct::class , $id);
        $entity2 = $this->em->find(ECommerceProduct::class , $id);

        self::assertSame($entity, $entity2);
        self::assertEquals('Doctrine Cookbook', $entity2->getName());
    }

    /**
     * @group DDC-1033
     */
    public function testCloneProxy()
    {
        $id = $this->createProduct();

        /* @var $entity ECommerceProduct */
        $entity = $this->em->getReference(ECommerceProduct::class , $id);

        /* @var $clone ECommerceProduct */
        $clone = clone $entity;

        self::assertEquals($id, $entity->getId());
        self::assertEquals('Doctrine Cookbook', $entity->getName());

        self::assertFalse($this->em->contains($clone), "Cloning a reference proxy should return an unmanaged/detached entity.");
        self::assertEquals($id, $clone->getId(), "Cloning a reference proxy should return same id.");
        self::assertEquals('Doctrine Cookbook', $clone->getName(), "Cloning a reference proxy should return same product name.");

        // domain logic, Product::__clone sets isCloned public property
        self::assertTrue($clone->isCloned);
        self::assertFalse($entity->isCloned);
    }

    /**
     * @group DDC-733
     */
    public function testInitializeProxy()
    {
        $id = $this->createProduct();

        /* @var $entity ECommerceProduct */
        $entity = $this->em->getReference(ECommerceProduct::class , $id);

        self::assertFalse($entity->__isInitialized__, "Pre-Condition: Object is unitialized proxy.");
        $this->em->getUnitOfWork()->initializeObject($entity);
        self::assertTrue($entity->__isInitialized__, "Should be initialized after called UnitOfWork::initializeObject()");
    }

    /**
     * @group DDC-1163
     */
    public function testInitializeChangeAndFlushProxy()
    {
        $id = $this->createProduct();

        /* @var $entity ECommerceProduct */
        $entity = $this->em->getReference(ECommerceProduct::class , $id);
        $entity->setName('Doctrine 2 Cookbook');

        $this->em->flush();
        $this->em->clear();

        $entity = $this->em->getReference(ECommerceProduct::class , $id);
        self::assertEquals('Doctrine 2 Cookbook', $entity->getName());
    }

    /**
     * @group DDC-1022
     */
    public function testWakeupCalledOnProxy()
    {
        $id = $this->createProduct();

        /* @var $entity ECommerceProduct */
        $entity = $this->em->getReference(ECommerceProduct::class , $id);

        self::assertFalse($entity->wakeUp);

        $entity->setName('Doctrine 2 Cookbook');

        self::assertTrue($entity->wakeUp, "Loading the proxy should call __wakeup().");
    }

    public function testDoNotInitializeProxyOnGettingTheIdentifier()
    {
        $id = $this->createProduct();

        /* @var $entity ECommerceProduct */
        $entity = $this->em->getReference(ECommerceProduct::class , $id);

        self::assertFalse($entity->__isInitialized__, "Pre-Condition: Object is unitialized proxy.");
        self::assertEquals($id, $entity->getId());
        self::assertFalse($entity->__isInitialized__, "Getting the identifier doesn't initialize the proxy.");
    }

    /**
     * @group DDC-1625
     */
    public function testDoNotInitializeProxyOnGettingTheIdentifier_DDC_1625()
    {
        $id = $this->createAuction();

        /* @var $entity CompanyAuction */
        $entity = $this->em->getReference(CompanyAuction::class , $id);

        self::assertFalse($entity->__isInitialized__, "Pre-Condition: Object is unitialized proxy.");
        self::assertEquals($id, $entity->getId());
        self::assertFalse($entity->__isInitialized__, "Getting the identifier doesn't initialize the proxy when extending.");
    }

    public function testDoNotInitializeProxyOnGettingTheIdentifierAndReturnTheRightType()
    {
        $product = new ECommerceProduct();
        $product->setName('Doctrine Cookbook');

        $shipping = new ECommerceShipping();
        $shipping->setDays(1);
        $product->setShipping($shipping);
        $this->em->persist($product);
        $this->em->flush();
        $this->em->clear();

        $id = $shipping->getId();

        $product = $this->em->getRepository(ECommerceProduct::class)->find($product->getId());

        $entity = $product->getShipping();
        self::assertFalse($entity->__isInitialized__, "Pre-Condition: Object is unitialized proxy.");
        self::assertEquals($id, $entity->getId());
        self::assertSame($id, $entity->getId(), "Check that the id's are the same value, and type.");
        self::assertFalse($entity->__isInitialized__, "Getting the identifier doesn't initialize the proxy.");
    }

    public function testInitializeProxyOnGettingSomethingOtherThanTheIdentifier()
    {
        $id = $this->createProduct();

        /* @var $entity ECommerceProduct */
        $entity = $this->em->getReference(ECommerceProduct::class , $id);

        self::assertFalse($entity->__isInitialized__, "Pre-Condition: Object is unitialized proxy.");
        self::assertEquals('Doctrine Cookbook', $entity->getName());
        self::assertTrue($entity->__isInitialized__, "Getting something other than the identifier initializes the proxy.");
    }

    /**
     * @group DDC-1604
     */
    public function testCommonPersistenceProxy()
    {
        $id = $this->createProduct();

        /* @var $entity ECommerceProduct */
        $entity = $this->em->getReference(ECommerceProduct::class , $id);
        $className = ClassUtils::getClass($entity);

        self::assertInstanceOf(Proxy::class, $entity);
        self::assertFalse($entity->__isInitialized());
        self::assertEquals(ECommerceProduct::class, $className);

        $restName = str_replace($this->em->getConfiguration()->getProxyNamespace(), "", get_class($entity));
        $restName = substr(get_class($entity), strlen($this->em->getConfiguration()->getProxyNamespace()) +1);
        $proxyFileName = $this->em->getConfiguration()->getProxyDir() . DIRECTORY_SEPARATOR . str_replace("\\", "", $restName) . ".php";
        self::assertTrue(file_exists($proxyFileName), "Proxy file name cannot be found generically.");

        $entity->__load();
        self::assertTrue($entity->__isInitialized());
    }
}
