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
    public function getUnitOfWork() : UnitOfWork
    {
        return $this->uowMock ?? $this->wrapped->getUnitOfWork();
    }

    /**
     * Sets a (mock) UnitOfWork that will be returned when getUnitOfWork() is called.
     */
    public function setUnitOfWork(UnitOfWork $uow) : void
    {
        $this->uowMock = $uow;
    }

    public function setProxyFactory(ProxyFactory $proxyFactory) : void
    {
        $this->proxyFactoryMock = $proxyFactory;
    }

    public function getProxyFactory() : ProxyFactory
    {
        return $this->proxyFactoryMock ?? $this->wrapped->getProxyFactory();
    }

    /**
     * Mock factory method to create an EntityManager.
     *
     * {@inheritdoc}
     */
    public static function create($conn, ?Configuration $config = null, ?EventManager $eventManager = null) : EntityManagerInterface
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
}
