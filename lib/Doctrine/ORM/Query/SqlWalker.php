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

namespace Doctrine\ORM\Query;

use Doctrine\DBAL\LockMode,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Query,
    Doctrine\ORM\Query\QueryException;

/**
 * The SqlWalker is a TreeWalker that walks over a DQL AST and constructs
 * the corresponding SQL.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @since 2.0
 * @todo Rename: SQLWalker
 */
class SqlWalker implements TreeWalker
{
    /**
     * @var ResultSetMapping
     */
    private $_rsm;

    /** Counters for generating unique column aliases, table aliases and parameter indexes. */
    private $_aliasCounter = 0;
    private $_tableAliasCounter = 0;
    private $_scalarResultCounter = 1;
    private $_sqlParamIndex = 0;

    /**
     * @var ParserResult
     */
    private $_parserResult;

    /**
     * @var EntityManager
     */
    private $_em;

    /**
     * @var Doctrine\DBAL\Connection
     */
    private $_conn;

    /**
     * @var AbstractQuery
     */
    private $_query;

    private $_tableAliasMap = array();

    /** Map from result variable names to their SQL column alias names. */
    private $_scalarResultAliasMap = array();

    /** Map of all components/classes that appear in the DQL query. */
    private $_queryComponents;

    /** A list of classes that appear in non-scalar SelectExpressions. */
    private $_selectedClasses = array();

    /**
     * The DQL alias of the root class of the currently traversed query.
     */
    private $_rootAliases = array();

    /**
     * Flag that indicates whether to generate SQL table aliases in the SQL.
     * These should only be generated for SELECT queries, not for UPDATE/DELETE.
     */
    private $_useSqlTableAliases = true;

    /**
     * The database platform abstraction.
     *
     * @var AbstractPlatform
     */
    private $_platform;

    /**
     * {@inheritDoc}
     */
    public function __construct($query, $parserResult, array $queryComponents)
    {
        $this->_query = $query;
        $this->_parserResult = $parserResult;
        $this->_queryComponents = $queryComponents;
        $this->_rsm = $parserResult->getResultSetMapping();
        $this->_em = $query->getEntityManager();
        $this->_conn = $this->_em->getConnection();
        $this->_platform = $this->_conn->getDatabasePlatform();
    }

    /**
     * Gets the Query instance used by the walker.
     *
     * @return Query.
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * Gets the Connection used by the walker.
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->_conn;
    }

    /**
     * Gets the EntityManager used by the walker.
     *
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->_em;
    }

    /**
     * Gets the information about a single query component.
     *
     * @param string $dqlAlias The DQL alias.
     * @return array
     */
    public function getQueryComponent($dqlAlias)
    {
        return $this->_queryComponents[$dqlAlias];
    }

    /**
     * Gets an executor that can be used to execute the result of this walker.
     *
     * @return AbstractExecutor
     */
    public function getExecutor($AST)
    {
        $isDeleteStatement = $AST instanceof AST\DeleteStatement;
        $isUpdateStatement = $AST instanceof AST\UpdateStatement;

        if ($isDeleteStatement) {
            $primaryClass = $this->_em->getClassMetadata(
                $AST->deleteClause->abstractSchemaName
            );

            if ($primaryClass->isInheritanceTypeJoined()) {
                return new Exec\MultiTableDeleteExecutor($AST, $this);
            } else {
                return new Exec\SingleTableDeleteUpdateExecutor($AST, $this);
            }
        } else if ($isUpdateStatement) {
            $primaryClass = $this->_em->getClassMetadata(
                $AST->updateClause->abstractSchemaName
            );

            if ($primaryClass->isInheritanceTypeJoined()) {
                return new Exec\MultiTableUpdateExecutor($AST, $this);
            } else {
                return new Exec\SingleTableDeleteUpdateExecutor($AST, $this);
            }
        }

        return new Exec\SingleSelectExecutor($AST, $this);
    }

    /**
     * Generates a unique, short SQL table alias.
     *
     * @param string $tableName Table name
     * @param string $dqlAlias The DQL alias.
     * @return string Generated table alias.
     */
    public function getSQLTableAlias($tableName, $dqlAlias = '')
    {
        $tableName .= ($dqlAlias) ? '@[' . $dqlAlias . ']' : '';

        if ( ! isset($this->_tableAliasMap[$tableName])) {
            $this->_tableAliasMap[$tableName] = strtolower(substr($tableName, 0, 1)) . $this->_tableAliasCounter++ . '_';
        }

        return $this->_tableAliasMap[$tableName];
    }

    /**
     * Forces the SqlWalker to use a specific alias for a table name, rather than
     * generating an alias on its own.
     *
     * @param string $tableName
     * @param string $alias
     * @param string $dqlAlias
     * @return string
     */
    public function setSQLTableAlias($tableName, $alias, $dqlAlias = '')
    {
        $tableName .= ($dqlAlias) ? '@[' . $dqlAlias . ']' : '';

        $this->_tableAliasMap[$tableName] = $alias;

        return $alias;
    }

    /**
     * Gets an SQL column alias for a column name.
     *
     * @param string $columnName
     * @return string
     */
    public function getSQLColumnAlias($columnName)
    {
        return $columnName . $this->_aliasCounter++;
    }

    /**
     * Generates the SQL JOINs that are necessary for Class Table Inheritance
     * for the given class.
     *
     * @param ClassMetadata $class The class for which to generate the joins.
     * @param string $dqlAlias The DQL alias of the class.
     * @return string The SQL.
     */
    private function _generateClassTableInheritanceJoins($class, $dqlAlias)
    {
        $sql = '';

        $baseTableAlias = $this->getSQLTableAlias($class->table['name'], $dqlAlias);

        // INNER JOIN parent class tables
        foreach ($class->parentClasses as $parentClassName) {
            $parentClass = $this->_em->getClassMetadata($parentClassName);
            $tableAlias = $this->getSQLTableAlias($parentClass->table['name'], $dqlAlias);
            // If this is a joined association we must use left joins to preserve the correct result.
            $sql .= isset($this->_queryComponents[$dqlAlias]['relation']) ? ' LEFT ' : ' INNER ';
            $sql .= 'JOIN ' . $parentClass->getQuotedTableName($this->_platform)
                  . ' ' . $tableAlias . ' ON ';
            $first = true;
            foreach ($class->identifier as $idField) {
                if ($first) $first = false; else $sql .= ' AND ';

                $columnName = $class->getQuotedColumnName($idField, $this->_platform);
                $sql .= $baseTableAlias . '.' . $columnName
                      . ' = '
                      . $tableAlias . '.' . $columnName;
            }
        }

        // LEFT JOIN subclass tables, if partial objects disallowed.
        if ( ! $this->_query->getHint(Query::HINT_FORCE_PARTIAL_LOAD)) {
            foreach ($class->subClasses as $subClassName) {
                $subClass = $this->_em->getClassMetadata($subClassName);
                $tableAlias = $this->getSQLTableAlias($subClass->table['name'], $dqlAlias);
                $sql .= ' LEFT JOIN ' . $subClass->getQuotedTableName($this->_platform)
                        . ' ' . $tableAlias . ' ON ';
                $first = true;
                foreach ($class->identifier as $idField) {
                    if ($first) $first = false; else $sql .= ' AND ';

                    $columnName = $class->getQuotedColumnName($idField, $this->_platform);
                    $sql .= $baseTableAlias . '.' . $columnName
                            . ' = '
                            . $tableAlias . '.' . $columnName;
                }
            }
        }

        return $sql;
    }

    private function _generateOrderedCollectionOrderByItems()
    {
        $sql = '';
        foreach ($this->_selectedClasses AS $dqlAlias => $class) {
            $qComp = $this->_queryComponents[$dqlAlias];
            if (isset($qComp['relation']['orderBy'])) {
                foreach ($qComp['relation']['orderBy'] AS $fieldName => $orientation) {
                    if ($qComp['metadata']->isInheritanceTypeJoined()) {
                        $tableName = $this->_em->getUnitOfWork()->getEntityPersister($class->name)->getOwningTable($fieldName);
                    } else {
                        $tableName = $qComp['metadata']->table['name'];
                    }

                    if ($sql != '') {
                        $sql .= ', ';
                    }
                    $sql .= $this->getSQLTableAlias($tableName, $dqlAlias) . '.' .
                            $qComp['metadata']->getQuotedColumnName($fieldName, $this->_platform) . " $orientation";
                }
            }
        }
        return $sql;
    }

    /**
     * Generates a discriminator column SQL condition for the class with the given DQL alias.
     *
     * @param array $dqlAliases List of root DQL aliases to inspect for discriminator restrictions.
     * @return string
     */
    private function _generateDiscriminatorColumnConditionSQL(array $dqlAliases)
    {
        $encapsulate = false;
        $sql = '';

        foreach ($dqlAliases as $dqlAlias) {
            $class = $this->_queryComponents[$dqlAlias]['metadata'];

            if ($class->isInheritanceTypeSingleTable()) {
                $conn = $this->_em->getConnection();
                $values = array();
                if ($class->discriminatorValue !== null) { // discrimnators can be 0
                    $values[] = $conn->quote($class->discriminatorValue);
                }

                foreach ($class->subClasses as $subclassName) {
                    $values[] = $conn->quote($this->_em->getClassMetadata($subclassName)->discriminatorValue);
                }

                if ($sql != '') {
                    $sql .= ' AND ';
                    $encapsulate = true;
                }

                $sql .= ($sql != '' ? ' AND ' : '')
                      . (($this->_useSqlTableAliases) ? $this->getSQLTableAlias($class->table['name'], $dqlAlias) . '.' : '')
                      . $class->discriminatorColumn['name'] . ' IN (' . implode(', ', $values) . ')';
            }
        }

        return ($encapsulate) ? '(' . $sql . ')' : $sql;
    }

