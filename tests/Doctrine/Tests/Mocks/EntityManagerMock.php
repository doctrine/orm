<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use BadMethodCallException;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Tests\TestUtil;

use function sprintf;

/**
 * Special EntityManager mock used for testing purposes.
 */
class EntityManagerMock extends EntityManager
{
    /** @var UnitOfWork|null */
    private $_uowMock;

    /** @var ProxyFactory|null */
    private $_proxyFactoryMock;

    public function __construct(Connection $conn, ?Configuration $config = null, ?EventManager $eventManager = null)
    {
        if ($config === null) {
            $config = new Configuration();
            TestUtil::configureProxies($config);
            $config->setMetadataDriverImpl(ORMSetup::createDefaultAnnotationDriver());
        }

        parent::__construct($conn, $config, $eventManager);
    }

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
     * {@inheritdoc}
     */
    public static function create($connection, Configuration $config, ?EventManager $eventManager = null): self
    {
        throw new BadMethodCallException(sprintf('Call to deprecated method %s().', __METHOD__));
    }
}
