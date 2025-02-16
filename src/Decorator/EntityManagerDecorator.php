<?php

declare(strict_types=1);

namespace Doctrine\ORM\Decorator;

use DateTimeInterface;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\Cache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ObjectManagerDecorator;

/**
 * Base class for EntityManager decorators
 *
 * @extends ObjectManagerDecorator<EntityManagerInterface>
 */
abstract class EntityManagerDecorator extends ObjectManagerDecorator implements EntityManagerInterface
{
    public function __construct(EntityManagerInterface $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    public function getRepository(string $className): EntityRepository
    {
        return $this->wrapped->getRepository($className);
    }

    public function getMetadataFactory(): ClassMetadataFactory
    {
        return $this->wrapped->getMetadataFactory();
    }

    public function getClassMetadata(string $className): ClassMetadata
    {
        return $this->wrapped->getClassMetadata($className);
    }

    public function getConnection(): Connection
    {
        return $this->wrapped->getConnection();
    }

    public function getExpressionBuilder(): Expr
    {
        return $this->wrapped->getExpressionBuilder();
    }

    public function beginTransaction(): void
    {
        $this->wrapped->beginTransaction();
    }

    public function wrapInTransaction(callable $func): mixed
    {
        return $this->wrapped->wrapInTransaction($func);
    }

    public function commit(): void
    {
        $this->wrapped->commit();
    }

    public function rollback(): void
    {
        $this->wrapped->rollback();
    }

    public function createQuery(string $dql = ''): Query
    {
        return $this->wrapped->createQuery($dql);
    }

    public function createNativeQuery(string $sql, ResultSetMapping $rsm): NativeQuery
    {
        return $this->wrapped->createNativeQuery($sql, $rsm);
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return $this->wrapped->createQueryBuilder();
    }

    public function getReference(string $entityName, mixed $id): object|null
    {
        return $this->wrapped->getReference($entityName, $id);
    }

    public function close(): void
    {
        $this->wrapped->close();
    }

    public function lock(object $entity, LockMode|int $lockMode, DateTimeInterface|int|null $lockVersion = null): void
    {
        $this->wrapped->lock($entity, $lockMode, $lockVersion);
    }

    public function find(string $className, mixed $id, LockMode|int|null $lockMode = null, int|null $lockVersion = null): object|null
    {
        return $this->wrapped->find($className, $id, $lockMode, $lockVersion);
    }

    public function refresh(object $object, LockMode|int|null $lockMode = null): void
    {
        $this->wrapped->refresh($object, $lockMode);
    }

    public function getEventManager(): EventManager
    {
        return $this->wrapped->getEventManager();
    }

    public function getConfiguration(): Configuration
    {
        return $this->wrapped->getConfiguration();
    }

    public function isOpen(): bool
    {
        return $this->wrapped->isOpen();
    }

    public function getUnitOfWork(): UnitOfWork
    {
        return $this->wrapped->getUnitOfWork();
    }

    public function newHydrator(string|int $hydrationMode): AbstractHydrator
    {
        return $this->wrapped->newHydrator($hydrationMode);
    }

    public function getProxyFactory(): ProxyFactory
    {
        return $this->wrapped->getProxyFactory();
    }

    public function getFilters(): FilterCollection
    {
        return $this->wrapped->getFilters();
    }

    public function isFiltersStateClean(): bool
    {
        return $this->wrapped->isFiltersStateClean();
    }

    public function hasFilters(): bool
    {
        return $this->wrapped->hasFilters();
    }

    public function getCache(): Cache|null
    {
        return $this->wrapped->getCache();
    }
}
