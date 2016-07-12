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

namespace Doctrine\ORM\Persisters\Entity;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ColumnMetadata;
use Doctrine\ORM\Utility\PersisterHelper;

/**
 * The joined subclass persister maps a single entity instance to several tables in the
 * database as it is defined by the <tt>Class Table Inheritance</tt> strategy.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Alexander <iam.asm89@gmail.com>
 * @since 2.0
 * @see http://martinfowler.com/eaaCatalog/classTableInheritance.html
 */
class JoinedSubclassPersister extends AbstractEntityInheritancePersister
{
    /**
     * Map of table to quoted table names.
     *
     * @var array
     */
    private $quotedTableMap = [];

    /**
     * Gets the name of the table that owns the column the given field is mapped to.
     *
     * @param string $fieldName
     *
     * @return string
     *
     * @override
     */
    public function getOwningTable($fieldName)
    {
        $property = $this->class->getProperty($fieldName);

        switch (true) {
            case isset($this->class->associationMappings[$fieldName]['inherited']):
                $cm = $this->em->getClassMetadata($this->class->associationMappings[$fieldName]['inherited']);
                break;

            case ($property && $this->class->isInheritedProperty($fieldName)):
                $cm = $property->getDeclaringClass();
                break;

            default:
                $cm = $this->class;
                break;
        }

        $tableName        = $cm->getTableName();
        $quotedTableName  = $this->quoteStrategy->getTableName($cm, $this->platform);

        $this->quotedTableMap[$tableName] = $quotedTableName;

        return $tableName;
    }

    /**
     * {@inheritdoc}
     */
    public function executeInserts()
    {
        if ( ! $this->queuedInserts) {
            return [];
        }

        $postInsertIds  = [];
        $idGenerator    = $this->class->idGenerator;
        $isPostInsertId = $idGenerator->isPostInsertGenerator();
        $rootClass      = ($this->class->name !== $this->class->rootEntityName)
            ? $this->em->getClassMetadata($this->class->rootEntityName)
            : $this->class;

        // Prepare statement for the root table
        $rootPersister = $this->em->getUnitOfWork()->getEntityPersister($rootClass->name);
        $rootTableName = $rootClass->getTableName();
        $rootTableStmt = $this->conn->prepare($rootPersister->getInsertSQL());

        // Prepare statements for sub tables.
        $subTableStmts = [];

        if ($rootClass !== $this->class) {
            $subTableStmts[$this->class->getTableName()] = $this->conn->prepare($this->getInsertSQL());
        }

        foreach ($this->class->parentClasses as $parentClassName) {
            $parentClass = $this->em->getClassMetadata($parentClassName);
            $parentTableName = $parentClass->getTableName();

            if ($parentClass !== $rootClass) {
                $parentPersister = $this->em->getUnitOfWork()->getEntityPersister($parentClassName);

                $subTableStmts[$parentTableName] = $this->conn->prepare($parentPersister->getInsertSQL());
            }
        }

        // Execute all inserts. For each entity:
        // 1) Insert on root table
        // 2) Insert on sub tables
        foreach ($this->queuedInserts as $entity) {
            $insertData = $this->prepareInsertData($entity);

            // Execute insert on root table
            $paramIndex = 1;

            foreach ($insertData[$rootTableName] as $columnName => $value) {
                $type = $this->columns[$columnName] instanceof ColumnMetadata
                    ? $this->columns[$columnName]->getType()
                    : $this->columns[$columnName]['type']
                ;

                $rootTableStmt->bindValue($paramIndex++, $value, $type);
            }

            $rootTableStmt->execute();

            if ($isPostInsertId) {
                $generatedId = $idGenerator->generate($this->em, $entity);
                $id          = [$this->class->identifier[0] => $generatedId];

                $postInsertIds[] = [
                    'generatedId' => $generatedId,
                    'entity' => $entity,
                ];
            } else {
                $id = $this->em->getUnitOfWork()->getEntityIdentifier($entity);
            }

            if ($this->class->isVersioned()) {
                $this->assignDefaultVersionValue($entity, $id);
            }

            // Execute inserts on subtables.
            // The order doesn't matter because all child tables link to the root table via FK.
            foreach ($subTableStmts as $tableName => $stmt) {
                /** @var \Doctrine\DBAL\Statement $stmt */
                $paramIndex = 1;
                $data       = isset($insertData[$tableName])
                    ? $insertData[$tableName]
                    : [];

                foreach ((array) $id as $idName => $idVal) {
                    $type = Type::getType('string');

                    if (isset($this->columns[$idName])) {
                        $type = $this->columns[$idName] instanceof ColumnMetadata
                            ? $this->columns[$idName]->getType()
                            : $this->columns[$idName]['type']
                        ;
                    }

                    $stmt->bindValue($paramIndex++, $idVal, $type);
                }

                foreach ($data as $columnName => $value) {
                    if (!is_array($id) || !isset($id[$columnName])) {
                        $type = $this->columns[$columnName] instanceof ColumnMetadata
                            ? $this->columns[$columnName]->getType()
                            : $this->columns[$columnName]['type']
                        ;

                        $stmt->bindValue($paramIndex++, $value, $type);
                    }
                }

                $stmt->execute();
            }
        }

        $rootTableStmt->closeCursor();

        foreach ($subTableStmts as $stmt) {
            $stmt->closeCursor();
        }

        $this->queuedInserts = [];

        return $postInsertIds;
    }

