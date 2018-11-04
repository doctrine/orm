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
use Doctrine\Tests\Models\FriendObject\ComparableObject;
use Doctrine\Tests\Models\ProxySpecifics\FuncGetArgs;
use Doctrine\Tests\OrmTestCase;
use PHPUnit_Framework_MockObject_MockObject;
use ProxyManager\Proxy\GhostObjectInterface;
use stdClass;
use function json_encode;

/**
 * Test the proxy generator. Its work is generating on-the-fly subclasses of a given model, which implement the Proxy pattern.
 */
class ProxyFactoryTest extends OrmTestCase
{
    /** @var ConnectionMock */
    private $connectionMock;

    /** @var UnitOfWorkMock */
    private $uowMock;

    /** @var EntityManagerMock */
    private $emMock;

    /** @var StaticProxyFactory */
    private $proxyFactory;

    /** @var ClassMetadataBuildingContext */
    private $metadataBuildingContext;

    /**
     * {@inheritDoc}
     */
    protected function setUp() : void
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

    public function testReferenceProxyDelegatesLoadingToThePersister() : void
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
            ->will($this->returnValue(new stdClass()));

        $this->uowMock->setEntityPersister(ECommerceFeature::class, $persister);

        /** @var GhostObjectInterface|ECommerceFeature $proxy */
        $proxy = $this->proxyFactory->getProxy($classMetaData, $identifier);

