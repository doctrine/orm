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

namespace Doctrine\ORM\Persisters;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\ResultSetMapping;

use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Types\Type;

use Doctrine\Common\Collections\Criteria;

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
     * Map that maps column names to the table names that own them.
     * This is mainly a temporary cache, used during a single request.
     *
     * @var array
     */
    private $owningTableMap = array();

    /**
     * Map of table to quoted table names.
     *
     * @var array
     */
    private $quotedTableMap = array();

    /**
     * {@inheritdoc}
     */
    protected function getDiscriminatorColumnTableName()
    {
        $class = ($this->class->name !== $this->class->rootEntityName)
            ? $this->em->getClassMetadata($this->class->rootEntityName)
            : $this->class;

        return $class->getTableName();
    }

    /**
     * This function finds the ClassMetadata instance in an inheritance hierarchy
     * that is responsible for enabling versioning.
     *
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    private function getVersionedClassMetadata()
    {
        if (isset($this->class->fieldMappings[$this->class->versionField]['inherited'])) {
            $definingClassName = $this->class->fieldMappings[$this->class->versionField]['inherited'];

            return $this->em->getClassMetadata($definingClassName);
        }

        return $this->class;
    }

    /**
     * Gets the name of the table that owns the column the given field is mapped to.
     *
     * @param string $fieldName
     * @return string
     * @override
     */
    public function getOwningTable($fieldName)
    {
        if (isset($this->owningTableMap[$fieldName])) {
            return $this->owningTableMap[$fieldName];
        }

        if (isset($this->class->associationMappings[$fieldName]['inherited'])) {
            $cm = $this->em->getClassMetadata($this->class->associationMappings[$fieldName]['inherited']);
        } else if (isset($this->class->fieldMappings[$fieldName]['inherited'])) {
            $cm = $this->em->getClassMetadata($this->class->fieldMappings[$fieldName]['inherited']);
        } else {
            $cm = $this->class;
        }

        $tableName = $cm->getTableName();

        $this->owningTableMap[$fieldName] = $tableName;
        $this->quotedTableMap[$tableName] = $this->quoteStrategy->getTableName($cm, $this->platform);

        return $tableName;
    }

    /**
     * {@inheritdoc}
     */
    public function executeInserts()
    {
        if ( ! $this->queuedInserts) {
            return;
        }

        $postInsertIds = array();
        $idGen = $this->class->idGenerator;
        $isPostInsertId = $idGen->isPostInsertGenerator();

        // Prepare statement for the root table
        $rootClass     = ($this->class->name !== $this->class->rootEntityName) ? $this->em->getClassMetadata($this->class->rootEntityName) : $this->class;
        $rootPersister = $this->em->getUnitOfWork()->getEntityPersister($rootClass->name);
        $rootTableName = $rootClass->getTableName();
        $rootTableStmt = $this->conn->prepare($rootPersister->getInsertSQL());

        // Prepare statements for sub tables.
        $subTableStmts = array();

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
                $rootTableStmt->bindValue($paramIndex++, $value, $this->columnTypes[$columnName]);
            }

            $rootTableStmt->execute();

            if ($isPostInsertId) {
                $id = $idGen->generate($this->em, $entity);
                $postInsertIds[$id] = $entity;
            } else {
                $id = $this->em->getUnitOfWork()->getEntityIdentifier($entity);
            }

            // Execute inserts on subtables.
            // The order doesn't matter because all child tables link to the root table via FK.
            foreach ($subTableStmts as $tableName => $stmt) {
                $data = isset($insertData[$tableName]) ? $insertData[$tableName] : array();
                $paramIndex = 1;

                foreach ((array) $id as $idName => $idVal) {
                    $type = isset($this->columnTypes[$idName]) ? $this->columnTypes[$idName] : Type::STRING;

                    $stmt->bindValue($paramIndex++, $idVal, $type);
                }

                foreach ($data as $columnName => $value) {
                    $stmt->bindValue($paramIndex++, $value, $this->columnTypes[$columnName]);
                }

                $stmt->execute();
            }
        }

        $rootTableStmt->closeCursor();

        foreach ($subTableStmts as $stmt) {
            $stmt->closeCursor();
        }

        if ($this->class->isVersioned) {
            $this->assignDefaultVersionValue($entity, $id);
        }

        $this->queuedInserts = array();

        return $postInsertIds;
    }

    /**
     * {@inheritdoc}
     */
    public function update($entity)
    {
        $updateData = $this->prepareUpdateData($entity);

        if (($isVersioned = $this->class->isVersioned) != false) {
            $versionedClass = $this->getVersionedClassMetadata();
            $versionedTable = $versionedClass->getTableName();
        }

        if ($updateData) {
            foreach ($updateData as $tableName => $data) {
                $this->updateTable(
                    $entity, $this->quotedTableMap[$tableName], $data, $isVersioned && $versionedTable == $tableName
                );
            }

            // Make sure the table with the version column is updated even if no columns on that
            // table were affected.
            if ($isVersioned && ! isset($updateData[$versionedTable])) {
                $this->updateTable($entity, $this->quoteStrategy->getTableName($versionedClass, $this->platform), array(), true);

                $id = $this->em->getUnitOfWork()->getEntityIdentifier($entity);
                $this->assignDefaultVersionValue($entity, $id);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity)
    {
        $identifier = $this->em->getUnitOfWork()->getEntityIdentifier($entity);
        $this->deleteJoinTableRecords($identifier);

        $id = array_combine($this->class->getIdentifierColumnNames(), $identifier);

        // If the database platform supports FKs, just
        // delete the row from the root table. Cascades do the rest.
        if ($this->platform->supportsForeignKeyConstraints()) {
            $this->conn->delete(
                $this->quoteStrategy->getTableName($this->em->getClassMetadata($this->class->rootEntityName), $this->platform), $id
            );
        } else {
            // Delete from all tables individually, starting from this class' table up to the root table.
            $this->conn->delete($this->quoteStrategy->getTableName($this->class, $this->platform), $id);

            foreach ($this->class->parentClasses as $parentClass) {
                $this->conn->delete(
                    $this->quoteStrategy->getTableName($this->em->getClassMetadata($parentClass), $this->platform), $id
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getSelectEntitiesSQL($criteria, $assoc = null, $lockMode = 0, $limit = null, $offset = null, array $orderBy = null)
    {
        $idColumns = $this->class->getIdentifierColumnNames();
        $baseTableAlias = $this->getSQLTableAlias($this->class->name);

        // Create the column list fragment only once
        if ($this->selectColumnListSql === null) {

            $this->rsm = new ResultSetMapping();
            $this->rsm->addEntityResult($this->class->name, 'r');

            // Add regular columns
            $columnList = '';

            foreach ($this->class->fieldMappings as $fieldName => $mapping) {
                if ($columnList != '') $columnList .= ', ';

                $columnList .= $this->getSelectColumnSQL(
                    $fieldName,
                    isset($mapping['inherited']) ? $this->em->getClassMetadata($mapping['inherited']) : $this->class
                );
            }

            // Add foreign key columns
            foreach ($this->class->associationMappings as $assoc2) {
                if ($assoc2['isOwningSide'] && $assoc2['type'] & ClassMetadata::TO_ONE) {
                    $tableAlias = isset($assoc2['inherited']) ? $this->getSQLTableAlias($assoc2['inherited']) : $baseTableAlias;

                    foreach ($assoc2['targetToSourceKeyColumns'] as $srcColumn) {
                        if ($columnList != '') $columnList .= ', ';

                        $columnList .= $this->getSelectJoinColumnSQL(
                            $tableAlias,
                            $srcColumn,
                            isset($assoc2['inherited']) ? $assoc2['inherited'] : $this->class->name
                        );
                    }
                }
            }

            // Add discriminator column (DO NOT ALIAS, see AbstractEntityInheritancePersister#_processSQLResult).
            $discrColumn = $this->class->discriminatorColumn['name'];
            $tableAlias  = ($this->class->rootEntityName == $this->class->name) ? $baseTableAlias : $this->getSQLTableAlias($this->class->rootEntityName);
            $columnList .= ', ' . $tableAlias . '.' . $discrColumn;

            $resultColumnName = $this->platform->getSQLResultCasing($discrColumn);

            $this->rsm->setDiscriminatorColumn('r', $resultColumnName);
            $this->rsm->addMetaResult('r', $resultColumnName, $discrColumn);
        }

        // INNER JOIN parent tables
        $joinSql = '';

        foreach ($this->class->parentClasses as $parentClassName) {
            $parentClass = $this->em->getClassMetadata($parentClassName);
            $tableAlias = $this->getSQLTableAlias($parentClassName);
            $joinSql .= ' INNER JOIN ' . $this->quoteStrategy->getTableName($parentClass, $this->platform) . ' ' . $tableAlias . ' ON ';
            $first = true;

            foreach ($idColumns as $idColumn) {
                if ($first) $first = false; else $joinSql .= ' AND ';

                $joinSql .= $baseTableAlias . '.' . $idColumn . ' = ' . $tableAlias . '.' . $idColumn;
            }
        }

        // OUTER JOIN sub tables
        foreach ($this->class->subClasses as $subClassName) {
            $subClass = $this->em->getClassMetadata($subClassName);
            $tableAlias = $this->getSQLTableAlias($subClassName);

            if ($this->selectColumnListSql === null) {
                // Add subclass columns
                foreach ($subClass->fieldMappings as $fieldName => $mapping) {
                    if (isset($mapping['inherited'])) continue;

                    $columnList .= ', ' . $this->getSelectColumnSQL($fieldName, $subClass);
                }

                // Add join columns (foreign keys)
                foreach ($subClass->associationMappings as $assoc2) {
                    if ($assoc2['isOwningSide'] && $assoc2['type'] & ClassMetadata::TO_ONE && ! isset($assoc2['inherited'])) {
                        foreach ($assoc2['targetToSourceKeyColumns'] as $srcColumn) {
                            if ($columnList != '') $columnList .= ', ';

                            $columnList .= $this->getSelectJoinColumnSQL(
                                $tableAlias,
                                $srcColumn,
                                isset($assoc2['inherited']) ? $assoc2['inherited'] : $subClass->name
                            );
                        }
                    }
                }
            }

            // Add LEFT JOIN
            $joinSql .= ' LEFT JOIN ' . $this->quoteStrategy->getTableName($subClass, $this->platform) . ' ' . $tableAlias . ' ON ';
            $first = true;

            foreach ($idColumns as $idColumn) {
                if ($first) $first = false; else $joinSql .= ' AND ';

                $joinSql .= $baseTableAlias . '.' . $idColumn . ' = ' . $tableAlias . '.' . $idColumn;
            }
        }

        $joinSql .= ($assoc != null && $assoc['type'] == ClassMetadata::MANY_TO_MANY) ? $this->getSelectManyToManyJoinSQL($assoc) : '';

        $conditionSql = ($criteria instanceof Criteria)
            ? $this->getSelectConditionCriteriaSQL($criteria)
            : $this->getSelectConditionSQL($criteria, $assoc);

        // If the current class in the root entity, add the filters
        if ($filterSql = $this->generateFilterConditionSQL($this->em->getClassMetadata($this->class->rootEntityName), $this->getSQLTableAlias($this->class->rootEntityName))) {
            if ($conditionSql) {
                $conditionSql .= ' AND ';
            }

            $conditionSql .= $filterSql;
        }

        $orderBy = ($assoc !== null && isset($assoc['orderBy'])) ? $assoc['orderBy'] : $orderBy;
        $orderBySql = $orderBy ? $this->getOrderBySQL($orderBy, $baseTableAlias) : '';

        if ($this->selectColumnListSql === null) {
            $this->selectColumnListSql = $columnList;
        }

        $lockSql = '';

        if ($lockMode == LockMode::PESSIMISTIC_READ) {
            $lockSql = ' ' . $this->platform->getReadLockSql();
        } else if ($lockMode == LockMode::PESSIMISTIC_WRITE) {
            $lockSql = ' ' . $this->platform->getWriteLockSql();
        }

        return $this->platform->modifyLimitQuery('SELECT ' . $this->selectColumnListSql
                . ' FROM ' . $this->quoteStrategy->getTableName($this->class, $this->platform) . ' ' . $baseTableAlias
                . $joinSql
                . ($conditionSql != '' ? ' WHERE ' . $conditionSql : '') . $orderBySql, $limit, $offset)
                . $lockSql;
    }

    /**
     * Get the FROM and optionally JOIN conditions to lock the entity managed by this persister.
     *
     * @return string
     */
    public function getLockTablesSql()
    {
        $idColumns = $this->class->getIdentifierColumnNames();
        $baseTableAlias = $this->getSQLTableAlias($this->class->name);

        // INNER JOIN parent tables
        $joinSql = '';

        foreach ($this->class->parentClasses as $parentClassName) {
            $parentClass = $this->em->getClassMetadata($parentClassName);
            $tableAlias = $this->getSQLTableAlias($parentClassName);
            $joinSql .= ' INNER JOIN ' . $this->quoteStrategy->getTableName($parentClass, $this->platform) . ' ' . $tableAlias . ' ON ';
            $first = true;

            foreach ($idColumns as $idColumn) {
                if ($first) $first = false; else $joinSql .= ' AND ';

                $joinSql .= $baseTableAlias . '.' . $idColumn . ' = ' . $tableAlias . '.' . $idColumn;
            }
        }

        return 'FROM ' .$this->quoteStrategy->getTableName($this->class, $this->platform) . ' ' . $baseTableAlias . $joinSql;
    }

    /*
     * Ensure this method is never called. This persister overrides getSelectEntitiesSQL directly.
     */
    protected function getSelectColumnListSQL()
    {
        throw new \BadMethodCallException("Illegal invocation of ".__METHOD__.".");
    }

    /**
     * {@inheritdoc} 
     */
    protected function getInsertColumnList()
    {
        // Identifier columns must always come first in the column list of subclasses.
        $columns = $this->class->parentClasses ? $this->class->getIdentifierColumnNames() : array();

        foreach ($this->class->reflFields as $name => $field) {
            if (isset($this->class->fieldMappings[$name]['inherited']) && ! isset($this->class->fieldMappings[$name]['id'])
                    || isset($this->class->associationMappings[$name]['inherited'])
                    || ($this->class->isVersioned && $this->class->versionField == $name)) {
                continue;
            }

            if (isset($this->class->associationMappings[$name])) {
                $assoc = $this->class->associationMappings[$name];
                if ($assoc['type'] & ClassMetadata::TO_ONE && $assoc['isOwningSide']) {
                    foreach ($assoc['targetToSourceKeyColumns'] as $sourceCol) {
                        $columns[] = $sourceCol;
                    }
                }
            } else if ($this->class->name != $this->class->rootEntityName ||
                    ! $this->class->isIdGeneratorIdentity() || $this->class->identifier[0] != $name) {
                $columns[]                  = $this->quoteStrategy->getColumnName($name, $this->class, $this->platform);
                $this->columnTypes[$name]   = $this->class->fieldMappings[$name]['type'];
            }
        }

        // Add discriminator column if it is the topmost class.
        if ($this->class->name == $this->class->rootEntityName) {
            $columns[] = $this->class->discriminatorColumn['name'];
        }

        return $columns;
    }

    /**
     * {@inheritdoc}
     */
    protected function assignDefaultVersionValue($entity, $id)
    {
        $value = $this->fetchVersionValue($this->getVersionedClassMetadata(), $id);
        $this->class->setFieldValue($entity, $this->class->versionField, $value);
    }

}
