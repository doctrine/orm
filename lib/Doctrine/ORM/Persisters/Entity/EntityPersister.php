<?php

declare(strict_types=1);

namespace Doctrine\ORM\Persisters\Entity;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ManyToManyAssociationMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Mapping\OneToManyAssociationMetadata;
use Doctrine\ORM\Mapping\ToOneAssociationMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * Entity persister interface
 * Define the behavior that should be implemented by all entity persisters.
 */
interface EntityPersister
{
    /**
     * @return ClassMetadata
     */
    public function getClassMetadata();

    /**
     * Gets the ResultSetMapping used for hydration.
     *
     * @return ResultSetMapping
     */
    public function getResultSetMapping();

    /**
     * Extracts the identifier values of an entity that relies on this persister.
     *
     * For composite identifiers, the identifier values are returned as an array
     * with the same order as the field order in {@link ClassMetadata#identifier}.
     *
     * @param object $entity
     *
     * @return mixed[]
     */
    public function getIdentifier($entity) : array;

    /**
     * Populates the entity identifier of an entity.
     *
     * @param object  $entity
     * @param mixed[] $id
     */
    public function setIdentifier($entity, array $id) : void;

    /**
     * @param object $entity
     *
     * @return mixed|null
     */
    public function getColumnValue($entity, string $columnName);

    /**
     * Gets the SELECT SQL to select one or more entities by a set of field criteria.
     *
     * @param Criteria|Criteria[] $criteria
     * @param int|null            $lockMode
     * @param int|null            $limit
     * @param int|null            $offset
     * @param mixed[]             $orderBy
     *
     * @return string
     */
    public function getSelectSQL(
        $criteria,
        ?AssociationMetadata $association = null,
        $lockMode = null,
        $limit = null,
        $offset = null,
        array $orderBy = []
    );

    /**
     * Get the COUNT SQL to count entities (optionally based on a criteria)
     *
     * @param Criteria|mixed[] $criteria
     *
     * @return string
     */
    public function getCountSQL($criteria = []);

    /**
     * Expands the parameters from the given criteria and use the correct binding types if found.
     *
     * @param mixed[] $criteria
     *
     * @return mixed[][]
     */
    public function expandParameters($criteria);

    /**
     * Expands Criteria Parameters by walking the expressions and grabbing all parameters and types from it.
     *
     * @return mixed[][]
     */
    public function expandCriteriaParameters(Criteria $criteria);

    /**
     * Gets the SQL WHERE condition for matching a field with a given value.
     *
     * @param string      $field
     * @param mixed       $value
     * @param string|null $comparison
     *
     * @return string
     */
    public function getSelectConditionStatementSQL(
        $field,
        $value,
        ?AssociationMetadata $association = null,
        $comparison = null
    );

    /**
     * Inserts an entity. Returns any generated post-insert identifiers that were created as a result of the insertion.
     * The insertion happens instantaneously.
     *
     * Subclasses may override this method to customize the semantics of entity deletion.
     *
     * @return void
     */
    public function insert($entity);

    /**
     * Updates a managed entity. The entity is updated according to its current changeset in the running UnitOfWork.
     * If there is no changeset, nothing is updated.
     *
     * Subclasses may override this method to customize the semantics of entity update.
     *
     * @param object $entity The entity to update.
     *
     * @return void
     */
    public function update($entity);

    /**
     * Deletes a managed entity.
     *
     * The entity to delete must be managed and have a persistent identifier. The deletion happens instantaneously.
     *
     * Subclasses may override this method to customize the semantics of entity deletion.
     *
     * @param object $entity The entity to delete.
     *
     * @return bool TRUE if the entity got deleted in the database, FALSE otherwise.
     */
    public function delete($entity);

    /**
     * Count entities (optionally filtered by a criteria)
     *
     * @param  Criteria[]|Criteria $criteria
     *
     * @return int
     */
    public function count($criteria = []);

    /**
     * Locks all rows of this entity matching the given criteria with the specified pessimistic lock mode.
     *
     * @param Criteria[] $criteria
     * @param int        $lockMode One of the Doctrine\DBAL\LockMode::* constants.
     *
     * @return void
     */
    public function lock(array $criteria, $lockMode);

    /**
     * Checks whether the given managed entity exists in the database.
     *
     * @param object $entity
     *
     * @return bool TRUE if the entity exists in the database, FALSE otherwise.
     */
    public function exists($entity, ?Criteria $extraConditions = null);

    /**
     * Refreshes a managed entity.
     *
     * @param mixed[]  $id       The identifier of the entity as an associative array from
     *                           column or field names to values.
     * @param object   $entity   The entity to refresh.
     * @param int|null $lockMode One of the \Doctrine\DBAL\LockMode::* constants
     *                           or NULL if no specific lock mode should be used
     *                           for refreshing the managed entity.
     *
     * @return void
     */
    public function refresh(array $id, $entity, $lockMode = null);