        $proxy->getDescription();
    }

    public function testSkipMappedSuperClassesOnGeneration() : void
    {
        $cm                     = new ClassMetadata(stdClass::class, $this->metadataBuildingContext);
        $cm->isMappedSuperclass = true;

        self::assertSame(
            0,
            $this->proxyFactory->generateProxyClasses([$cm]),
            'No proxies generated.'
        );
    }

    /**
     * @group 6625
     * @group embedded
     */
    public function testSkipEmbeddableClassesOnGeneration() : void
    {
        $cm                  = new ClassMetadata(stdClass::class, $this->metadataBuildingContext);
        $cm->isEmbeddedClass = true;

        self::assertSame(
            0,
            $this->proxyFactory->generateProxyClasses([$cm]),
            'No proxies generated.'
        );
    }

    /**
     * @group DDC-1771
     */
    public function testSkipAbstractClassesOnGeneration() : void
    {
        $cm = new ClassMetadata(AbstractClass::class, $this->metadataBuildingContext);

        self::assertNotNull($cm->getReflectionClass());

        $num = $this->proxyFactory->generateProxyClasses([$cm]);

        self::assertEquals(0, $num, 'No proxies generated.');
    }

    /**
     * @group DDC-2432
     */
    public function testFailedProxyLoadingDoesNotMarkTheProxyAsInitialized() : void
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

        /** @var GhostObjectInterface|ECommerceFeature $proxy */
        $proxy = $this->proxyFactory->getProxy($classMetaData, ['id' => 42]);

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
    public function testFailedProxyCloningDoesNotMarkTheProxyAsInitialized() : void
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

        /** @var GhostObjectInterface|ECommerceFeature $proxy */
        $proxy = $this->proxyFactory->getProxy($classMetaData, ['id' => 42]);

        try {
            $cloned = clone $proxy;
            $this->fail('An exception was expected to be raised');
        } catch (EntityNotFoundException $exception) {
        }

        self::assertFalse($proxy->isProxyInitialized());
    }

    public function testProxyClonesParentFields() : void
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
            ->willReturnCallback(static function (array $id, CompanyEmployee $companyEmployee) {
                $companyEmployee->setSalary(1000); // A property on the CompanyEmployee
                $companyEmployee->setName('Bob'); // A property on the parent class, CompanyPerson

                return $companyEmployee;
            });

        $this->uowMock->setEntityPersister(CompanyEmployee::class, $persister);

        /** @var GhostObjectInterface|CompanyEmployee $proxy */
        $proxy = $this->proxyFactory->getProxy($classMetaData, $identifier);

        $cloned = clone $proxy;

        self::assertSame(42, $cloned->getId(), 'Expected the Id to be cloned');
        self::assertSame(1000, $cloned->getSalary(), 'Expect properties on the CompanyEmployee class to be cloned');
        self::assertSame('Bob', $cloned->getName(), 'Expect properties on the CompanyPerson class to be cloned');
    }

    public function testFriendObjectsDoNotLazyLoadIfNotAccessingLazyState() : void
    {
        /** @var BasicEntityPersister|PHPUnit_Framework_MockObject_MockObject $persister */
        $persister = $this->createMock(BasicEntityPersister::class);
        $persister->expects(self::never())->method('loadById');

        $this->uowMock->setEntityPersister(ComparableObject::class, $persister);

        /** @var ComparableObject|GhostObjectInterface $comparable */
        $comparable = $this->proxyFactory->getProxy(
            $this->emMock->getClassMetadata(ComparableObject::class),
            ['id' => 123]
        );

        self::assertInstanceOf(ComparableObject::class, $comparable);
        self::assertInstanceOf(GhostObjectInterface::class, $comparable);
        self::assertFalse($comparable->isProxyInitialized());

        // due to implementation details, identity check is not reading lazy state:
        self::assertTrue($comparable->equalTo($comparable));

        self::assertFalse($comparable->isProxyInitialized());
    }

    public function testFriendObjectsLazyLoadWhenAccessingLazyState() : void
    {
        /** @var BasicEntityPersister|PHPUnit_Framework_MockObject_MockObject $persister */
        $persister = $this
            ->getMockBuilder(BasicEntityPersister::class)
            ->setConstructorArgs([$this->emMock, $this->emMock->getClassMetadata(ComparableObject::class)])
            ->setMethods(['loadById'])
            ->getMock();

        $persister
            ->expects(self::exactly(2))
            ->method('loadById')
            ->with(
                self::logicalOr(['id' => 123], ['id' => 456]),
                self::logicalAnd(
                    self::isInstanceOf(GhostObjectInterface::class),
                    self::isInstanceOf(ComparableObject::class)
                )
            )
            ->willReturnCallback(static function (array $id, ComparableObject $comparableObject) {
                $comparableObject->setComparedFieldValue(json_encode($id));

                return $comparableObject;
            });

        $this->uowMock->setEntityPersister(ComparableObject::class, $persister);

        $metadata = $this->emMock->getClassMetadata(ComparableObject::class);

        /** @var ComparableObject|GhostObjectInterface $comparable1 */
        $comparable1 = $this->proxyFactory->getProxy($metadata, ['id' => 123]);
        /** @var ComparableObject|GhostObjectInterface $comparable2 */
        $comparable2 = $this->proxyFactory->getProxy($metadata, ['id' => 456]);

        self::assertInstanceOf(ComparableObject::class, $comparable1);
        self::assertInstanceOf(ComparableObject::class, $comparable2);
        self::assertInstanceOf(GhostObjectInterface::class, $comparable1);
        self::assertInstanceOf(GhostObjectInterface::class, $comparable2);
        self::assertNotSame($comparable1, $comparable2);
        self::assertFalse($comparable1->isProxyInitialized());
        self::assertFalse($comparable2->isProxyInitialized());

        self::assertFalse(
            $comparable1->equalTo($comparable2),
            'Due to implementation details, identity check is not reading lazy state'
        );

        self::assertTrue($comparable1->isProxyInitialized());
        self::assertTrue($comparable2->isProxyInitialized());
    }

    public function testProxyMethodsSupportFuncGetArgsLogic() : void
    {
        /** @var BasicEntityPersister|PHPUnit_Framework_MockObject_MockObject $persister */
        $persister = $this->createMock(BasicEntityPersister::class);
        $persister->expects(self::never())->method('loadById');

        $this->uowMock->setEntityPersister(FuncGetArgs::class, $persister);

        /** @var FuncGetArgs|GhostObjectInterface $funcGetArgs */
        $funcGetArgs = $this->proxyFactory->getProxy(
            $this->emMock->getClassMetadata(FuncGetArgs::class),
            ['id' => 123]
        );

        self::assertInstanceOf(GhostObjectInterface::class, $funcGetArgs);
        self::assertFalse($funcGetArgs->isProxyInitialized());

        self::assertSame(
            [1, 2, 3, 4],
            $funcGetArgs->funcGetArgsCallingMethod(1, 2, 3, 4),
            '`func_get_args()` calls are now supported in proxy implementations'
        );

        self::assertFalse($funcGetArgs->isProxyInitialized(), 'No state was accessed anyway');
    }
}

abstract class AbstractClass
{
}
