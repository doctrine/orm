<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Proxy\Factory\ProxyFactory;
use Doctrine\ORM\Proxy\Factory\StaticProxyFactory;
use Doctrine\ORM\Utility\StaticClassNameConverter;
use Doctrine\Tests\Models\Company\CompanyAuction;
use Doctrine\Tests\Models\ECommerce\ECommerceProduct;
use Doctrine\Tests\Models\ECommerce\ECommerceShipping;
use Doctrine\Tests\OrmFunctionalTestCase;
use ProxyManager\GeneratorStrategy\FileWriterGeneratorStrategy;
use ProxyManager\Proxy\GhostObjectInterface;
use ReflectionClass;
use function get_class;

/**
 * Tests the generation of a proxy object for lazy loading.
 */
class ReferenceProxyTest extends OrmFunctionalTestCase
{
    /** @var ProxyFactory */
    private $factory;

    protected function setUp() : void
    {
        $this->useModelSet('ecommerce');
        $this->useModelSet('company');

        parent::setUp();

        $configuration = $this->em->getConfiguration();

        $configuration->setProxyNamespace(__NAMESPACE__ . '\\ProxyTest');
        $configuration->setProxyDir(__DIR__ . '/../../Proxies');
        $configuration->setAutoGenerateProxyClasses(StaticProxyFactory::AUTOGENERATE_ALWAYS);

        $this->factory = new StaticProxyFactory($this->em, $configuration->buildGhostObjectFactory());
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

    public function testLazyLoadsFieldValuesFromDatabase() : void
    {
        $id = $this->createProduct();

        $productProxy = $this->em->getReference(ECommerceProduct::class, ['id' => $id]);

        self::assertEquals('Doctrine Cookbook', $productProxy->getName());
    }

    /**
     * @group DDC-727
     */
    public function testAccessMetatadaForProxy() : void
    {
        $id = $this->createProduct();

        $entity = $this->em->getReference(ECommerceProduct::class, $id);
        $class  = $this->em->getClassMetadata(get_class($entity));

        self::assertEquals(ECommerceProduct::class, $class->getClassName());
    }

    /**
     * @group DDC-1033
     */
    public function testReferenceFind() : void
    {
        $id = $this->createProduct();

        $entity  = $this->em->getReference(ECommerceProduct::class, $id);
        $entity2 = $this->em->find(ECommerceProduct::class, $id);

        self::assertSame($entity, $entity2);
        self::assertEquals('Doctrine Cookbook', $entity2->getName());
    }

    /**
     * @group DDC-1033
     */
    public function testCloneProxy() : void
    {
        $id = $this->createProduct();

        /** @var ECommerceProduct $entity */
        $entity = $this->em->getReference(ECommerceProduct::class, $id);

        /** @var ECommerceProduct $clone */
        $clone = clone $entity;

        self::assertEquals($id, $entity->getId());
        self::assertEquals('Doctrine Cookbook', $entity->getName());

        self::assertFalse($this->em->contains($clone), 'Cloning a reference proxy should return an unmanaged/detached entity.');
        self::assertTrue($this->em->contains($entity), 'Real instance should be managed');
        self::assertEquals($id, $clone->getId(), 'Cloning a reference proxy should return same id.');
        self::assertEquals('Doctrine Cookbook', $clone->getName(), 'Cloning a reference proxy should return same product name.');
        self::assertEquals('Doctrine Cookbook', $entity->getName(), 'Real instance should contain the real data too');

        // domain logic, Product::__clone sets isCloned public property
        self::assertTrue($clone->isCloned);
        self::assertFalse($entity->isCloned);
    }

    /**
     * @group DDC-733
     */
    public function testInitializeProxy() : void
    {
        $id = $this->createProduct();

        /** @var ECommerceProduct|GhostObjectInterface $entity */
        $entity = $this->em->getReference(ECommerceProduct::class, $id);

        self::assertFalse($entity->isProxyInitialized(), 'Pre-Condition: Object is unitialized proxy.');

        $this->em->getUnitOfWork()->initializeObject($entity);

        self::assertTrue($entity->isProxyInitialized(), 'Should be initialized after called UnitOfWork::initializeObject()');
    }

    /**
     * @group DDC-1163
     */
    public function testInitializeChangeAndFlushProxy() : void
    {
        $id = $this->createProduct();

        /** @var ECommerceProduct|GhostObjectInterface $entity */
        $entity = $this->em->getReference(ECommerceProduct::class, $id);

        $entity->setName('Doctrine 2 Cookbook');

        $this->em->flush();
        $this->em->clear();

        /** @var ECommerceProduct|GhostObjectInterface $entity */
        $entity = $this->em->getReference(ECommerceProduct::class, $id);

        self::assertEquals('Doctrine 2 Cookbook', $entity->getName());
    }

    public function testDoNotInitializeProxyOnGettingTheIdentifier() : void
    {
        $id = $this->createProduct();

        /** @var ECommerceProduct|GhostObjectInterface $entity */
        $entity = $this->em->getReference(ECommerceProduct::class, $id);

        self::assertFalse($entity->isProxyInitialized(), 'Pre-Condition: Object is unitialized proxy.');
        self::assertEquals($id, $entity->getId());
        self::assertFalse($entity->isProxyInitialized(), "Getting the identifier doesn't initialize the proxy.");
    }

    /**
     * @group DDC-1625
     */
    public function testDoNotInitializeProxyOnGettingTheIdentifierDDC1625() : void
    {
        $id = $this->createAuction();

        /** @var CompanyAuction|GhostObjectInterface $entity */
        $entity = $this->em->getReference(CompanyAuction::class, $id);

        self::assertFalse($entity->isProxyInitialized(), 'Pre-Condition: Object is unitialized proxy.');
        self::assertEquals($id, $entity->getId());
        self::assertFalse($entity->isProxyInitialized(), "Getting the identifier doesn't initialize the proxy when extending.");
    }

    public function testDoNotInitializeProxyOnGettingTheIdentifierAndReturnTheRightType() : void
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

        /** @var ECommerceProduct|GhostObjectInterface $entity */
        $product = $this->em->getRepository(ECommerceProduct::class)->find($product->getId());

        $entity = $product->getShipping();

        self::assertFalse($entity->isProxyInitialized(), 'Pre-Condition: Object is unitialized proxy.');
        self::assertEquals($id, $entity->getId());
        self::assertSame($id, $entity->getId(), "Check that the id's are the same value, and type.");
        self::assertFalse($entity->isProxyInitialized(), "Getting the identifier doesn't initialize the proxy.");
    }

