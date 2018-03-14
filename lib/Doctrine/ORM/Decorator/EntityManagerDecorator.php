<?php

declare(strict_types=1);

namespace Doctrine\ORM\Decorator;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Cache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Proxy\Factory\ProxyFactory;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Utility\IdentifierFlattener;

/**
 * Base class for EntityManager decorators
 */
abstract class EntityManagerDecorator implements EntityManagerInterface
{
    /** @var EntityManagerInterface */
    protected $wrapped;

    public function __construct(EntityManagerInterface $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection() : Connection
    {
        return $this->wrapped->getConnection();
    }

    /**
     * {@inheritdoc}
     */
    public function getExpressionBuilder() : Expr
    {
        return $this->wrapped->getExpressionBuilder();
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierFlattener() : IdentifierFlattener
    {
        return $this->wrapped->getIdentifierFlattener();
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction() : void
    {
        $this->wrapped->beginTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function transactional(callable $func)
    {
        return $this->wrapped->transactional($func);
    }

    /**
     * {@inheritdoc}
     */
    public function commit() : void
    {
        $this->wrapped->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function rollback() : void
    {
        $this->wrapped->rollback();
    }

    /**
     * {@inheritdoc}
     */
    public function createQuery(string $dql = '') : Query
    {
        return $this->wrapped->createQuery($dql);
    }

    /**
     * {@inheritdoc}
     */
    public function createNativeQuery(string $sql, ResultSetMapping $rsm) : NativeQuery
    {
        return $this->wrapped->createNativeQuery($sql, $rsm);
    }

    /**
     * {@inheritdoc}
     */
    public function createQueryBuilder() : QueryBuilder
    {
        return $this->wrapped->createQueryBuilder();
    }

    /**
     * {@inheritdoc}
     */
    public function getReference(string $entityName, $id) : ?object
    {
        return $this->wrapped->getReference($entityName, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getPartialReference(string $entityName, $identifier) : ?object
    {
        return $this->wrapped->getPartialReference($entityName, $identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function close() : void
    {
        $this->wrapped->close();
    }

    /**
     * {@inheritdoc}
     */
    public function lock(object $entity, int $lockMode, $lockVersion = null) : void
    {
        $this->wrapped->lock($entity, $lockMode, $lockVersion);
    }

    /**
     * {@inheritdoc}
     */
    public function find($entityName, $id, $lockMode = null, $lockVersion = null) : ?object
    {
        return $this->wrapped->find($entityName, $id, $lockMode, $lockVersion);
    }

    /**
     * {@inheritdoc}
     */
    public function flush() : void
    {
        $this->wrapped->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getEventManager() : EventManager
    {
        return $this->wrapped->getEventManager();
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration() : Configuration
    {
        return $this->wrapped->getConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    public function isOpen() : bool
    {
        return $this->wrapped->isOpen();
    }

    /**
     * {@inheritdoc}
     */
    public function getUnitOfWork() : UnitOfWork
    {
        return $this->wrapped->getUnitOfWork();
    }

    /**
     * {@inheritdoc}
     */
    public function getHydrator($hydrationMode) : AbstractHydrator
    {
        return $this->wrapped->getHydrator($hydrationMode);
    }

    /**
     * {@inheritdoc}
     */
    public function newHydrator($hydrationMode) : AbstractHydrator
    {
        return $this->wrapped->newHydrator($hydrationMode);
    }

    /**
     * {@inheritdoc}
     */
    public function getProxyFactory() : ProxyFactory
    {
        return $this->wrapped->getProxyFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters() : FilterCollection
    {
        return $this->wrapped->getFilters();
    }

    /**
     * {@inheritdoc}
     */
    public function isFiltersStateClean() : bool
    {
        return $this->wrapped->isFiltersStateClean();
    }

    /**
     * {@inheritdoc}
     */
    public function hasFilters() : bool
    {
        return $this->wrapped->hasFilters();
    }

    /**
     * {@inheritdoc}
     */
    public function getCache() : ?Cache
    {
        return $this->wrapped->getCache();
    }

    /**
     * {@inheritdoc}
     */
    public function persist(object $object) : void
    {
        $this->wrapped->persist($object);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(object $object) : void
    {
        $this->wrapped->remove($object);
    }

    /**
     * {@inheritdoc}
     */
    public function merge(object $object) : object
    {
        return $this->wrapped->merge($object);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(?string $objectName = null) : void
    {
        $this->wrapped->clear($objectName);
    }

    /**
     * {@inheritdoc}
     */
    public function detach(object $object) : void
    {
        $this->wrapped->detach($object);
    }

    /**
     * {@inheritdoc}
     */
    public function refresh(object $object) : void
    {
        $this->wrapped->refresh($object);
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository(string $className) : EntityRepository
    {
        return $this->wrapped->getRepository($className);
    }

    /**
     * {@inheritdoc}
     */
    public function getClassMetadata(string $className) : ClassMetadata
    {
        return $this->wrapped->getClassMetadata($className);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataFactory() : ClassMetadataFactory
    {
        return $this->wrapped->getMetadataFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function initializeObject(object $obj) : void
    {
        $this->wrapped->initializeObject($obj);
    }

    /**
     * {@inheritdoc}
     */
    public function contains(object $object) : bool
    {
        return $this->wrapped->contains($object);
    }
}
