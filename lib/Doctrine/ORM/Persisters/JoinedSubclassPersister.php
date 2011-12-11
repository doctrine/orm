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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Persisters;

use Doctrine\ORM\ORMException,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\DBAL\LockMode,
    Doctrine\DBAL\Types\Type,
    Doctrine\ORM\Query\ResultSetMapping;

/**
 * The joined subclass persister maps a single entity instance to several tables in the
 * database as it is defined by the <tt>Class Table Inheritance</tt> strategy.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
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
    private $_owningTableMap = array();

    /**
     * Map of table to quoted table names.
     * 
     * @var array
     */
    private $_quotedTableMap = array();

    /**
     * {@inheritdoc}
     */
    protected function _getDiscriminatorColumnTableName()
    {
        if ($this->_class->name == $this->_class->rootEntityName) {
            return $this->_class->table['name'];
        } else {
            return $this->_em->getClassMetadata($this->_class->rootEntityName)->table['name'];
        }
    }

    /**
     * This function finds the ClassMetadata instance in an inheritance hierarchy
     * that is responsible for enabling versioning.
     *
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    private function _getVersionedClassMetadata()
    {
        if (isset($this->_class->fieldMappings[$this->_class->versionField]['inherited'])) {
            $definingClassName = $this->_class->fieldMappings[$this->_class->versionField]['inherited'];
            return $this->_em->getClassMetadata($definingClassName);
        }
        return $this->_class;
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
        if (!isset($this->_owningTableMap[$fieldName])) {
            if (isset($this->_class->associationMappings[$fieldName]['inherited'])) {
                $cm = $this->_em->getClassMetadata($this->_class->associationMappings[$fieldName]['inherited']);
            } else if (isset($this->_class->fieldMappings[$fieldName]['inherited'])) {
                $cm = $this->_em->getClassMetadata($this->_class->fieldMappings[$fieldName]['inherited']);
            } else {
                $cm = $this->_class;
            }
            $this->_owningTableMap[$fieldName] = $cm->table['name'];
            $this->_quotedTableMap[$cm->table['name']] = $cm->getQuotedTableName($this->_platform);
        }

        return $this->_owningTableMap[$fieldName];
    }

    /**
     * {@inheritdoc}
     */
    public function executeInserts()
    {
        if ( ! $this->_queuedInserts) {
            return;
        }

        $postInsertIds = array();
        $idGen = $this->_class->idGenerator;
        $isPostInsertId = $idGen->isPostInsertGenerator();

        // Prepare statement for the root table
        $rootClass = $this->_class->name == $this->_class->rootEntityName ?
                $this->_class : $this->_em->getClassMetadata($this->_class->rootEntityName);
        $rootPersister = $this->_em->getUnitOfWork()->getEntityPersister($rootClass->name);
        $rootTableName = $rootClass->table['name'];
        $rootTableStmt = $this->_conn->prepare($rootPersister->_getInsertSQL());

        // Prepare statements for sub tables.
        $subTableStmts = array();
        if ($rootClass !== $this->_class) {
            $subTableStmts[$this->_class->table['name']] = $this->_conn->prepare($this->_getInsertSQL());
        }
        foreach ($this->_class->parentClasses as $parentClassName) {
            $parentClass = $this->_em->getClassMetadata($parentClassName);
            $parentTableName = $parentClass->table['name'];
            if ($parentClass !== $rootClass) {
                $parentPersister = $this->_em->getUnitOfWork()->getEntityPersister($parentClassName);
                $subTableStmts[$parentTableName] = $this->_conn->prepare($parentPersister->_getInsertSQL());
            }
        }

        // Execute all inserts. For each entity:
        // 1) Insert on root table
        // 2) Insert on sub tables
        foreach ($this->_queuedInserts as $entity) {
            $insertData = $this->_prepareInsertData($entity);

            // Execute insert on root table
            $paramIndex = 1;
            
            foreach ($insertData[$rootTableName] as $columnName => $value) {
                $rootTableStmt->bindValue($paramIndex++, $value, $this->_columnTypes[$columnName]);
            }
            
            $rootTableStmt->execute();

            if ($isPostInsertId) {
                $id = $idGen->generate($this->_em, $entity);
                $postInsertIds[$id] = $entity;
            } else {
                $id = $this->_em->getUnitOfWork()->getEntityIdentifier($entity);
            }

            // Execute inserts on subtables.
            // The order doesn't matter because all child tables link to the root table via FK.
            foreach ($subTableStmts as $tableName => $stmt) {
                $data = isset($insertData[$tableName]) ? $insertData[$tableName] : array();
                $paramIndex = 1;
                
                foreach ((array) $id as $idName => $idVal) {
                    $type = isset($this->_columnTypes[$idName]) ? $this->_columnTypes[$idName] : Type::STRING;
                    
                    $stmt->bindValue($paramIndex++, $idVal, $type);
                }
                
                foreach ($data as $columnName => $value) {
                    $stmt->bindValue($paramIndex++, $value, $this->_columnTypes[$columnName]);
                }
                
                $stmt->execute();
            }
        }

        $rootTableStmt->closeCursor();
        foreach ($subTableStmts as $stmt) {
            $stmt->closeCursor();
        }

        if ($this->_class->isVersioned) {
            $this->assignDefaultVersionValue($entity, $id);
        }

        $this->_queuedInserts = array();

        return $postInsertIds;
    }

    /**
     * {@inheritdoc}
     */
    public function update($entity)
    {
        $updateData = $this->_prepareUpdateData($entity);

        if (($isVersioned = $this->_class->isVersioned) != false) {
            $versionedClass = $this->_getVersionedClassMetadata();
            $versionedTable = $versionedClass->table['name'];
        }

        if ($updateData) {
            foreach ($updateData as $tableName => $data) {
                $this->_updateTable($entity, $this->_quotedTableMap[$tableName], $data, $isVersioned && $versionedTable == $tableName);
            }
            
            // Make sure the table with the version column is updated even if no columns on that
            // table were affected.
            if ($isVersioned && ! isset($updateData[$versionedTable])) {
                $this->_updateTable($entity, $versionedClass->getQuotedTableName($this->_platform), array(), true);

                $id = $this->_em->getUnitOfWork()->getEntityIdentifier($entity);
                $this->assignDefaultVersionValue($entity, $id);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity)
    {
        $identifier = $this->_em->getUnitOfWork()->getEntityIdentifier($entity);
        $this->deleteJoinTableRecords($identifier);

        $id = array_combine($this->_class->getIdentifierColumnNames(), $identifier);

        // If the database platform supports FKs, just
        // delete the row from the root table. Cascades do the rest.
        if ($this->_platform->supportsForeignKeyConstraints()) {
            $this->_conn->delete($this->_em->getClassMetadata($this->_class->rootEntityName)
                    ->getQuotedTableName($this->_platform), $id);
        } else {
            // Delete from all tables individually, starting from this class' table up to the root table.
            $this->_conn->delete($this->_class->getQuotedTableName($this->_platform), $id);
            
            foreach ($this->_class->parentClasses as $parentClass) {
                $this->_conn->delete($this->_em->getClassMetadata($parentClass)->getQuotedTableName($this->_platform), $id);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function _getSelectEntitiesSQL(array $criteria, $assoc = null, $lockMode = 0, $limit = null, $offset = null, array $orderBy = null)
    {
        $idColumns = $this->_class->getIdentifierColumnNames();
        $baseTableAlias = $this->_getSQLTableAlias($this->_class->name);

        // Create the column list fragment only once
        if ($this->_selectColumnListSql === null) {
            
            $this->_rsm = new ResultSetMapping();
            $this->_rsm->addEntityResult($this->_class->name, 'r');
            
            // Add regular columns
            $columnList = '';
            foreach ($this->_class->fieldMappings as $fieldName => $mapping) {
                if ($columnList != '') $columnList .= ', ';
                $columnList .= $this->_getSelectColumnSQL($fieldName,
                        isset($mapping['inherited']) ?
                        $this->_em->getClassMetadata($mapping['inherited']) :
                        $this->_class);
            }

            // Add foreign key columns
            foreach ($this->_class->associationMappings as $assoc2) {
                if ($assoc2['isOwningSide'] && $assoc2['type'] & ClassMetadata::TO_ONE) {
                    $tableAlias = isset($assoc2['inherited']) ?
                            $this->_getSQLTableAlias($assoc2['inherited'])
                            : $baseTableAlias;
                    foreach ($assoc2['targetToSourceKeyColumns'] as $srcColumn) {
                        if ($columnList != '') $columnList .= ', ';
                        $columnList .= $this->getSelectJoinColumnSQL($tableAlias, $srcColumn,
                            isset($assoc2['inherited']) ? $assoc2['inherited'] : $this->_class->name
                        );
                    }
                }
            }

            // Add discriminator column (DO NOT ALIAS, see AbstractEntityInheritancePersister#_processSQLResult).
            $discrColumn = $this->_class->discriminatorColumn['name'];
            if ($this->_class->rootEntityName == $this->_class->name) {
                $columnList .= ", $baseTableAlias.$discrColumn";
            } else {
                $columnList .= ', ' . $this->_getSQLTableAlias($this->_class->rootEntityName)
                        . ".$discrColumn";
            }

            $resultColumnName = $this->_platform->getSQLResultCasing($discrColumn);
            $this->_rsm->setDiscriminatorColumn('r', $resultColumnName);
            $this->_rsm->addMetaResult('r', $resultColumnName, $discrColumn);
        }

        // INNER JOIN parent tables
        $joinSql = '';
        foreach ($this->_class->parentClasses as $parentClassName) {
            $parentClass = $this->_em->getClassMetadata($parentClassName);
            $tableAlias = $this->_getSQLTableAlias($parentClassName);
            $joinSql .= ' INNER JOIN ' . $parentClass->getQuotedTableName($this->_platform) . ' ' . $tableAlias . ' ON ';
            $first = true;
            foreach ($idColumns as $idColumn) {
                if ($first) $first = false; else $joinSql .= ' AND ';
                $joinSql .= $baseTableAlias . '.' . $idColumn . ' = ' . $tableAlias . '.' . $idColumn;
            }
        }

        // OUTER JOIN sub tables
        foreach ($this->_class->subClasses as $subClassName) {
            $subClass = $this->_em->getClassMetadata($subClassName);
            $tableAlias = $this->_getSQLTableAlias($subClassName);

            if ($this->_selectColumnListSql === null) {
                // Add subclass columns
                foreach ($subClass->fieldMappings as $fieldName => $mapping) {
                    if (isset($mapping['inherited'])) {
                        continue;
                    }
                    $columnList .= ', ' . $this->_getSelectColumnSQL($fieldName, $subClass);
                }

                // Add join columns (foreign keys)
                foreach ($subClass->associationMappings as $assoc2) {
                    if ($assoc2['isOwningSide'] && $assoc2['type'] & ClassMetadata::TO_ONE
                            && ! isset($assoc2['inherited'])) {
                        foreach ($assoc2['targetToSourceKeyColumns'] as $srcColumn) {
                            if ($columnList != '') $columnList .= ', ';
                            $columnList .= $this->getSelectJoinColumnSQL($tableAlias, $srcColumn,
                                isset($assoc2['inherited']) ? $assoc2['inherited'] : $subClass->name
                            );
                        }
                    }
                }
            }

            // Add LEFT JOIN
            $joinSql .= ' LEFT JOIN ' . $subClass->getQuotedTableName($this->_platform) . ' ' . $tableAlias . ' ON ';
            $first = true;
            foreach ($idColumns as $idColumn) {
                if ($first) $first = false; else $joinSql .= ' AND ';
                $joinSql .= $baseTableAlias . '.' . $idColumn . ' = ' . $tableAlias . '.' . $idColumn;
            }
        }

        $joinSql .= $assoc != null && $assoc['type'] == ClassMetadata::MANY_TO_MANY ?
                $this->_getSelectManyToManyJoinSQL($assoc) : '';

        $conditionSql = $this->_getSelectConditionSQL($criteria, $assoc);

        $orderBy = ($assoc !== null && isset($assoc['orderBy'])) ? $assoc['orderBy'] : $orderBy;
        $orderBySql = $orderBy ? $this->_getOrderBySQL($orderBy, $baseTableAlias) : '';

        if ($this->_selectColumnListSql === null) {
            $this->_selectColumnListSql = $columnList;
        }

        $lockSql = '';
        if ($lockMode == LockMode::PESSIMISTIC_READ) {
            $lockSql = ' ' . $this->_platform->getReadLockSql();
        } else if ($lockMode == LockMode::PESSIMISTIC_WRITE) {
            $lockSql = ' ' . $this->_platform->getWriteLockSql();
        }

        return $this->_platform->modifyLimitQuery('SELECT ' . $this->_selectColumnListSql
                . ' FROM ' . $this->_class->getQuotedTableName($this->_platform) . ' ' . $baseTableAlias
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
        $idColumns = $this->_class->getIdentifierColumnNames();
        $baseTableAlias = $this->_getSQLTableAlias($this->_class->name);

        // INNER JOIN parent tables
        $joinSql = '';
        foreach ($this->_class->parentClasses as $parentClassName) {
            $parentClass = $this->_em->getClassMetadata($parentClassName);
            $tableAlias = $this->_getSQLTableAlias($parentClassName);
            $joinSql .= ' INNER JOIN ' . $parentClass->getQuotedTableName($this->_platform) . ' ' . $tableAlias . ' ON ';
            $first = true;
            foreach ($idColumns as $idColumn) {
                if ($first) $first = false; else $joinSql .= ' AND ';
                $joinSql .= $baseTableAlias . '.' . $idColumn . ' = ' . $tableAlias . '.' . $idColumn;
            }
        }

        return 'FROM ' . $this->_class->getQuotedTableName($this->_platform) . ' ' . $baseTableAlias . $joinSql;
    }
    
    /* Ensure this method is never called. This persister overrides _getSelectEntitiesSQL directly. */
    protected function _getSelectColumnListSQL()
    {
        throw new \BadMethodCallException("Illegal invocation of ".__METHOD__.".");
    }
    
    /** {@inheritdoc} */
    protected function _getInsertColumnList()
    {
        // Identifier columns must always come first in the column list of subclasses.
        $columns = $this->_class->parentClasses ? $this->_class->getIdentifierColumnNames() : array();

        foreach ($this->_class->reflFields as $name => $field) {
            if (isset($this->_class->fieldMappings[$name]['inherited']) && ! isset($this->_class->fieldMappings[$name]['id'])
                    || isset($this->_class->associationMappings[$name]['inherited'])
                    || ($this->_class->isVersioned && $this->_class->versionField == $name)) {
                continue;
            }

            if (isset($this->_class->associationMappings[$name])) {
                $assoc = $this->_class->associationMappings[$name];
                if ($assoc['type'] & ClassMetadata::TO_ONE && $assoc['isOwningSide']) {
                    foreach ($assoc['targetToSourceKeyColumns'] as $sourceCol) {
                        $columns[] = $sourceCol;
                    }
                }
            } else if ($this->_class->name != $this->_class->rootEntityName ||
                    ! $this->_class->isIdGeneratorIdentity() || $this->_class->identifier[0] != $name) {
                $columns[] = $this->_class->getQuotedColumnName($name, $this->_platform);
            }
        }

        // Add discriminator column if it is the topmost class.
        if ($this->_class->name == $this->_class->rootEntityName) {
            $columns[] = $this->_class->discriminatorColumn['name'];
        }

        return $columns;
    }

    /**
     * {@inheritdoc}
     */
    protected function assignDefaultVersionValue($entity, $id)
    {
        $value = $this->fetchVersionValue($this->_getVersionedClassMetadata(), $id);
        $this->_class->setFieldValue($entity, $this->_class->versionField, $value);
    }
}
