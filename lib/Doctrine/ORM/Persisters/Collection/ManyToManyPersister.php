<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Persisters\Collection;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\SqlExpressionVisitor;
use Doctrine\ORM\Persisters\SqlValueVisitor;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Query;

/**
 * Persister for many-to-many collections.
 *
 * @author  Roman Borschel <roman@code-factory.org>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Alexander <iam.asm89@gmail.com>
 * @since   2.0
 */
class ManyToManyPersister extends AbstractCollectionPersister
{
    /**
     * {@inheritdoc}
     */
    public function delete(PersistentCollection $collection)
    {
        $mapping = $collection->getMapping();

        if ( ! $mapping['isOwningSide']) {
            return; // ignore inverse side
        }

        $this->conn->executeUpdate($this->getDeleteSQL($collection), $this->getDeleteSQLParameters($collection));
    }

    /**
     * {@inheritdoc}
     */
    public function update(PersistentCollection $collection)
    {
        $mapping = $collection->getMapping();

        if ( ! $mapping['isOwningSide']) {
            return; // ignore inverse side
        }

        $insertSql = $this->getInsertRowSQL($collection);
        $deleteSql = $this->getDeleteRowSQL($collection);

        foreach ($collection->getDeleteDiff() as $element) {
            $this->conn->executeUpdate($deleteSql, $this->getDeleteRowSQLParameters($collection, $element));
        }

        foreach ($collection->getInsertDiff() as $element) {
            $this->conn->executeUpdate($insertSql, $this->getInsertRowSQLParameters($collection, $element));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get(PersistentCollection $collection, $index)
    {
        $mapping = $collection->getMapping();

        if ( ! isset($mapping['indexBy'])) {
            throw new \BadMethodCallException("Selecting a collection by index is only supported on indexed collections.");
        }

        $persister = $this->uow->getEntityPersister($mapping['targetEntity']);
        $mappedKey = $mapping['isOwningSide']
            ? $mapping['inversedBy']
            : $mapping['mappedBy'];

        return $persister->load(array($mappedKey => $collection->getOwner(), $mapping['indexBy'] => $index), null, $mapping, array(), 0, 1);
    }

    /**
     * {@inheritdoc}
     */
    public function count(PersistentCollection $collection)
    {
        $conditions     = array();
        $params         = array();
        $mapping        = $collection->getMapping();
        $id             = $this->uow->getEntityIdentifier($collection->getOwner());
        $sourceClass    = $this->em->getClassMetadata($mapping['sourceEntity']);
        $targetClass    = $this->em->getClassMetadata($mapping['targetEntity']);
        $association    = ( ! $mapping['isOwningSide'])
            ? $targetClass->associationMappings[$mapping['mappedBy']]
            : $mapping;

        $joinTableName  = $this->quoteStrategy->getJoinTableName($association, $sourceClass, $this->platform);
        $joinColumns    = ( ! $mapping['isOwningSide'])
            ? $association['joinTable']['inverseJoinColumns']
            : $association['joinTable']['joinColumns'];

        foreach ($joinColumns as $joinColumn) {
            $columnName     = $this->quoteStrategy->getJoinColumnName($joinColumn, $sourceClass, $this->platform);
            $referencedName = $joinColumn['referencedColumnName'];
            $conditions[]   = 't.' . $columnName . ' = ?';
            $params[]       = $id[$sourceClass->getFieldForColumn($referencedName)];
        }

        list($joinTargetEntitySQL, $filterSql) = $this->getFilterSql($mapping);

        if ($filterSql) {
            $conditions[] = $filterSql;
        }

        // If there is a provided criteria, make part of conditions
        // @todo Fix this. Current SQL returns something like:
        //
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

        return $this->conn->fetchColumn($sql, $params);
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
     * {@inheritdoc}
     */
    public function containsKey(PersistentCollection $collection, $key)
    {
        $mapping = $collection->getMapping();

        if ( ! isset($mapping['indexBy'])) {
            throw new \BadMethodCallException("Selecting a collection by index is only supported on indexed collections.");
        }

        list($quotedJoinTable, $whereClauses, $params) = $this->getJoinTableRestrictionsWithKey($collection, $key, true);

        $sql = 'SELECT 1 FROM ' . $quotedJoinTable . ' WHERE ' . implode(' AND ', $whereClauses);

        return (bool) $this->conn->fetchColumn($sql, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function contains(PersistentCollection $collection, $element)
    {
        if ( ! $this->isValidEntityState($element)) {
            return false;
        }

        list($quotedJoinTable, $whereClauses, $params) = $this->getJoinTableRestrictions($collection, $element, true);

        $sql = 'SELECT 1 FROM ' . $quotedJoinTable . ' WHERE ' . implode(' AND ', $whereClauses);

        return (bool) $this->conn->fetchColumn($sql, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function removeElement(PersistentCollection $collection, $element)
    {
        if ( ! $this->isValidEntityState($element)) {
            return false;
        }

        list($quotedJoinTable, $whereClauses, $params) = $this->getJoinTableRestrictions($collection, $element, false);

        $sql = 'DELETE FROM ' . $quotedJoinTable . ' WHERE ' . implode(' AND ', $whereClauses);

        return (bool) $this->conn->executeUpdate($sql, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function loadCriteria(PersistentCollection $collection, Criteria $criteria)
    {
        $mapping       = $collection->getMapping();
        $owner         = $collection->getOwner();
        $ownerMetadata = $this->em->getClassMetadata(get_class($owner));
        $whereClauses  = $params = array();

        foreach ($mapping['relationToSourceKeyColumns'] as $key => $value) {
            $whereClauses[] = sprintf('t.%s = ?', $key);
            $params[]       = $ownerMetadata->getFieldValue($owner, $value);
        }

        $parameters = $this->expandCriteriaParameters($criteria);

        foreach ($parameters as $parameter) {
            list($name, $value) = $parameter;
            $whereClauses[]     = sprintf('te.%s = ?', $name);
            $params[]           = $value;
        }

        $mapping      = $collection->getMapping();
        $targetClass  = $this->em->getClassMetadata($mapping['targetEntity']);
        $tableName    = $this->quoteStrategy->getTableName($targetClass, $this->platform);
        $joinTable    = $this->quoteStrategy->getJoinTableName($mapping, $ownerMetadata, $this->platform);
        $onConditions = $this->getOnConditionSQL($mapping);

        $rsm = new Query\ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata($mapping['targetEntity'], 'te');

        $sql = 'SELECT ' . $rsm->generateSelectClause()
            . ' FROM ' . $tableName . ' te'
            . ' JOIN ' . $joinTable  . ' t ON'
            . implode(' AND ', $onConditions)
            . ' WHERE ' . implode(' AND ', $whereClauses);

        $stmt = $this->conn->executeQuery($sql, $params);

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
     * @param array $mapping Array containing mapping information.
     *
     * @return string[] ordered tuple:
     *                   - JOIN condition to add to the SQL
     *                   - WHERE condition to add to the SQL
     */
    public function getFilterSql($mapping)
    {
        $targetClass = $this->em->getClassMetadata($mapping['targetEntity']);
        $rootClass   = $this->em->getClassMetadata($targetClass->rootEntityName);
        $filterSql   = $this->generateFilterConditionSQL($rootClass, 'te');

        if ('' === $filterSql) {
            return array('', '');
        }

        // A join is needed if there is filtering on the target entity
        $tableName = $this->quoteStrategy->getTableName($rootClass, $this->platform);
        $joinSql   = ' JOIN ' . $tableName . ' te'
            . ' ON' . implode(' AND ', $this->getOnConditionSQL($mapping));

        return array($joinSql, $filterSql);
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
        $filterClauses = array();

        foreach ($this->em->getFilters()->getEnabledFilters() as $filter) {
            if ($filterExpr = $filter->addFilterConstraint($targetEntity, $targetTableAlias)) {
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
     * @param  array $mapping
     *
     * @return array
     */
    protected function getOnConditionSQL($mapping)
    {
        $targetClass = $this->em->getClassMetadata($mapping['targetEntity']);
        $association = ( ! $mapping['isOwningSide'])
            ? $targetClass->associationMappings[$mapping['mappedBy']]
            : $mapping;

        $joinColumns = $mapping['isOwningSide']
            ? $association['joinTable']['inverseJoinColumns']
            : $association['joinTable']['joinColumns'];

        $conditions = array();

        foreach ($joinColumns as $joinColumn) {
            $joinColumnName = $this->quoteStrategy->getJoinColumnName($joinColumn, $targetClass, $this->platform);
            $refColumnName  = $this->quoteStrategy->getReferencedJoinColumnName($joinColumn, $targetClass, $this->platform);

            $conditions[] = ' t.' . $joinColumnName . ' = ' . 'te.' . $refColumnName;
        }

        return $conditions;
    }

    /**
     * {@inheritdoc}
     *
     * @override
     */
    protected function getDeleteSQL(PersistentCollection $collection)
    {
        $columns    = array();
        $mapping    = $collection->getMapping();
        $class      = $this->em->getClassMetadata(get_class($collection->getOwner()));
        $joinTable  = $this->quoteStrategy->getJoinTableName($mapping, $class, $this->platform);

        foreach ($mapping['joinTable']['joinColumns'] as $joinColumn) {
            $columns[] = $this->quoteStrategy->getJoinColumnName($joinColumn, $class, $this->platform);
        }

        return 'DELETE FROM ' . $joinTable
            . ' WHERE ' . implode(' = ? AND ', $columns) . ' = ?';
    }

    /**
     * {@inheritdoc}
     *
     * @override
     *
     * @internal Order of the parameters must be the same as the order of the columns in getDeleteSql.
     */
    protected function getDeleteSQLParameters(PersistentCollection $collection)
    {
        $mapping    = $collection->getMapping();
        $identifier = $this->uow->getEntityIdentifier($collection->getOwner());

        // Optimization for single column identifier
        if (count($mapping['relationToSourceKeyColumns']) === 1) {
            return array(reset($identifier));
        }

        // Composite identifier
        $sourceClass = $this->em->getClassMetadata($mapping['sourceEntity']);
        $params      = array();

        foreach ($mapping['relationToSourceKeyColumns'] as $columnName => $refColumnName) {
            $params[] = isset($sourceClass->fieldNames[$refColumnName])
                ? $identifier[$sourceClass->fieldNames[$refColumnName]]
                : $identifier[$sourceClass->getFieldForColumn($columnName)];
        }

        return $params;
    }

    /**
     * Gets the SQL statement used for deleting a row from the collection.
     *
     * @param \Doctrine\ORM\PersistentCollection $collection
     *
     * @return string
     */
    protected function getDeleteRowSQL(PersistentCollection $collection)
    {
        $mapping = $collection->getMapping();
        $class   = $this->em->getClassMetadata($mapping['sourceEntity']);
        $columns = array();

        foreach ($mapping['joinTable']['joinColumns'] as $joinColumn) {
            $columns[] = $this->quoteStrategy->getJoinColumnName($joinColumn, $class, $this->platform);
        }

        foreach ($mapping['joinTable']['inverseJoinColumns'] as $joinColumn) {
            $columns[] = $this->quoteStrategy->getJoinColumnName($joinColumn, $class, $this->platform);
        }

        return 'DELETE FROM ' . $this->quoteStrategy->getJoinTableName($mapping, $class, $this->platform)
            . ' WHERE ' . implode(' = ? AND ', $columns) . ' = ?';
    }

    /**
     * Gets the SQL parameters for the corresponding SQL statement to delete the given
     * element from the given collection.
     *
     * @internal Order of the parameters must be the same as the order of the columns in getDeleteRowSql.
     *
     * @param \Doctrine\ORM\PersistentCollection $collection
     * @param mixed                              $element
     *
     * @return array
     */
    protected function getDeleteRowSQLParameters(PersistentCollection $collection, $element)
    {
        return $this->collectJoinTableColumnParameters($collection, $element);
    }

    /**
     * Gets the SQL statement used for inserting a row in the collection.
     *
     * @param \Doctrine\ORM\PersistentCollection $collection
     *
     * @return string
     */
    protected function getInsertRowSQL(PersistentCollection $collection)
    {
        $columns = array();
        $mapping = $collection->getMapping();
        $class   = $this->em->getClassMetadata($mapping['sourceEntity']);

        foreach ($mapping['joinTable']['joinColumns'] as $joinColumn) {
            $columns[] = $this->quoteStrategy->getJoinColumnName($joinColumn, $class, $this->platform);
        }

        foreach ($mapping['joinTable']['inverseJoinColumns'] as $joinColumn) {
            $columns[] = $this->quoteStrategy->getJoinColumnName($joinColumn, $class, $this->platform);
        }

        return 'INSERT INTO ' . $this->quoteStrategy->getJoinTableName($mapping, $class, $this->platform)
            . ' (' . implode(', ', $columns) . ')'
            . ' VALUES'
            . ' (' . implode(', ', array_fill(0, count($columns), '?')) . ')';
    }

    /**
     * Gets the SQL parameters for the corresponding SQL statement to insert the given
     * element of the given collection into the database.
     *
     * @internal Order of the parameters must be the same as the order of the columns in getInsertRowSql.
     *
     * @param \Doctrine\ORM\PersistentCollection $collection
     * @param mixed                              $element
     *
     * @return array
     */
    protected function getInsertRowSQLParameters(PersistentCollection $collection, $element)
    {
        return $this->collectJoinTableColumnParameters($collection, $element);
    }

    /**
     * Collects the parameters for inserting/deleting on the join table in the order
     * of the join table columns as specified in ManyToManyMapping#joinTableColumns.
     *
     * @param \Doctrine\ORM\PersistentCollection $collection
     * @param object                             $element
     *
     * @return array
     */
    private function collectJoinTableColumnParameters(PersistentCollection $collection, $element)
    {
        $params      = array();
        $mapping     = $collection->getMapping();
        $isComposite = count($mapping['joinTableColumns']) > 2;

        $identifier1 = $this->uow->getEntityIdentifier($collection->getOwner());
        $identifier2 = $this->uow->getEntityIdentifier($element);

        if ($isComposite) {
            $class1 = $this->em->getClassMetadata(get_class($collection->getOwner()));
            $class2 = $collection->getTypeClass();
        }

        foreach ($mapping['joinTableColumns'] as $joinTableColumn) {
            $isRelationToSource = isset($mapping['relationToSourceKeyColumns'][$joinTableColumn]);

            if ( ! $isComposite) {
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
     * @param \Doctrine\ORM\PersistentCollection $collection
     * @param string                             $key
     * @param boolean                            $addFilters Whether the filter SQL should be included or not.
     *
     * @return array
     */
    private function getJoinTableRestrictionsWithKey(PersistentCollection $collection, $key, $addFilters)
    {
        $filterMapping  = $collection->getMapping();
        $mapping        = $filterMapping;
        $indexBy        = $mapping['indexBy'];
        $id             = $this->uow->getEntityIdentifier($collection->getOwner());

        $targetEntity   = $this->em->getClassMetadata($mapping['targetEntity']);

        if (! $mapping['isOwningSide']) {
            $associationSourceClass = $this->em->getClassMetadata($mapping['targetEntity']);
            $mapping  = $associationSourceClass->associationMappings[$mapping['mappedBy']];
            $joinColumns = $mapping['joinTable']['joinColumns'];
            $relationMode = 'relationToTargetKeyColumns';
        } else {
            $joinColumns = $mapping['joinTable']['inverseJoinColumns'];
            $associationSourceClass = $this->em->getClassMetadata($mapping['sourceEntity']);
            $relationMode = 'relationToSourceKeyColumns';
        }

        $quotedJoinTable = $this->quoteStrategy->getJoinTableName($mapping, $associationSourceClass, $this->platform). ' t';
        $whereClauses    = array();
        $params          = array();

        $joinNeeded = !in_array($indexBy, $targetEntity->identifier);

        if ($joinNeeded) { // extra join needed if indexBy is not a @id
            $joinConditions = array();

            foreach ($joinColumns as $joinTableColumn) {
                $joinConditions[] = 't.' . $joinTableColumn['name'] . ' = tr.' . $joinTableColumn['referencedColumnName'];
            }
            $tableName = $this->quoteStrategy->getTableName($targetEntity, $this->platform);
            $quotedJoinTable .= ' JOIN ' . $tableName . ' tr ON ' . implode(' AND ', $joinConditions);

            $whereClauses[] = 'tr.' . $targetEntity->getColumnName($indexBy) . ' = ?';
            $params[] = $key;

        }

        foreach ($mapping['joinTableColumns'] as $joinTableColumn) {
            if (isset($mapping[$relationMode][$joinTableColumn])) {
                $whereClauses[] = 't.' . $joinTableColumn . ' = ?';
                $params[] = $id[$targetEntity->getFieldForColumn($mapping[$relationMode][$joinTableColumn])];
            } elseif (!$joinNeeded) {
                $whereClauses[] = 't.' . $joinTableColumn . ' = ?';
                $params[] = $key;
            }
        }

        if ($addFilters) {
            list($joinTargetEntitySQL, $filterSql) = $this->getFilterSql($filterMapping);

            if ($filterSql) {
                $quotedJoinTable .= ' ' . $joinTargetEntitySQL;
                $whereClauses[] = $filterSql;
            }
        }

        return array($quotedJoinTable, $whereClauses, $params);
    }

    /**
     * @param \Doctrine\ORM\PersistentCollection $collection
     * @param object                             $element
     * @param boolean                            $addFilters Whether the filter SQL should be included or not.
     *
     * @return array
     */
    private function getJoinTableRestrictions(PersistentCollection $collection, $element, $addFilters)
    {
        $filterMapping  = $collection->getMapping();
        $mapping        = $filterMapping;

        if ( ! $mapping['isOwningSide']) {
            $sourceClass = $this->em->getClassMetadata($mapping['targetEntity']);
            $targetClass = $this->em->getClassMetadata($mapping['sourceEntity']);
            $sourceId = $this->uow->getEntityIdentifier($element);
            $targetId = $this->uow->getEntityIdentifier($collection->getOwner());

            $mapping = $sourceClass->associationMappings[$mapping['mappedBy']];
        } else {
            $sourceClass = $this->em->getClassMetadata($mapping['sourceEntity']);
            $targetClass = $this->em->getClassMetadata($mapping['targetEntity']);
            $sourceId = $this->uow->getEntityIdentifier($collection->getOwner());
            $targetId = $this->uow->getEntityIdentifier($element);
        }

        $quotedJoinTable = $this->quoteStrategy->getJoinTableName($mapping, $sourceClass, $this->platform);
        $whereClauses    = array();
        $params          = array();

        foreach ($mapping['joinTableColumns'] as $joinTableColumn) {
            $whereClauses[] = ($addFilters ? 't.' : '') . $joinTableColumn . ' = ?';

            if (isset($mapping['relationToTargetKeyColumns'][$joinTableColumn])) {
                $params[] = $targetId[$targetClass->getFieldForColumn($mapping['relationToTargetKeyColumns'][$joinTableColumn])];

                continue;
            }

            // relationToSourceKeyColumns
            $params[] = $sourceId[$sourceClass->getFieldForColumn($mapping['relationToSourceKeyColumns'][$joinTableColumn])];
        }

        if ($addFilters) {
            $quotedJoinTable .= ' t';

            list($joinTargetEntitySQL, $filterSql) = $this->getFilterSql($filterMapping);

            if ($filterSql) {
                $quotedJoinTable .= ' ' . $joinTargetEntitySQL;
                $whereClauses[] = $filterSql;
            }
        }

        return array($quotedJoinTable, $whereClauses, $params);
    }

    /**
     * Expands Criteria Parameters by walking the expressions and grabbing all
     * parameters and types from it.
     *
     * @param \Doctrine\Common\Collections\Criteria $criteria
     *
     * @return array
     */
    private function expandCriteriaParameters(Criteria $criteria)
    {
        $expression = $criteria->getWhereExpression();

        if ($expression === null) {
            return array();
        }

        $valueVisitor = new SqlValueVisitor();

        $valueVisitor->dispatch($expression);

        list($values, $types) = $valueVisitor->getParamsAndTypes();

        return $types;
    }
}