    /**
     * Walks down a SelectStatement AST node, thereby generating the appropriate SQL.
     *
     * @return string The SQL.
     */
    public function walkSelectStatement(AST\SelectStatement $AST)
    {
        $sql = $this->walkSelectClause($AST->selectClause);
        $sql .= $this->walkFromClause($AST->fromClause);

        if (($whereClause = $AST->whereClause) !== null) {
            $sql .= $this->walkWhereClause($whereClause);
        } else if (($discSql = $this->_generateDiscriminatorColumnConditionSQL($this->_rootAliases)) !== '') {
            $sql .= ' WHERE ' . $discSql;
        }

        $sql .= $AST->groupByClause ? $this->walkGroupByClause($AST->groupByClause) : '';
        $sql .= $AST->havingClause ? $this->walkHavingClause($AST->havingClause) : '';

        if (($orderByClause = $AST->orderByClause) !== null) {
            $sql .= $AST->orderByClause ? $this->walkOrderByClause($AST->orderByClause) : '';
        } else if (($orderBySql = $this->_generateOrderedCollectionOrderByItems()) !== '') {
            $sql .= ' ORDER BY '.$orderBySql;
        }


        $sql = $this->_platform->modifyLimitQuery(
            $sql, $this->_query->getMaxResults(), $this->_query->getFirstResult()
        );

        if (($lockMode = $this->_query->getHint(Query::HINT_LOCK_MODE)) !== false) {
            if ($lockMode == LockMode::PESSIMISTIC_READ) {
                $sql .= " " . $this->_platform->getReadLockSQL();
            } else if ($lockMode == LockMode::PESSIMISTIC_WRITE) {
                $sql .= " " . $this->_platform->getWriteLockSQL();
            } else if ($lockMode == LockMode::OPTIMISTIC) {
                foreach ($this->_selectedClasses AS $class) {
                    if ( ! $class->isVersioned) {
                        throw \Doctrine\ORM\OptimisticLockException::lockFailed();
                    }
                }
            }
        }

        return $sql;
    }

    /**
     * Walks down an UpdateStatement AST node, thereby generating the appropriate SQL.
     *
     * @param UpdateStatement
     * @return string The SQL.
     */
    public function walkUpdateStatement(AST\UpdateStatement $AST)
    {
        $this->_useSqlTableAliases = false;
        $sql = $this->walkUpdateClause($AST->updateClause);

        if (($whereClause = $AST->whereClause) !== null) {
            $sql .= $this->walkWhereClause($whereClause);
        } else if (($discSql = $this->_generateDiscriminatorColumnConditionSQL($this->_rootAliases)) !== '') {
            $sql .= ' WHERE ' . $discSql;
        }

        return $sql;
    }

    /**
     * Walks down a DeleteStatement AST node, thereby generating the appropriate SQL.
     *
     * @param DeleteStatement
     * @return string The SQL.
     */
    public function walkDeleteStatement(AST\DeleteStatement $AST)
    {
        $this->_useSqlTableAliases = false;
        $sql = $this->walkDeleteClause($AST->deleteClause);

        if (($whereClause = $AST->whereClause) !== null) {
            $sql .= $this->walkWhereClause($whereClause);
        } else if (($discSql = $this->_generateDiscriminatorColumnConditionSQL($this->_rootAliases)) !== '') {
            $sql .= ' WHERE ' . $discSql;
        }

        return $sql;
    }


    /**
     * Walks down an IdentificationVariable (no AST node associated), thereby generating the SQL.
     *
     * @param string $identificationVariable
     * @param string $fieldName
     * @return string The SQL.
     */
    public function walkIdentificationVariable($identificationVariable, $fieldName = null)
    {
        $class = $this->_queryComponents[$identificationVariable]['metadata'];

        if (
            $fieldName !== null && $class->isInheritanceTypeJoined() &&
            isset($class->fieldMappings[$fieldName]['inherited'])
        ) {
            $class = $this->_em->getClassMetadata($class->fieldMappings[$fieldName]['inherited']);
        }

        return $this->getSQLTableAlias($class->table['name'], $identificationVariable);
    }

    /**
     * Walks down a PathExpression AST node, thereby generating the appropriate SQL.
     *
     * @param mixed
     * @return string The SQL.
     */
    public function walkPathExpression($pathExpr)
    {
        $sql = '';

        switch ($pathExpr->type) {
            case AST\PathExpression::TYPE_STATE_FIELD:
                $fieldName = $pathExpr->field;
                $dqlAlias = $pathExpr->identificationVariable;
                $class = $this->_queryComponents[$dqlAlias]['metadata'];

                if ($this->_useSqlTableAliases) {
                    $sql .= $this->walkIdentificationVariable($dqlAlias, $fieldName) . '.';
                }

                $sql .= $class->getQuotedColumnName($fieldName, $this->_platform);
                break;

            case AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION:
                // 1- the owning side:
                //    Just use the foreign key, i.e. u.group_id
                $fieldName = $pathExpr->field;
                $dqlAlias = $pathExpr->identificationVariable;
                $class = $this->_queryComponents[$dqlAlias]['metadata'];

                if (isset($class->associationMappings[$fieldName]['inherited'])) {
                    $class = $this->_em->getClassMetadata($class->associationMappings[$fieldName]['inherited']);
                }

                $assoc = $class->associationMappings[$fieldName];

                if ($assoc['isOwningSide']) {
                    // COMPOSITE KEYS NOT (YET?) SUPPORTED
                    if (count($assoc['sourceToTargetKeyColumns']) > 1) {
                        throw QueryException::associationPathCompositeKeyNotSupported();
                    }

                    if ($this->_useSqlTableAliases) {
                        $sql .= $this->getSQLTableAlias($class->table['name'], $dqlAlias) . '.';
                    }

                    $sql .= reset($assoc['targetToSourceKeyColumns']);
                } else {
                    throw QueryException::associationPathInverseSideNotSupported();
                }
                break;
                
            default:
                throw QueryException::invalidPathExpression($pathExpr);
        }

        return $sql;
    }

    /**
     * Walks down a SelectClause AST node, thereby generating the appropriate SQL.
     *
     * @param $selectClause
     * @return string The SQL.
     */
    public function walkSelectClause($selectClause)
    {
        $sql = 'SELECT ' . (($selectClause->isDistinct) ? 'DISTINCT ' : '') . implode(
            ', ', array_map(array($this, 'walkSelectExpression'), $selectClause->selectExpressions)
        );

        $addMetaColumns = ! $this->_query->getHint(Query::HINT_FORCE_PARTIAL_LOAD) &&
                $this->_query->getHydrationMode() == Query::HYDRATE_OBJECT
                ||
                $this->_query->getHydrationMode() != Query::HYDRATE_OBJECT &&
                $this->_query->getHint(Query::HINT_INCLUDE_META_COLUMNS);

        foreach ($this->_selectedClasses as $dqlAlias => $class) {
            // Register as entity or joined entity result
            if ($this->_queryComponents[$dqlAlias]['relation'] === null) {
                $this->_rsm->addEntityResult($class->name, $dqlAlias);
            } else {
                $this->_rsm->addJoinedEntityResult(
                    $class->name, $dqlAlias,
                    $this->_queryComponents[$dqlAlias]['parent'],
                    $this->_queryComponents[$dqlAlias]['relation']['fieldName']
                );
            }

            if ($class->isInheritanceTypeSingleTable() || $class->isInheritanceTypeJoined()) {
                // Add discriminator columns to SQL
                $rootClass = $this->_em->getClassMetadata($class->rootEntityName);
                $tblAlias = $this->getSQLTableAlias($rootClass->table['name'], $dqlAlias);
                $discrColumn = $rootClass->discriminatorColumn;
                $columnAlias = $this->getSQLColumnAlias($discrColumn['name']);
                $sql .= ", $tblAlias." . $discrColumn['name'] . ' AS ' . $columnAlias;

                $columnAlias = $this->_platform->getSQLResultCasing($columnAlias);
                $this->_rsm->setDiscriminatorColumn($dqlAlias, $columnAlias);
                $this->_rsm->addMetaResult($dqlAlias, $columnAlias, $discrColumn['fieldName']);

                // Add foreign key columns to SQL, if necessary
                if ($addMetaColumns) {
                    //FIXME: Include foreign key columns of child classes also!!??
                    foreach ($class->associationMappings as $assoc) {
                        if ($assoc['isOwningSide'] && $assoc['type'] & ClassMetadata::TO_ONE) {
                            if (isset($assoc['inherited'])) {
                                $owningClass = $this->_em->getClassMetadata($assoc['inherited']);
                                $sqlTableAlias = $this->getSQLTableAlias($owningClass->table['name'], $dqlAlias);
                            } else {
                                $sqlTableAlias = $this->getSQLTableAlias($class->table['name'], $dqlAlias);
                            }
                            
                            foreach ($assoc['targetToSourceKeyColumns'] as $srcColumn) {
                                $columnAlias = $this->getSQLColumnAlias($srcColumn);
                                $sql .= ", $sqlTableAlias." . $srcColumn . ' AS ' . $columnAlias;
                                $columnAlias = $this->_platform->getSQLResultCasing($columnAlias);
                                $this->_rsm->addMetaResult($dqlAlias, $this->_platform->getSQLResultCasing($columnAlias), $srcColumn, (isset($assoc['id']) && $assoc['id'] === true));
                            }
                        }
                    }
                }
            } else {
                // Add foreign key columns to SQL, if necessary
                if ($addMetaColumns) {
                    $sqlTableAlias = $this->getSQLTableAlias($class->table['name'], $dqlAlias);
                    foreach ($class->associationMappings as $assoc) {
                        if ($assoc['isOwningSide'] && $assoc['type'] & ClassMetadata::TO_ONE) {
                            foreach ($assoc['targetToSourceKeyColumns'] as $srcColumn) {
                                $columnAlias = $this->getSQLColumnAlias($srcColumn);
                                $sql .= ', ' . $sqlTableAlias . '.' . $srcColumn . ' AS ' . $columnAlias;
                                $columnAlias = $this->_platform->getSQLResultCasing($columnAlias);
                                $this->_rsm->addMetaResult($dqlAlias, $this->_platform->getSQLResultCasing($columnAlias), $srcColumn, (isset($assoc['id']) && $assoc['id'] === true));
                            }
                        }
                    }
                }
            }
        }

        return $sql;
    }

