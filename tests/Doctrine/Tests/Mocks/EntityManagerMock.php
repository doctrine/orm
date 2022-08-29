<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\UnitOfWork;

/**
 * Special EntityManager mock used for testing purposes.
 */
class EntityManagerMock extends EntityManager
{
    private UnitOfWork|null $_uowMock            = null;
    private ProxyFactory|null $_proxyFactoryMock = null;

    public function getUnitOfWork(): UnitOfWork
    {
        return $this->_uowMock ?? parent::getUnitOfWork();
    }

    /* Mock API */

    /**
     * Sets a (mock) UnitOfWork that will be returned when getUnitOfWork() is called.
     */
    public function setUnitOfWork(UnitOfWork $uow): void
    {
        $this->_uowMock = $uow;
    }

    public function setProxyFactory(ProxyFactory $proxyFactory): void
    {
        $this->_proxyFactoryMock = $proxyFactory;
    }

    public function getProxyFactory(): ProxyFactory
    {
        return $this->_proxyFactoryMock ?? parent::getProxyFactory();
    }

    /**
     * Mock factory method to create an EntityManager.
     *
     * {@inheritdoc}
     */
    public static function create($conn, Configuration|null $config = null, EventManager|null $eventManager = null): self
    {
        if ($config === null) {
            $config = new Configuration();
            $config->setProxyDir(__DIR__ . '/../Proxies');
            $config->setProxyNamespace('Doctrine\Tests\Proxies');
            $config->setMetadataDriverImpl(ORMSetup::createDefaultAnnotationDriver());
        }

        return new EntityManagerMock($conn, $config);
    }
}
