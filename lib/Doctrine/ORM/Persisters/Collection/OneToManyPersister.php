<?php

declare(strict_types=1);

namespace Doctrine\ORM\Persisters\Collection;

use BadMethodCallException;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\ToManyAssociationMetadata;
use Doctrine\ORM\PersistentCollection;
use function array_keys;
use function array_map;
use function array_merge;
use function array_reverse;
use function array_values;
use function implode;
use function sprintf;

/**
 * Persister for one-to-many collections.
 */
class OneToManyPersister extends AbstractCollectionPersister
{
    /**
     * {@inheritdoc}
     */
    public function delete(PersistentCollection $collection)
    {
        // The only valid case here is when you have weak entities. In this
        // scenario, you have @OneToMany with orphanRemoval=true, and replacing
        // the entire collection with a new would trigger this operation.
        $association = $collection->getMapping();

        if (! $association->isOrphanRemoval()) {
            // Handling non-orphan removal should never happen, as @OneToMany
            // can only be inverse side. For owning side one to many, it is
            // required to have a join table, which would classify as a ManyToManyPersister.
            return;
        }

        $targetClass = $this->em->getClassMetadata($association->getTargetEntity());

        return $targetClass->inheritanceType === InheritanceType::JOINED
            ? $this->deleteJoinedEntityCollection($collection)
            : $this->deleteEntityCollection($collection);
    }