    /**
     * Walks down a FromClause AST node, thereby generating the appropriate SQL.
     *
     * @return string The SQL.
     */
    public function walkFromClause($fromClause)
    {
        $identificationVarDecls = $fromClause->identificationVariableDeclarations;
        $sqlParts = array();

        foreach ($identificationVarDecls as $identificationVariableDecl) {
            $sql = '';

            $rangeDecl = $identificationVariableDecl->rangeVariableDeclaration;
            $dqlAlias = $rangeDecl->aliasIdentificationVariable;
        
            $this->_rootAliases[] = $dqlAlias;

            $class = $this->_em->getClassMetadata($rangeDecl->abstractSchemaName);
            $sql .= $class->getQuotedTableName($this->_platform) . ' '
                  . $this->getSQLTableAlias($class->table['name'], $dqlAlias);

            if ($class->isInheritanceTypeJoined()) {
                $sql .= $this->_generateClassTableInheritanceJoins($class, $dqlAlias);
            }

            foreach ($identificationVariableDecl->joinVariableDeclarations as $joinVarDecl) {
                $sql .= $this->walkJoinVariableDeclaration($joinVarDecl);
            }

            if ($identificationVariableDecl->indexBy) {
                $this->_rsm->addIndexBy(
                    $identificationVariableDecl->indexBy->simpleStateFieldPathExpression->identificationVariable,
                    $identificationVariableDecl->indexBy->simpleStateFieldPathExpression->field
                );
            }

            $sqlParts[] = $this->_platform->appendLockHint($sql, $this->_query->getHint(Query::HINT_LOCK_MODE));
        }

        return ' FROM ' . implode(', ', $sqlParts);
    }

    /**
     * Walks down a FunctionNode AST node, thereby generating the appropriate SQL.
     *
     * @return string The SQL.
     */
    public function walkFunction($function)
    {
        return $function->getSql($this);
    }

    /**
     * Walks down an OrderByClause AST node, thereby generating the appropriate SQL.
     *
     * @param OrderByClause
     * @return string The SQL.
     */
    public function walkOrderByClause($orderByClause)
    {
        $colSql = $this->_generateOrderedCollectionOrderByItems();
        if ($colSql != '') {
            $colSql = ", ".$colSql;
        }

        // OrderByClause ::= "ORDER" "BY" OrderByItem {"," OrderByItem}*
        return ' ORDER BY ' . implode(
            ', ', array_map(array($this, 'walkOrderByItem'), $orderByClause->orderByItems)
        )  . $colSql;
    }

    /**
     * Walks down an OrderByItem AST node, thereby generating the appropriate SQL.
     *
     * @param OrderByItem
     * @return string The SQL.
     */
    public function walkOrderByItem($orderByItem)
    {
        $sql = '';
        $expr = $orderByItem->expression;

        if ($expr instanceof AST\PathExpression) {
            $sql = $this->walkPathExpression($expr);
        } else {
            $columnName = $this->_queryComponents[$expr]['token']['value'];

            $sql = $this->_scalarResultAliasMap[$columnName];
        }

        return $sql . ' ' . strtoupper($orderByItem->type);
    }

    /**
     * Walks down a HavingClause AST node, thereby generating the appropriate SQL.
     *
     * @param HavingClause
     * @return string The SQL.
     */
    public function walkHavingClause($havingClause)
    {
        return ' HAVING ' . $this->walkConditionalExpression($havingClause->conditionalExpression);
    }

