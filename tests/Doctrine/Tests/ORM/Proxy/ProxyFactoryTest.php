<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Proxy;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataBuildingContext;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\ORM\Proxy\Factory\StaticProxyFactory;
use Doctrine\ORM\Reflection\RuntimeReflectionService;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Mocks\DriverMock;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Mocks\UnitOfWorkMock;
use Doctrine\Tests\Models\Company\CompanyEmployee;
use Doctrine\Tests\Models\ECommerce\ECommerceFeature;
use Doctrine\Tests\OrmTestCase;
use ProxyManager\Proxy\GhostObjectInterface;

/**
 * Test the proxy generator. Its work is generating on-the-fly subclasses of a given model, which implement the Proxy pattern.
 * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 */
class ProxyFactoryTest extends OrmTestCase
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
     * @var StaticProxyFactory
     */
    private $proxyFactory;

    /**
     * @var ClassMetadataBuildingContext
     */
    private $metadataBuildingContext;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->metadataBuildingContext = new ClassMetadataBuildingContext(
            $this->createMock(ClassMetadataFactory::class),
            new RuntimeReflectionService()
        );
        $this->connectionMock          = new ConnectionMock([], new DriverMock());
        $this->emMock                  = EntityManagerMock::create($this->connectionMock);
        $this->uowMock                 = new UnitOfWorkMock($this->emMock);

        $this->emMock->setUnitOfWork($this->uowMock);

        $this->proxyFactory = new StaticProxyFactory(
            $this->emMock,
            $this->emMock->getConfiguration()->buildGhostObjectFactory()
        );
    }

    public function testReferenceProxyDelegatesLoadingToThePersister()
    {
        $identifier    = ['id' => 42];
        $classMetaData = $this->emMock->getClassMetadata(ECommerceFeature::class);

        $persister = $this
            ->getMockBuilder(BasicEntityPersister::class)
            ->setConstructorArgs([$this->emMock, $classMetaData])
            ->setMethods(['loadById'])
            ->getMock();

        $persister
            ->expects($this->atLeastOnce())
            ->method('loadById')
            ->with(
                $identifier,
                self::logicalAnd(
                    self::isInstanceOf(GhostObjectInterface::class),
                    self::isInstanceOf(ECommerceFeature::class)
                )
            )
            ->will($this->returnValue(new \stdClass()));

        $this->uowMock->setEntityPersister(ECommerceFeature::class, $persister);

        /* @var $proxy GhostObjectInterface|ECommerceFeature */
        $proxy = $this->proxyFactory->getProxy(ECommerceFeature::class, $identifier);

        $proxy->getDescription();
    }

    /**
     * @group DDC-1771
     */
    public function testSkipAbstractClassesOnGeneration()
    {
        $cm = new ClassMetadata(AbstractClass::class, $this->metadataBuildingContext);

        self::assertNotNull($cm->getReflectionClass());

        $num = $this->proxyFactory->generateProxyClasses([$cm]);

        self::assertEquals(0, $num, "No proxies generated.");
    }

    /**
     * @group DDC-2432
     */
    public function testFailedProxyLoadingDoesNotMarkTheProxyAsInitialized()
    {
        $classMetaData = $this->emMock->getClassMetadata(ECommerceFeature::class);

        $persister = $this
            ->getMockBuilder(BasicEntityPersister::class)
            ->setConstructorArgs([$this->emMock, $classMetaData])
            ->setMethods(['load'])
            ->getMock();

        $persister
            ->expects($this->atLeastOnce())
            ->method('load')
            ->will($this->returnValue(null));

        $this->uowMock->setEntityPersister(ECommerceFeature::class, $persister);

        /* @var $proxy GhostObjectInterface|ECommerceFeature */
        $proxy = $this->proxyFactory->getProxy(ECommerceFeature::class, ['id' => 42]);

        try {
            $proxy->getDescription();
            $this->fail('An exception was expected to be raised');
        } catch (EntityNotFoundException $exception) {
        }

        self::assertFalse($proxy->isProxyInitialized());
    }

    /**
     * @group DDC-2432
     */
    public function testFailedProxyCloningDoesNotMarkTheProxyAsInitialized()
    {
        $classMetaData = $this->emMock->getClassMetadata(ECommerceFeature::class);

        $persister = $this
            ->getMockBuilder(BasicEntityPersister::class)
            ->setConstructorArgs([$this->emMock, $classMetaData])
            ->setMethods(['load'])
            ->getMock();

        $persister
            ->expects($this->atLeastOnce())
            ->method('load')
            ->will($this->returnValue(null));

        $this->uowMock->setEntityPersister(ECommerceFeature::class, $persister);

        /* @var $proxy GhostObjectInterface|ECommerceFeature */
        $proxy = $this->proxyFactory->getProxy(ECommerceFeature::class, ['id' => 42]);

        try {
            $cloned = clone $proxy;
            $this->fail('An exception was expected to be raised');
        } catch (EntityNotFoundException $exception) {
        }

        self::assertFalse($proxy->isProxyInitialized());
    }

    public function testProxyClonesParentFields()
    {
        $identifier    = ['id' => 42];
        $classMetaData = $this->emMock->getClassMetadata(CompanyEmployee::class);

        $persister = $this
            ->getMockBuilder(BasicEntityPersister::class)
            ->setConstructorArgs([$this->emMock, $classMetaData])
            ->setMethods(['loadById'])
            ->getMock();

        $persister
            ->expects(self::atLeastOnce())
            ->method('loadById')
            ->with(
                $identifier,
                self::logicalAnd(
                    self::isInstanceOf(GhostObjectInterface::class),
                    self::isInstanceOf(CompanyEmployee::class)
                )
            )
            ->willReturnCallback(function (array $id, CompanyEmployee $companyEmployee) {
                $companyEmployee->setSalary(1000); // A property on the CompanyEmployee
                $companyEmployee->setName('Bob'); // A property on the parent class, CompanyPerson

                return $companyEmployee;
            });

        $this->uowMock->setEntityPersister(CompanyEmployee::class, $persister);

        /* @var $proxy GhostObjectInterface|CompanyEmployee */
        $proxy = $this->proxyFactory->getProxy(CompanyEmployee::class, $identifier);

        $cloned = clone $proxy;

        self::assertSame(42, $cloned->getId(), 'Expected the Id to be cloned');
        self::assertSame(1000, $cloned->getSalary(), 'Expect properties on the CompanyEmployee class to be cloned');
        self::assertSame('Bob', $cloned->getName(), 'Expect properties on the CompanyPerson class to be cloned');
    }
}

abstract class AbstractClass
{

}
