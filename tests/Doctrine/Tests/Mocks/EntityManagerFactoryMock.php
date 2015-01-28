<?php

namespace Doctrine\Tests\Mocks;

/**
 * Special EntityManagerFactory mock used for testing purposes.
 */
class EntityManagerFactoryMock extends \Doctrine\ORM\EntityManagerFactory
{
    /**
     * Mock factory method to create an EntityManager.
     *
     * {@inheritdoc}
     */
    public static function create($conn, \Doctrine\ORM\Configuration $config = null,
            \Doctrine\Common\EventManager $eventManager = null)
    {
        if (null === $config) {
            $config = new \Doctrine\ORM\Configuration();
            $config->setProxyDir(__DIR__ . '/../Proxies');
            $config->setProxyNamespace('Doctrine\Tests\Proxies');
            $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(array(), true));
        }
        if (null === $eventManager) {
            $eventManager = new \Doctrine\Common\EventManager();
        }

        return new EntityManagerMock($conn, $config, $eventManager);
    }
}