    /**
     * Walks down a JoinVariableDeclaration AST node and creates the corresponding SQL.
     *
     * @param JoinVariableDeclaration $joinVarDecl
     * @return string The SQL.
     */
    public function walkJoinVariableDeclaration($joinVarDecl)
    {
        $join = $joinVarDecl->join;
        $joinType = $join->joinType;

        if ($joinVarDecl->indexBy) {
            // For Many-To-One or One-To-One associations this obviously makes no sense, but is ignored silently.
            $this->_rsm->addIndexBy(
                $joinVarDecl->indexBy->simpleStateFieldPathExpression->identificationVariable,
                $joinVarDecl->indexBy->simpleStateFieldPathExpression->field
            );
        }

        if ($joinType == AST\Join::JOIN_TYPE_LEFT || $joinType == AST\Join::JOIN_TYPE_LEFTOUTER) {
            $sql = ' LEFT JOIN ';
        } else {
            $sql = ' INNER JOIN ';
        }

        $joinAssocPathExpr = $join->joinAssociationPathExpression;
        $joinedDqlAlias = $join->aliasIdentificationVariable;
        $relation = $this->_queryComponents[$joinedDqlAlias]['relation'];
        $targetClass = $this->_em->getClassMetadata($relation['targetEntity']);
        $sourceClass = $this->_em->getClassMetadata($relation['sourceEntity']);
        $targetTableName = $targetClass->getQuotedTableName($this->_platform);
        $targetTableAlias = $this->getSQLTableAlias($targetClass->table['name'], $joinedDqlAlias);
        $sourceTableAlias = $this->getSQLTableAlias($sourceClass->table['name'], $joinAssocPathExpr->identificationVariable);

        // Ensure we got the owning side, since it has all mapping info
        $assoc = ( ! $relation['isOwningSide']) ? $targetClass->associationMappings[$relation['mappedBy']] : $relation;

        if ($this->_query->getHint(Query::HINT_INTERNAL_ITERATION) == true) {
            if ($relation['type'] == ClassMetadata::ONE_TO_MANY || $relation['type'] == ClassMetadata::MANY_TO_MANY) {
                throw QueryException::iterateWithFetchJoinNotAllowed($assoc);
            }
        }

        if ($joinVarDecl->indexBy) {
            // For Many-To-One or One-To-One associations this obviously makes no sense, but is ignored silently.
            $this->_rsm->addIndexBy(
                $joinVarDecl->indexBy->simpleStateFieldPathExpression->identificationVariable,
                $joinVarDecl->indexBy->simpleStateFieldPathExpression->field
            );
        } else if (isset($relation['indexBy'])) {
            $this->_rsm->addIndexBy($joinedDqlAlias, $relation['indexBy']);
        }

        // This condition is not checking ClassMetadata::MANY_TO_ONE, because by definition it cannot
        // be the owning side and previously we ensured that $assoc is always the owning side of the associations.
        // The owning side is necessary at this point because only it contains the JoinColumn information.
        if ($assoc['type'] & ClassMetadata::TO_ONE) {

            $sql .= $targetTableName . ' ' . $targetTableAlias . ' ON ';
            $first = true;

            foreach ($assoc['sourceToTargetKeyColumns'] as $sourceColumn => $targetColumn) {
                if ( ! $first) $sql .= ' AND '; else $first = false;

                if ($relation['isOwningSide']) {
                    if ($targetClass->containsForeignIdentifier && !isset($targetClass->fieldNames[$targetColumn])) {
                        $quotedTargetColumn = $targetColumn; // Join columns cannot be quoted.
                    } else {
                        $quotedTargetColumn = $targetClass->getQuotedColumnName($targetClass->fieldNames[$targetColumn], $this->_platform);
                    }
                    $sql .= $sourceTableAlias . '.' . $sourceColumn . ' = ' . $targetTableAlias . '.' . $quotedTargetColumn;
                } else {
                    if ($sourceClass->containsForeignIdentifier && !isset($sourceClass->fieldNames[$targetColumn])) {
                        $quotedTargetColumn = $targetColumn; // Join columns cannot be quoted.
                    } else {
                        $quotedTargetColumn = $sourceClass->getQuotedColumnName($sourceClass->fieldNames[$targetColumn], $this->_platform);
                    }
                    $sql .= $sourceTableAlias . '.' . $quotedTargetColumn . ' = ' . $targetTableAlias . '.' . $sourceColumn;
                }
            }
        } else if ($assoc['type'] == ClassMetadata::MANY_TO_MANY) {
            // Join relation table
            $joinTable = $assoc['joinTable'];
            $joinTableAlias = $this->getSQLTableAlias($joinTable['name'], $joinedDqlAlias);
            $sql .= $sourceClass->getQuotedJoinTableName($assoc, $this->_platform) . ' ' . $joinTableAlias . ' ON ';

            $first = true;
            if ($relation['isOwningSide']) {
                foreach ($assoc['relationToSourceKeyColumns'] as $relationColumn => $sourceColumn) {
                    if ( ! $first) $sql .= ' AND '; else $first = false;

                    if ($sourceClass->containsForeignIdentifier && !isset($sourceClass->fieldNames[$sourceColumn])) {
                        $quotedTargetColumn = $sourceColumn; // Join columns cannot be quoted.
                    } else {
                        $quotedTargetColumn = $sourceClass->getQuotedColumnName($sourceClass->fieldNames[$sourceColumn], $this->_platform);
                    }

                    $sql .= $sourceTableAlias . '.' . $quotedTargetColumn . ' = ' . $joinTableAlias . '.' . $relationColumn;
                }
            } else {
                foreach ($assoc['relationToTargetKeyColumns'] as $relationColumn => $targetColumn) {
                    if ( ! $first) $sql .= ' AND '; else $first = false;

                    if ($sourceClass->containsForeignIdentifier && !isset($sourceClass->fieldNames[$targetColumn])) {
                        $quotedTargetColumn = $targetColumn; // Join columns cannot be quoted.
                    } else {
                        $quotedTargetColumn = $sourceClass->getQuotedColumnName($sourceClass->fieldNames[$targetColumn], $this->_platform);
                    }

                    $sql .= $sourceTableAlias . '.' . $quotedTargetColumn . ' = ' . $joinTableAlias . '.' . $relationColumn;
                }
            }

            // Join target table
            $sql .= ($joinType == AST\Join::JOIN_TYPE_LEFT || $joinType == AST\Join::JOIN_TYPE_LEFTOUTER)
            	? ' LEFT JOIN ' : ' INNER JOIN ';
            $sql .= $targetTableName . ' ' . $targetTableAlias . ' ON ';

            $first = true;
            if ($relation['isOwningSide']) {
                foreach ($assoc['relationToTargetKeyColumns'] as $relationColumn => $targetColumn) {
                    if ( ! $first) $sql .= ' AND '; else $first = false;

                    if ($targetClass->containsForeignIdentifier && !isset($targetClass->fieldNames[$targetColumn])) {
                        $quotedTargetColumn = $targetColumn; // Join columns cannot be quoted.
                    } else {
                        $quotedTargetColumn = $targetClass->getQuotedColumnName($targetClass->fieldNames[$targetColumn], $this->_platform);
                    }

                    $sql .= $targetTableAlias . '.' . $quotedTargetColumn . ' = ' . $joinTableAlias . '.' . $relationColumn;
                }
            } else {
                foreach ($assoc['relationToSourceKeyColumns'] as $relationColumn => $sourceColumn) {
                    if ( ! $first) $sql .= ' AND '; else $first = false;

                    if ($targetClass->containsForeignIdentifier && !isset($targetClass->fieldNames[$sourceColumn])) {
                        $quotedTargetColumn = $sourceColumn; // Join columns cannot be quoted.
                    } else {
                        $quotedTargetColumn = $targetClass->getQuotedColumnName($targetClass->fieldNames[$sourceColumn], $this->_platform);
                    }

                    $sql .= $targetTableAlias . '.' . $quotedTargetColumn . ' = ' . $joinTableAlias . '.' . $relationColumn;
                }
            }
        }

        // Handle WITH clause
        if (($condExpr = $join->conditionalExpression) !== null) {
            // Phase 2 AST optimization: Skip processment of ConditionalExpression
            // if only one ConditionalTerm is defined
            $sql .= ' AND (' . $this->walkConditionalExpression($condExpr) . ')';
        }

        $discrSql = $this->_generateDiscriminatorColumnConditionSQL(array($joinedDqlAlias));

        if ($discrSql) {
            $sql .= ' AND ' . $discrSql;
        }

        // FIXME: these should either be nested or all forced to be left joins (DDC-XXX)
        if ($targetClass->isInheritanceTypeJoined()) {
            $sql .= $this->_generateClassTableInheritanceJoins($targetClass, $joinedDqlAlias);
        }

        return $sql;
    }
    
    /**
     * Walks down a CoalesceExpression AST node and generates the corresponding SQL.
     *
     * @param CoalesceExpression $coalesceExpression
     * @return string The SQL.
     */
    public function walkCoalesceExpression($coalesceExpression)
    {
        $sql = 'COALESCE(';
        
        $scalarExpressions = array();
        
        foreach ($coalesceExpression->scalarExpressions as $scalarExpression) {
            $scalarExpressions[] = $this->walkSimpleArithmeticExpression($scalarExpression);
        }
        
        $sql .= implode(', ', $scalarExpressions) . ')';
        
        return $sql;
    }
    
    public function walkCaseExpression($expression)
    {
        switch (true) {
            case ($expression instanceof AST\CoalesceExpression):
                return $this->walkCoalesceExpression($expression);
                
            case ($expression instanceof AST\NullIfExpression):
                return $this->walkNullIfExpression($expression);
                
            default:
                return '';
        }
    }
    
    /**
     * Walks down a NullIfExpression AST node and generates the corresponding SQL.
     *
     * @param NullIfExpression $nullIfExpression
     * @return string The SQL.
     */
    public function walkNullIfExpression($nullIfExpression)
    {
        $firstExpression = is_string($nullIfExpression->firstExpression) 
            ? $this->_conn->quote($nullIfExpression->firstExpression)
            : $this->walkSimpleArithmeticExpression($nullIfExpression->firstExpression);
        
        $secondExpression = is_string($nullIfExpression->secondExpression) 
            ? $this->_conn->quote($nullIfExpression->secondExpression)
            : $this->walkSimpleArithmeticExpression($nullIfExpression->secondExpression);
        
        return 'NULLIF(' . $firstExpression . ', ' . $secondExpression . ')';
    }

