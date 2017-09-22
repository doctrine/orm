<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Configuration\ProxyConfiguration;
use Doctrine\ORM\Proxy\Factory\DefaultProxyResolver;
use Doctrine\ORM\Proxy\Factory\ProxyFactory;
use Doctrine\ORM\Proxy\Factory\ProxyResolver;
use Doctrine\ORM\Proxy\Factory\StaticProxyFactory;
use Doctrine\Tests\Models\Company\CompanyAuction;
use Doctrine\Tests\Models\ECommerce\ECommerceProduct;
use Doctrine\Tests\Models\ECommerce\ECommerceShipping;
use Doctrine\Tests\OrmFunctionalTestCase;
use ProxyManager\Proxy\GhostObjectInterface;

/**
 * Tests the generation of a proxy object for lazy loading.
 *
 * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class ReferenceProxyTest extends OrmFunctionalTestCase
{
    /**
     * @var ProxyResolver
     */
    private $resolver;

    /**
     * @var ProxyFactory
     */
    private $factory;

    protected function setUp()
    {
        $this->useModelSet('ecommerce');
        $this->useModelSet('company');

        parent::setUp();

        $namespace = 'Doctrine\Tests\Proxies';
        $directory = __DIR__ . '/../../Proxies';

        $this->resolver = new DefaultProxyResolver($namespace, $directory);

        $proxyConfiguration = new ProxyConfiguration();

        $proxyConfiguration->setDirectory($directory);
        $proxyConfiguration->setNamespace($namespace);
        $proxyConfiguration->setAutoGenerate(ProxyFactory::AUTOGENERATE_ALWAYS);
        $proxyConfiguration->setResolver($this->resolver);

        $this->factory = new StaticProxyFactory($this->em, $proxyConfiguration);
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

        self::assertEquals(ECommerceProduct::class, $class->getClassName());
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
        self::assertTrue($this->em->contains($entity), "Real instance should be managed");
        self::assertEquals($id, $clone->getId(), "Cloning a reference proxy should return same id.");
        self::assertEquals('Doctrine Cookbook', $clone->getName(), "Cloning a reference proxy should return same product name.");
        self::assertEquals('Doctrine Cookbook', $entity->getName(), "Real instance should contain the real data too");

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

        /* @var $entity ECommerceProduct|GhostObjectInterface */
        $entity = $this->em->getReference(ECommerceProduct::class , $id);

        self::assertFalse($entity->isProxyInitialized(), "Pre-Condition: Object is unitialized proxy.");

        $this->em->getUnitOfWork()->initializeObject($entity);

        self::assertTrue($entity->isProxyInitialized(), "Should be initialized after called UnitOfWork::initializeObject()");
    }

    /**
     * @group DDC-1163
     */
    public function testInitializeChangeAndFlushProxy()
    {
        $id = $this->createProduct();

        /* @var $entity ECommerceProduct|GhostObjectInterface */
        $entity = $this->em->getReference(ECommerceProduct::class , $id);

        $entity->setName('Doctrine 2 Cookbook');

        $this->em->flush();
        $this->em->clear();

        /* @var $entity ECommerceProduct|GhostObjectInterface */
        $entity = $this->em->getReference(ECommerceProduct::class , $id);

        self::assertEquals('Doctrine 2 Cookbook', $entity->getName());
    }

    public function testDoNotInitializeProxyOnGettingTheIdentifier()
    {
        $id = $this->createProduct();

        /* @var $entity ECommerceProduct|GhostObjectInterface */
        $entity = $this->em->getReference(ECommerceProduct::class , $id);

        self::assertFalse($entity->isProxyInitialized(), "Pre-Condition: Object is unitialized proxy.");
        self::assertEquals($id, $entity->getId());
        self::assertFalse($entity->isProxyInitialized(), "Getting the identifier doesn't initialize the proxy.");
    }

    /**
     * @group DDC-1625
     */
    public function testDoNotInitializeProxyOnGettingTheIdentifier_DDC_1625()
    {
        $id = $this->createAuction();

        /* @var $entity CompanyAuction|GhostObjectInterface */
        $entity = $this->em->getReference(CompanyAuction::class , $id);

        self::assertFalse($entity->isProxyInitialized(), "Pre-Condition: Object is unitialized proxy.");
        self::assertEquals($id, $entity->getId());
        self::assertFalse($entity->isProxyInitialized(), "Getting the identifier doesn't initialize the proxy when extending.");
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

        /* @var $entity ECommerceProduct|GhostObjectInterface */
        $product = $this->em->getRepository(ECommerceProduct::class)->find($product->getId());

        $entity = $product->getShipping();

        self::assertFalse($entity->isProxyInitialized(), "Pre-Condition: Object is unitialized proxy.");
        self::assertEquals($id, $entity->getId());
        self::assertSame($id, $entity->getId(), "Check that the id's are the same value, and type.");
        self::assertFalse($entity->isProxyInitialized(), "Getting the identifier doesn't initialize the proxy.");
    }

    public function testInitializeProxyOnGettingSomethingOtherThanTheIdentifier()
    {
        $id = $this->createProduct();

        /* @var $entity ECommerceProduct|GhostObjectInterface */
        $entity = $this->em->getReference(ECommerceProduct::class , $id);

        self::assertFalse($entity->isProxyInitialized(), "Pre-Condition: Object is unitialized proxy.");
        self::assertEquals('Doctrine Cookbook', $entity->getName());
        self::assertTrue($entity->isProxyInitialized(), "Getting something other than the identifier initializes the proxy.");
    }

    /**
     * @group DDC-1604
     */
    public function testCommonPersistenceProxy()
    {
        $id = $this->createProduct();

        /* @var $entity ECommerceProduct|GhostObjectInterface */
        $entity = $this->em->getReference(ECommerceProduct::class , $id);

        $className = ClassUtils::getClass($entity);

        self::assertInstanceOf(GhostObjectInterface::class, $entity);
        self::assertFalse($entity->isProxyInitialized());
        self::assertEquals(ECommerceProduct::class, $className);

        $proxyFileName = $this->resolver->resolveProxyClassPath(ECommerceProduct::class );

        self::assertTrue(file_exists($proxyFileName), "Proxy file name cannot be found generically.");

        $entity->initializeProxy();

        self::assertTrue($entity->isProxyInitialized());
    }
}
