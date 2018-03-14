<?php

declare(strict_types=1);

namespace Doctrine\ORM;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Proxy\Factory\ProxyFactory;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Utility\IdentifierFlattener;
use ProxyManager\Proxy\GhostObjectInterface;

/**
 * EntityManager interface
 */
interface EntityManagerInterface
{
    /**
     * @param mixed $id
     */
    public function find(string $className, $id) : ?object;
    public function persist(object $object) : void;
    public function remove(object $object) : void;
    public function merge(object $object) : object;
    public function clear(?string $objectName = null) : void;
    public function detach(object $object) : void;
    public function refresh(object $object) : void;
    public function flush() : void;
    public function getRepository(string $className) : EntityRepository;
    public function getClassMetadata(string $className) : ClassMetadata;
    public function getMetadataFactory() : ClassMetadataFactory;
    public function initializeObject(object $obj) : void;
    public function contains(object $object) : bool;

    /**
     * Returns the cache API for managing the second level cache regions or NULL if the cache is not enabled.
     */
    public function getCache() : ?Cache;

    /**
     * Gets the database connection object used by the EntityManager.
     */
    public function getConnection() : Connection;

    /**
     * Gets an ExpressionBuilder used for object-oriented construction of query expressions.
     * Example:
     * <code>
     *     $qb = $em->createQueryBuilder();
     *     $expr = $em->getExpressionBuilder();
     *     $qb->select('u')->from('User', 'u')
     *         ->where($expr->orX($expr->eq('u.id', 1), $expr->eq('u.id', 2)));
     * </code>
     */
    public function getExpressionBuilder() : Expr;

    /**
     * Gets an IdentifierFlattener used for converting Entities into an array of identifier values.
     */
    public function getIdentifierFlattener() : IdentifierFlattener;

    /**
     * Starts a transaction on the underlying database connection.
     */
    public function beginTransaction() : void;

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
     * @param callable $func The function to execute transactionally.
     *
     * @return mixed The value returned from the closure.
     *
     * @throws \Throwable
     */
    public function transactional(callable $func);

    /**
     * Commits a transaction on the underlying database connection.
     */
    public function commit() : void;

    /**
     * Performs a rollback on the underlying database connection.
     */
    public function rollback() : void;

    /**
     * Creates a new Query object.
     *
     * @param string $dql The DQL string.
     */
    public function createQuery(string $dql = '') : Query;

    /**
     * Creates a native SQL query.
     *
     * @param ResultSetMapping $rsm The ResultSetMapping to use.
     */
    public function createNativeQuery(string $sql, ResultSetMapping $rsm) : NativeQuery;

    /**
     * Create a QueryBuilder instance
     */
    public function createQueryBuilder() : QueryBuilder;

    /**
     * Gets a reference to the entity identified by the given type and identifier
     * without actually loading it, if the entity is not yet loaded.
     *
     * @param string $entityName The name of the entity type.
     * @param mixed  $id         The entity identifier.
     *
     * @return object|GhostObjectInterface|null The entity reference.
     *
     * @throws ORMException
     */
    public function getReference(string $entityName, $id) : ?object;

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
     *
     * @return object The (partial) entity reference.
     */
    public function getPartialReference(string $entityName, $identifier) : ?object;

    /**
     * Closes the EntityManager. All entities that are currently managed
     * by this EntityManager become detached. The EntityManager may no longer
     * be used after it is closed.
     */
    public function close() : void;

    /**
     * Acquire a lock on the given entity.
     *
     * @param mixed $lockVersion
     */
    public function lock(object $entity, int $lockMode, $lockVersion = null) : void;

    /**
     * Gets the EventManager used by the EntityManager.
     */
    public function getEventManager() : EventManager;

    /**
     * Gets the Configuration used by the EntityManager.
     */
    public function getConfiguration() : Configuration;

    /**
     * Check if the Entity manager is open or closed.
     */
    public function isOpen() : bool;

    /**
     * Gets the UnitOfWork used by the EntityManager to coordinate operations.
     */
    public function getUnitOfWork() : UnitOfWork;

    /**
     * Gets a hydrator for the given hydration mode.
     *
     * This method caches the hydrator instances which is used for all queries that don't
     * selectively iterate over the result.
     *
     * @param int|string $hydrationMode
     *
     * @deprecated
     */
    public function getHydrator($hydrationMode) : AbstractHydrator;

    /**
     * Create a new instance for the given hydration mode.
     *
     * @param int|string $hydrationMode
     */
    public function newHydrator($hydrationMode) : AbstractHydrator;

    /**
     * Gets the proxy factory used by the EntityManager to create entity proxies.
     */
    public function getProxyFactory() : ProxyFactory;

    /**
     * Gets the enabled filters.
     *
     * @return FilterCollection The active filter collection.
     */
    public function getFilters() : FilterCollection;

    /**
     * Checks whether the state of the filter collection is clean.
     *
     * @return bool True, if the filter collection is clean.
     */
    public function isFiltersStateClean() : bool;

    /**
     * Checks whether the Entity Manager has filters.
     *
     * @return bool True, if the EM has a filter collection.
     */
    public function hasFilters() : bool;
}