    /**
     * Walks down a SelectExpression AST node and generates the corresponding SQL.
     *
     * @param SelectExpression $selectExpression
     * @return string The SQL.
     */
    public function walkSelectExpression($selectExpression)
    {
        $sql = '';
        $expr = $selectExpression->expression;

        if ($expr instanceof AST\PathExpression) {
            if ($expr->type == AST\PathExpression::TYPE_STATE_FIELD) {
                $fieldName = $expr->field;
                $dqlAlias = $expr->identificationVariable;
                $qComp = $this->_queryComponents[$dqlAlias];
                $class = $qComp['metadata'];

                if ( ! $selectExpression->fieldIdentificationVariable) {
                    $resultAlias = $fieldName;
                } else {
                    $resultAlias = $selectExpression->fieldIdentificationVariable;
                }

                if ($class->isInheritanceTypeJoined()) {
                    $tableName = $this->_em->getUnitOfWork()->getEntityPersister($class->name)->getOwningTable($fieldName);
                } else {
                    $tableName = $class->getTableName();
                }

                $sqlTableAlias = $this->getSQLTableAlias($tableName, $dqlAlias);
                $columnName = $class->getQuotedColumnName($fieldName, $this->_platform);

                $columnAlias = $this->getSQLColumnAlias($columnName);
                $sql .= $sqlTableAlias . '.' . $columnName . ' AS ' . $columnAlias;
                $columnAlias = $this->_platform->getSQLResultCasing($columnAlias);
                $this->_rsm->addScalarResult($columnAlias, $resultAlias);
            } else {
                throw QueryException::invalidPathExpression($expr->type);
            }
        }
        else if ($expr instanceof AST\AggregateExpression) {
            if ( ! $selectExpression->fieldIdentificationVariable) {
                $resultAlias = $this->_scalarResultCounter++;
            } else {
                $resultAlias = $selectExpression->fieldIdentificationVariable;
            }

            $columnAlias = 'sclr' . $this->_aliasCounter++;
            $sql .= $this->walkAggregateExpression($expr) . ' AS ' . $columnAlias;
            $this->_scalarResultAliasMap[$resultAlias] = $columnAlias;

            $columnAlias = $this->_platform->getSQLResultCasing($columnAlias);
            $this->_rsm->addScalarResult($columnAlias, $resultAlias);
        }
        else if ($expr instanceof AST\Subselect) {
            if ( ! $selectExpression->fieldIdentificationVariable) {
                $resultAlias = $this->_scalarResultCounter++;
            } else {
                $resultAlias = $selectExpression->fieldIdentificationVariable;
            }

            $columnAlias = 'sclr' . $this->_aliasCounter++;
            $sql .= '(' . $this->walkSubselect($expr) . ') AS '.$columnAlias;
            $this->_scalarResultAliasMap[$resultAlias] = $columnAlias;

            $columnAlias = $this->_platform->getSQLResultCasing($columnAlias);
            $this->_rsm->addScalarResult($columnAlias, $resultAlias);
        }
        else if ($expr instanceof AST\Functions\FunctionNode) {
            if ( ! $selectExpression->fieldIdentificationVariable) {
                $resultAlias = $this->_scalarResultCounter++;
            } else {
                $resultAlias = $selectExpression->fieldIdentificationVariable;
            }

            $columnAlias = 'sclr' . $this->_aliasCounter++;
            $sql .= $this->walkFunction($expr) . ' AS ' . $columnAlias;
            $this->_scalarResultAliasMap[$resultAlias] = $columnAlias;

            $columnAlias = $this->_platform->getSQLResultCasing($columnAlias);
            $this->_rsm->addScalarResult($columnAlias, $resultAlias);
        } else if (
            $expr instanceof AST\SimpleArithmeticExpression ||
            $expr instanceof AST\ArithmeticTerm ||
            $expr instanceof AST\ArithmeticFactor ||
            $expr instanceof AST\ArithmeticPrimary ||
            $expr instanceof AST\Literal
        ) {
            if ( ! $selectExpression->fieldIdentificationVariable) {
                $resultAlias = $this->_scalarResultCounter++;
            } else {
                $resultAlias = $selectExpression->fieldIdentificationVariable;
            }

            $columnAlias = 'sclr' . $this->_aliasCounter++;
            
            if ($expr instanceof AST\Literal) {
                $sql .= $this->walkLiteral($expr) . ' AS ' .$columnAlias;
            } else {
                $sql .= $this->walkSimpleArithmeticExpression($expr) . ' AS ' . $columnAlias;
            }
            
            $this->_scalarResultAliasMap[$resultAlias] = $columnAlias;

            $columnAlias = $this->_platform->getSQLResultCasing($columnAlias);
            $this->_rsm->addScalarResult($columnAlias, $resultAlias);
        } else if (
            $expr instanceof AST\NullIfExpression ||
            $expr instanceof AST\CoalesceExpression ||
            $expr instanceof AST\CaseExpression
        ) {
            if ( ! $selectExpression->fieldIdentificationVariable) {
                $resultAlias = $this->_scalarResultCounter++;
            } else {
                $resultAlias = $selectExpression->fieldIdentificationVariable;
            }

            $columnAlias = 'sclr' . $this->_aliasCounter++;
            
            $sql .= $this->walkCaseExpression($expr) . ' AS ' . $columnAlias;
            
            $this->_scalarResultAliasMap[$resultAlias] = $columnAlias;

            $columnAlias = $this->_platform->getSQLResultCasing($columnAlias);
            $this->_rsm->addScalarResult($columnAlias, $resultAlias);
        } else {
            // IdentificationVariable or PartialObjectExpression
            if ($expr instanceof AST\PartialObjectExpression) {
                $dqlAlias = $expr->identificationVariable;
                $partialFieldSet = $expr->partialFieldSet;
            } else {
                $dqlAlias = $expr;
                $partialFieldSet = array();
            }

            $queryComp = $this->_queryComponents[$dqlAlias];
            $class = $queryComp['metadata'];

            if ( ! isset($this->_selectedClasses[$dqlAlias])) {
                $this->_selectedClasses[$dqlAlias] = $class;
            }

            $beginning = true;
            // Select all fields from the queried class
            foreach ($class->fieldMappings as $fieldName => $mapping) {
                if ($partialFieldSet && !in_array($fieldName, $partialFieldSet)) {
                    continue;
                }

                if (isset($mapping['inherited'])) {
                    $tableName = $this->_em->getClassMetadata($mapping['inherited'])->table['name'];
                } else {
                    $tableName = $class->table['name'];
                }

                if ($beginning) $beginning = false; else $sql .= ', ';

                $sqlTableAlias = $this->getSQLTableAlias($tableName, $dqlAlias);
                $columnAlias = $this->getSQLColumnAlias($mapping['columnName']);
                $sql .= $sqlTableAlias . '.' . $class->getQuotedColumnName($fieldName, $this->_platform)
                      . ' AS ' . $columnAlias;

                $columnAlias = $this->_platform->getSQLResultCasing($columnAlias);
                $this->_rsm->addFieldResult($dqlAlias, $columnAlias, $fieldName, $class->name);
            }

            // Add any additional fields of subclasses (excluding inherited fields)
            // 1) on Single Table Inheritance: always, since its marginal overhead
            // 2) on Class Table Inheritance only if partial objects are disallowed,
            //    since it requires outer joining subtables.
            if ($class->isInheritanceTypeSingleTable() || ! $this->_query->getHint(Query::HINT_FORCE_PARTIAL_LOAD)) {
                foreach ($class->subClasses as $subClassName) {
                    $subClass = $this->_em->getClassMetadata($subClassName);
                    $sqlTableAlias = $this->getSQLTableAlias($subClass->table['name'], $dqlAlias);
                    foreach ($subClass->fieldMappings as $fieldName => $mapping) {
                        if (isset($mapping['inherited']) || $partialFieldSet && !in_array($fieldName, $partialFieldSet)) {
                            continue;
                        }

                        if ($beginning) $beginning = false; else $sql .= ', ';

                        $columnAlias = $this->getSQLColumnAlias($mapping['columnName']);
                        $sql .= $sqlTableAlias . '.' . $subClass->getQuotedColumnName($fieldName, $this->_platform)
                                . ' AS ' . $columnAlias;

                        $columnAlias = $this->_platform->getSQLResultCasing($columnAlias);
                        $this->_rsm->addFieldResult($dqlAlias, $columnAlias, $fieldName, $subClassName);
                    }

                    // Add join columns (foreign keys) of the subclass
                    //TODO: Probably better do this in walkSelectClause to honor the INCLUDE_META_COLUMNS hint
                    foreach ($subClass->associationMappings as $fieldName => $assoc) {
                        if ($assoc['isOwningSide'] && $assoc['type'] & ClassMetadata::TO_ONE && ! isset($assoc['inherited'])) {
                            foreach ($assoc['targetToSourceKeyColumns'] as $srcColumn) {
                                if ($beginning) $beginning = false; else $sql .= ', ';
                                $columnAlias = $this->getSQLColumnAlias($srcColumn);
                                $sql .= $sqlTableAlias . '.' . $srcColumn . ' AS ' . $columnAlias;
                                $this->_rsm->addMetaResult($dqlAlias, $this->_platform->getSQLResultCasing($columnAlias), $srcColumn);
                            }
                        }
                    }
                }
            }
        }

        return $sql;
    }

    /**
     * Walks down a QuantifiedExpression AST node, thereby generating the appropriate SQL.
     *
     * @param QuantifiedExpression
     * @return string The SQL.
     */
    public function walkQuantifiedExpression($qExpr)
    {
        return ' ' . strtoupper($qExpr->type)
             . '(' . $this->walkSubselect($qExpr->subselect) . ')';
    }

    /**
     * Walks down a Subselect AST node, thereby generating the appropriate SQL.
     *
     * @param Subselect
     * @return string The SQL.
     */
    public function walkSubselect($subselect)
    {
        $useAliasesBefore = $this->_useSqlTableAliases;
        $this->_useSqlTableAliases = true;

        $sql = $this->walkSimpleSelectClause($subselect->simpleSelectClause);
        $sql .= $this->walkSubselectFromClause($subselect->subselectFromClause);
        $sql .= $subselect->whereClause ? $this->walkWhereClause($subselect->whereClause) : '';
        $sql .= $subselect->groupByClause ? $this->walkGroupByClause($subselect->groupByClause) : '';
        $sql .= $subselect->havingClause ? $this->walkHavingClause($subselect->havingClause) : '';
        $sql .= $subselect->orderByClause ? $this->walkOrderByClause($subselect->orderByClause) : '';

        $this->_useSqlTableAliases = $useAliasesBefore;

        return $sql;
    }

    /**
     * Walks down a SubselectFromClause AST node, thereby generating the appropriate SQL.
     *
     * @param SubselectFromClause
     * @return string The SQL.
     */
    public function walkSubselectFromClause($subselectFromClause)
    {
        $identificationVarDecls = $subselectFromClause->identificationVariableDeclarations;
        $sqlParts = array ();

        foreach ($identificationVarDecls as $subselectIdVarDecl) {
            $sql = '';

            $rangeDecl = $subselectIdVarDecl->rangeVariableDeclaration;
            $dqlAlias = $rangeDecl->aliasIdentificationVariable;

            $class = $this->_em->getClassMetadata($rangeDecl->abstractSchemaName);
            $sql .= $class->getQuotedTableName($this->_platform) . ' '
                  . $this->getSQLTableAlias($class->table['name'], $dqlAlias);

            if ($class->isInheritanceTypeJoined()) {
                $sql .= $this->_generateClassTableInheritanceJoins($class, $dqlAlias);
            }

            foreach ($subselectIdVarDecl->joinVariableDeclarations as $joinVarDecl) {
                $sql .= $this->walkJoinVariableDeclaration($joinVarDecl);
            }

            $sqlParts[] = $this->_platform->appendLockHint($sql, $this->_query->getHint(Query::HINT_LOCK_MODE));
        }

        return ' FROM ' . implode(', ', $sqlParts);
    }

