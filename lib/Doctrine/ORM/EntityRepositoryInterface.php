<?php

declare(strict_types=1);

namespace Doctrine\ORM;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

interface EntityRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * Creates a new QueryBuilder instance that is prepopulated for this entity name.
     *
     * @param string $alias
     * @param string $indexBy The index for the from.
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder($alias, $indexBy = null) : QueryBuilder;

    /**
     * Creates a new result set mapping builder for this entity.
     * The column naming strategy is "INCREMENT".
     *
     * @param string $alias
     *
     * @return ResultSetMappingBuilder
     */
    public function createResultSetMappingBuilder($alias) : ResultSetMappingBuilder;

    /**
     * Creates a new Query instance based on a predefined metadata named query.
     *
     * @param string $queryName
     *
     * @return Query
     */
    public function createNamedQuery($queryName) : Query;

    /**
     * Creates a native SQL query.
     *
     * @param string $queryName
     *
     * @return NativeQuery
     */
    public function createNativeNamedQuery($queryName) : NativeQuery;

    /**
     * Clears the repository, causing all managed entities to become detached.
     *
     * @return void
     */
    public function clear() : void;

    /**
     * Finds an entity by its primary key / identifier.
     *
     * @param mixed $id The identifier.
     * @param int|null $lockMode One of the \Doctrine\DBAL\LockMode::* constants
     *                              or NULL if no specific lock mode should be used
     *                              during the search.
     * @param int|null $lockVersion The lock version.
     *
     * @return object|null The entity instance or NULL if the entity can not be found.
     */
    public function find($id, $lockMode = null, $lockVersion = null) : ?object;

    /**
     * Finds all entities in the repository.
     *
     * @return object[] The entities.
     */
    public function findAll() : array;

    /**
     * Finds entities by a set of criteria.
     *
     * @param mixed[]  $criteria
     * @param string[] $orderBy
     * @param int|null $limit
     * @param int|null $offset
     *
     * @return object[] The objects.
     *
     * @todo guilhermeblanco Change orderBy to use a blank array by default (requires Common\Persistence change).
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null) : array;

    /**
     * Finds a single entity by a set of criteria.
     *
     * @param string[] $criteria
     * @param string[] $orderBy
     *
     * @return object|null The entity instance or NULL if the entity can not be found.
     */
    public function findOneBy(array $criteria, array $orderBy = []) : ?object;

    /**
     * Counts entities by a set of criteria.
     *
     * @param Criteria[] $criteria
     *
     * @return int The cardinality of the objects that match the given criteria.
     *
     * @todo Add this method to `ObjectRepository` interface in the next major release
     */
    public function count(array $criteria) : int;

    public function getClassName() : string;

    /**
     * Select all elements from a selectable that match the expression and
     * return a new collection containing these elements.
     */
    public function matching(Criteria $criteria) : Collection;
}