    /**
     * {@inheritdoc}
     */
    public function update($entity)
    {
        $updateData = $this->prepareUpdateData($entity);

        if ( ! $updateData) {
            return;
        }

        $isVersioned = $this->class->isVersioned();

        foreach ($updateData as $tableName => $data) {
            $quotedTableName = $this->quotedTableMap[$tableName];
            $versioned       = $isVersioned && $this->class->versionProperty->getTableName() === $tableName;

            $this->updateTable($entity, $quotedTableName, $data, $versioned);
        }

        // Make sure the table with the version column is updated even if no columns on that
        // table were affected.
        if ($isVersioned) {
            $versionedClass = $this->class->versionProperty->getDeclaringClass();
            $versionedTable = $versionedClass->getTableName();

            if ( ! isset($updateData[$versionedTable])) {
                $tableName = $this->quoteStrategy->getTableName($versionedClass, $this->platform);

                $this->updateTable($entity, $tableName, [], true);
            }

            $identifiers = $this->em->getUnitOfWork()->getEntityIdentifier($entity);

            $this->assignDefaultVersionValue($entity, $identifiers);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity)
    {
        $identifier = $this->em->getUnitOfWork()->getEntityIdentifier($entity);
        $id         = array_combine(array_keys($this->class->getIdentifierColumns($this->em)), $identifier);

        $this->deleteJoinTableRecords($identifier);

        // If the database platform supports FKs, just
        // delete the row from the root table. Cascades do the rest.
        if ($this->platform->supportsForeignKeyConstraints()) {
            $rootClass  = $this->em->getClassMetadata($this->class->rootEntityName);
            $rootTable  = $this->quoteStrategy->getTableName($rootClass, $this->platform);

            return (bool) $this->conn->delete($rootTable, $id);
        }

        // Delete from all tables individually, starting from this class' table up to the root table.
        $rootTable = $this->quoteStrategy->getTableName($this->class, $this->platform);

        $affectedRows = $this->conn->delete($rootTable, $id);

        foreach ($this->class->parentClasses as $parentClass) {
            $parentMetadata = $this->em->getClassMetadata($parentClass);
            $parentTable    = $this->quoteStrategy->getTableName($parentMetadata, $this->platform);

            $this->conn->delete($parentTable, $id);
        }

        return (bool) $affectedRows;
    }

    /**
     * {@inheritdoc}
     */
    public function getSelectSQL($criteria, $assoc = null, $lockMode = null, $limit = null, $offset = null, array $orderBy = null)
    {
        $this->switchPersisterContext($offset, $limit);

        $baseTableAlias = $this->getSQLTableAlias($this->class->getTableName());
        $joinSql        = $this->getJoinSql($baseTableAlias);

        if ($assoc != null && $assoc['type'] == ClassMetadata::MANY_TO_MANY) {
            $joinSql .= $this->getSelectManyToManyJoinSQL($assoc);
        }

        $conditionSql = ($criteria instanceof Criteria)
            ? $this->getSelectConditionCriteriaSQL($criteria)
            : $this->getSelectConditionSQL($criteria, $assoc);

        // If the current class in the root entity, add the filters
        $rootClass  = $this->em->getClassMetadata($this->class->rootEntityName);
        $tableAlias = $this->getSQLTableAlias($rootClass->getTableName());

        if ($filterSql = $this->generateFilterConditionSQL($rootClass, $tableAlias)) {
            $conditionSql .= $conditionSql
                ? ' AND ' . $filterSql
                : $filterSql;
        }

        $orderBySql = '';

        if ($assoc !== null && isset($assoc['orderBy'])) {
            $orderBy = $assoc['orderBy'];
        }

        if ($orderBy) {
            $orderBySql = $this->getOrderBySQL($orderBy, $baseTableAlias);
        }

        $lockSql = '';

        switch ($lockMode) {
            case LockMode::PESSIMISTIC_READ:

                $lockSql = ' ' . $this->platform->getReadLockSQL();

                break;

            case LockMode::PESSIMISTIC_WRITE:

                $lockSql = ' ' . $this->platform->getWriteLockSQL();

                break;
        }

        $tableName  = $this->quoteStrategy->getTableName($this->class, $this->platform);
        $from       = ' FROM ' . $tableName . ' ' . $baseTableAlias;
        $where      = $conditionSql != '' ? ' WHERE ' . $conditionSql : '';
        $lock       = $this->platform->appendLockHint($from, $lockMode);
        $columnList = $this->getSelectColumnsSQL();
        $query      = 'SELECT '  . $columnList
                    . $lock
                    . $joinSql
                    . $where
                    . $orderBySql;

        return $this->platform->modifyLimitQuery($query, $limit, $offset) . $lockSql;
    }

    /**
     * {@inheritDoc}
     */
    public function getCountSQL($criteria = [])
    {
        $tableName      = $this->quoteStrategy->getTableName($this->class, $this->platform);
        $baseTableAlias = $this->getSQLTableAlias($this->class->getTableName());
        $joinSql        = $this->getJoinSql($baseTableAlias);

        $conditionSql = ($criteria instanceof Criteria)
            ? $this->getSelectConditionCriteriaSQL($criteria)
            : $this->getSelectConditionSQL($criteria);

        $rootClass  = $this->em->getClassMetadata($this->class->rootEntityName);
        $tableAlias = $this->getSQLTableAlias($rootClass->getTableName());
        $filterSql  = $this->generateFilterConditionSQL($rootClass, $tableAlias);

        if ('' !== $filterSql) {
            $conditionSql = $conditionSql
                ? $conditionSql . ' AND ' . $filterSql
                : $filterSql;
        }

        $sql = 'SELECT COUNT(*) '
            . 'FROM ' . $tableName . ' ' . $baseTableAlias
            . $joinSql
            . (empty($conditionSql) ? '' : ' WHERE ' . $conditionSql);

        return $sql;
    }

    /**
     * {@inheritdoc}
     */
    protected function getLockTablesSql($lockMode)
    {
        $joinSql            = '';
        $identifierColumns  = $this->class->getIdentifierColumns($this->em);
        $baseTableAlias     = $this->getSQLTableAlias($this->class->getTableName());

        // INNER JOIN parent tables
        foreach ($this->class->parentClasses as $parentClassName) {
            $conditions   = [];
            $parentClass  = $this->em->getClassMetadata($parentClassName);
            $tableAlias   = $this->getSQLTableAlias($parentClass->getTableName());
            $joinSql     .= ' INNER JOIN ' . $this->quoteStrategy->getTableName($parentClass, $this->platform) . ' ' . $tableAlias . ' ON ';

            foreach ($identifierColumns as $idColumn) {
                $quotedColumnName = $idColumn instanceof ColumnMetadata
                    ? $this->platform->quoteIdentifier($idColumn->getColumnName())
                    : $this->platform->quoteIdentifier($idColumn['name'])
                ;

                $conditions[] = $baseTableAlias . '.' . $quotedColumnName . ' = ' . $tableAlias . '.' . $quotedColumnName;
            }

            $joinSql .= implode(' AND ', $conditions);
        }

        return parent::getLockTablesSql($lockMode) . $joinSql;
    }

    /**
     * Ensure this method is never called. This persister overrides getSelectEntitiesSQL directly.
     *
     * @return string
     */
    protected function getSelectColumnsSQL()
    {
        // Create the column list fragment only once
        if ($this->currentPersisterContext->selectColumnListSql !== null) {
            return $this->currentPersisterContext->selectColumnListSql;
        }

        $this->currentPersisterContext->rsm->addEntityResult($this->class->name, 'r');

        $columnList = [];

        // Add regular columns
        foreach ($this->class->getProperties() as $fieldName => $property) {
            $columnList[] = $this->getSelectColumnSQL($fieldName, $property->getDeclaringClass());
        }

        // Add foreign key columns
        foreach ($this->class->associationMappings as $field => $mapping) {
            if ( ! $mapping['isOwningSide'] || ! ($mapping['type'] & ClassMetadata::TO_ONE)) {
                continue;
            }

            $targetClass = $this->em->getClassMetadata($mapping['targetEntity']);

            foreach ($mapping['joinColumns'] as $joinColumn) {
                $type = PersisterHelper::getTypeOfColumn($joinColumn['referencedColumnName'], $targetClass, $this->em);

                $columnList[] = $this->getSelectJoinColumnSQL($joinColumn['tableName'], $joinColumn['name'], $type);
            }
        }

        // Add discriminator column (DO NOT ALIAS, see AbstractEntityInheritancePersister#processSQLResult).
        $discrColumn        = $this->class->discriminatorColumn;
        $discrColumnName    = $discrColumn->getColumnName();
        $discrColumnType    = $discrColumn->getType();
        $resultColumnName   = $this->platform->getSQLResultCasing($discrColumnName);

        $this->currentPersisterContext->rsm->setDiscriminatorColumn('r', $resultColumnName);
        $this->currentPersisterContext->rsm->addMetaResult('r', $resultColumnName, $discrColumnName, false, $discrColumnType);

        $columnList[] = $discrColumnType->convertToDatabaseValueSQL(
            $this->getSQLTableAlias($discrColumn->getTableName()) . '.' . $discrColumnName,
            $this->platform
        );

        // sub tables
        foreach ($this->class->subClasses as $subClassName) {
            $subClass   = $this->em->getClassMetadata($subClassName);

            // Add subclass columns
            foreach ($subClass->getProperties() as $fieldName => $property) {
                if ($subClass->isInheritedProperty($fieldName)) {
                    continue;
                }

                $columnList[] = $this->getSelectColumnSQL($fieldName, $subClass);
            }

            // Add join columns (foreign keys)
            foreach ($subClass->associationMappings as $mapping) {
                if ( ! $mapping['isOwningSide'] || ! ($mapping['type'] & ClassMetadata::TO_ONE) || isset($mapping['inherited'])) {
                    continue;
                }

                $targetClass = $this->em->getClassMetadata($mapping['targetEntity']);

                foreach ($mapping['joinColumns'] as $joinColumn) {
                    $type = PersisterHelper::getTypeOfColumn($joinColumn['referencedColumnName'], $targetClass, $this->em);

                    $columnList[] = $this->getSelectJoinColumnSQL($joinColumn['tableName'], $joinColumn['name'], $type);
                }
            }
        }

        $this->currentPersisterContext->selectColumnListSql = implode(', ', $columnList);

        return $this->currentPersisterContext->selectColumnListSql;
    }

    /**
     * {@inheritdoc}
     */
    protected function getInsertColumnList()
    {
        // Identifier columns must always come first in the column list of subclasses.
        $columns = [];
        $parentColumns = $this->class->parentClasses
            ? $this->class->getIdentifierColumns($this->em)
            : [];

        foreach ($parentColumns as $columnName => $column) {
            $columns[] = $columnName;

            $this->columns[$columnName] = $column;
        }

        $versionPropertyName = $this->class->isVersioned()
            ? $this->class->versionProperty->getName()
            : null
        ;

        foreach ($this->class->reflFields as $name => $field) {
            $property = $this->class->getProperty($name);

            if (($property && $this->class->isInheritedProperty($name))
                || isset($this->class->associationMappings[$name]['inherited'])
                || ($versionPropertyName === $name)
                /*|| isset($this->class->embeddedClasses[$name])*/) {
                continue;
            }

            if (isset($this->class->associationMappings[$name])) {
                $assoc = $this->class->associationMappings[$name];

                if ($assoc['isOwningSide'] && $assoc['type'] & ClassMetadata::TO_ONE) {
                    $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);

                    foreach ($assoc['joinColumns'] as $joinColumn) {
                        $sourceColumn = $joinColumn['name'];
                        $targetColumn = $joinColumn['referencedColumnName'];

                        $columns[] = $sourceColumn;

                        $this->columns[$sourceColumn] = array_merge(
                            $joinColumn,
                            ['type' => PersisterHelper::getTypeOfColumn($targetColumn, $targetClass, $this->em)]
                        );
                    }
                }

                continue;
            }

            if ($this->class->name != $this->class->rootEntityName
                || ! $this->class->isIdGeneratorIdentity() || $this->class->identifier[0] !== $name) {
                $columnName = $property->getColumnName();

                $columns[] = $columnName;

                $this->columns[$columnName] = $property;
            }
        }

        // Add discriminator column if it is the topmost class.
        if ($this->class->name === $this->class->rootEntityName) {
            $discrColumn     = $this->class->discriminatorColumn;
            $discrColumnName = $discrColumn->getColumnName();

            $columns[] = $discrColumnName;

            $this->columns[$discrColumnName] = $discrColumn;
        }

        return $columns;
    }

