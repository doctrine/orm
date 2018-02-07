<?php

declare(strict_types=1);

namespace Doctrine\Performance\Mock;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Proxy\Factory\StaticProxyFactory;
use Doctrine\ORM\Query\ResultSetMapping;

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
        return new StaticProxyFactory($this, $this->realEntityManager->getConfiguration()->buildGhostObjectFactory());
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadataFactory() : \Doctrine\Common\Persistence\Mapping\ClassMetadataFactory
    {
        return $this->realEntityManager->getMetadataFactory();
    }

    /**
     * {@inheritDoc}
     */
    public function getClassMetadata($className) : ClassMetadata
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
    public function getCache() : ?\Doctrine\ORM\Cache
    {
        return $this->realEntityManager->getCache();
    }

    /**
     * {@inheritDoc}
     */
    public function getConnection() : \Doctrine\DBAL\Connection
    {
        return $this->realEntityManager->getConnection();
    }

    /**
     * {@inheritDoc}
     */
    public function getExpressionBuilder() : \Doctrine\ORM\Query\Expr
    {
        return $this->realEntityManager->getExpressionBuilder();
    }

    /**
     * {@inheritDoc}
     */
    public function beginTransaction() : void
    {
        $this->realEntityManager->beginTransaction();
    }

    /**
     * {@inheritDoc}
     */
    public function transactional(callable $func)
    {
        return $this->realEntityManager->transactional($func);
    }

    /**
     * {@inheritDoc}
     */
    public function commit() : void
    {
        $this->realEntityManager->commit();
    }

    /**
     * {@inheritDoc}
     */
    public function rollback() : void
    {
        $this->realEntityManager->rollback();
    }

    /**
     * {@inheritDoc}
     */
    public function createQuery($dql = '') : \Doctrine\ORM\Query
    {
        return $this->realEntityManager->createQuery($dql);
    }

    /**
     * {@inheritDoc}
     */
    public function createNativeQuery($sql, ResultSetMapping $rsm) : \Doctrine\ORM\NativeQuery
    {
        return $this->realEntityManager->createNativeQuery($sql, $rsm);
    }

    /**
     * {@inheritDoc}
     */
    public function createNamedNativeQuery($name) : \Doctrine\ORM\NativeQuery
    {
        return $this->realEntityManager->createNamedNativeQuery($name);
    }

    /**
     * {@inheritDoc}
     */
    public function createQueryBuilder() : \Doctrine\ORM\QueryBuilder
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
    public function close() : void
    {
        $this->realEntityManager->close();
    }

    /**
     * {@inheritDoc}
     */
    public function lock($entity, $lockMode, $lockVersion = null) : void
    {
        $this->realEntityManager->lock($entity, $lockMode, $lockVersion);
    }

    /**
     * {@inheritDoc}
     */
    public function getEventManager() : \Doctrine\Common\EventManager
    {
        return $this->realEntityManager->getEventManager();
    }

    /**
     * {@inheritDoc}
     */
    public function getConfiguration() : \Doctrine\ORM\Configuration
    {
        return $this->realEntityManager->getConfiguration();
    }

    /**
     * {@inheritDoc}
     */
    public function isOpen() : bool
    {
        return $this->realEntityManager->isOpen();
    }

    /**
     * {@inheritDoc}
     */
    public function getHydrator($hydrationMode) : \Doctrine\ORM\Internal\Hydration\AbstractHydrator
    {
        return $this->realEntityManager->getHydrator($hydrationMode);
    }

    /**
     * {@inheritDoc}
     */
    public function newHydrator($hydrationMode) : \Doctrine\ORM\Internal\Hydration\AbstractHydrator
    {
        return $this->realEntityManager->newHydrator($hydrationMode);
    }

    /**
     * {@inheritDoc}
     */
    public function getFilters() : \Doctrine\ORM\Query\FilterCollection
    {
        return $this->realEntityManager->getFilters();
    }

    /**
     * {@inheritDoc}
     */
    public function isFiltersStateClean() : bool
    {
        return $this->realEntityManager->isFiltersStateClean();
    }

    /**
     * {@inheritDoc}
     */
    public function hasFilters() : bool
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
    public function persist($object) : void
    {
        $this->realEntityManager->persist($object);
    }

    /**
     * {@inheritDoc}
     */
    public function remove($object) : void
    {
        $this->realEntityManager->remove($object);
    }

    /**
     * {@inheritDoc}
     */
    public function clear($objectName = null) : void
    {
        $this->realEntityManager->clear($objectName);
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated
     */
    public function merge($object)
    {
        throw new \BadMethodCallException('@TODO method disabled - will be removed in 3.0 with a release of doctrine/common');
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated
     */
    public function detach($object) : void
    {
        throw new \BadMethodCallException('@TODO method disabled - will be removed in 3.0 with a release of doctrine/common');
    }

    /**
     * {@inheritDoc}
     */
    public function refresh($object) : void
    {
        $this->realEntityManager->refresh($object);
    }

    /**
     * {@inheritDoc}
     */
    public function flush() : void
    {
        $this->realEntityManager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getRepository($className) : \Doctrine\Common\Persistence\ObjectRepository
    {
        return $this->realEntityManager->getRepository($className);
    }

    /**
     * {@inheritDoc}
     */
    public function initializeObject($obj) : void
    {
        $this->realEntityManager->initializeObject($obj);
    }

    /**
     * {@inheritDoc}
     */
    public function contains($object) : bool
    {
        return $this->realEntityManager->contains($object);
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifierFlattener() : \Doctrine\ORM\Utility\IdentifierFlattener
    {
        return $this->realEntityManager->getIdentifierFlattener();
    }
}
