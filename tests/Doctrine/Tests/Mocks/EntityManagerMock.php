<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\Factory\ProxyFactory;
use Doctrine\ORM\UnitOfWork;
use ReflectionClass;
use ReflectionException;

/**
 * Special EntityManager mock used for testing purposes.
 */
class EntityManagerMock extends EntityManagerDecorator
{
    /** @var UnitOfWork|null */
    private $uowMock;

    /** @var ProxyFactory|null */
    private $proxyFactoryMock;

    public function getWrappedEntityManager() : EntityManagerInterface
    {
        return $this->wrapped;
    }

    /**
     * {@inheritdoc}
     */
    public function getUnitOfWork()
    {
        return $this->uowMock ?? $this->wrapped->getUnitOfWork();
    }

    /**
     * Sets a (mock) UnitOfWork that will be returned when getUnitOfWork() is called.
     *
     * @param UnitOfWork $unitOfWork
     *
     * @return void
     */
    public function setUnitOfWork($unitOfWork)
    {
        $this->uowMock = $unitOfWork;

        $this->swapPropertyValue($this->wrapped, 'unitOfWork', $unitOfWork);
    }

    /**
     * @param ProxyFactory $proxyFactory
     *
     * @return void
     */
    public function setProxyFactory($proxyFactory)
    {
        $this->proxyFactoryMock = $proxyFactory;

        $this->swapPropertyValue($this->wrapped, 'proxyFactory', $proxyFactory);
    }

    /**
     * @return ProxyFactory
     */
    public function getProxyFactory()
    {
        return $this->proxyFactoryMock ?? $this->wrapped->getProxyFactory();
    }

    /**
     * Mock factory method to create an EntityManager.
     *
     * {@inheritdoc}
     */
    public static function create($conn, ?Configuration $config = null, ?EventManager $eventManager = null)
    {
        if ($config === null) {
            $config = new Configuration();

            $config->setProxyDir(__DIR__ . '/../Proxies');
            $config->setProxyNamespace('Doctrine\Tests\Proxies');
            $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver());
        }

        if ($eventManager === null) {
            $eventManager = $conn->getEventManager();
        }

        $em = EntityManager::create($conn, $config, $eventManager);

        return new EntityManagerMock($em);
    }

    /**
     * @param object $object
     * @param mixed  $newValue
     *
     * @throws ReflectionException
     */
    private function swapPropertyValue($object, string $propertyName, $newValue) : void
    {
        $reflectionClass    = new ReflectionClass($object);
        $reflectionProperty = $reflectionClass->getProperty($propertyName);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $newValue);
    }
}
