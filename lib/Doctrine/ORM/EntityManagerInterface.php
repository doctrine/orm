<?php

declare(strict_types=1);

namespace Doctrine\ORM;

use BadMethodCallException;
use DateTimeInterface;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ObjectManager;

/**
 * EntityManager interface
 *
 * @method Mapping\ClassMetadataFactory getMetadataFactory()
 * @method mixed wrapInTransaction(callable $func)
 */
interface EntityManagerInterface extends ObjectManager
{
    /**
     * {@inheritdoc}
     *
     * @psalm-param class-string<T> $className
     *
     * @psalm-return EntityRepository<T>
     *
     * @template T
     */
    public function getRepository($className);

    /**
     * Returns the cache API for managing the second level cache regions or NULL if the cache is not enabled.
     *
     * @return Cache|null
     */
    public function getCache();

    /**
     * Gets the database connection object used by the EntityManager.
     *
     * @return Connection
     */
    public function getConnection();

    /**
     * Gets an ExpressionBuilder used for object-oriented construction of query expressions.
     *
     * Example:
     *
     * <code>
     *     $qb = $em->createQueryBuilder();
     *     $expr = $em->getExpressionBuilder();
     *     $qb->select('u')->from('User', 'u')
     *         ->where($expr->orX($expr->eq('u.id', 1), $expr->eq('u.id', 2)));
     * </code>
     *
     * @return Expr
     */
    public function getExpressionBuilder();

    /**
     * Starts a transaction on the underlying database connection.
     *
     * @return void
     */
    public function beginTransaction();

    /**
     * Executes a function in a transaction.
     *
     * The function gets passed this EntityManager instance as an (optional) parameter.
     *
     * {@link flush} is invoked prior to transaction commit.
     *
     * If an exception occurs during execution of the function or flushing or transaction commit,
     * the transaction is rolled back, the EntityManager closed and the exception re-thrown.
     *
     * @deprecated 2.10 Use {@link wrapInTransaction} instead.
     *
     * @param callable $func The function to execute transactionally.
     *
     * @return mixed The non-empty value returned from the closure or true instead.
     */
    public function transactional($func);

    /**
     * Executes a function in a transaction.
     *
     * The function gets passed this EntityManager instance as an (optional) parameter.
     *
     * {@link flush} is invoked prior to transaction commit.
     *
     * If an exception occurs during execution of the function or flushing or transaction commit,
     * the transaction is rolled back, the EntityManager closed and the exception re-thrown.
     *
     * @param callable(self): T $func The function to execute transactionally.
     *
     * @return T The value returned from the closure.
     *
     * @template T
     */
    // public function wrapInTransaction(callable $func);

    /**
     * Commits a transaction on the underlying database connection.
     *
     * @return void
     */
    public function commit();

    /**
     * Performs a rollback on the underlying database connection.
     *
     * @return void
     */
    public function rollback();

    /**
     * Creates a new Query object.
     *
     * @param string $dql The DQL string.
     *
     * @return Query
     */
    public function createQuery($dql = '');

    /**
     * Creates a Query from a named query.
     *
     * @param string $name
     *
     * @return Query
     */
    public function createNamedQuery($name);

    /**
     * Creates a native SQL query.
     *
     * @param string           $sql
     * @param ResultSetMapping $rsm The ResultSetMapping to use.
     *
     * @return NativeQuery
     */
    public function createNativeQuery($sql, ResultSetMapping $rsm);

    /**
     * Creates a NativeQuery from a named native query.
     *
     * @param string $name
     *
     * @return NativeQuery
     */
    public function createNamedNativeQuery($name);

    /**
     * Create a QueryBuilder instance
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder();

    /**
     * Gets a reference to the entity identified by the given type and identifier
     * without actually loading it, if the entity is not yet loaded.
     *
     * @param string $entityName The name of the entity type.
     * @param mixed  $id         The entity identifier.
     * @psalm-param class-string<T> $entityName
     *
     * @return object|null The entity reference.
     * @psalm-return T|null
     *
     * @throws ORMException
     *
     * @template T
     */
    public function getReference($entityName, $id);