    /**
     * Walks down a SimpleSelectClause AST node, thereby generating the appropriate SQL.
     *
     * @param SimpleSelectClause
     * @return string The SQL.
     */
    public function walkSimpleSelectClause($simpleSelectClause)
    {
        return 'SELECT' . ($simpleSelectClause->isDistinct ? ' DISTINCT' : '')
             . $this->walkSimpleSelectExpression($simpleSelectClause->simpleSelectExpression);
    }

    /**
     * Walks down a SimpleSelectExpression AST node, thereby generating the appropriate SQL.
     *
     * @param SimpleSelectExpression
     * @return string The SQL.
     */
    public function walkSimpleSelectExpression($simpleSelectExpression)
    {
        $sql = '';
        $expr = $simpleSelectExpression->expression;

        if ($expr instanceof AST\PathExpression) {
            $sql .= $this->walkPathExpression($expr);
        } else if ($expr instanceof AST\AggregateExpression) {
            if ( ! $simpleSelectExpression->fieldIdentificationVariable) {
                $alias = $this->_scalarResultCounter++;
            } else {
                $alias = $simpleSelectExpression->fieldIdentificationVariable;
            }

            $sql .= $this->walkAggregateExpression($expr) . ' AS dctrn__' . $alias;
        } else if ($expr instanceof AST\Subselect) {
            if ( ! $simpleSelectExpression->fieldIdentificationVariable) {
                $alias = $this->_scalarResultCounter++;
            } else {
                $alias = $simpleSelectExpression->fieldIdentificationVariable;
            }

            $columnAlias = 'sclr' . $this->_aliasCounter++;
            $sql .= '(' . $this->walkSubselect($expr) . ') AS ' . $columnAlias;
            $this->_scalarResultAliasMap[$alias] = $columnAlias;
        } else if ($expr instanceof AST\Functions\FunctionNode) {
            if ( ! $simpleSelectExpression->fieldIdentificationVariable) {
                $alias = $this->_scalarResultCounter++;
            } else {
                $alias = $simpleSelectExpression->fieldIdentificationVariable;
            }

            $columnAlias = 'sclr' . $this->_aliasCounter++;
            $sql .= $this->walkFunction($expr) . ' AS ' . $columnAlias;
            $this->_scalarResultAliasMap[$alias] = $columnAlias;
        } else if (
            $expr instanceof AST\SimpleArithmeticExpression ||
            $expr instanceof AST\ArithmeticTerm ||
            $expr instanceof AST\ArithmeticFactor ||
            $expr instanceof AST\ArithmeticPrimary
        ) {
            if ( ! $simpleSelectExpression->fieldIdentificationVariable) {
                $alias = $this->_scalarResultCounter++;
            } else {
                $alias = $simpleSelectExpression->fieldIdentificationVariable;
            }

            $columnAlias = 'sclr' . $this->_aliasCounter++;
            $sql .= $this->walkSimpleArithmeticExpression($expr) . ' AS ' . $columnAlias;
            $this->_scalarResultAliasMap[$alias] = $columnAlias;
        } else {
            // IdentificationVariable
            $class = $this->_queryComponents[$expr]['metadata'];
            $tableAlias = $this->getSQLTableAlias($class->getTableName(), $expr);
            $first = true;

            foreach ($class->identifier as $identifier) {
                if ($first) $first = false; else $sql .= ', ';
                $sql .= $tableAlias . '.' . $class->getQuotedColumnName($identifier, $this->_platform);
            }
        }

        return ' ' . $sql;
    }

    /**
     * Walks down an AggregateExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AggregateExpression
     * @return string The SQL.
     */
    public function walkAggregateExpression($aggExpression)
    {
        return $aggExpression->functionName . '(' . ($aggExpression->isDistinct ? 'DISTINCT ' : '')
             . $this->walkSimpleArithmeticExpression($aggExpression->pathExpression) . ')';
    }

    /**
     * Walks down a GroupByClause AST node, thereby generating the appropriate SQL.
     *
     * @param GroupByClause
     * @return string The SQL.
     */
    public function walkGroupByClause($groupByClause)
    {
        $sql = '';
        foreach ($groupByClause->groupByItems AS $groupByItem) {
            if (is_string($groupByItem)) {
                foreach ($this->_queryComponents[$groupByItem]['metadata']->identifier AS $idField) {
                    if ($sql != '') {
                        $sql .= ', ';
                    }
                    $groupByItem = new AST\PathExpression(AST\PathExpression::TYPE_STATE_FIELD, $groupByItem, $idField);
                    $groupByItem->type = AST\PathExpression::TYPE_STATE_FIELD;
                    $sql .= $this->walkGroupByItem($groupByItem);
                }
            } else {
                if ($sql != '') {
                    $sql .= ', ';
                }
                $sql .= $this->walkGroupByItem($groupByItem);
            }
        }
        return ' GROUP BY ' . $sql;
    }

    /**
     * Walks down a GroupByItem AST node, thereby generating the appropriate SQL.
     *
     * @param GroupByItem
     * @return string The SQL.
     */
    public function walkGroupByItem(AST\PathExpression $pathExpr)
    {
        return $this->walkPathExpression($pathExpr);
    }

    /**
     * Walks down a DeleteClause AST node, thereby generating the appropriate SQL.
     *
     * @param DeleteClause
     * @return string The SQL.
     */
    public function walkDeleteClause(AST\DeleteClause $deleteClause)
    {
        $sql = 'DELETE FROM ';
        $class = $this->_em->getClassMetadata($deleteClause->abstractSchemaName);
        $sql .= $class->getQuotedTableName($this->_platform);

        $this->setSQLTableAlias($class->getTableName(), $class->getTableName(), $deleteClause->aliasIdentificationVariable);

        $this->_rootAliases[] = $deleteClause->aliasIdentificationVariable;

        return $sql;
    }

    /**
     * Walks down an UpdateClause AST node, thereby generating the appropriate SQL.
     *
     * @param UpdateClause
     * @return string The SQL.
     */
    public function walkUpdateClause($updateClause)
    {
        $sql = 'UPDATE ';
        $class = $this->_em->getClassMetadata($updateClause->abstractSchemaName);
        $sql .= $class->getQuotedTableName($this->_platform);

        $this->setSQLTableAlias($class->getTableName(), $class->getTableName(), $updateClause->aliasIdentificationVariable);

        $this->_rootAliases[] = $updateClause->aliasIdentificationVariable;

        $sql .= ' SET ' . implode(
            ', ', array_map(array($this, 'walkUpdateItem'), $updateClause->updateItems)
        );

        return $sql;
    }

    /**
     * Walks down an UpdateItem AST node, thereby generating the appropriate SQL.
     *
     * @param UpdateItem
     * @return string The SQL.
     */
    public function walkUpdateItem($updateItem)
    {
        $useTableAliasesBefore = $this->_useSqlTableAliases;
        $this->_useSqlTableAliases = false;

        $sql = $this->walkPathExpression($updateItem->pathExpression) . ' = ';

        $newValue = $updateItem->newValue;

        if ($newValue === null) {
            $sql .= 'NULL';
        } else if ($newValue instanceof AST\Node) {
            $sql .= $newValue->dispatch($this);
        } else {
            $sql .= $this->_conn->quote($newValue);
        }

        $this->_useSqlTableAliases = $useTableAliasesBefore;

        return $sql;
    }

    /**
     * Walks down a WhereClause AST node, thereby generating the appropriate SQL.
     *
     * @param WhereClause
     * @return string The SQL.
     */
    public function walkWhereClause($whereClause)
    {
        $discrSql = $this->_generateDiscriminatorColumnConditionSql($this->_rootAliases);
        $condSql = $this->walkConditionalExpression($whereClause->conditionalExpression);

        return ' WHERE ' . (( ! $discrSql) ? $condSql : '(' . $condSql . ') AND ' . $discrSql);
    }

    /**
     * Walk down a ConditionalExpression AST node, thereby generating the appropriate SQL.
     *
     * @param ConditionalExpression
     * @return string The SQL.
     */
    public function walkConditionalExpression($condExpr)
    {
        // Phase 2 AST optimization: Skip processment of ConditionalExpression
        // if only one ConditionalTerm is defined
        return ( ! ($condExpr instanceof AST\ConditionalExpression))
            ? $this->walkConditionalTerm($condExpr)
            : implode(
                ' OR ', array_map(array($this, 'walkConditionalTerm'), $condExpr->conditionalTerms)
            );
    }

