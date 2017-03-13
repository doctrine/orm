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
use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ManyToManyAssociationMetadata;
use Doctrine\ORM\Mapping\ToManyAssociationMetadata;
use Doctrine\ORM\Persisters\SqlValueVisitor;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Query;
use Doctrine\ORM\Utility\PersisterHelper;

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
        $association = $collection->getMapping();

        if (! $association->isOwningSide()) {
            return; // ignore inverse side
        }

        $class     = $this->em->getClassMetadata($association->getSourceEntity());
        $joinTable = $association->getJoinTable();
        $types     = [];

        foreach ($joinTable->getJoinColumns() as $joinColumn) {
            $types[] = PersisterHelper::getTypeOfColumn($joinColumn->getReferencedColumnName(), $class, $this->em);
        }

        $sql    = $this->getDeleteSQL($collection);
        $params = $this->getDeleteSQLParameters($collection);

        $this->conn->executeUpdate($sql, $params, $types);
    }

    /**
     * {@inheritdoc}
     */
    public function update(PersistentCollection $collection)
    {
        $association = $collection->getMapping();

        if (! $association->isOwningSide()) {
            return; // ignore inverse side
        }

        list($deleteSql, $deleteTypes) = $this->getDeleteRowSQL($collection);
        list($insertSql, $insertTypes) = $this->getInsertRowSQL($collection);

        foreach ($collection->getDeleteDiff() as $element) {
            $this->conn->executeUpdate(
                $deleteSql,
                $this->getDeleteRowSQLParameters($collection, $element),
                $deleteTypes
            );
        }

        foreach ($collection->getInsertDiff() as $element) {
            $this->conn->executeUpdate(
                $insertSql,
                $this->getInsertRowSQLParameters($collection, $element),
                $insertTypes
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get(PersistentCollection $collection, $index)
    {
        $association = $collection->getMapping();

        if (! ($association instanceof ToManyAssociationMetadata && $association->getIndexedBy())) {
            throw new \BadMethodCallException("Selecting a collection by index is only supported on indexed collections.");
        }

        $persister = $this->uow->getEntityPersister($association->getTargetEntity());
        $mappedKey = $association->isOwningSide()
            ? $association->getInversedBy()
            : $association->getMappedBy()
        ;

        $criteria  = [
            $mappedKey                   => $collection->getOwner(),
            $association->getIndexedBy() => $index,
        ];

        return $persister->load($criteria, null, $association, [], 0, 1);
    }

    /**
     * {@inheritdoc}
     */
    public function count(PersistentCollection $collection)
    {
        $conditions        = [];
        $params            = [];
        $types             = [];
        $association       = $collection->getMapping();
        $id                = $this->uow->getEntityIdentifier($collection->getOwner());
        $sourceClass       = $this->em->getClassMetadata($association->getSourceEntity());
        $targetClass       = $this->em->getClassMetadata($association->getTargetEntity());
        $owningAssociation = ! $association->isOwningSide()
            ? $targetClass->associationMappings[$association->getMappedBy()]
            : $association
        ;

        $joinTable      = $owningAssociation->getJoinTable();
        $joinTableName  = $joinTable->getQuotedQualifiedName($this->platform);
        $joinColumns    = $association->isOwningSide()
            ? $joinTable->getJoinColumns()
            : $joinTable->getInverseJoinColumns()
        ;

        foreach ($joinColumns as $joinColumn) {
            $quotedColumnName = $this->platform->quoteIdentifier($joinColumn->getColumnName());
            $referencedName   = $joinColumn->getReferencedColumnName();

            if (! $joinColumn->getType()) {
                $joinColumn->setType(PersisterHelper::getTypeOfColumn($referencedName, $sourceClass, $this->em));
            }

            $conditions[]   = 't.' . $quotedColumnName . ' = ?';
            $params[]       = $id[$sourceClass->fieldNames[$referencedName]];
            $types[]        = $joinColumn->getType();
        }

        list($joinTargetEntitySQL, $filterSql) = $this->getFilterSql($association);

        if ($filterSql) {
            $conditions[] = $filterSql;
        }

        // If there is a provided criteria, make part of conditions
        // @todo Fix this. Current SQL returns something like:
        //
        /*if ($criteria && ($expression = $criteria->getWhereExpression()) !== null) {
            // A join is needed on the target entity
            $targetTableName = $targetClass->table->getQuotedQualifiedName($this->platform);
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

        return $this->conn->fetchColumn($sql, $params, 0, $types);
    }

    /**
     * {@inheritDoc}
     */
    public function slice(PersistentCollection $collection, $offset, $length = null)
    {
        $association = $collection->getMapping();
        $persister   = $this->uow->getEntityPersister($association->getTargetEntity());

        return $persister->getManyToManyCollection($association, $collection->getOwner(), $offset, $length);
    }
    /**
     * {@inheritdoc}
     */
    public function containsKey(PersistentCollection $collection, $key)
    {
        $association = $collection->getMapping();

        if (! ($association instanceof ToManyAssociationMetadata && $association->getIndexedBy())) {
            throw new \BadMethodCallException("Selecting a collection by index is only supported on indexed collections.");
        }

        list($quotedJoinTable, $whereClauses, $params, $types) = $this->getJoinTableRestrictionsWithKey($collection, $key, true);

        $sql = 'SELECT 1 FROM ' . $quotedJoinTable . ' WHERE ' . implode(' AND ', $whereClauses);

        return (bool) $this->conn->fetchColumn($sql, $params, 0, $types);
    }

    /**
     * {@inheritDoc}
     */
    public function contains(PersistentCollection $collection, $element)
    {
        if ( ! $this->isValidEntityState($element)) {
            return false;
        }

        list($quotedJoinTable, $whereClauses, $params, $types) = $this->getJoinTableRestrictions($collection, $element, true);

        $sql = 'SELECT 1 FROM ' . $quotedJoinTable . ' WHERE ' . implode(' AND ', $whereClauses);

        return (bool) $this->conn->fetchColumn($sql, $params, 0, $types);
    }

    /**
     * {@inheritDoc}
     */
    public function removeElement(PersistentCollection $collection, $element)
    {
        if ( ! $this->isValidEntityState($element)) {
            return false;
        }

        list($quotedJoinTable, $whereClauses, $params, $types) = $this->getJoinTableRestrictions($collection, $element, false);

        $sql = 'DELETE FROM ' . $quotedJoinTable . ' WHERE ' . implode(' AND ', $whereClauses);

        return (bool) $this->conn->executeUpdate($sql, $params, $types);
    }

    /**
     * {@inheritDoc}
     */
    public function loadCriteria(PersistentCollection $collection, Criteria $criteria)
    {
        $association   = $collection->getMapping();
        $owner         = $collection->getOwner();
        $ownerMetadata = $this->em->getClassMetadata(get_class($owner));
        $identifier    = $this->uow->getEntityIdentifier($owner);
        $targetClass   = $this->em->getClassMetadata($association->getTargetEntity());
        $onConditions  = $this->getOnConditionSQL($association);
        $whereClauses  = $params = $types = [];

        if (! $association->isOwningSide()) {
            $association = $targetClass->associationMappings[$association->getMappedBy()];
            $joinColumns = $association->getJoinTable()->getInverseJoinColumns();
        } else {
            $joinColumns = $association->getJoinTable()->getJoinColumns();
        }

        foreach ($joinColumns as $joinColumn) {
            $quotedColumnName = $this->platform->quoteIdentifier($joinColumn->getColumnName());
            $referencedName   = $joinColumn->getReferencedColumnName();

            if (! $joinColumn->getType()) {
                $joinColumn->setType(
                    PersisterHelper::getTypeOfColumn($referencedName, $ownerMetadata, $this->em)
                );
            }

            $whereClauses[] = sprintf('t.%s = ?', $quotedColumnName);
            $params[] = $identifier[$ownerMetadata->fieldNames[$referencedName]];
            $types[] = $joinColumn->getType();
        }

        $parameters = $this->expandCriteriaParameters($criteria);

        foreach ($parameters as $parameter) {
            list($name, $value) = $parameter;

            $property   = $targetClass->getProperty($name);
            $columnName = $this->platform->quoteIdentifier($property->getColumnName());

            $whereClauses[] = sprintf('te.%s = ?', $columnName);
            $params[]       = $value;
            $types[]        = $property->getType();
        }

        $tableName        = $targetClass->table->getQuotedQualifiedName($this->platform);
        $joinTableName    = $association->getJoinTable()->getQuotedQualifiedName($this->platform);
        $resultSetMapping = new Query\ResultSetMappingBuilder($this->em);

        $resultSetMapping->addRootEntityFromClassMetadata($targetClass->name, 'te');

        $sql = 'SELECT ' . $resultSetMapping->generateSelectClause()
            . ' FROM ' . $tableName . ' te'
            . ' JOIN ' . $joinTableName . ' t ON'
            . implode(' AND ', $onConditions)
            . ' WHERE ' . implode(' AND ', $whereClauses);

        $sql .= $this->getOrderingSql($criteria, $targetClass);
        $sql .= $this->getLimitSql($criteria);

        $stmt = $this->conn->executeQuery($sql, $params, $types);

        return $this->em->newHydrator(Query::HYDRATE_OBJECT)->hydrateAll($stmt, $resultSetMapping);
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
     * @param ManyToManyAssociationMetadata $association
     *
     * @return string[] ordered tuple:
     *                   - JOIN condition to add to the SQL
     *                   - WHERE condition to add to the SQL
     */
    public function getFilterSql(ManyToManyAssociationMetadata $association)
    {
        $targetClass = $this->em->getClassMetadata($association->getTargetEntity());
        $rootClass   = $this->em->getClassMetadata($targetClass->rootEntityName);
        $filterSql   = $this->generateFilterConditionSQL($rootClass, 'te');

        if ('' === $filterSql) {
            return ['', ''];
        }

        // A join is needed if there is filtering on the target entity
        $tableName = $rootClass->table->getQuotedQualifiedName($this->platform);
        $joinSql   = ' JOIN ' . $tableName . ' te'
            . ' ON' . implode(' AND ', $this->getOnConditionSQL($association));

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
            if ($filterExpr = $filter->addFilterConstraint($targetEntity, $targetTableAlias)) {
                $filterClauses[] = '(' . $filterExpr . ')';
            }
        }
        
        if (! $filterClauses) {
            return '';
        }

        $filterSql = implode(' AND ', $filterClauses);

        return count($filterClauses) > 1
            ? '(' . $filterSql . ')'
            : $filterSql
        ;
    }

    /**
     * Generate ON condition
     *
     * @param ManyToManyAssociationMetadata $association
     *
     * @return array
     */
    protected function getOnConditionSQL(ManyToManyAssociationMetadata $association)
    {
        $targetClass       = $this->em->getClassMetadata($association->getTargetEntity());
        $owningAssociation = ! $association->isOwningSide()
            ? $targetClass->associationMappings[$association->getMappedBy()]
            : $association;

        $joinTable   = $owningAssociation->getJoinTable();
        $joinColumns = $association->isOwningSide()
            ? $joinTable->getInverseJoinColumns()
            : $joinTable->getJoinColumns()
        ;

        $conditions = [];

        foreach ($joinColumns as $joinColumn) {
            $quotedColumnName           = $this->platform->quoteIdentifier($joinColumn->getColumnName());
            $quotedReferencedColumnName = $this->platform->quoteIdentifier($joinColumn->getReferencedColumnName());

            $conditions[] = ' t.' . $quotedColumnName . ' = ' . 'te.' . $quotedReferencedColumnName;
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
        $association   = $collection->getMapping();
        $joinTable     = $association->getJoinTable();
        $joinTableName = $joinTable->getQuotedQualifiedName($this->platform);
        $columns       = [];

        foreach ($joinTable->getJoinColumns() as $joinColumn) {
            $columns[] = $this->platform->quoteIdentifier($joinColumn->getColumnName());
        }

        return 'DELETE FROM ' . $joinTableName . ' WHERE ' . implode(' = ? AND ', $columns) . ' = ?';
    }

    /**
     * {@inheritdoc}
     *
     * Internal note: Order of the parameters must be the same as the order of the columns in getDeleteSql.
     * @override
     */
    protected function getDeleteSQLParameters(PersistentCollection $collection)
    {
        $association = $collection->getMapping();
        $identifier  = $this->uow->getEntityIdentifier($collection->getOwner());
        $joinTable   = $association->getJoinTable();
        $joinColumns = $joinTable->getJoinColumns();

        // Optimization for single column identifier
        if (count($joinColumns) === 1) {
            return [reset($identifier)];
        }

        // Composite identifier
        $sourceClass = $this->em->getClassMetadata($association->getSourceEntity());
        $params      = [];

        foreach ($joinColumns as $joinColumn) {
            $params[] = $identifier[$sourceClass->fieldNames[$joinColumn->getReferencedColumnName()]];
        }

        return $params;
    }

    /**
     * Gets the SQL statement used for deleting a row from the collection.
     *
     * @param \Doctrine\ORM\PersistentCollection $collection
     *
     * @return string[]|string[][] ordered tuple containing the SQL to be executed and an array
     *                             of types for bound parameters
     */
    protected function getDeleteRowSQL(PersistentCollection $collection)
    {
        $association = $collection->getMapping();
        $class       = $this->em->getClassMetadata($association->getSourceEntity());
        $targetClass = $this->em->getClassMetadata($association->getTargetEntity());
        $columns     = [];
        $types       = [];

        $joinTable     = $association->getJoinTable();
        $joinTableName = $joinTable->getQuotedQualifiedName($this->platform);

        foreach ($joinTable->getJoinColumns() as $joinColumn) {
            $columns[] = $this->platform->quoteIdentifier($joinColumn->getColumnName());
            $types[]   = PersisterHelper::getTypeOfColumn($joinColumn->getReferencedColumnName(), $class, $this->em);
        }

        foreach ($joinTable->getInverseJoinColumns() as $joinColumn) {
            $columns[] = $this->platform->quoteIdentifier($joinColumn->getColumnName());
            $types[]   = PersisterHelper::getTypeOfColumn($joinColumn->getReferencedColumnName(), $targetClass, $this->em);
        }

        return [
            sprintf('DELETE FROM %s WHERE %s = ?', $joinTableName, implode(' = ? AND ', $columns)),
            $types,
        ];
    }

    /**
     * Gets the SQL parameters for the corresponding SQL statement to delete the given
     * element from the given collection.
     *
     * Internal note: Order of the parameters must be the same as the order of the columns in getDeleteRowSql.
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
     * @return string[]|string[][] ordered tuple containing the SQL to be executed and an array
     *                             of types for bound parameters
     */
    protected function getInsertRowSQL(PersistentCollection $collection)
    {
        $association = $collection->getMapping();
        $class       = $this->em->getClassMetadata($association->getSourceEntity());
        $targetClass = $this->em->getClassMetadata($association->getTargetEntity());
        $columns     = [];
        $types       = [];

        $joinTable     = $association->getJoinTable();
        $joinTableName = $joinTable->getQuotedQualifiedName($this->platform);

        foreach ($joinTable->getJoinColumns() as $joinColumn) {
            $columns[] = $this->platform->quoteIdentifier($joinColumn->getColumnName());
            $types[]   = PersisterHelper::getTypeOfColumn($joinColumn->getReferencedColumnName(), $class, $this->em);
        }

        foreach ($joinTable->getInverseJoinColumns() as $joinColumn) {
            $columns[] = $this->platform->quoteIdentifier($joinColumn->getColumnName());
            $types[]   = PersisterHelper::getTypeOfColumn($joinColumn->getReferencedColumnName(), $targetClass, $this->em);
        }

        $columnNamesAsString  = implode(', ', $columns);
        $columnValuesAsString = implode(', ', array_fill(0, count($columns), '?'));

        return [
            sprintf('INSERT INTO %s (%s) VALUES (%s)', $joinTableName, $columnNamesAsString, $columnValuesAsString),
            $types,
        ];
    }

    /**
     * Gets the SQL parameters for the corresponding SQL statement to insert the given
     * element of the given collection into the database.
     *
     * Internal note: Order of the parameters must be the same as the order of the columns in getInsertRowSql.
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
     * of the join table columns.
     *
     * @param \Doctrine\ORM\PersistentCollection $collection
     * @param object                             $element
     *
     * @return array
     */
    private function collectJoinTableColumnParameters(PersistentCollection $collection, $element)
    {
        $params           = [];
        $association      = $collection->getMapping();
        $owningClass      = $this->em->getClassMetadata(get_class($collection->getOwner()));
        $targetClass      = $collection->getTypeClass();
        $owningIdentifier = $this->uow->getEntityIdentifier($collection->getOwner());
        $targetIdentifier = $this->uow->getEntityIdentifier($element);
        $joinTable        = $association->getJoinTable();

        foreach ($joinTable->getJoinColumns() as $joinColumn) {
            $fieldName = $owningClass->fieldNames[$joinColumn->getReferencedColumnName()];

            $params[] = $owningIdentifier[$fieldName];
        }

        foreach ($joinTable->getInverseJoinColumns() as $joinColumn) {
            $fieldName = $targetClass->fieldNames[$joinColumn->getReferencedColumnName()];

            $params[] = $targetIdentifier[$fieldName];
        }

        return $params;
    }

    /**
     * @param \Doctrine\ORM\PersistentCollection $collection
     * @param string                             $key
     * @param boolean                            $addFilters Whether the filter SQL should be included or not.
     *
     * @return array ordered vector:
     *                - quoted join table name
     *                - where clauses to be added for filtering
     *                - parameters to be bound for filtering
     *                - types of the parameters to be bound for filtering
     */
    private function getJoinTableRestrictionsWithKey(PersistentCollection $collection, $key, $addFilters)
    {
        $association       = $collection->getMapping();
        $owningAssociation = $association;
        $indexBy           = $owningAssociation->getIndexedBy();
        $id                = $this->uow->getEntityIdentifier($collection->getOwner());
        $sourceClass       = $this->em->getClassMetadata($owningAssociation->getSourceEntity());
        $targetClass       = $this->em->getClassMetadata($owningAssociation->getTargetEntity());

        if (! $owningAssociation->isOwningSide()) {
            $owningAssociation  = $targetClass->associationMappings[$owningAssociation->getMappedBy()];
            $joinTable          = $owningAssociation->getJoinTable();
            $joinColumns        = $joinTable->getJoinColumns();
            $inverseJoinColumns = $joinTable->getInverseJoinColumns();
        } else {
            $joinTable          = $owningAssociation->getJoinTable();
            $joinColumns        = $joinTable->getInverseJoinColumns();
            $inverseJoinColumns = $joinTable->getJoinColumns();
        }

        $joinTableName   = $joinTable->getQuotedQualifiedName($this->platform);
        $quotedJoinTable = $joinTableName . ' t';
        $whereClauses    = [];
        $params          = [];
        $types           = [];
        $joinNeeded      = ! in_array($indexBy, $targetClass->identifier);

        if ($joinNeeded) { // extra join needed if indexBy is not a @id
            $joinConditions = [];

            foreach ($joinColumns as $joinColumn) {
                $quotedColumnName           = $this->platform->quoteIdentifier($joinColumn->getColumnName());
                $quotedReferencedColumnName = $this->platform->quoteIdentifier($joinColumn->getReferencedColumnName());

                $joinConditions[] = ' t.' . $quotedColumnName . ' = ' . 'tr.' . $quotedReferencedColumnName;
            }

            $tableName        = $targetClass->table->getQuotedQualifiedName($this->platform);
            $quotedJoinTable .= ' JOIN ' . $tableName . ' tr ON ' . implode(' AND ', $joinConditions);
            $columnName       = $targetClass->getColumnName($indexBy);

            $whereClauses[] = 'tr.' . $this->platform->quoteIdentifier($columnName) . ' = ?';
            $params[]       = $key;
            $types[]        = PersisterHelper::getTypeOfColumn($columnName, $targetClass, $this->em);
        }

        foreach ($inverseJoinColumns as $joinColumn) {
            if (! $joinColumn->getType()) {
                $joinColumn->setType(
                    PersisterHelper::getTypeOfColumn($joinColumn->getReferencedColumnName(), $sourceClass, $this->em)
                );
            }

            $whereClauses[] = 't.' . $this->platform->quoteIdentifier($joinColumn->getColumnName()) . ' = ?';
            $params[]       = $id[$sourceClass->fieldNames[$joinColumn->getReferencedColumnName()]];
            $types[]        = $joinColumn->getType();
        }

        if ( ! $joinNeeded) {
            foreach ($joinColumns as $joinColumn) {
                if (! $joinColumn->getType()) {
                    $joinColumn->setType(
                        PersisterHelper::getTypeOfColumn($joinColumn->getReferencedColumnName(), $targetClass, $this->em)
                    );
                }

                $whereClauses[] = 't.' . $this->platform->quoteIdentifier($joinColumn->getColumnName()) . ' = ?';
                $params[]       = $key;
                $types[]        = $joinColumn->getType();
            }
        }

        if ($addFilters) {
            list($joinTargetEntitySQL, $filterSql) = $this->getFilterSql($association);

            if ($filterSql) {
                $quotedJoinTable .= ' ' . $joinTargetEntitySQL;
                $whereClauses[] = $filterSql;
            }
        }

        return [$quotedJoinTable, $whereClauses, $params, $types];
    }

    /**
     * @param \Doctrine\ORM\PersistentCollection $collection
     * @param object                             $element
     * @param boolean                            $addFilters Whether the filter SQL should be included or not.
     *
     * @return array ordered vector:
     *                - quoted join table name
     *                - where clauses to be added for filtering
     *                - parameters to be bound for filtering
     *                - types of the parameters to be bound for filtering
     */
    private function getJoinTableRestrictions(PersistentCollection $collection, $element, $addFilters)
    {
        $association       = $collection->getMapping();
        $owningAssociation = $association;

        if (! $association->isOwningSide()) {
            $sourceClass = $this->em->getClassMetadata($association->getTargetEntity());
            $targetClass = $this->em->getClassMetadata($association->getSourceEntity());
            $sourceId = $this->uow->getEntityIdentifier($element);
            $targetId = $this->uow->getEntityIdentifier($collection->getOwner());

            $owningAssociation = $sourceClass->associationMappings[$association->getMappedBy()];
        } else {
            $sourceClass = $this->em->getClassMetadata($association->getSourceEntity());
            $targetClass = $this->em->getClassMetadata($association->getTargetEntity());
            $sourceId = $this->uow->getEntityIdentifier($collection->getOwner());
            $targetId = $this->uow->getEntityIdentifier($element);
        }

        $joinTable       = $owningAssociation->getJoinTable();
        $joinTableName   = $joinTable->getQuotedQualifiedName($this->platform);
        $quotedJoinTable = $joinTableName;
        $whereClauses    = [];
        $params          = [];
        $types           = [];

        foreach ($joinTable->getJoinColumns() as $joinColumn) {
            $quotedColumnName     = $this->platform->quoteIdentifier($joinColumn->getColumnName());
            $referencedColumnName = $joinColumn->getReferencedColumnName();

            if (! $joinColumn->getType()) {
                $joinColumn->setType(
                    PersisterHelper::getTypeOfColumn($referencedColumnName, $sourceClass, $this->em)
                );
            }

            $whereClauses[] = ($addFilters ? 't.' : '') . $quotedColumnName . ' = ?';
            $params[]       = $sourceId[$sourceClass->fieldNames[$referencedColumnName]];
            $types[]        = $joinColumn->getType();
        }

        foreach ($joinTable->getInverseJoinColumns() as $joinColumn) {
            $quotedColumnName     = $this->platform->quoteIdentifier($joinColumn->getColumnName());
            $referencedColumnName = $joinColumn->getReferencedColumnName();

            if (! $joinColumn->getType()) {
                $joinColumn->setType(
                    PersisterHelper::getTypeOfColumn($referencedColumnName, $targetClass, $this->em)
                );
            }

            $whereClauses[] = ($addFilters ? 't.' : '') . $quotedColumnName . ' = ?';
            $params[]       = $targetId[$targetClass->fieldNames[$referencedColumnName]];
            $types[]        = $joinColumn->getType();
        }

        if ($addFilters) {
            $quotedJoinTable .= ' t';

            list($joinTargetEntitySQL, $filterSql) = $this->getFilterSql($association);

            if ($filterSql) {
                $quotedJoinTable .= ' ' . $joinTargetEntitySQL;
                $whereClauses[] = $filterSql;
            }
        }

        return [$quotedJoinTable, $whereClauses, $params, $types];
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
            return [];
        }

        $valueVisitor = new SqlValueVisitor();

        $valueVisitor->dispatch($expression);

        list(, $types) = $valueVisitor->getParamsAndTypes();

        return $types;
    }

    /**
     * @param Criteria $criteria
     * @param ClassMetadata $targetClass
     * @return string
     */
    private function getOrderingSql(Criteria $criteria, ClassMetadata $targetClass)
    {
        $orderings = $criteria->getOrderings();

        if ($orderings) {
            $orderBy = [];

            foreach ($orderings as $name => $direction) {
                $property   = $targetClass->getProperty($name);
                $columnName = $this->platform->quoteIdentifier($property->getColumnName());

                $orderBy[] = $columnName . ' ' . $direction;
            }

            return ' ORDER BY ' . implode(', ', $orderBy);
        }
        return '';
    }

    /**
     * @param Criteria $criteria
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getLimitSql(Criteria $criteria)
    {
        $limit  = $criteria->getMaxResults();
        $offset = $criteria->getFirstResult();
        if ($limit !== null || $offset !== null) {
            return $this->platform->modifyLimitQuery('', $limit, $offset);
        }
        return '';
    }
}