    /**
     * Gets a partial reference to the entity identified by the given type and identifier
     * without actually loading it, if the entity is not yet loaded.
     *
     * The returned reference may be a partial object if the entity is not yet loaded/managed.
     * If it is a partial object it will not initialize the rest of the entity state on access.
     * Thus you can only ever safely access the identifier of an entity obtained through
     * this method.
     *
     * The use-cases for partial references involve maintaining bidirectional associations
     * without loading one side of the association or to update an entity without loading it.
     * Note, however, that in the latter case the original (persistent) entity data will
     * never be visible to the application (especially not event listeners) as it will
     * never be loaded in the first place.
     *
     * @param string $entityName The name of the entity type.
     * @param mixed  $identifier The entity identifier.
     * @psalm-param class-string<T> $entityName
     *
     * @return object|null The (partial) entity reference
     * @psalm-return T|null
     *
     * @template T
     */
    public function getPartialReference($entityName, $identifier);

    /**
     * Closes the EntityManager. All entities that are currently managed
     * by this EntityManager become detached. The EntityManager may no longer
     * be used after it is closed.
     *
     * @return void
     */
    public function close();

    /**
     * Creates a copy of the given entity. Can create a shallow or a deep copy.
     *
     * @deprecated 2.7 This method is being removed from the ORM and won't have any replacement
     *
     * @param object $entity The entity to copy.
     * @param bool   $deep   FALSE for a shallow copy, TRUE for a deep copy.
     *
     * @return object The new entity.
     *
     * @throws BadMethodCallException
     */
    public function copy($entity, $deep = false);

    /**
     * Acquire a lock on the given entity.
     *
     * @param object                     $entity
     * @param int                        $lockMode
     * @param int|DateTimeInterface|null $lockVersion
     * @psalm-param LockMode::* $lockMode
     *
     * @return void
     *
     * @throws OptimisticLockException
     * @throws PessimisticLockException
     */
    public function lock($entity, $lockMode, $lockVersion = null);

    /**
     * Gets the EventManager used by the EntityManager.
     *
     * @return EventManager
     */
    public function getEventManager();

    /**
     * Gets the Configuration used by the EntityManager.
     *
     * @return Configuration
     */
    public function getConfiguration();

    /**
     * Check if the Entity manager is open or closed.
     *
     * @return bool
     */
    public function isOpen();

    /**
     * Gets the UnitOfWork used by the EntityManager to coordinate operations.
     *
     * @return UnitOfWork
     */
    public function getUnitOfWork();

    /**
     * Gets a hydrator for the given hydration mode.
     *
     * This method caches the hydrator instances which is used for all queries that don't
     * selectively iterate over the result.
     *
     * @deprecated
     *
     * @param string|int $hydrationMode
     * @psalm-param string|AbstractQuery::HYDRATE_* $hydrationMode
     *
     * @return AbstractHydrator
     */
    public function getHydrator($hydrationMode);

    /**
     * Create a new instance for the given hydration mode.
     *
     * @param string|int $hydrationMode
     * @psalm-param string|AbstractQuery::HYDRATE_* $hydrationMode
     *
     * @return AbstractHydrator
     *
     * @throws ORMException
     */
    public function newHydrator($hydrationMode);

    /**
     * Gets the proxy factory used by the EntityManager to create entity proxies.
     *
     * @return ProxyFactory
     */
    public function getProxyFactory();

    /**
     * Gets the enabled filters.
     *
     * @return FilterCollection The active filter collection.
     */
    public function getFilters();

    /**
     * Checks whether the state of the filter collection is clean.
     *
     * @return bool True, if the filter collection is clean.
     */
    public function isFiltersStateClean();

    /**
     * Checks whether the Entity Manager has filters.
     *
     * @return bool True, if the EM has a filter collection.
     */
    public function hasFilters();

    /**
     * {@inheritDoc}
     *
     * @psalm-param string|class-string<T> $className
     *
     * @return Mapping\ClassMetadata
     * @psalm-return Mapping\ClassMetadata<T>
     *
     * @psalm-template T of object
     */
    public function getClassMetadata($className);
}