    /**
     * Walks down a ConditionalTerm AST node, thereby generating the appropriate SQL.
     *
     * @param ConditionalTerm
     * @return string The SQL.
     */
    public function walkConditionalTerm($condTerm)
    {
        // Phase 2 AST optimization: Skip processment of ConditionalTerm
        // if only one ConditionalFactor is defined
        return ( ! ($condTerm instanceof AST\ConditionalTerm))
            ? $this->walkConditionalFactor($condTerm)
            : implode(
                ' AND ', array_map(array($this, 'walkConditionalFactor'), $condTerm->conditionalFactors)
            );
    }

    /**
     * Walks down a ConditionalFactor AST node, thereby generating the appropriate SQL.
     *
     * @param ConditionalFactor
     * @return string The SQL.
     */
    public function walkConditionalFactor($factor)
    {
        // Phase 2 AST optimization: Skip processment of ConditionalFactor
        // if only one ConditionalPrimary is defined
        return ( ! ($factor instanceof AST\ConditionalFactor))
            ? $this->walkConditionalPrimary($factor)
            : ($factor->not ? 'NOT ' : '') . $this->walkConditionalPrimary($factor->conditionalPrimary);
    }

    /**
     * Walks down a ConditionalPrimary AST node, thereby generating the appropriate SQL.
     *
     * @param ConditionalPrimary
     * @return string The SQL.
     */
    public function walkConditionalPrimary($primary)
    {
        if ($primary->isSimpleConditionalExpression()) {
            return $primary->simpleConditionalExpression->dispatch($this);
        } else if ($primary->isConditionalExpression()) {
            $condExpr = $primary->conditionalExpression;

            return '(' . $this->walkConditionalExpression($condExpr) . ')';
        }
    }

    /**
     * Walks down an ExistsExpression AST node, thereby generating the appropriate SQL.
     *
     * @param ExistsExpression
     * @return string The SQL.
     */
    public function walkExistsExpression($existsExpr)
    {
        $sql = ($existsExpr->not) ? 'NOT ' : '';

        $sql .= 'EXISTS (' . $this->walkSubselect($existsExpr->subselect) . ')';

        return $sql;
    }

    /**
     * Walks down a CollectionMemberExpression AST node, thereby generating the appropriate SQL.
     *
     * @param CollectionMemberExpression
     * @return string The SQL.
     */
    public function walkCollectionMemberExpression($collMemberExpr)
    {
        $sql = $collMemberExpr->not ? 'NOT ' : '';
        $sql .= 'EXISTS (SELECT 1 FROM ';
        $entityExpr = $collMemberExpr->entityExpression;
        $collPathExpr = $collMemberExpr->collectionValuedPathExpression;
        
        $fieldName = $collPathExpr->field;
        $dqlAlias = $collPathExpr->identificationVariable;
        
        $class = $this->_queryComponents[$dqlAlias]['metadata'];
        
        if ($entityExpr instanceof AST\InputParameter) {
            $dqlParamKey = $entityExpr->name;
            $entity = $this->_query->getParameter($dqlParamKey);
        } else {
            //TODO
            throw new \BadMethodCallException("Not implemented");
        }
        
        $assoc = $class->associationMappings[$fieldName];
        
        if ($assoc['type'] == ClassMetadata::ONE_TO_MANY) {
            $targetClass = $this->_em->getClassMetadata($assoc['targetEntity']);
            $targetTableAlias = $this->getSQLTableAlias($targetClass->table['name']);
            $sourceTableAlias = $this->getSQLTableAlias($class->table['name'], $dqlAlias);
            
            $sql .= $targetClass->getQuotedTableName($this->_platform)
                  . ' ' . $targetTableAlias . ' WHERE ';
                    
            $owningAssoc = $targetClass->associationMappings[$assoc['mappedBy']];
            
            $first = true;
            
            foreach ($owningAssoc['targetToSourceKeyColumns'] as $targetColumn => $sourceColumn) {
                if ($first) $first = false; else $sql .= ' AND ';
                
                $sql .= $sourceTableAlias . '.' . $class->getQuotedColumnName($class->fieldNames[$targetColumn], $this->_platform) 
                      . ' = ' 
                      . $targetTableAlias . '.' . $sourceColumn;
            }
            
            $sql .= ' AND ';
            $first = true;
            
            foreach ($targetClass->identifier as $idField) {
                if ($first) $first = false; else $sql .= ' AND ';
                
                $this->_parserResult->addParameterMapping($dqlParamKey, $this->_sqlParamIndex++);
                $sql .= $targetTableAlias . '.' 
                      . $targetClass->getQuotedColumnName($idField, $this->_platform) . ' = ?';
            }
        } else { // many-to-many
            $targetClass = $this->_em->getClassMetadata($assoc['targetEntity']);
            
            $owningAssoc = $assoc['isOwningSide'] ? $assoc : $targetClass->associationMappings[$assoc['mappedBy']];
            $joinTable = $owningAssoc['joinTable'];

            // SQL table aliases
            $joinTableAlias = $this->getSQLTableAlias($joinTable['name']);
            $targetTableAlias = $this->getSQLTableAlias($targetClass->table['name']);
            $sourceTableAlias = $this->getSQLTableAlias($class->table['name'], $dqlAlias);
            
            // join to target table
            $sql .= $targetClass->getQuotedJoinTableName($owningAssoc, $this->_platform)
                  . ' ' . $joinTableAlias . ' INNER JOIN '
                  . $targetClass->getQuotedTableName($this->_platform)
                  . ' ' . $targetTableAlias . ' ON ';
            
            // join conditions
            $joinColumns = $assoc['isOwningSide']
                ? $joinTable['inverseJoinColumns']
                : $joinTable['joinColumns'];

            $first = true;
            foreach ($joinColumns as $joinColumn) {
                if ($first) $first = false; else $sql .= ' AND ';

                $sql .= $joinTableAlias . '.' . $joinColumn['name'] . ' = '
                        . $targetTableAlias . '.' . $targetClass->getQuotedColumnName(
                                $targetClass->fieldNames[$joinColumn['referencedColumnName']],
                                $this->_platform);
            }

            $sql .= ' WHERE ';

            $joinColumns = $assoc['isOwningSide']
                ? $joinTable['joinColumns']
                : $joinTable['inverseJoinColumns'];

            $first = true;
            foreach ($joinColumns as $joinColumn) {
                if ($first) $first = false; else $sql .= ' AND ';

                $sql .= $joinTableAlias . '.' . $joinColumn['name'] . ' = ' 
                      . $sourceTableAlias . '.' . $class->getQuotedColumnName(
                              $class->fieldNames[$joinColumn['referencedColumnName']],
                              $this->_platform);
            }
            
            $sql .= ' AND ';
            $first = true;
            
            foreach ($targetClass->identifier as $idField) {
                if ($first) $first = false; else $sql .= ' AND ';
                
                $this->_parserResult->addParameterMapping($dqlParamKey, $this->_sqlParamIndex++);
                $sql .= $targetTableAlias . '.' 
                      . $targetClass->getQuotedColumnName($idField, $this->_platform) . ' = ?';
            }
        }

        return $sql . ')';
    }
    
    /**
     * Walks down an EmptyCollectionComparisonExpression AST node, thereby generating the appropriate SQL.
     *
     * @param EmptyCollectionComparisonExpression
     * @return string The SQL.
     */
    public function walkEmptyCollectionComparisonExpression($emptyCollCompExpr)
    {
        $sizeFunc = new AST\Functions\SizeFunction('size');
        $sizeFunc->collectionPathExpression = $emptyCollCompExpr->expression;

        return $sizeFunc->getSql($this) . ($emptyCollCompExpr->not ? ' > 0' : ' = 0');
    }

    /**
     * Walks down a NullComparisonExpression AST node, thereby generating the appropriate SQL.
     *
     * @param NullComparisonExpression
     * @return string The SQL.
     */
    public function walkNullComparisonExpression($nullCompExpr)
    {
        $sql = '';
        $innerExpr = $nullCompExpr->expression;

        if ($innerExpr instanceof AST\InputParameter) {
            $dqlParamKey = $innerExpr->name;
            $this->_parserResult->addParameterMapping($dqlParamKey, $this->_sqlParamIndex++);
            $sql .= ' ?';
        } else {
            $sql .= $this->walkPathExpression($innerExpr);
        }

        $sql .= ' IS' . ($nullCompExpr->not ? ' NOT' : '') . ' NULL';

        return $sql;
    }

    /**
     * Walks down an InExpression AST node, thereby generating the appropriate SQL.
     *
     * @param InExpression
     * @return string The SQL.
     */
    public function walkInExpression($inExpr)
    {
        $sql = $this->walkPathExpression($inExpr->pathExpression)
             . ($inExpr->not ? ' NOT' : '') . ' IN (';

        if ($inExpr->subselect) {
            $sql .= $this->walkSubselect($inExpr->subselect);
        } else {
            $sql .= implode(', ', array_map(array($this, 'walkInParameter'), $inExpr->literals));
        }

        $sql .= ')';

        return $sql;
    }