    /**
     * @param string $baseTableAlias
     *
     * @return string
     */
    private function getJoinSql($baseTableAlias)
    {
        $joinSql           = '';
        $identifierColumns = $this->class->getIdentifierColumns($this->em);

        // INNER JOIN parent tables
        foreach ($this->class->parentClasses as $parentClassName) {
            $conditions   = [];
            $parentClass  = $this->em->getClassMetadata($parentClassName);
            $tableAlias   = $this->getSQLTableAlias($parentClass->getTableName());
            $joinSql     .= ' INNER JOIN ' . $this->quoteStrategy->getTableName($parentClass, $this->platform) . ' ' . $tableAlias . ' ON ';

            foreach ($identifierColumns as $idColumn) {
                $quotedColumnName = $idColumn instanceof ColumnMetadata
                    ? $this->platform->quoteIdentifier($idColumn->getColumnName())
                    : $this->platform->quoteIdentifier($idColumn['name'])
                ;

                $conditions[] = $baseTableAlias . '.' . $quotedColumnName . ' = ' . $tableAlias . '.' . $quotedColumnName;
            }

            $joinSql .= implode(' AND ', $conditions);
        }

        // OUTER JOIN sub tables
        foreach ($this->class->subClasses as $subClassName) {
            $conditions  = [];
            $subClass    = $this->em->getClassMetadata($subClassName);
            $tableAlias  = $this->getSQLTableAlias($subClass->getTableName());
            $joinSql    .= ' LEFT JOIN ' . $this->quoteStrategy->getTableName($subClass, $this->platform) . ' ' . $tableAlias . ' ON ';

            foreach ($identifierColumns as $idColumn) {
                $quotedColumnName = $idColumn instanceof ColumnMetadata
                    ? $this->platform->quoteIdentifier($idColumn->getColumnName())
                    : $this->platform->quoteIdentifier($idColumn['name'])
                ;

                $conditions[] = $baseTableAlias . '.' . $quotedColumnName . ' = ' . $tableAlias . '.' . $quotedColumnName;
            }

            $joinSql .= implode(' AND ', $conditions);
        }

        return $joinSql;
    }
}
