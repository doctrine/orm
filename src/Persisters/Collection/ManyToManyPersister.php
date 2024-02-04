<?php

declare(strict_types=1);

namespace Doctrine\ORM\Persisters\Collection;

use BadMethodCallException;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Persisters\SqlValueVisitor;
use Doctrine\ORM\Query;
use Doctrine\ORM\Utility\PersisterHelper;

use function array_fill;
use function array_pop;
use function count;
use function get_class;
use function implode;
use function in_array;
use function reset;
use function sprintf;

/**
 * Persister for many-to-many collections.
 *
 * @psalm-import-type AssociationMapping from ClassMetadata
 */
class ManyToManyPersister extends AbstractCollectionPersister
{
    /**
     * {@inheritDoc}
     */
    public function delete(PersistentCollection $collection)
    {
        $mapping = $collection->getMapping();

        if (! $mapping['isOwningSide']) {
            return; // ignore inverse side
        }

        $types = [];
        $class = $this->em->getClassMetadata($mapping['sourceEntity']);

        foreach ($mapping['joinTable']['joinColumns'] as $joinColumn) {
            $types[] = PersisterHelper::getTypeOfColumn($joinColumn['referencedColumnName'], $class, $this->em);
        }

        $this->conn->executeStatement($this->getDeleteSQL($collection), $this->getDeleteSQLParameters($collection), $types);
    }

