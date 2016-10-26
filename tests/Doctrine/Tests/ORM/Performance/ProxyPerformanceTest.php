<?php

namespace Doctrine\Tests\ORM\Performance;

use Doctrine\Tests\OrmPerformanceTestCase;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;

/**
 * Performance test used to measure performance of proxy instantiation
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 * @group performance
 */
class ProxyPerformanceTest extends OrmPerformanceTestCase
{
    /**
     * @return array
     */
    public function entitiesProvider()
    {
        return array(
            array('Doctrine\Tests\Models\CMS\CmsEmployee'),
            array('Doctrine\Tests\Models\CMS\CmsUser'),
        );
    }

    /**
     * @dataProvider entitiesProvider
     */
    public function testProxyInstantiationPerformance($entityName)
    {
        $proxyFactory = $this->_getEntityManager()->getProxyFactory();
        $this->setMaxRunningTime(5);
        $start = microtime(true);

        for ($i = 0; $i < 100000; $i += 1) {
            $user = $proxyFactory->getProxy($entityName, array('id' => $i));
        }

        echo __FUNCTION__ . " - " . (microtime(true) - $start) . " seconds with " . $entityName . PHP_EOL;
    }

    /**
     * @dataProvider entitiesProvider
     */
    public function testProxyForcedInitializationPerformance($entityName)
    {
        $em              = new MockEntityManager($this->_getEntityManager());
        $proxyFactory    = $em->getProxyFactory();
        /* @var $user \Doctrine\Common\Proxy\Proxy */
        $user            = $proxyFactory->getProxy($entityName, array('id' => 1));
        $initializer     = $user->__getInitializer();

        $this->setMaxRunningTime(5);
        $start = microtime(true);

        for ($i = 0; $i < 100000;  $i += 1) {
            $user->__setInitialized(false);
            $user->__setInitializer($initializer);
            $user->__load();
            $user->__load();
        }

        echo __FUNCTION__ . " - " . (microtime(true) - $start) . " seconds with " . $entityName . PHP_EOL;
    }
}

/**
 * Mock entity manager to fake `getPersister()`
 */
class MockEntityManager extends EntityManager
{
    /** @var EntityManager */
    private $em;

    /** @param EntityManager $em */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /** {@inheritDoc} */
    public function getProxyFactory()
    {
        $config = $this->em->getConfiguration();

        return new ProxyFactory(
            $this,
            $config->getProxyDir(),
            $config->getProxyNamespace(),
            $config->getAutoGenerateProxyClasses()
        );
    }

    /** {@inheritDoc} */
    public function getMetadataFactory()
    {
        return $this->em->getMetadataFactory();
    }

    /** {@inheritDoc} */
    public function getClassMetadata($className)
    {
        return $this->em->getClassMetadata($className);
    }

    /** {@inheritDoc} */
    public function getUnitOfWork()
    {
        return new MockUnitOfWork();
    }
}

/**
 * Mock UnitOfWork manager to fake `getPersister()`
 */
class MockUnitOfWork extends UnitOfWork
{
    /** @var PersisterMock */
    private $entityPersister;

    /** */
    public function __construct()
    {
        $this->entityPersister = new PersisterMock();
    }

    /** {@inheritDoc} */
    public function getEntityPersister($entityName)
    {
        return $this->entityPersister;
    }
}

/**
 * Mock persister (we don't want PHPUnit comparator API to play a role in here)
 */
class PersisterMock extends BasicEntityPersister
{
    /** */
    public function __construct()
    {
    }

    /** {@inheritDoc} */
    public function load(array $criteria, $entity = null, $assoc = null, array $hints = array(), $lockMode = 0, $limit = null, array $orderBy = null)
    {
        return $entity;
    }
}
