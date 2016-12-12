<?php

namespace Doctrine\Tests\Mocks;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;

/**
 * Special EntityManager mock used for testing purposes.
 */
class EntityManagerMock extends EntityManager
{
    /**
     * @var \Doctrine\ORM\UnitOfWork|null
     */
    private $_uowMock;

    /**
     * @var \Doctrine\ORM\Proxy\ProxyFactory|null
     */
    private $_proxyFactoryMock;

    /**
     * {@inheritdoc}
     */
    public function getUnitOfWork()
    {
        return isset($this->_uowMock) ? $this->_uowMock : parent::getUnitOfWork();
    }

    /* Mock API */

    /**
     * Sets a (mock) UnitOfWork that will be returned when getUnitOfWork() is called.
     *
     * @param \Doctrine\ORM\UnitOfWork $uow
     *
     * @return void
     */
    public function setUnitOfWork($uow)
    {
        $this->_uowMock = $uow;
    }

    /**
     * @param \Doctrine\ORM\Proxy\ProxyFactory $proxyFactory
     *
     * @return void
     */
    public function setProxyFactory($proxyFactory)
    {
        $this->_proxyFactoryMock = $proxyFactory;
    }

    /**
     * @return \Doctrine\ORM\Proxy\ProxyFactory
     */
    public function getProxyFactory()
    {
        return isset($this->_proxyFactoryMock) ? $this->_proxyFactoryMock : parent::getProxyFactory();
    }

    /**
     * Mock factory method to create an EntityManager.
     *
     * {@inheritdoc}
     */
    public static function create($conn, Configuration $config = null, EventManager $eventManager = null)
    {
        if (null === $config) {
            $config = new Configuration();
            $config->setProxyDir(__DIR__ . '/../Proxies');
            $config->setProxyNamespace('Doctrine\Tests\Proxies');
            $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver([], true));
        }
        if (null === $eventManager) {
            $eventManager = new EventManager();
        }

        return new EntityManagerMock($conn, $config, $eventManager);
    }
}