    /**
     * {@inheritDoc}
     */
    public function update(PersistentCollection $collection)
    {
        $mapping = $collection->getMapping();

        if (! $mapping['isOwningSide']) {
            return; // ignore inverse side
        }

        [$deleteSql, $deleteTypes] = $this->getDeleteRowSQL($collection);
        [$insertSql, $insertTypes] = $this->getInsertRowSQL($collection);

        foreach ($collection->getDeleteDiff() as $element) {
            $this->conn->executeStatement(
                $deleteSql,
                $this->getDeleteRowSQLParameters($collection, $element),
                $deleteTypes
            );
        }

        foreach ($collection->getInsertDiff() as $element) {
            $this->conn->executeStatement(
                $insertSql,
                $this->getInsertRowSQLParameters($collection, $element),
                $insertTypes
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function get(PersistentCollection $collection, $index)
    {
        $mapping = $collection->getMapping();

        if (! isset($mapping['indexBy'])) {
            throw new BadMethodCallException('Selecting a collection by index is only supported on indexed collections.');
        }

        $persister = $this->uow->getEntityPersister($mapping['targetEntity']);
        $mappedKey = $mapping['isOwningSide']
            ? $mapping['inversedBy']
            : $mapping['mappedBy'];

        return $persister->load([$mappedKey => $collection->getOwner(), $mapping['indexBy'] => $index], null, $mapping, [], 0, 1);
    }

    /**
     * {@inheritDoc}
     */
    public function count(PersistentCollection $collection)
    {
        $conditions  = [];
        $params      = [];
        $types       = [];
        $mapping     = $collection->getMapping();
        $id          = $this->uow->getEntityIdentifier($collection->getOwner());
        $sourceClass = $this->em->getClassMetadata($mapping['sourceEntity']);
        $targetClass = $this->em->getClassMetadata($mapping['targetEntity']);
        $association = ! $mapping['isOwningSide']
            ? $targetClass->associationMappings[$mapping['mappedBy']]
            : $mapping;

        $joinTableName = $this->quoteStrategy->getJoinTableName($association, $sourceClass, $this->platform);
        $joinColumns   = ! $mapping['isOwningSide']
            ? $association['joinTable']['inverseJoinColumns']
            : $association['joinTable']['joinColumns'];

        foreach ($joinColumns as $joinColumn) {
            $columnName     = $this->quoteStrategy->getJoinColumnName($joinColumn, $sourceClass, $this->platform);
            $referencedName = $joinColumn['referencedColumnName'];
            $conditions[]   = 't.' . $columnName . ' = ?';
            $params[]       = $id[$sourceClass->getFieldForColumn($referencedName)];
            $types[]        = PersisterHelper::getTypeOfColumn($referencedName, $sourceClass, $this->em);
        }

        [$joinTargetEntitySQL, $filterSql] = $this->getFilterSql($mapping);

        if ($filterSql) {
            $conditions[] = $filterSql;
        }

        // If there is a provided criteria, make part of conditions
        // @todo Fix this. Current SQL returns something like:
        /*if ($criteria && ($expression = $criteria->getWhereExpression()) !== null) {
            // A join is needed on the target entity
            $targetTableName = $this->quoteStrategy->getTableName($targetClass, $this->platform);
            $targetJoinSql   = ' JOIN ' . $targetTableName . ' te'
                . ' ON' . implode(' AND ', $this->getOnConditionSQL($association));

            // And criteria conditions needs to be added
            $persister    = $this->uow->getEntityPersister($targetClass->name);
            $visitor      = new SqlExpressionVisitor($persister, $targetClass);
            $conditions[] = $visitor->dispatch($expression);

            $joinTargetEntitySQL = $targetJoinSql . $joinTargetEntitySQL;
        }*/

        $sql = 'SELECT COUNT(*)'
            . ' FROM ' . $joinTableName . ' t'
            . $joinTargetEntitySQL
            . ' WHERE ' . implode(' AND ', $conditions);

        return (int) $this->conn->fetchOne($sql, $params, $types);
    }

    /**
     * {@inheritDoc}
     */
    public function slice(PersistentCollection $collection, $offset, $length = null)
    {
        $mapping   = $collection->getMapping();
        $persister = $this->uow->getEntityPersister($mapping['targetEntity']);

        return $persister->getManyToManyCollection($mapping, $collection->getOwner(), $offset, $length);
    }

    /**
     * {@inheritDoc}
     */
    public function containsKey(PersistentCollection $collection, $key)
    {
        $mapping = $collection->getMapping();

        if (! isset($mapping['indexBy'])) {
            throw new BadMethodCallException('Selecting a collection by index is only supported on indexed collections.');
        }

        [$quotedJoinTable, $whereClauses, $params, $types] = $this->getJoinTableRestrictionsWithKey(
            $collection,
            (string) $key,
            true
        );

        $sql = 'SELECT 1 FROM ' . $quotedJoinTable . ' WHERE ' . implode(' AND ', $whereClauses);

        return (bool) $this->conn->fetchOne($sql, $params, $types);
    }

    /**
     * {@inheritDoc}
     */
    public function contains(PersistentCollection $collection, $element)
    {
        if (! $this->isValidEntityState($element)) {
            return false;
        }

        [$quotedJoinTable, $whereClauses, $params, $types] = $this->getJoinTableRestrictions(
            $collection,
            $element,
            true
        );

        $sql = 'SELECT 1 FROM ' . $quotedJoinTable . ' WHERE ' . implode(' AND ', $whereClauses);

        return (bool) $this->conn->fetchOne($sql, $params, $types);
    }

    /**
     * {@inheritDoc}
     */
    public function loadCriteria(PersistentCollection $collection, Criteria $criteria)
    {
        $mapping       = $collection->getMapping();
        $owner         = $collection->getOwner();
        $ownerMetadata = $this->em->getClassMetadata(get_class($owner));
        $id            = $this->uow->getEntityIdentifier($owner);
        $targetClass   = $this->em->getClassMetadata($mapping['targetEntity']);
        $onConditions  = $this->getOnConditionSQL($mapping);
        $whereClauses  = $params = [];
        $paramTypes    = [];

        if (! $mapping['isOwningSide']) {
            $associationSourceClass = $targetClass;
            $mapping                = $targetClass->associationMappings[$mapping['mappedBy']];
            $sourceRelationMode     = 'relationToTargetKeyColumns';
        } else {
            $associationSourceClass = $ownerMetadata;
            $sourceRelationMode     = 'relationToSourceKeyColumns';
        }

        foreach ($mapping[$sourceRelationMode] as $key => $value) {
            $whereClauses[] = sprintf('t.%s = ?', $key);
            $params[]       = $ownerMetadata->containsForeignIdentifier
                ? $id[$ownerMetadata->getFieldForColumn($value)]
                : $id[$ownerMetadata->fieldNames[$value]];
            $paramTypes[]   = PersisterHelper::getTypeOfColumn($value, $ownerMetadata, $this->em);
        }

        $parameters = $this->expandCriteriaParameters($criteria);

        foreach ($parameters as $parameter) {
            [$name, $value, $operator] = $parameter;

            $field = $this->quoteStrategy->getColumnName($name, $targetClass, $this->platform);

            if ($value === null && ($operator === Comparison::EQ || $operator === Comparison::NEQ)) {
                $whereClauses[] = sprintf('te.%s %s NULL', $field, $operator === Comparison::EQ ? 'IS' : 'IS NOT');
            } else {
                $whereClauses[] = sprintf('te.%s %s ?', $field, $operator);
                $params[]       = $value;
                $paramTypes[]   = PersisterHelper::getTypeOfField($name, $targetClass, $this->em)[0];
            }
        }

        $tableName = $this->quoteStrategy->getTableName($targetClass, $this->platform);
        $joinTable = $this->quoteStrategy->getJoinTableName($mapping, $associationSourceClass, $this->platform);

        $rsm = new Query\ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata($targetClass->name, 'te');

        $sql = 'SELECT ' . $rsm->generateSelectClause()
            . ' FROM ' . $tableName . ' te'
            . ' JOIN ' . $joinTable . ' t ON'
            . implode(' AND ', $onConditions)
            . ' WHERE ' . implode(' AND ', $whereClauses);

        $sql .= $this->getOrderingSql($criteria, $targetClass);

        $sql .= $this->getLimitSql($criteria);

        $stmt = $this->conn->executeQuery($sql, $params, $paramTypes);

        return $this
            ->em
            ->newHydrator(Query::HYDRATE_OBJECT)
            ->hydrateAll($stmt, $rsm);
    }

    /**
     * Generates the filter SQL for a given mapping.
     *
     * This method is not used for actually grabbing the related entities
     * but when the extra-lazy collection methods are called on a filtered
     * association. This is why besides the many to many table we also
     * have to join in the actual entities table leading to additional
     * JOIN.
     *
     * @param mixed[] $mapping Array containing mapping information.
     * @psalm-param AssociationMapping $mapping
     *
     * @return string[] ordered tuple:
     *                   - JOIN condition to add to the SQL
     *                   - WHERE condition to add to the SQL
     * @psalm-return array{0: string, 1: string}
     */
    public function getFilterSql($mapping)
    {
        $targetClass = $this->em->getClassMetadata($mapping['targetEntity']);
        $rootClass   = $this->em->getClassMetadata($targetClass->rootEntityName);
        $filterSql   = $this->generateFilterConditionSQL($rootClass, 'te');

        if ($filterSql === '') {
            return ['', ''];
        }

        // A join is needed if there is filtering on the target entity
        $tableName = $this->quoteStrategy->getTableName($rootClass, $this->platform);
        $joinSql   = ' JOIN ' . $tableName . ' te'
            . ' ON' . implode(' AND ', $this->getOnConditionSQL($mapping));

        return [$joinSql, $filterSql];
    }

    /**
     * Generates the filter SQL for a given entity and table alias.
     *
     * @param ClassMetadata $targetEntity     Metadata of the target entity.
     * @param string        $targetTableAlias The table alias of the joined/selected table.
     *
     * @return string The SQL query part to add to a query.
     */
    protected function generateFilterConditionSQL(ClassMetadata $targetEntity, $targetTableAlias)
    {
        $filterClauses = [];

        foreach ($this->em->getFilters()->getEnabledFilters() as $filter) {
            $filterExpr = $filter->addFilterConstraint($targetEntity, $targetTableAlias);
            if ($filterExpr) {
                $filterClauses[] = '(' . $filterExpr . ')';
            }
        }

        return $filterClauses
            ? '(' . implode(' AND ', $filterClauses) . ')'
            : '';
    }

    /**
     * Generate ON condition
     *
     * @param mixed[] $mapping
     * @psalm-param AssociationMapping $mapping
     *
     * @return string[]
     * @psalm-return list<string>
     */
    protected function getOnConditionSQL($mapping)
    {
        $targetClass = $this->em->getClassMetadata($mapping['targetEntity']);
        $association = ! $mapping['isOwningSide']
            ? $targetClass->associationMappings[$mapping['mappedBy']]
            : $mapping;

        $joinColumns = $mapping['isOwningSide']
            ? $association['joinTable']['inverseJoinColumns']
            : $association['joinTable']['joinColumns'];

        $conditions = [];

        foreach ($joinColumns as $joinColumn) {
            $joinColumnName = $this->quoteStrategy->getJoinColumnName($joinColumn, $targetClass, $this->platform);
            $refColumnName  = $this->quoteStrategy->getReferencedJoinColumnName($joinColumn, $targetClass, $this->platform);

            $conditions[] = ' t.' . $joinColumnName . ' = te.' . $refColumnName;
        }

        return $conditions;
    }

    /** @return string */
    protected function getDeleteSQL(PersistentCollection $collection)
    {
        $columns   = [];
        $mapping   = $collection->getMapping();
        $class     = $this->em->getClassMetadata(get_class($collection->getOwner()));
        $joinTable = $this->quoteStrategy->getJoinTableName($mapping, $class, $this->platform);

        foreach ($mapping['joinTable']['joinColumns'] as $joinColumn) {
            $columns[] = $this->quoteStrategy->getJoinColumnName($joinColumn, $class, $this->platform);
        }

        return 'DELETE FROM ' . $joinTable
            . ' WHERE ' . implode(' = ? AND ', $columns) . ' = ?';
    }

    /**
     * Internal note: Order of the parameters must be the same as the order of the columns in getDeleteSql.
     *
     * @return list<mixed>
     */
    protected function getDeleteSQLParameters(PersistentCollection $collection)
    {
        $mapping    = $collection->getMapping();
        $identifier = $this->uow->getEntityIdentifier($collection->getOwner());

        // Optimization for single column identifier
        if (count($mapping['relationToSourceKeyColumns']) === 1) {
            return [reset($identifier)];
        }

        // Composite identifier
        $sourceClass = $this->em->getClassMetadata($mapping['sourceEntity']);
        $params      = [];

        foreach ($mapping['relationToSourceKeyColumns'] as $columnName => $refColumnName) {
            $params[] = isset($sourceClass->fieldNames[$refColumnName])
                ? $identifier[$sourceClass->fieldNames[$refColumnName]]
                : $identifier[$sourceClass->getFieldForColumn($refColumnName)];
        }

        return $params;
    }

    /**
     * Gets the SQL statement used for deleting a row from the collection.
     *
     * @return string[]|string[][] ordered tuple containing the SQL to be executed and an array
     *                             of types for bound parameters
     * @psalm-return array{0: string, 1: list<string>}
     */
    protected function getDeleteRowSQL(PersistentCollection $collection)
    {
        $mapping     = $collection->getMapping();
        $class       = $this->em->getClassMetadata($mapping['sourceEntity']);
        $targetClass = $this->em->getClassMetadata($mapping['targetEntity']);
        $columns     = [];
        $types       = [];

        foreach ($mapping['joinTable']['joinColumns'] as $joinColumn) {
            $columns[] = $this->quoteStrategy->getJoinColumnName($joinColumn, $class, $this->platform);
            $types[]   = PersisterHelper::getTypeOfColumn($joinColumn['referencedColumnName'], $class, $this->em);
        }

        foreach ($mapping['joinTable']['inverseJoinColumns'] as $joinColumn) {
            $columns[] = $this->quoteStrategy->getJoinColumnName($joinColumn, $targetClass, $this->platform);
            $types[]   = PersisterHelper::getTypeOfColumn($joinColumn['referencedColumnName'], $targetClass, $this->em);
        }

        return [
            'DELETE FROM ' . $this->quoteStrategy->getJoinTableName($mapping, $class, $this->platform)
            . ' WHERE ' . implode(' = ? AND ', $columns) . ' = ?',
            $types,
        ];
    }

    /**
     * Gets the SQL parameters for the corresponding SQL statement to delete the given
     * element from the given collection.
     *
     * Internal note: Order of the parameters must be the same as the order of the columns in getDeleteRowSql.
     *
     * @param mixed $element
     *
     * @return mixed[]
     * @psalm-return list<mixed>
     */
    protected function getDeleteRowSQLParameters(PersistentCollection $collection, $element)
    {
        return $this->collectJoinTableColumnParameters($collection, $element);
    }

    /**
     * Gets the SQL statement used for inserting a row in the collection.
     *
     * @return string[]|string[][] ordered tuple containing the SQL to be executed and an array
     *                             of types for bound parameters
     * @psalm-return array{0: string, 1: list<string>}
     */
    protected function getInsertRowSQL(PersistentCollection $collection)
    {
        $columns     = [];
        $types       = [];
        $mapping     = $collection->getMapping();
        $class       = $this->em->getClassMetadata($mapping['sourceEntity']);
        $targetClass = $this->em->getClassMetadata($mapping['targetEntity']);

        foreach ($mapping['joinTable']['joinColumns'] as $joinColumn) {
            $columns[] = $this->quoteStrategy->getJoinColumnName($joinColumn, $targetClass, $this->platform);
            $types[]   = PersisterHelper::getTypeOfColumn($joinColumn['referencedColumnName'], $class, $this->em);
        }

        foreach ($mapping['joinTable']['inverseJoinColumns'] as $joinColumn) {
            $columns[] = $this->quoteStrategy->getJoinColumnName($joinColumn, $targetClass, $this->platform);
            $types[]   = PersisterHelper::getTypeOfColumn($joinColumn['referencedColumnName'], $targetClass, $this->em);
        }

        return [
            'INSERT INTO ' . $this->quoteStrategy->getJoinTableName($mapping, $class, $this->platform)
            . ' (' . implode(', ', $columns) . ')'
            . ' VALUES'
            . ' (' . implode(', ', array_fill(0, count($columns), '?')) . ')',
            $types,
        ];
    }

    /**
     * Gets the SQL parameters for the corresponding SQL statement to insert the given
     * element of the given collection into the database.
     *
     * Internal note: Order of the parameters must be the same as the order of the columns in getInsertRowSql.
     *
     * @param object $element
     *
     * @return mixed[]
     * @psalm-return list<mixed>
     */
    protected function getInsertRowSQLParameters(PersistentCollection $collection, $element)
    {
        return $this->collectJoinTableColumnParameters($collection, $element);
    }

    /**
     * Collects the parameters for inserting/deleting on the join table in the order
     * of the join table columns as specified in ManyToManyMapping#joinTableColumns.
     *
     * @param object $element
     *
     * @return mixed[]
     * @psalm-return list<mixed>
     */
    private function collectJoinTableColumnParameters(
        PersistentCollection $collection,
        $element
    ): array {
        $params      = [];
        $mapping     = $collection->getMapping();
        $isComposite = count($mapping['joinTableColumns']) > 2;

        $identifier1 = $this->uow->getEntityIdentifier($collection->getOwner());
        $identifier2 = $this->uow->getEntityIdentifier($element);

        $class1 = $class2 = null;
        if ($isComposite) {
            $class1 = $this->em->getClassMetadata(get_class($collection->getOwner()));
            $class2 = $collection->getTypeClass();
        }

        foreach ($mapping['joinTableColumns'] as $joinTableColumn) {
            $isRelationToSource = isset($mapping['relationToSourceKeyColumns'][$joinTableColumn]);

            if (! $isComposite) {
                $params[] = $isRelationToSource ? array_pop($identifier1) : array_pop($identifier2);

                continue;
            }

            if ($isRelationToSource) {
                $params[] = $identifier1[$class1->getFieldForColumn($mapping['relationToSourceKeyColumns'][$joinTableColumn])];

                continue;
            }

            $params[] = $identifier2[$class2->getFieldForColumn($mapping['relationToTargetKeyColumns'][$joinTableColumn])];
        }

        return $params;
    }

    /**
     * @param bool $addFilters Whether the filter SQL should be included or not.
     *
     * @return mixed[] ordered vector:
     *                - quoted join table name
     *                - where clauses to be added for filtering
     *                - parameters to be bound for filtering
     *                - types of the parameters to be bound for filtering
     * @psalm-return array{0: string, 1: list<string>, 2: list<mixed>, 3: list<string>}
     */
    private function getJoinTableRestrictionsWithKey(
        PersistentCollection $collection,
        string $key,
        bool $addFilters
    ): array {
        $filterMapping = $collection->getMapping();
        $mapping       = $filterMapping;
        $indexBy       = $mapping['indexBy'];
        $id            = $this->uow->getEntityIdentifier($collection->getOwner());
        $sourceClass   = $this->em->getClassMetadata($mapping['sourceEntity']);
        $targetClass   = $this->em->getClassMetadata($mapping['targetEntity']);

        if (! $mapping['isOwningSide']) {
            $associationSourceClass = $this->em->getClassMetadata($mapping['targetEntity']);
            $mapping                = $associationSourceClass->associationMappings[$mapping['mappedBy']];
            $joinColumns            = $mapping['joinTable']['joinColumns'];
            $sourceRelationMode     = 'relationToTargetKeyColumns';
            $targetRelationMode     = 'relationToSourceKeyColumns';
        } else {
            $associationSourceClass = $this->em->getClassMetadata($mapping['sourceEntity']);
            $joinColumns            = $mapping['joinTable']['inverseJoinColumns'];
            $sourceRelationMode     = 'relationToSourceKeyColumns';
            $targetRelationMode     = 'relationToTargetKeyColumns';
        }

        $quotedJoinTable = $this->quoteStrategy->getJoinTableName($mapping, $associationSourceClass, $this->platform) . ' t';
        $whereClauses    = [];
        $params          = [];
        $types           = [];

        $joinNeeded = ! in_array($indexBy, $targetClass->identifier, true);

        if ($joinNeeded) { // extra join needed if indexBy is not a @id
            $joinConditions = [];

            foreach ($joinColumns as $joinTableColumn) {
                $joinConditions[] = 't.' . $joinTableColumn['name'] . ' = tr.' . $joinTableColumn['referencedColumnName'];
            }

            $tableName        = $this->quoteStrategy->getTableName($targetClass, $this->platform);
            $quotedJoinTable .= ' JOIN ' . $tableName . ' tr ON ' . implode(' AND ', $joinConditions);
            $columnName       = $targetClass->getColumnName($indexBy);

            $whereClauses[] = 'tr.' . $columnName . ' = ?';
            $params[]       = $key;
            $types[]        = PersisterHelper::getTypeOfColumn($columnName, $targetClass, $this->em);
        }

        foreach ($mapping['joinTableColumns'] as $joinTableColumn) {
            if (isset($mapping[$sourceRelationMode][$joinTableColumn])) {
                $column         = $mapping[$sourceRelationMode][$joinTableColumn];
                $whereClauses[] = 't.' . $joinTableColumn . ' = ?';
                $params[]       = $sourceClass->containsForeignIdentifier
                    ? $id[$sourceClass->getFieldForColumn($column)]
                    : $id[$sourceClass->fieldNames[$column]];
                $types[]        = PersisterHelper::getTypeOfColumn($column, $sourceClass, $this->em);
            } elseif (! $joinNeeded) {
                $column = $mapping[$targetRelationMode][$joinTableColumn];

                $whereClauses[] = 't.' . $joinTableColumn . ' = ?';
                $params[]       = $key;
                $types[]        = PersisterHelper::getTypeOfColumn($column, $targetClass, $this->em);
            }
        }

        if ($addFilters) {
            [$joinTargetEntitySQL, $filterSql] = $this->getFilterSql($filterMapping);

            if ($filterSql) {
                $quotedJoinTable .= ' ' . $joinTargetEntitySQL;
                $whereClauses[]   = $filterSql;
            }
        }

        return [$quotedJoinTable, $whereClauses, $params, $types];
    }

    /**
     * @param bool   $addFilters Whether the filter SQL should be included or not.
     * @param object $element
     *
     * @return mixed[] ordered vector:
     *                - quoted join table name
     *                - where clauses to be added for filtering
     *                - parameters to be bound for filtering
     *                - types of the parameters to be bound for filtering
     * @psalm-return array{0: string, 1: list<string>, 2: list<mixed>, 3: list<string>}
     */
    private function getJoinTableRestrictions(
        PersistentCollection $collection,
        $element,
        bool $addFilters
    ): array {
        $filterMapping = $collection->getMapping();
        $mapping       = $filterMapping;

        if (! $mapping['isOwningSide']) {
            $sourceClass = $this->em->getClassMetadata($mapping['targetEntity']);
            $targetClass = $this->em->getClassMetadata($mapping['sourceEntity']);
            $sourceId    = $this->uow->getEntityIdentifier($element);
            $targetId    = $this->uow->getEntityIdentifier($collection->getOwner());

            $mapping = $sourceClass->associationMappings[$mapping['mappedBy']];
        } else {
            $sourceClass = $this->em->getClassMetadata($mapping['sourceEntity']);
            $targetClass = $this->em->getClassMetadata($mapping['targetEntity']);
            $sourceId    = $this->uow->getEntityIdentifier($collection->getOwner());
            $targetId    = $this->uow->getEntityIdentifier($element);
        }

        $quotedJoinTable = $this->quoteStrategy->getJoinTableName($mapping, $sourceClass, $this->platform);
        $whereClauses    = [];
        $params          = [];
        $types           = [];

        foreach ($mapping['joinTableColumns'] as $joinTableColumn) {
            $whereClauses[] = ($addFilters ? 't.' : '') . $joinTableColumn . ' = ?';

            if (isset($mapping['relationToTargetKeyColumns'][$joinTableColumn])) {
                $targetColumn = $mapping['relationToTargetKeyColumns'][$joinTableColumn];
                $params[]     = $targetId[$targetClass->getFieldForColumn($targetColumn)];
                $types[]      = PersisterHelper::getTypeOfColumn($targetColumn, $targetClass, $this->em);

                continue;
            }

            // relationToSourceKeyColumns
            $targetColumn = $mapping['relationToSourceKeyColumns'][$joinTableColumn];
            $params[]     = $sourceId[$sourceClass->getFieldForColumn($targetColumn)];
            $types[]      = PersisterHelper::getTypeOfColumn($targetColumn, $sourceClass, $this->em);
        }

        if ($addFilters) {
            $quotedJoinTable .= ' t';

            [$joinTargetEntitySQL, $filterSql] = $this->getFilterSql($filterMapping);

            if ($filterSql) {
                $quotedJoinTable .= ' ' . $joinTargetEntitySQL;
                $whereClauses[]   = $filterSql;
            }
        }

        return [$quotedJoinTable, $whereClauses, $params, $types];
    }

    /**
     * Expands Criteria Parameters by walking the expressions and grabbing all
     * parameters and types from it.
     *
     * @return mixed[][]
     */
    private function expandCriteriaParameters(Criteria $criteria): array
    {
        $expression = $criteria->getWhereExpression();

        if ($expression === null) {
            return [];
        }

        $valueVisitor = new SqlValueVisitor();

        $valueVisitor->dispatch($expression);

        [, $types] = $valueVisitor->getParamsAndTypes();

        return $types;
    }

    private function getOrderingSql(Criteria $criteria, ClassMetadata $targetClass): string
    {
        $orderings = $criteria->getOrderings();
        if ($orderings) {
            $orderBy = [];
            foreach ($orderings as $name => $direction) {
                $field     = $this->quoteStrategy->getColumnName(
                    $name,
                    $targetClass,
                    $this->platform
                );
                $orderBy[] = $field . ' ' . $direction;
            }

            return ' ORDER BY ' . implode(', ', $orderBy);
        }

        return '';
    }

    /** @throws DBALException */
    private function getLimitSql(Criteria $criteria): string
    {
        $limit  = $criteria->getMaxResults();
        $offset = $criteria->getFirstResult();

        return $this->platform->modifyLimitQuery('', $limit, $offset ?? 0);
    }
}
