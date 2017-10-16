<?php

namespace Doctrine\Performance\Mock;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\ORM\Performance\MockUnitOfWork;

/**
 * An entity manager mock that prevents lazy-loading of proxies
 */
class NonProxyLoadingEntityManager implements EntityManagerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $realEntityManager;

    public function __construct(EntityManagerInterface $realEntityManager)
    {
        $this->realEntityManager = $realEntityManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getProxyFactory()
    {
        $config = $this->realEntityManager->getConfiguration();

        return new ProxyFactory(
            $this,
            $config->getProxyDir(),
            $config->getProxyNamespace(),
            $config->getAutoGenerateProxyClasses()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadataFactory()
    {
        return $this->realEntityManager->getMetadataFactory();
    }

    /**
     * {@inheritDoc}
     */
    public function getClassMetadata($className)
    {
        return $this->realEntityManager->getClassMetadata($className);
    }

    /**
     * {@inheritDoc}
     */
    public function getUnitOfWork()
    {
        return new NonProxyLoadingUnitOfWork();
    }

    /**
     * {@inheritDoc}
     */
    public function getCache()
    {
        return $this->realEntityManager->getCache();
    }

    /**
     * {@inheritDoc}
     */
    public function getConnection()
    {
        return $this->realEntityManager->getConnection();
    }

    /**
     * {@inheritDoc}
     */
    public function getExpressionBuilder()
    {
        return $this->realEntityManager->getExpressionBuilder();
    }

    /**
     * {@inheritDoc}
     */
    public function beginTransaction()
    {
        $this->realEntityManager->beginTransaction();
    }

    /**
     * {@inheritDoc}
     */
    public function transactional($func)
    {
        return $this->realEntityManager->transactional($func);
    }

    /**
     * {@inheritDoc}
     */
    public function commit()
    {
        $this->realEntityManager->commit();
    }

    /**
     * {@inheritDoc}
     */
    public function rollback()
    {
        $this->realEntityManager->rollback();
    }

    /**
     * {@inheritDoc}
     */
    public function createQuery($dql = '')
    {
        return $this->realEntityManager->createQuery($dql);
    }

    /**
     * {@inheritDoc}
     */
    public function createNamedQuery($name)
    {
        return $this->realEntityManager->createNamedQuery($name);
    }

    /**
     * {@inheritDoc}
     */
    public function createNativeQuery($sql, ResultSetMapping $rsm)
    {
        return $this->realEntityManager->createNativeQuery($sql, $rsm);
    }

    /**
     * {@inheritDoc}
     */
    public function createNamedNativeQuery($name)
    {
        return $this->realEntityManager->createNamedNativeQuery($name);
    }

    /**
     * {@inheritDoc}
     */
    public function createQueryBuilder()
    {
        return $this->realEntityManager->createQueryBuilder();
    }

    /**
     * {@inheritDoc}
     */
    public function getReference($entityName, $id)
    {
        return $this->realEntityManager->getReference($entityName, $id);
    }

    /**
     * {@inheritDoc}
     */
    public function getPartialReference($entityName, $identifier)
    {
        return $this->realEntityManager->getPartialReference($entityName, $identifier);
    }

    /**
     * {@inheritDoc}
     */
    public function close()
    {
        $this->realEntityManager->close();
    }

    /**
     * {@inheritDoc}
     */
    public function copy($entity, $deep = false)
    {
        return $this->realEntityManager->copy($entity, $deep);
    }

    /**
     * {@inheritDoc}
     */
    public function lock($entity, $lockMode, $lockVersion = null)
    {
        $this->realEntityManager->lock($entity, $lockMode, $lockVersion);
    }

    /**
     * {@inheritDoc}
     */
    public function getEventManager()
    {
        return $this->realEntityManager->getEventManager();
    }

    /**
     * {@inheritDoc}
     */
    public function getConfiguration()
    {
        return $this->realEntityManager->getConfiguration();
    }

    /**
     * {@inheritDoc}
     */
    public function isOpen()
    {
        return $this->realEntityManager->isOpen();
    }

    /**
     * {@inheritDoc}
     */
    public function getHydrator($hydrationMode)
    {
        return $this->realEntityManager->getHydrator($hydrationMode);
    }

    /**
     * {@inheritDoc}
     */
    public function newHydrator($hydrationMode)
    {
        return $this->realEntityManager->newHydrator($hydrationMode);
    }

    /**
     * {@inheritDoc}
     */
    public function getFilters()
    {
        return $this->realEntityManager->getFilters();
    }

    /**
     * {@inheritDoc}
     */
    public function isFiltersStateClean()
    {
        return $this->realEntityManager->isFiltersStateClean();
    }

    /**
     * {@inheritDoc}
     */
    public function hasFilters()
    {
        return $this->realEntityManager->hasFilters();
    }

    /**
     * {@inheritDoc}
     */
    public function find($className, $id)
    {
        return $this->realEntityManager->find($className, $id);
    }

    /**
     * {@inheritDoc}
     */
    public function persist($object)
    {
        $this->realEntityManager->persist($object);
    }

    /**
     * {@inheritDoc}
     */
    public function remove($object)
    {
        $this->realEntityManager->remove($object);
    }

    /**
     * {@inheritDoc}
     */
    public function merge($object)
    {
        return $this->realEntityManager->merge($object);
    }

    /**
     * {@inheritDoc}
     */
    public function clear($objectName = null)
    {
        $this->realEntityManager->clear($objectName);
    }

    /**
     * {@inheritDoc}
     */
    public function detach($object)
    {
        $this->realEntityManager->detach($object);
    }

    /**
     * {@inheritDoc}
     */
    public function refresh($object)
    {
        $this->realEntityManager->refresh($object);
    }

    /**
     * {@inheritDoc}
     */
    public function flush()
    {
        $this->realEntityManager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getRepository($className)
    {
        return $this->realEntityManager->getRepository($className);
    }

    /**
     * {@inheritDoc}
     */
    public function initializeObject($obj)
    {
        $this->realEntityManager->initializeObject($obj);
    }

    /**
     * {@inheritDoc}
     */
    public function contains($object)
    {
        return $this->realEntityManager->contains($object);
    }
}