    /**
     * Walks down an InstanceOfExpression AST node, thereby generating the appropriate SQL.
     *
     * @param InstanceOfExpression
     * @return string The SQL.
     */
    public function walkInstanceOfExpression($instanceOfExpr)
    {
        $sql = '';

        $dqlAlias = $instanceOfExpr->identificationVariable;
        $discrClass = $class = $this->_queryComponents[$dqlAlias]['metadata'];
        $fieldName = null;

        if ($class->discriminatorColumn) {
            $discrClass = $this->_em->getClassMetadata($class->rootEntityName);
        }

        if ($this->_useSqlTableAliases) {
            $sql .= $this->getSQLTableAlias($discrClass->table['name'], $dqlAlias) . '.';
        }

        $sql .= $class->discriminatorColumn['name'] . ($instanceOfExpr->not ? ' <> ' : ' = ');

        if ($instanceOfExpr->value instanceof AST\InputParameter) {
            // We need to modify the parameter value to be its correspondent mapped value
            $dqlParamKey = $instanceOfExpr->value->name;
            $paramValue  = $this->_query->getParameter($dqlParamKey);
            
            if ( ! ($paramValue instanceof \Doctrine\ORM\Mapping\ClassMetadata)) {
                throw QueryException::invalidParameterType('ClassMetadata', get_class($paramValue));
            }
            
            $entityClassName = $paramValue->name;
        } else {
            // Get name from ClassMetadata to resolve aliases.
            $entityClassName = $this->_em->getClassMetadata($instanceOfExpr->value)->name;
        }

        if ($entityClassName == $class->name) {
            $sql .= $this->_conn->quote($class->discriminatorValue);
        } else {
            $discrMap = array_flip($class->discriminatorMap);
            if (!isset($discrMap[$entityClassName])) {
                throw QueryException::instanceOfUnrelatedClass($entityClassName, $class->rootEntityName);
            }
            
            $sql .= $this->_conn->quote($discrMap[$entityClassName]);
        }

        return $sql;
    }

    /**
     * Walks down an InParameter AST node, thereby generating the appropriate SQL.
     *
     * @param InParameter
     * @return string The SQL.
     */
    public function walkInParameter($inParam)
    {
        return $inParam instanceof AST\InputParameter ?
                $this->walkInputParameter($inParam) :
                $this->walkLiteral($inParam);
    }

    /**
     * Walks down a literal that represents an AST node, thereby generating the appropriate SQL.
     *
     * @param mixed
     * @return string The SQL.
     */
    public function walkLiteral($literal)
    {
        switch ($literal->type) {
            case AST\Literal::STRING:
                return $this->_conn->quote($literal->value);
            case AST\Literal::BOOLEAN:
                $bool = strtolower($literal->value) == 'true' ? true : false;
                $boolVal = $this->_conn->getDatabasePlatform()->convertBooleans($bool);
                return is_string($boolVal) ? $this->_conn->quote($boolVal) : $boolVal;
            case AST\Literal::NUMERIC:
                return $literal->value;
            default:
                throw QueryException::invalidLiteral($literal);
        }
    }

    /**
     * Walks down a BetweenExpression AST node, thereby generating the appropriate SQL.
     *
     * @param BetweenExpression
     * @return string The SQL.
     */
    public function walkBetweenExpression($betweenExpr)
    {
        $sql = $this->walkArithmeticExpression($betweenExpr->expression);

        if ($betweenExpr->not) $sql .= ' NOT';

        $sql .= ' BETWEEN ' . $this->walkArithmeticExpression($betweenExpr->leftBetweenExpression)
              . ' AND ' . $this->walkArithmeticExpression($betweenExpr->rightBetweenExpression);

        return $sql;
    }

    /**
     * Walks down a LikeExpression AST node, thereby generating the appropriate SQL.
     *
     * @param LikeExpression
     * @return string The SQL.
     */
    public function walkLikeExpression($likeExpr)
    {
        $stringExpr = $likeExpr->stringExpression;
        $sql = $stringExpr->dispatch($this) . ($likeExpr->not ? ' NOT' : '') . ' LIKE ';

        if ($likeExpr->stringPattern instanceof AST\InputParameter) {
            $inputParam = $likeExpr->stringPattern;
            $dqlParamKey = $inputParam->name;
            $this->_parserResult->addParameterMapping($dqlParamKey, $this->_sqlParamIndex++);
            $sql .= '?';
        } else {
            $sql .= $this->_conn->quote($likeExpr->stringPattern);
        }

        if ($likeExpr->escapeChar) {
            $sql .= ' ESCAPE ' . $this->_conn->quote($likeExpr->escapeChar);
        }

        return $sql;
    }

    /**
     * Walks down a StateFieldPathExpression AST node, thereby generating the appropriate SQL.
     *
     * @param StateFieldPathExpression
     * @return string The SQL.
     */
    public function walkStateFieldPathExpression($stateFieldPathExpression)
    {
        return $this->walkPathExpression($stateFieldPathExpression);
    }

    /**
     * Walks down a ComparisonExpression AST node, thereby generating the appropriate SQL.
     *
     * @param ComparisonExpression
     * @return string The SQL.
     */
    public function walkComparisonExpression($compExpr)
    {
        $sql = '';
        $leftExpr = $compExpr->leftExpression;
        $rightExpr = $compExpr->rightExpression;

        if ($leftExpr instanceof AST\Node) {
            $sql .= $leftExpr->dispatch($this);
        } else {
            $sql .= is_numeric($leftExpr) ? $leftExpr : $this->_conn->quote($leftExpr);
        }

        $sql .= ' ' . $compExpr->operator . ' ';

        if ($rightExpr instanceof AST\Node) {
            $sql .= $rightExpr->dispatch($this);
        } else {
            $sql .= is_numeric($rightExpr) ? $rightExpr : $this->_conn->quote($rightExpr);
        }

        return $sql;
    }

    /**
     * Walks down an InputParameter AST node, thereby generating the appropriate SQL.
     *
     * @param InputParameter
     * @return string The SQL.
     */
    public function walkInputParameter($inputParam)
    {
        $this->_parserResult->addParameterMapping($inputParam->name, $this->_sqlParamIndex++);

        return '?';
    }

    /**
     * Walks down an ArithmeticExpression AST node, thereby generating the appropriate SQL.
     *
     * @param ArithmeticExpression
     * @return string The SQL.
     */
    public function walkArithmeticExpression($arithmeticExpr)
    {
        return ($arithmeticExpr->isSimpleArithmeticExpression())
        	? $this->walkSimpleArithmeticExpression($arithmeticExpr->simpleArithmeticExpression)
        	: '(' . $this->walkSubselect($arithmeticExpr->subselect) . ')';
    }

    /**
     * Walks down an SimpleArithmeticExpression AST node, thereby generating the appropriate SQL.
     *
     * @param SimpleArithmeticExpression
     * @return string The SQL.
     */
    public function walkSimpleArithmeticExpression($simpleArithmeticExpr)
    {
        return ( ! ($simpleArithmeticExpr instanceof AST\SimpleArithmeticExpression))
            ? $this->walkArithmeticTerm($simpleArithmeticExpr)
            : implode(
                ' ', array_map(array($this, 'walkArithmeticTerm'), $simpleArithmeticExpr->arithmeticTerms)
            );
    }

    /**
     * Walks down an ArithmeticTerm AST node, thereby generating the appropriate SQL.
     *
     * @param mixed
     * @return string The SQL.
     */
    public function walkArithmeticTerm($term)
    {
        if (is_string($term)) {
            return $term;
        }

        // Phase 2 AST optimization: Skip processment of ArithmeticTerm
        // if only one ArithmeticFactor is defined
        return ( ! ($term instanceof AST\ArithmeticTerm))
            ? $this->walkArithmeticFactor($term)
            : implode(
                ' ', array_map(array($this, 'walkArithmeticFactor'), $term->arithmeticFactors)
            );
    }

    /**
     * Walks down an ArithmeticFactor that represents an AST node, thereby generating the appropriate SQL.
     *
     * @param mixed
     * @return string The SQL.
     */
    public function walkArithmeticFactor($factor)
    {
        if (is_string($factor)) {
            return $factor;
        }
        
        // Phase 2 AST optimization: Skip processment of ArithmeticFactor
        // if only one ArithmeticPrimary is defined
        return ( ! ($factor instanceof AST\ArithmeticFactor))
            ? $this->walkArithmeticPrimary($factor)
            : ($factor->isNegativeSigned() ? '-' : ($factor->isPositiveSigned() ? '+' : '')) 
                . $this->walkArithmeticPrimary($factor->arithmeticPrimary);
    }

    /**
     * Walks down an ArithmeticPrimary that represents an AST node, thereby generating the appropriate SQL.
     *
     * @param mixed
     * @return string The SQL.
     */
    public function walkArithmeticPrimary($primary)
    {
        if ($primary instanceof AST\SimpleArithmeticExpression) {
            return '(' . $this->walkSimpleArithmeticExpression($primary) . ')';
        } else if ($primary instanceof AST\Node) {
            return $primary->dispatch($this);
        }

        // TODO: We need to deal with IdentificationVariable here
        return '';
    }

    /**
     * Walks down a StringPrimary that represents an AST node, thereby generating the appropriate SQL.
     *
     * @param mixed
     * @return string The SQL.
     */
    public function walkStringPrimary($stringPrimary)
    {
        return (is_string($stringPrimary))
            ? $this->_conn->quote($stringPrimary)
            : $stringPrimary->dispatch($this);
    }
}
