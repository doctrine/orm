<?php

declare(strict_types=1);

namespace Doctrine\Performance\Mock;

use BadMethodCallException;
use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Cache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Proxy\Factory\StaticProxyFactory;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Utility\IdentifierFlattener;

/**
 * An entity manager mock that prevents lazy-loading of proxies
 */
class NonProxyLoadingEntityManager implements EntityManagerInterface
{
    /** @var EntityManagerInterface */
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
    public function getMetadataFactory() : ClassMetadataFactory
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
    public function getCache() : ?Cache
    {
        return $this->realEntityManager->getCache();
    }

    /**
     * {@inheritDoc}
     */
    public function getConnection() : Connection
    {
        return $this->realEntityManager->getConnection();
    }

    /**
     * {@inheritDoc}
     */
    public function getExpressionBuilder() : Expr
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
    public function createQuery($dql = '') : Query
    {
        return $this->realEntityManager->createQuery($dql);
    }

    /**
     * {@inheritDoc}
     */
    public function createNativeQuery($sql, ResultSetMapping $rsm) : NativeQuery
    {
        return $this->realEntityManager->createNativeQuery($sql, $rsm);
    }

    /**
     * {@inheritDoc}
     */
    public function createQueryBuilder() : QueryBuilder
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
    public function getEventManager() : EventManager
    {
        return $this->realEntityManager->getEventManager();
    }

    /**
     * {@inheritDoc}
     */
    public function getConfiguration() : Configuration
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
    public function getHydrator($hydrationMode) : AbstractHydrator
    {
        return $this->realEntityManager->getHydrator($hydrationMode);
    }

    /**
     * {@inheritDoc}
     */
    public function newHydrator($hydrationMode) : AbstractHydrator
    {
        return $this->realEntityManager->newHydrator($hydrationMode);
    }

    /**
     * {@inheritDoc}
     */
    public function getFilters() : FilterCollection
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
        throw new BadMethodCallException('@TODO method disabled - will be removed in 3.0 with a release of doctrine/common');
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated
     */
    public function detach($object) : void
    {
        throw new BadMethodCallException('@TODO method disabled - will be removed in 3.0 with a release of doctrine/common');
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
    public function getRepository($className) : ObjectRepository
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
    public function getIdentifierFlattener() : IdentifierFlattener
    {
        return $this->realEntityManager->getIdentifierFlattener();
    }
}