    /**
     * Loads an entity by a list of field criteria.
     *
     * @param mixed[]                  $criteria    The criteria by which to load the entity.
     * @param object|null              $entity      The entity to load the data into. If not specified, a new entity is created.
     * @param AssociationMetadata|null $association The association that connects the entity to load to another entity, if any.
     * @param mixed[]                  $hints       Hints for entity creation.
     * @param int|null                 $lockMode    One of the \Doctrine\DBAL\LockMode::* constants or NULL if no specific lock mode
     *                                              should be used for loading the entity.
     * @param int|null                 $limit       Limit number of results.
     * @param mixed[]                  $orderBy     Criteria to order by.
     *
     * @return object|null The loaded and managed entity instance or NULL if the entity can not be found.
     *
     * @todo Check identity map? loadById method? Try to guess whether $criteria is the id?
     */
    public function load(
        array $criteria,
        $entity = null,
        ?AssociationMetadata $association = null,
        array $hints = [],
        $lockMode = null,
        $limit = null,
        array $orderBy = []
    );

    /**
     * Loads an entity by identifier.
     *
     * @param mixed[]     $identifier The entity identifier.
     * @param object|null $entity     The entity to load the data into. If not specified, a new entity is created.
     *
     * @return object The loaded and managed entity instance or NULL if the entity can not be found.
     *
     * @todo Check parameters
     */
    public function loadById(array $identifier, $entity = null);

    /**
     * Loads Entities matching the given Criteria object.
     *
     * @return mixed[]
     */
    public function loadCriteria(Criteria $criteria);

    /**
     * Loads a list of entities by a list of field criteria.
     *
     * @param mixed[]  $criteria
     * @param mixed[]  $orderBy
     * @param int|null $limit    Limit number of results.
     * @param int|null $offset
     *
     * @return mixed[]
     */
    public function loadAll(array $criteria = [], array $orderBy = [], $limit = null, $offset = null);

    /**
     * Loads an entity of this persister's mapped class as part of a single-valued
     * association from another entity.
     *
     * @param ToOneAssociationMetadata $association  The association to load.
     * @param object                   $sourceEntity The entity that owns the association (not necessarily the "owning side").
     * @param mixed[]                  $identifier   The identifier of the entity to load. Must be provided if
     *                                               the association to load represents the owning side, otherwise
     *                                               the identifier is derived from the $sourceEntity.
     *
     * @return object The loaded and managed entity instance or NULL if the entity can not be found.
     *
     * @throws MappingException
     */
    public function loadToOneEntity(
        ToOneAssociationMetadata $association,
        $sourceEntity,
        array $identifier = []
    );

    /**
     * Returns an array with (sliced or full list) of elements in the specified collection.
     *
     * @param OneToManyAssociationMetadata $association  The association mapping of the association being loaded.
     * @param object                       $sourceEntity The entity that owns the collection.
     * @param int|null                     $offset
     * @param int|null                     $limit        Limit number of results.
     *
     * @return mixed[]
     */
    public function getOneToManyCollection(
        OneToManyAssociationMetadata $association,
        $sourceEntity,
        $offset = null,
        $limit = null
    );

    /**
     * Loads a collection of entities in a one-to-many association.
     *
     * @param OneToManyAssociationMetadata $association  The association mapping of the association being loaded.
     * @param object                       $sourceEntity The entity that owns the collection.
     * @param PersistentCollection         $collection   The collection to load/fill.
     *
     * @return mixed[]
     */
    public function loadOneToManyCollection(
        OneToManyAssociationMetadata $association,
        $sourceEntity,
        PersistentCollection $collection
    );

    /**
     * Gets (sliced or full) elements of the given collection.
     *
     * @param ManyToManyAssociationMetadata $association  The association mapping of the association being loaded.
     * @param object                        $sourceEntity The entity that owns the collection.
     * @param int|null                      $offset
     * @param int|null                      $limit        Limit number of results.
     *
     * @return mixed[]
     */
    public function getManyToManyCollection(
        ManyToManyAssociationMetadata $association,
        $sourceEntity,
        $offset = null,
        $limit = null
    );

    /**
     * Loads a collection of entities of a many-to-many association.
     *
     * @param ManyToManyAssociationMetadata $association  The association mapping of the association being loaded.
     * @param object                        $sourceEntity The entity that owns the collection.
     * @param PersistentCollection          $collection   The collection to load/fill.
     *
     * @return mixed[]
     */
    public function loadManyToManyCollection(
        ManyToManyAssociationMetadata $association,
        $sourceEntity,
        PersistentCollection $collection
    );
}