    public function testInitializeProxyOnGettingSomethingOtherThanTheIdentifier() : void
    {
        $id = $this->createProduct();

        /** @var ECommerceProduct|GhostObjectInterface $entity */
        $entity = $this->em->getReference(ECommerceProduct::class, $id);

        self::assertFalse($entity->isProxyInitialized(), 'Pre-Condition: Object is unitialized proxy.');
        self::assertEquals('Doctrine Cookbook', $entity->getName());
        self::assertTrue($entity->isProxyInitialized(), 'Getting something other than the identifier initializes the proxy.');
    }

    /**
     * @group DDC-1604
     */
    public function testCommonPersistenceProxy() : void
    {
        $id = $this->createProduct();

        /** @var ECommerceProduct|GhostObjectInterface $entity */
        $entity = $this->em->getReference(ECommerceProduct::class, $id);

        $className = StaticClassNameConverter::getClass($entity);

        self::assertInstanceOf(GhostObjectInterface::class, $entity);
        self::assertFalse($entity->isProxyInitialized());
        self::assertEquals(ECommerceProduct::class, $className);

        $proxyManagerConfiguration = $this->em->getConfiguration()->getProxyManagerConfiguration();

        self::assertInstanceOf(
            FileWriterGeneratorStrategy::class,
            $proxyManagerConfiguration->getGeneratorStrategy(),
            'Proxies are being written to disk in this test'
        );

        $proxy = $this->factory->getProxy(
            $this->em->getClassMetadata(ECommerceProduct::class),
            ['id' => 123]
        );

        $proxyClass = new ReflectionClass($proxy);

        self::assertFileExists($proxyClass->getFileName());

        $entity->initializeProxy();

        self::assertTrue($entity->isProxyInitialized());
    }
}
