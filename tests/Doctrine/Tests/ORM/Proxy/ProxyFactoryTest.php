<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Proxy;

use Doctrine\Common\EventManager;
use Doctrine\Common\Proxy\Proxy as CommonProxy;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\Persistence\Proxy;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Mocks\UnitOfWorkMock;
use Doctrine\Tests\Models\Company\CompanyEmployee;
use Doctrine\Tests\Models\Company\CompanyPerson;
use Doctrine\Tests\Models\ECommerce\ECommerceFeature;
use Doctrine\Tests\OrmTestCase;
use Doctrine\Tests\PHPUnitCompatibility\MockBuilderCompatibilityTools;
use ReflectionProperty;
use stdClass;

use function assert;
use function sys_get_temp_dir;

/**
 * Test the proxy generator. Its work is generating on-the-fly subclasses of a given model, which implement the Proxy pattern.
 */
class ProxyFactoryTest extends OrmTestCase
{
    use MockBuilderCompatibilityTools;

    /** @var UnitOfWorkMock */
    private $uowMock;

    /** @var EntityManagerMock */
    private $emMock;

    /** @var ProxyFactory */
    private $proxyFactory;

    protected function setUp(): void
    {
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('supportsIdentityColumns')
            ->willReturn(true);

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')
            ->willReturn($platform);
        $connection->method('getEventManager')
            ->willReturn(new EventManager());

        $this->emMock  = new EntityManagerMock($connection);
        $this->uowMock = new UnitOfWorkMock($this->emMock);
        $this->emMock->setUnitOfWork($this->uowMock);
        $this->proxyFactory = new ProxyFactory($this->emMock, sys_get_temp_dir(), 'Proxies', ProxyFactory::AUTOGENERATE_ALWAYS);
    }

    public function testReferenceProxyDelegatesLoadingToThePersister(): void
    {
        $identifier = ['id' => 42];
        $proxyClass = 'Proxies\__CG__\Doctrine\Tests\Models\ECommerce\ECommerceFeature';
        $persister  = $this->getMockBuilderWithOnlyMethods(BasicEntityPersister::class, ['load'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->uowMock->setEntityPersister(ECommerceFeature::class, $persister);

        $proxy = $this->proxyFactory->getProxy(ECommerceFeature::class, $identifier);

        $persister
            ->expects(self::atLeastOnce())
            ->method('load')
            ->with(self::equalTo($identifier), self::isInstanceOf($proxyClass))
            ->will(self::returnValue($proxy));

        $proxy->getDescription();
    }

    public function testSkipMappedSuperClassesOnGeneration(): void
    {
        $cm                     = new ClassMetadata(stdClass::class);
        $cm->isMappedSuperclass = true;

        self::assertSame(
            0,
            $this->proxyFactory->generateProxyClasses([$cm]),
            'No proxies generated.'
        );
    }

    /** @group 6625 */
    public function testSkipEmbeddableClassesOnGeneration(): void
    {
        $cm                  = new ClassMetadata(stdClass::class);
        $cm->isEmbeddedClass = true;

        self::assertSame(
            0,
            $this->proxyFactory->generateProxyClasses([$cm]),
            'No proxies generated.'
        );
    }

    /** @group DDC-1771 */
    public function testSkipAbstractClassesOnGeneration(): void
    {
        $cm = new ClassMetadata(AbstractClass::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        self::assertNotNull($cm->reflClass);

        $num = $this->proxyFactory->generateProxyClasses([$cm]);

        self::assertEquals(0, $num, 'No proxies generated.');
    }

    /** @group DDC-2432 */
    public function testFailedProxyLoadingDoesNotMarkTheProxyAsInitialized(): void
    {
        $persister = $this
            ->getMockBuilderWithOnlyMethods(BasicEntityPersister::class, ['load'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->uowMock->setEntityPersister(ECommerceFeature::class, $persister);

        $proxy = $this->proxyFactory->getProxy(ECommerceFeature::class, ['id' => 42]);
        assert($proxy instanceof Proxy);

        $persister
            ->expects(self::atLeastOnce())
            ->method('load')
            ->will(self::returnValue(null));

        try {
            $proxy->getDescription();
            self::fail('An exception was expected to be raised');
        } catch (EntityNotFoundException $exception) {
        }

        self::assertFalse($proxy->__isInitialized());
    }

    /** @group DDC-2432 */
    public function testFailedProxyCloningDoesNotMarkTheProxyAsInitialized(): void
    {
        $persister = $this
            ->getMockBuilderWithOnlyMethods(BasicEntityPersister::class, ['load'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->uowMock->setEntityPersister(ECommerceFeature::class, $persister);

        $proxy = $this->proxyFactory->getProxy(ECommerceFeature::class, ['id' => 42]);
        assert($proxy instanceof Proxy);

        $persister
            ->expects(self::atLeastOnce())
            ->method('load')
            ->will(self::returnValue(null));

        try {
            $cloned = clone $proxy;
            $cloned->__load();
            self::fail('An exception was expected to be raised');
        } catch (EntityNotFoundException $exception) {
        }

        self::assertFalse($proxy->__isInitialized());
    }

    public function testProxyClonesParentFields(): void
    {
        $companyEmployee = new CompanyEmployee();
        $companyEmployee->setSalary(1000); // A property on the CompanyEmployee
        $companyEmployee->setName('Bob'); // A property on the parent class, CompanyPerson

        // Set the id of the CompanyEmployee (which is in the parent CompanyPerson)
        $property = new ReflectionProperty(CompanyPerson::class, 'id');

        $property->setAccessible(true);
        $property->setValue($companyEmployee, 42);

        $classMetaData = $this->emMock->getClassMetadata(CompanyEmployee::class);

        $persister = $this
            ->getMockBuilderWithOnlyMethods(BasicEntityPersister::class, ['loadById', 'getClassMetadata'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->uowMock->setEntityPersister(CompanyEmployee::class, $persister);

        $proxy = $this->proxyFactory->getProxy(CompanyEmployee::class, ['id' => 42]);
        assert($proxy instanceof Proxy);

        $loadByIdMock = $persister
            ->expects(self::atLeastOnce())
            ->method('loadById');

        if ($proxy instanceof CommonProxy) {
            $loadByIdMock->willReturn($companyEmployee);

            $persister
                ->expects(self::atLeastOnce())
                ->method('getClassMetadata')
                ->willReturn($classMetaData);
        } else {
            $loadByIdMock->willReturnCallback(static function (array $id, CompanyEmployee $companyEmployee) {
                $companyEmployee->setSalary(1000); // A property on the CompanyEmployee
                $companyEmployee->setName('Bob'); // A property on the parent class, CompanyPerson

                return $companyEmployee;
            });
        }

        $cloned = clone $proxy;
        assert($cloned instanceof CompanyEmployee);

        self::assertSame(42, $cloned->getId(), 'Expected the Id to be cloned');
        self::assertSame(1000, $cloned->getSalary(), 'Expect properties on the CompanyEmployee class to be cloned');
        self::assertSame('Bob', $cloned->getName(), 'Expect properties on the CompanyPerson class to be cloned');
    }
}

abstract class AbstractClass
{
}