    /**
     * {@inheritdoc}
     */
    public function update(PersistentCollection $collection)
    {
        // This can never happen. One to many can only be inverse side.
        // For owning side one to many, it is required to have a join table,
        // then classifying it as a ManyToManyPersister.
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function get(PersistentCollection $collection, $index)
    {
        $association = $collection->getMapping();

        if (! ($association instanceof ToManyAssociationMetadata && $association->getIndexedBy())) {
            throw new BadMethodCallException('Selecting a collection by index is only supported on indexed collections.');
        }

        $persister = $this->uow->getEntityPersister($association->getTargetEntity());
        $criteria  = [
            $association->getMappedBy()  => $collection->getOwner(),
            $association->getIndexedBy() => $index,
        ];

        return $persister->load($criteria, null, $association, [], null, 1);
    }

    /**
     * {@inheritdoc}
     */
    public function count(PersistentCollection $collection)
    {
        $association = $collection->getMapping();
        $persister   = $this->uow->getEntityPersister($association->getTargetEntity());

        // only works with single id identifier entities. Will throw an
        // exception in Entity Persisters if that is not the case for the
        // 'mappedBy' field.
        $criteria = [
            $association->getMappedBy()  => $collection->getOwner(),
        ];

        return $persister->count($criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function slice(PersistentCollection $collection, $offset, $length = null)
    {
        $association = $collection->getMapping();
        $persister   = $this->uow->getEntityPersister($association->getTargetEntity());

        return $persister->getOneToManyCollection($association, $collection->getOwner(), $offset, $length);
    }

    /**
     * {@inheritdoc}
     */
    public function containsKey(PersistentCollection $collection, $key)
    {
        $association = $collection->getMapping();

        if (! ($association instanceof ToManyAssociationMetadata && $association->getIndexedBy())) {
            throw new BadMethodCallException('Selecting a collection by index is only supported on indexed collections.');
        }

        $persister = $this->uow->getEntityPersister($association->getTargetEntity());

        // only works with single id identifier entities. Will throw an
        // exception in Entity Persisters if that is not the case for the
        // 'mappedBy' field.
        $criteria = [
            $association->getMappedBy()  => $collection->getOwner(),
            $association->getIndexedBy() => $key,
        ];

        return (bool) $persister->count($criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function contains(PersistentCollection $collection, $element)
    {
        if (! $this->isValidEntityState($element)) {
            return false;
        }

        $association = $collection->getMapping();
        $persister   = $this->uow->getEntityPersister($association->getTargetEntity());

        // only works with single id identifier entities. Will throw an
        // exception in Entity Persisters if that is not the case for the
        // 'mappedBy' field.
        $criteria = new Criteria(
            Criteria::expr()->eq($association->getMappedBy(), $collection->getOwner())
        );

        return $persister->exists($element, $criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function removeElement(PersistentCollection $collection, $element)
    {
        $association = $collection->getMapping();

        if (! $association->isOrphanRemoval()) {
            // no-op: this is not the owning side, therefore no operations should be applied
            return false;
        }

        if (! $this->isValidEntityState($element)) {
            return false;
        }

        $persister = $this->uow->getEntityPersister($association->getTargetEntity());

        return $persister->delete($element);
    }

    /**
     * {@inheritdoc}
     */
    public function loadCriteria(PersistentCollection $collection, Criteria $criteria)
    {
        throw new BadMethodCallException('Filtering a collection by Criteria is not supported by this CollectionPersister.');
    }

    /**
     * @return int
     *
     * @throws DBALException
     */
    private function deleteEntityCollection(PersistentCollection $collection)
    {
        $association  = $collection->getMapping();
        $identifier   = $this->uow->getEntityIdentifier($collection->getOwner());
        $sourceClass  = $this->em->getClassMetadata($association->getSourceEntity());
        $targetClass  = $this->em->getClassMetadata($association->getTargetEntity());
        $inverseAssoc = $targetClass->getProperty($association->getMappedBy());
        $columns      = [];
        $parameters   = [];

        foreach ($inverseAssoc->getJoinColumns() as $joinColumn) {
            $columns[]    = $this->platform->quoteIdentifier($joinColumn->getColumnName());
            $parameters[] = $identifier[$sourceClass->fieldNames[$joinColumn->getReferencedColumnName()]];
        }

        $tableName = $targetClass->table->getQuotedQualifiedName($this->platform);
        $statement = 'DELETE FROM ' . $tableName . ' WHERE ' . implode(' = ? AND ', $columns) . ' = ?';

        return $this->conn->executeUpdate($statement, $parameters);
    }

    /**
     * Delete Class Table Inheritance entities.
     * A temporary table is needed to keep IDs to be deleted in both parent and child class' tables.
     *
     * Thanks Steve Ebersole (Hibernate) for idea on how to tackle reliably this scenario, we owe him a beer! =)
     *
     * @return int
     *
     * @throws DBALException
     */
    private function deleteJoinedEntityCollection(PersistentCollection $collection)
    {
        $association     = $collection->getMapping();
        $targetClass     = $this->em->getClassMetadata($association->getTargetEntity());
        $rootClass       = $this->em->getClassMetadata($targetClass->getRootClassName());
        $sourcePersister = $this->uow->getEntityPersister($association->getSourceEntity());

        // 1) Build temporary table DDL
        $tempTable         = $this->platform->getTemporaryTableName($rootClass->getTemporaryIdTableName());
        $idColumns         = $rootClass->getIdentifierColumns($this->em);
        $idColumnNameList  = implode(', ', array_keys($idColumns));
        $columnDefinitions = [];

        foreach ($idColumns as $columnName => $column) {
            $type = $column->getType();

            $columnDefinitions[$columnName] = [
                'notnull' => true,
                'type'    => $type,
            ];
        }

        $statement = $this->platform->getCreateTemporaryTableSnippetSQL() . ' ' . $tempTable
            . ' (' . $this->platform->getColumnDeclarationListSQL($columnDefinitions) . ')';

        $this->conn->executeUpdate($statement);

        // 2) Build insert table records into temporary table
        $dql   = ' SELECT t0.' . implode(', t0.', $rootClass->getIdentifierFieldNames())
               . ' FROM ' . $targetClass->getClassName() . ' t0 WHERE t0.' . $association->getMappedBy() . ' = :owner';
        $query = $this->em->createQuery($dql)->setParameter('owner', $collection->getOwner());

        $statement  = 'INSERT INTO ' . $tempTable . ' (' . $idColumnNameList . ') ' . $query->getSQL();
        $parameters = array_values($sourcePersister->getIdentifier($collection->getOwner()));
        $numDeleted = $this->conn->executeUpdate($statement, $parameters);

        // 3) Create statement used in DELETE ... WHERE ... IN (subselect)
        $deleteSQLTemplate = sprintf(
            'DELETE FROM %%s WHERE (%s) IN (SELECT %s FROM %s)',
            $idColumnNameList,
            $idColumnNameList,
            $tempTable
        );

        // 4) Delete records on each table in the hierarchy
        $hierarchyClasses = array_merge(
            array_map(
                function ($className) {
                    return $this->em->getClassMetadata($className);
                },
                array_reverse($targetClass->getSubClasses())
            ),
            [$targetClass],
            $targetClass->getAncestorsIterator()->getArrayCopy()
        );

        foreach ($hierarchyClasses as $class) {
            $statement = sprintf($deleteSQLTemplate, $class->table->getQuotedQualifiedName($this->platform));

            $this->conn->executeUpdate($statement);
        }

        // 5) Drop temporary table
        $statement = $this->platform->getDropTemporaryTableSQL($tempTable);

        $this->conn->executeUpdate($statement);

        return $numDeleted;
    }
}
