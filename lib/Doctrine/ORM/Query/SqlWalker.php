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
    Doctrine\DBAL\Types\Type,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Query,
    Doctrine\ORM\Query\QueryException,
    Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * The SqlWalker is a TreeWalker that walks over a DQL AST and constructs
 * the corresponding SQL.
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Roman Borschel <roman@code-factory.org>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Alexander <iam.asm89@gmail.com>
 * @since  2.0
 * @todo Rename: SQLWalker
 */
class SqlWalker implements TreeWalker
{
    /**
     * @var string
     */
    const HINT_DISTINCT = 'doctrine.distinct';
  
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
     * @var \Doctrine\DBAL\Connection
     */
    private $_conn;

    /**
     * @var AbstractQuery
     */
    private $_query;

    private $_tableAliasMap = array();

    /** Map from result variable names to their SQL column alias names. */
    private $_scalarResultAliasMap = array();

    /**
     * Map from DQL-Alias + Field-Name to SQL Column Alias
     *
     * @var array
     */
    private $_scalarFields = array();

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
        switch (true) {
            case ($AST instanceof AST\DeleteStatement):
                $primaryClass = $this->_em->getClassMetadata($AST->deleteClause->abstractSchemaName);

                return ($primaryClass->isInheritanceTypeJoined())
                    ? new Exec\MultiTableDeleteExecutor($AST, $this)
                    : new Exec\SingleTableDeleteUpdateExecutor($AST, $this);

            case ($AST instanceof AST\UpdateStatement):
                $primaryClass = $this->_em->getClassMetadata($AST->updateClause->abstractSchemaName);

                return ($primaryClass->isInheritanceTypeJoined())
                    ? new Exec\MultiTableUpdateExecutor($AST, $this)
                    : new Exec\SingleTableDeleteUpdateExecutor($AST, $this);

            default:
                return new Exec\SingleSelectExecutor($AST, $this);
        }
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
        // Trim the column alias to the maximum identifier length of the platform.
        // If the alias is to long, characters are cut off from the beginning.
        return $this->_platform->getSQLResultCasing(
            substr($columnName . $this->_aliasCounter++, -$this->_platform->getMaxIdentifierLength())
        );
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

        $baseTableAlias = $this->getSQLTableAlias($class->getTableName(), $dqlAlias);

        // INNER JOIN parent class tables
        foreach ($class->parentClasses as $parentClassName) {
            $parentClass = $this->_em->getClassMetadata($parentClassName);
            $tableAlias  = $this->getSQLTableAlias($parentClass->getTableName(), $dqlAlias);

            // If this is a joined association we must use left joins to preserve the correct result.
            $sql .= isset($this->_queryComponents[$dqlAlias]['relation']) ? ' LEFT ' : ' INNER ';
            $sql .= 'JOIN ' . $parentClass->getQuotedTableName($this->_platform) . ' ' . $tableAlias . ' ON ';

            $sqlParts = array();

            foreach ($class->getQuotedIdentifierColumnNames($this->_platform) as $columnName) {
                $sqlParts[] = $baseTableAlias . '.' . $columnName . ' = ' . $tableAlias . '.' . $columnName;
            }

            // Add filters on the root class
            if ($filterSql = $this->generateFilterConditionSQL($parentClass, $tableAlias)) {
                $sqlParts[] = $filterSql;
            }

            $sql .= implode(' AND ', $sqlParts);
        }

        // Ignore subclassing inclusion if partial objects is disallowed
        if ($this->_query->getHint(Query::HINT_FORCE_PARTIAL_LOAD)) {
            return $sql;
        }

        // LEFT JOIN child class tables
        foreach ($class->subClasses as $subClassName) {
            $subClass   = $this->_em->getClassMetadata($subClassName);
            $tableAlias = $this->getSQLTableAlias($subClass->getTableName(), $dqlAlias);

            $sql .= ' LEFT JOIN ' . $subClass->getQuotedTableName($this->_platform) . ' ' . $tableAlias . ' ON ';

            $sqlParts = array();

            foreach ($subClass->getQuotedIdentifierColumnNames($this->_platform) as $columnName) {
                $sqlParts[] = $baseTableAlias . '.' . $columnName . ' = ' . $tableAlias . '.' . $columnName;
            }

            $sql .= implode(' AND ', $sqlParts);
        }

        return $sql;
    }

    private function _generateOrderedCollectionOrderByItems()
    {
        $sqlParts = array();

        foreach ($this->_selectedClasses AS $selectedClass) {
            $dqlAlias = $selectedClass['dqlAlias'];
            $qComp    = $this->_queryComponents[$dqlAlias];

            if ( ! isset($qComp['relation']['orderBy'])) continue;

            foreach ($qComp['relation']['orderBy'] AS $fieldName => $orientation) {
                $columnName = $qComp['metadata']->getQuotedColumnName($fieldName, $this->_platform);
                $tableName  = ($qComp['metadata']->isInheritanceTypeJoined())
                    ? $this->_em->getUnitOfWork()->getEntityPersister($qComp['metadata']->name)->getOwningTable($fieldName)
                    : $qComp['metadata']->getTableName();

                $sqlParts[] = $this->getSQLTableAlias($tableName, $dqlAlias) . '.' . $columnName . ' ' . $orientation;
            }
        }

        return implode(', ', $sqlParts);
    }

    /**
     * Generates a discriminator column SQL condition for the class with the given DQL alias.
     *
     * @param array $dqlAliases List of root DQL aliases to inspect for discriminator restrictions.
     * @return string
     */
    private function _generateDiscriminatorColumnConditionSQL(array $dqlAliases)
    {
        $sqlParts = array();

        foreach ($dqlAliases as $dqlAlias) {
            $class = $this->_queryComponents[$dqlAlias]['metadata'];

            if ( ! $class->isInheritanceTypeSingleTable()) continue;

            $conn   = $this->_em->getConnection();
            $values = array();

            if ($class->discriminatorValue !== null) { // discrimnators can be 0
                $values[] = $conn->quote($class->discriminatorValue);
            }

            foreach ($class->subClasses as $subclassName) {
                $values[] = $conn->quote($this->_em->getClassMetadata($subclassName)->discriminatorValue);
            }

            $sqlParts[] = (($this->_useSqlTableAliases) ? $this->getSQLTableAlias($class->getTableName(), $dqlAlias) . '.' : '')
                        . $class->discriminatorColumn['name'] . ' IN (' . implode(', ', $values) . ')';
        }

        $sql = implode(' AND ', $sqlParts);

        return (count($sqlParts) > 1) ? '(' . $sql . ')' : $sql;
    }

    /**
     * Generates the filter SQL for a given entity and table alias.
     *
     * @param ClassMetadata $targetEntity Metadata of the target entity.
     * @param string $targetTableAlias The table alias of the joined/selected table.
     *
     * @return string The SQL query part to add to a query.
     */
    private function generateFilterConditionSQL(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if (!$this->_em->hasFilters()) {
            return '';
        }

        switch($targetEntity->inheritanceType) {
            case ClassMetadata::INHERITANCE_TYPE_NONE:
                break;
            case ClassMetadata::INHERITANCE_TYPE_JOINED:
                // The classes in the inheritance will be added to the query one by one,
                // but only the root node is getting filtered
                if ($targetEntity->name !== $targetEntity->rootEntityName) {
                    return '';
                }
                break;
            case ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE:
                // With STI the table will only be queried once, make sure that the filters
                // are added to the root entity
                $targetEntity = $this->_em->getClassMetadata($targetEntity->rootEntityName);
                break;
            default:
                //@todo: throw exception?
                return '';
            break;
        }

        $filterClauses = array();
        foreach ($this->_em->getFilters()->getEnabledFilters() as $filter) {
            if ('' !== $filterExpr = $filter->addFilterConstraint($targetEntity, $targetTableAlias)) {
                $filterClauses[] = '(' . $filterExpr . ')';
            }
        }

        return implode(' AND ', $filterClauses);
    }
    /**
     * Walks down a SelectStatement AST node, thereby generating the appropriate SQL.
     *
     * @return string The SQL.
     */
    public function walkSelectStatement(AST\SelectStatement $AST)
    {
        $sql  = $this->walkSelectClause($AST->selectClause);
        $sql .= $this->walkFromClause($AST->fromClause);
        $sql .= $this->walkWhereClause($AST->whereClause);
        $sql .= $AST->groupByClause ? $this->walkGroupByClause($AST->groupByClause) : '';
        $sql .= $AST->havingClause ? $this->walkHavingClause($AST->havingClause) : '';

        if (($orderByClause = $AST->orderByClause) !== null) {
            $sql .= $AST->orderByClause ? $this->walkOrderByClause($AST->orderByClause) : '';
        } else if (($orderBySql = $this->_generateOrderedCollectionOrderByItems()) !== '') {
            $sql .= ' ORDER BY ' . $orderBySql;
        }

        $sql = $this->_platform->modifyLimitQuery(
            $sql, $this->_query->getMaxResults(), $this->_query->getFirstResult()
        );

        if (($lockMode = $this->_query->getHint(Query::HINT_LOCK_MODE)) !== false) {
            switch ($lockMode) {
                case LockMode::PESSIMISTIC_READ:
                    $sql .= ' ' . $this->_platform->getReadLockSQL();
                    break;

                case LockMode::PESSIMISTIC_WRITE:
                    $sql .= ' ' . $this->_platform->getWriteLockSQL();
                    break;

                case LockMode::OPTIMISTIC:
                    foreach ($this->_selectedClasses AS $selectedClass) {
                        if ( ! $selectedClass['class']->isVersioned) {
                            throw \Doctrine\ORM\OptimisticLockException::lockFailed($selectedClass['class']->name);
                        }
                    }
                    break;
                case LockMode::NONE:
                    break;

                default:
                    throw \Doctrine\ORM\Query\QueryException::invalidLockMode();
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

        return $this->walkUpdateClause($AST->updateClause)
             . $this->walkWhereClause($AST->whereClause);
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

        return $this->walkDeleteClause($AST->deleteClause)
             . $this->walkWhereClause($AST->whereClause);
    }

    /**
     * Walks down an IdentificationVariable AST node, thereby generating the appropriate SQL.
     * This one differs of ->walkIdentificationVariable() because it generates the entity identifiers.
     *
     * @param string $identVariable
     * @return string
     */
    public function walkEntityIdentificationVariable($identVariable)
    {
        $class      = $this->_queryComponents[$identVariable]['metadata'];
        $tableAlias = $this->getSQLTableAlias($class->getTableName(), $identVariable);
        $sqlParts   = array();

        foreach ($class->getQuotedIdentifierColumnNames($this->_platform) as $columnName) {
            $sqlParts[] = $tableAlias . '.' . $columnName;
        }

        return implode(', ', $sqlParts);
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

        return $this->getSQLTableAlias($class->getTableName(), $identificationVariable);
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

                if ( ! $assoc['isOwningSide']) {
                    throw QueryException::associationPathInverseSideNotSupported();
                }

                // COMPOSITE KEYS NOT (YET?) SUPPORTED
                if (count($assoc['sourceToTargetKeyColumns']) > 1) {
                    throw QueryException::associationPathCompositeKeyNotSupported();
                }

                if ($this->_useSqlTableAliases) {
                    $sql .= $this->getSQLTableAlias($class->getTableName(), $dqlAlias) . '.';
                }

                $sql .= reset($assoc['targetToSourceKeyColumns']);
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
        $sql = 'SELECT ' . (($selectClause->isDistinct) ? 'DISTINCT ' : '');
        $sqlSelectExpressions = array_filter(array_map(array($this, 'walkSelectExpression'), $selectClause->selectExpressions));

        if ($this->_query->getHint(Query::HINT_INTERNAL_ITERATION) == true && $selectClause->isDistinct) {
            $this->_query->setHint(self::HINT_DISTINCT, true);
        }

        $addMetaColumns = ! $this->_query->getHint(Query::HINT_FORCE_PARTIAL_LOAD) &&
                $this->_query->getHydrationMode() == Query::HYDRATE_OBJECT
                ||
                $this->_query->getHydrationMode() != Query::HYDRATE_OBJECT &&
                $this->_query->getHint(Query::HINT_INCLUDE_META_COLUMNS);

        foreach ($this->_selectedClasses as $selectedClass) {
            $class       = $selectedClass['class'];
            $dqlAlias    = $selectedClass['dqlAlias'];
            $resultAlias = $selectedClass['resultAlias'];

            // Register as entity or joined entity result
            if ($this->_queryComponents[$dqlAlias]['relation'] === null) {
                $this->_rsm->addEntityResult($class->name, $dqlAlias, $resultAlias);
            } else {
                $this->_rsm->addJoinedEntityResult(
                    $class->name,
                    $dqlAlias,
                    $this->_queryComponents[$dqlAlias]['parent'],
                    $this->_queryComponents[$dqlAlias]['relation']['fieldName']
                );
            }

            if ($class->isInheritanceTypeSingleTable() || $class->isInheritanceTypeJoined()) {
                // Add discriminator columns to SQL
                $rootClass   = $this->_em->getClassMetadata($class->rootEntityName);
                $tblAlias    = $this->getSQLTableAlias($rootClass->getTableName(), $dqlAlias);
                $discrColumn = $rootClass->discriminatorColumn;
                $columnAlias = $this->getSQLColumnAlias($discrColumn['name']);

                $sqlSelectExpressions[] = $tblAlias . '.' . $discrColumn['name'] . ' AS ' . $columnAlias;

                $this->_rsm->setDiscriminatorColumn($dqlAlias, $columnAlias);
                $this->_rsm->addMetaResult($dqlAlias, $columnAlias, $discrColumn['fieldName']);
            }

            // Add foreign key columns to SQL, if necessary
            if ( ! $addMetaColumns && ! $class->containsForeignIdentifier) {
                continue;
            }

            // Add foreign key columns of class and also parent classes
            foreach ($class->associationMappings as $assoc) {
                if ( ! ($assoc['isOwningSide'] && $assoc['type'] & ClassMetadata::TO_ONE)) {
                    continue;
                } else if ( !$addMetaColumns && !isset($assoc['id'])) {
                    continue;
                }

                $owningClass   = (isset($assoc['inherited'])) ? $this->_em->getClassMetadata($assoc['inherited']) : $class;
                $sqlTableAlias = $this->getSQLTableAlias($owningClass->getTableName(), $dqlAlias);

                foreach ($assoc['targetToSourceKeyColumns'] as $srcColumn) {
                    $columnAlias = $this->getSQLColumnAlias($srcColumn);

                    $sqlSelectExpressions[] = $sqlTableAlias . '.' . $srcColumn . ' AS ' . $columnAlias;

                    $this->_rsm->addMetaResult($dqlAlias, $columnAlias, $srcColumn, (isset($assoc['id']) && $assoc['id'] === true));
                }
            }

            // Add foreign key columns to SQL, if necessary
            if ( ! $addMetaColumns) {
                continue;
            }

            // Add foreign key columns of subclasses
            foreach ($class->subClasses as $subClassName) {
                $subClass      = $this->_em->getClassMetadata($subClassName);
                $sqlTableAlias = $this->getSQLTableAlias($subClass->getTableName(), $dqlAlias);

                foreach ($subClass->associationMappings as $assoc) {
                    // Skip if association is inherited
                    if (isset($assoc['inherited'])) continue;

                    if ( ! ($assoc['isOwningSide'] && $assoc['type'] & ClassMetadata::TO_ONE)) continue;

                    foreach ($assoc['targetToSourceKeyColumns'] as $srcColumn) {
                        $columnAlias = $this->getSQLColumnAlias($srcColumn);

                        $sqlSelectExpressions[] = $sqlTableAlias . '.' . $srcColumn . ' AS ' . $columnAlias;

                        $this->_rsm->addMetaResult($dqlAlias, $columnAlias, $srcColumn);
                    }
                }
            }
        }

        $sql .= implode(', ', $sqlSelectExpressions);

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
                  . $this->getSQLTableAlias($class->getTableName(), $dqlAlias);

            if ($class->isInheritanceTypeJoined()) {
                $sql .= $this->_generateClassTableInheritanceJoins($class, $dqlAlias);
            }

            foreach ($identificationVariableDecl->joinVariableDeclarations as $joinVarDecl) {
                $sql .= $this->walkJoinVariableDeclaration($joinVarDecl);
            }

            if ($identificationVariableDecl->indexBy) {
                $alias = $identificationVariableDecl->indexBy->simpleStateFieldPathExpression->identificationVariable;
                $field = $identificationVariableDecl->indexBy->simpleStateFieldPathExpression->field;

                if (isset($this->_scalarFields[$alias][$field])) {
                    $this->_rsm->addIndexByScalar($this->_scalarFields[$alias][$field]);
                } else {
                    $this->_rsm->addIndexBy(
                        $identificationVariableDecl->indexBy->simpleStateFieldPathExpression->identificationVariable,
                        $identificationVariableDecl->indexBy->simpleStateFieldPathExpression->field
                    );
                }
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
        $orderByItems = array_map(array($this, 'walkOrderByItem'), $orderByClause->orderByItems);

        if (($collectionOrderByItems = $this->_generateOrderedCollectionOrderByItems()) !== '') {
            $orderByItems = array_merge($orderByItems, (array) $collectionOrderByItems);
        }

        return ' ORDER BY ' . implode(', ', $orderByItems);
    }

    /**
     * Walks down an OrderByItem AST node, thereby generating the appropriate SQL.
     *
     * @param OrderByItem
     * @return string The SQL.
     */
    public function walkOrderByItem($orderByItem)
    {
        $expr = $orderByItem->expression;
        $sql  = ($expr instanceof AST\PathExpression)
            ? $this->walkPathExpression($expr)
            : $this->walkResultVariable($this->_queryComponents[$expr]['token']['value']);

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
        $join     = $joinVarDecl->join;
        $joinType = $join->joinType;
        $sql      = ($joinType == AST\Join::JOIN_TYPE_LEFT || $joinType == AST\Join::JOIN_TYPE_LEFTOUTER)
            ? ' LEFT JOIN '
            : ' INNER JOIN ';

        if ($joinVarDecl->indexBy) {
            // For Many-To-One or One-To-One associations this obviously makes no sense, but is ignored silently.
            $this->_rsm->addIndexBy(
                $joinVarDecl->indexBy->simpleStateFieldPathExpression->identificationVariable,
                $joinVarDecl->indexBy->simpleStateFieldPathExpression->field
            );
        }

        $joinAssocPathExpr = $join->joinAssociationPathExpression;
        $joinedDqlAlias    = $join->aliasIdentificationVariable;

        $relation        = $this->_queryComponents[$joinedDqlAlias]['relation'];
        $targetClass     = $this->_em->getClassMetadata($relation['targetEntity']);
        $sourceClass     = $this->_em->getClassMetadata($relation['sourceEntity']);
        $targetTableName = $targetClass->getQuotedTableName($this->_platform);

        $targetTableAlias = $this->getSQLTableAlias($targetClass->getTableName(), $joinedDqlAlias);
        $sourceTableAlias = $this->getSQLTableAlias($sourceClass->getTableName(), $joinAssocPathExpr->identificationVariable);

        // Ensure we got the owning side, since it has all mapping info
        $assoc = ( ! $relation['isOwningSide']) ? $targetClass->associationMappings[$relation['mappedBy']] : $relation;
        if ($this->_query->getHint(Query::HINT_INTERNAL_ITERATION) == true && (!$this->_query->getHint(self::HINT_DISTINCT) || isset($this->_selectedClasses[$joinedDqlAlias]))) {
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
            $sql .= ($joinType == AST\Join::JOIN_TYPE_LEFT || $joinType == AST\Join::JOIN_TYPE_LEFTOUTER) ? ' LEFT JOIN ' : ' INNER JOIN ';
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

        // Apply the filters
        if ($filterExpr = $this->generateFilterConditionSQL($targetClass, $targetTableAlias)) {
            $sql .= ' AND ' . $filterExpr;
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
     * Walks down a CaseExpression AST node and generates the corresponding SQL.
     *
     * @param CoalesceExpression|NullIfExpression|GeneralCaseExpression|SimpleCaseExpression $expression
     * @return string The SQL.
     */
    public function walkCaseExpression($expression)
    {
        switch (true) {
            case ($expression instanceof AST\CoalesceExpression):
                return $this->walkCoalesceExpression($expression);

            case ($expression instanceof AST\NullIfExpression):
                return $this->walkNullIfExpression($expression);

            case ($expression instanceof AST\GeneralCaseExpression):
                return $this->walkGeneralCaseExpression($expression);

            case ($expression instanceof AST\SimpleCaseExpression):
                return $this->walkSimpleCaseExpression($expression);

            default:
                return '';
        }
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
     * Walks down a GeneralCaseExpression AST node and generates the corresponding SQL.
     *
     * @param GeneralCaseExpression $generalCaseExpression
     * @return string The SQL.
     */
    public function walkGeneralCaseExpression(AST\GeneralCaseExpression $generalCaseExpression)
    {
        $sql = 'CASE';

        foreach ($generalCaseExpression->whenClauses as $whenClause) {
            $sql .= ' WHEN ' . $this->walkConditionalExpression($whenClause->caseConditionExpression);
            $sql .= ' THEN ' . $this->walkSimpleArithmeticExpression($whenClause->thenScalarExpression);
        }

        $sql .= ' ELSE ' . $this->walkSimpleArithmeticExpression($generalCaseExpression->elseScalarExpression) . ' END';

        return $sql;
    }

    /**
     * Walks down a SimpleCaseExpression AST node and generates the corresponding SQL.
     *
     * @param SimpleCaseExpression $simpleCaseExpression
     * @return string The SQL.
     */
    public function walkSimpleCaseExpression($simpleCaseExpression)
    {
        $sql = 'CASE ' . $this->walkStateFieldPathExpression($simpleCaseExpression->caseOperand);

        foreach ($simpleCaseExpression->simpleWhenClauses as $simpleWhenClause) {
            $sql .= ' WHEN ' . $this->walkSimpleArithmeticExpression($simpleWhenClause->caseScalarExpression);
            $sql .= ' THEN ' . $this->walkSimpleArithmeticExpression($simpleWhenClause->thenScalarExpression);
        }

        $sql .= ' ELSE ' . $this->walkSimpleArithmeticExpression($simpleCaseExpression->elseScalarExpression) . ' END';

        return $sql;
    }

    /**
     * Walks down a SelectExpression AST node and generates the corresponding SQL.
     *
     * @param SelectExpression $selectExpression
     * @return string The SQL.
     */
    public function walkSelectExpression($selectExpression)
    {
        $sql    = '';
        $expr   = $selectExpression->expression;
        $hidden = $selectExpression->hiddenAliasResultVariable;

        switch (true) {
            case ($expr instanceof AST\PathExpression):
                if ($expr->type !== AST\PathExpression::TYPE_STATE_FIELD) {
                    throw QueryException::invalidPathExpression($expr->type);
                }

                $fieldName = $expr->field;
                $dqlAlias  = $expr->identificationVariable;
                $qComp     = $this->_queryComponents[$dqlAlias];
                $class     = $qComp['metadata'];

                $resultAlias = $selectExpression->fieldIdentificationVariable ?: $fieldName;
                $tableName   = ($class->isInheritanceTypeJoined())
                    ? $this->_em->getUnitOfWork()->getEntityPersister($class->name)->getOwningTable($fieldName)
                    : $class->getTableName();

                $sqlTableAlias = $this->getSQLTableAlias($tableName, $dqlAlias);
                $columnName    = $class->getQuotedColumnName($fieldName, $this->_platform);
                $columnAlias   = $this->getSQLColumnAlias($class->fieldMappings[$fieldName]['columnName']);

                $col = $sqlTableAlias . '.' . $columnName;

                $fieldType = $class->getTypeOfField($fieldName);

                if (isset($class->fieldMappings[$fieldName]['requireSQLConversion'])) {
                    $type = Type::getType($fieldType);
                    $col  = $type->convertToPHPValueSQL($col, $this->_conn->getDatabasePlatform());
                }

                $sql .= $col . ' AS ' . $columnAlias;

                $this->_scalarResultAliasMap[$resultAlias] = $columnAlias;

                if ( ! $hidden) {
                    $this->_rsm->addScalarResult($columnAlias, $resultAlias, $fieldType);
                    $this->_scalarFields[$dqlAlias][$fieldName] = $columnAlias;
                }
                break;

            case ($expr instanceof AST\AggregateExpression):
            case ($expr instanceof AST\Functions\FunctionNode):
            case ($expr instanceof AST\SimpleArithmeticExpression):
            case ($expr instanceof AST\ArithmeticTerm):
            case ($expr instanceof AST\ArithmeticFactor):
            case ($expr instanceof AST\ArithmeticPrimary):
            case ($expr instanceof AST\Literal):
            case ($expr instanceof AST\NullIfExpression):
            case ($expr instanceof AST\CoalesceExpression):
            case ($expr instanceof AST\GeneralCaseExpression):
            case ($expr instanceof AST\SimpleCaseExpression):
                $columnAlias = $this->getSQLColumnAlias('sclr');
                $resultAlias = $selectExpression->fieldIdentificationVariable ?: $this->_scalarResultCounter++;

                $sql .= $expr->dispatch($this) . ' AS ' . $columnAlias;

                $this->_scalarResultAliasMap[$resultAlias] = $columnAlias;

                if ( ! $hidden) {
                    // We cannot resolve field type here; assume 'string'.
                    $this->_rsm->addScalarResult($columnAlias, $resultAlias, 'string');
                }
                break;

            case ($expr instanceof AST\Subselect):
                $columnAlias = $this->getSQLColumnAlias('sclr');
                $resultAlias = $selectExpression->fieldIdentificationVariable ?: $this->_scalarResultCounter++;

                $sql .= '(' . $this->walkSubselect($expr) . ') AS ' . $columnAlias;

                $this->_scalarResultAliasMap[$resultAlias] = $columnAlias;

                if ( ! $hidden) {
                    // We cannot resolve field type here; assume 'string'.
                    $this->_rsm->addScalarResult($columnAlias, $resultAlias, 'string');
                }
                break;

            default:
                // IdentificationVariable or PartialObjectExpression
                if ($expr instanceof AST\PartialObjectExpression) {
                    $dqlAlias = $expr->identificationVariable;
                    $partialFieldSet = $expr->partialFieldSet;
                } else {
                    $dqlAlias = $expr;
                    $partialFieldSet = array();
                }

                $queryComp   = $this->_queryComponents[$dqlAlias];
                $class       = $queryComp['metadata'];
                $resultAlias = $selectExpression->fieldIdentificationVariable ?: null;

                if ( ! isset($this->_selectedClasses[$dqlAlias])) {
                    $this->_selectedClasses[$dqlAlias] = array(
                        'class'       => $class,
                        'dqlAlias'    => $dqlAlias,
                        'resultAlias' => $resultAlias
                    );
                }

                $sqlParts = array();

                // Select all fields from the queried class
                foreach ($class->fieldMappings as $fieldName => $mapping) {
                    if ($partialFieldSet && ! in_array($fieldName, $partialFieldSet)) {
                        continue;
                    }

                    $tableName = (isset($mapping['inherited']))
                        ? $this->_em->getClassMetadata($mapping['inherited'])->getTableName()
                        : $class->getTableName();

                    $sqlTableAlias    = $this->getSQLTableAlias($tableName, $dqlAlias);
                    $columnAlias      = $this->getSQLColumnAlias($mapping['columnName']);
                    $quotedColumnName = $class->getQuotedColumnName($fieldName, $this->_platform);

                    $col = $sqlTableAlias . '.' . $quotedColumnName;

                    if (isset($class->fieldMappings[$fieldName]['requireSQLConversion'])) {
                        $type = Type::getType($class->getTypeOfField($fieldName));
                        $col = $type->convertToPHPValueSQL($col, $this->_platform);
                    }

                    $sqlParts[] = $col . ' AS '. $columnAlias;

                    $this->_scalarResultAliasMap[$resultAlias][] = $columnAlias;

                    $this->_rsm->addFieldResult($dqlAlias, $columnAlias, $fieldName, $class->name);
                }

                // Add any additional fields of subclasses (excluding inherited fields)
                // 1) on Single Table Inheritance: always, since its marginal overhead
                // 2) on Class Table Inheritance only if partial objects are disallowed,
                //    since it requires outer joining subtables.
                if ($class->isInheritanceTypeSingleTable() || ! $this->_query->getHint(Query::HINT_FORCE_PARTIAL_LOAD)) {
                    foreach ($class->subClasses as $subClassName) {
                        $subClass      = $this->_em->getClassMetadata($subClassName);
                        $sqlTableAlias = $this->getSQLTableAlias($subClass->getTableName(), $dqlAlias);

                        foreach ($subClass->fieldMappings as $fieldName => $mapping) {
                            if (isset($mapping['inherited']) || $partialFieldSet && !in_array($fieldName, $partialFieldSet)) {
                                continue;
                            }

                            $columnAlias      = $this->getSQLColumnAlias($mapping['columnName']);
                            $quotedColumnName = $subClass->getQuotedColumnName($fieldName, $this->_platform);

                            $col = $sqlTableAlias . '.' . $quotedColumnName;

                            if (isset($subClass->fieldMappings[$fieldName]['requireSQLConversion'])) {
                                $type = Type::getType($subClass->getTypeOfField($fieldName));
                                $col = $type->convertToPHPValueSQL($col, $this->_platform);
                            }

                            $sqlParts[] = $col . ' AS ' . $columnAlias;

                            $this->_scalarResultAliasMap[$resultAlias][] = $columnAlias;

                            $this->_rsm->addFieldResult($dqlAlias, $columnAlias, $fieldName, $subClassName);
                        }
                    }
                }

                $sql .= implode(', ', $sqlParts);
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
        return ' ' . strtoupper($qExpr->type) . '(' . $this->walkSubselect($qExpr->subselect) . ')';
    }

    /**
     * Walks down a Subselect AST node, thereby generating the appropriate SQL.
     *
     * @param Subselect
     * @return string The SQL.
     */
    public function walkSubselect($subselect)
    {
        $useAliasesBefore  = $this->_useSqlTableAliases;
        $rootAliasesBefore = $this->_rootAliases;

        $this->_rootAliases = array(); // reset the rootAliases for the subselect
        $this->_useSqlTableAliases = true;

        $sql  = $this->walkSimpleSelectClause($subselect->simpleSelectClause);
        $sql .= $this->walkSubselectFromClause($subselect->subselectFromClause);
        $sql .= $this->walkWhereClause($subselect->whereClause);

        $sql .= $subselect->groupByClause ? $this->walkGroupByClause($subselect->groupByClause) : '';
        $sql .= $subselect->havingClause ? $this->walkHavingClause($subselect->havingClause) : '';
        $sql .= $subselect->orderByClause ? $this->walkOrderByClause($subselect->orderByClause) : '';

        $this->_rootAliases        = $rootAliasesBefore; // put the main aliases back
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
            $dqlAlias  = $rangeDecl->aliasIdentificationVariable;

            $class = $this->_em->getClassMetadata($rangeDecl->abstractSchemaName);
            $sql .= $class->getQuotedTableName($this->_platform) . ' '
                  . $this->getSQLTableAlias($class->getTableName(), $dqlAlias);

            $this->_rootAliases[] = $dqlAlias;

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
        $expr = $simpleSelectExpression->expression;
        $sql  = ' ';

        switch (true) {
            case ($expr instanceof AST\PathExpression):
                $sql .= $this->walkPathExpression($expr);
                break;

            case ($expr instanceof AST\AggregateExpression):
                $alias = $simpleSelectExpression->fieldIdentificationVariable ?: $this->_scalarResultCounter++;

                $sql .= $this->walkAggregateExpression($expr) . ' AS dctrn__' . $alias;
                break;

            case ($expr instanceof AST\Subselect):
                $alias = $simpleSelectExpression->fieldIdentificationVariable ?: $this->_scalarResultCounter++;

                $columnAlias = 'sclr' . $this->_aliasCounter++;
                $this->_scalarResultAliasMap[$alias] = $columnAlias;

                $sql .= '(' . $this->walkSubselect($expr) . ') AS ' . $columnAlias;
                break;

            case ($expr instanceof AST\Functions\FunctionNode):
            case ($expr instanceof AST\SimpleArithmeticExpression):
            case ($expr instanceof AST\ArithmeticTerm):
            case ($expr instanceof AST\ArithmeticFactor):
            case ($expr instanceof AST\ArithmeticPrimary):
            case ($expr instanceof AST\Literal):
            case ($expr instanceof AST\NullIfExpression):
            case ($expr instanceof AST\CoalesceExpression):
            case ($expr instanceof AST\GeneralCaseExpression):
            case ($expr instanceof AST\SimpleCaseExpression):
                $alias = $simpleSelectExpression->fieldIdentificationVariable ?: $this->_scalarResultCounter++;

                $columnAlias = $this->getSQLColumnAlias('sclr');
                $this->_scalarResultAliasMap[$alias] = $columnAlias;

                $sql .= $expr->dispatch($this) . ' AS ' . $columnAlias;
                break;

            default: // IdentificationVariable
                $sql .= $this->walkEntityIdentificationVariable($expr);
                break;
        }

        return $sql;
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
        $sqlParts = array();

        foreach ($groupByClause->groupByItems AS $groupByItem) {
            $sqlParts[] = $this->walkGroupByItem($groupByItem);
        }

        return ' GROUP BY ' . implode(', ', $sqlParts);
    }

    /**
     * Walks down a GroupByItem AST node, thereby generating the appropriate SQL.
     *
     * @param GroupByItem
     * @return string The SQL.
     */
    public function walkGroupByItem($groupByItem)
    {
        // StateFieldPathExpression
        if ( ! is_string($groupByItem)) {
            return $this->walkPathExpression($groupByItem);
        }

        // ResultVariable
        if (isset($this->_queryComponents[$groupByItem]['resultVariable'])) {
            return $this->walkResultVariable($groupByItem);
        }

        // IdentificationVariable
        $sqlParts = array();

        foreach ($this->_queryComponents[$groupByItem]['metadata']->fieldNames AS $field) {
            $item       = new AST\PathExpression(AST\PathExpression::TYPE_STATE_FIELD, $groupByItem, $field);
            $item->type = AST\PathExpression::TYPE_STATE_FIELD;

            $sqlParts[] = $this->walkPathExpression($item);
        }

        foreach ($this->_queryComponents[$groupByItem]['metadata']->associationMappings AS $mapping) {
            if ($mapping['isOwningSide'] && $mapping['type'] & ClassMetadataInfo::TO_ONE) {
                $item       = new AST\PathExpression(AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION, $groupByItem, $mapping['fieldName']);
                $item->type = AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION;

                $sqlParts[] = $this->walkPathExpression($item);
            }
        }

        return implode(', ', $sqlParts);
    }

    /**
     * Walks down a DeleteClause AST node, thereby generating the appropriate SQL.
     *
     * @param DeleteClause
     * @return string The SQL.
     */
    public function walkDeleteClause(AST\DeleteClause $deleteClause)
    {
        $class     = $this->_em->getClassMetadata($deleteClause->abstractSchemaName);
        $tableName = $class->getTableName();
        $sql       = 'DELETE FROM ' . $class->getQuotedTableName($this->_platform);

        $this->setSQLTableAlias($tableName, $tableName, $deleteClause->aliasIdentificationVariable);
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
        $class     = $this->_em->getClassMetadata($updateClause->abstractSchemaName);
        $tableName = $class->getTableName();
        $sql       = 'UPDATE ' . $class->getQuotedTableName($this->_platform);

        $this->setSQLTableAlias($tableName, $tableName, $updateClause->aliasIdentificationVariable);
        $this->_rootAliases[] = $updateClause->aliasIdentificationVariable;

        $sql .= ' SET ' . implode(', ', array_map(array($this, 'walkUpdateItem'), $updateClause->updateItems));

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

        $sql      = $this->walkPathExpression($updateItem->pathExpression) . ' = ';
        $newValue = $updateItem->newValue;

        switch (true) {
            case ($newValue instanceof AST\Node):
                $sql .= $newValue->dispatch($this);
                break;

            case ($newValue === null):
                $sql .= 'NULL';
                break;

            default:
                $sql .= $this->_conn->quote($newValue);
                break;
        }

        $this->_useSqlTableAliases = $useTableAliasesBefore;

        return $sql;
    }

    /**
     * Walks down a WhereClause AST node, thereby generating the appropriate SQL.
     * WhereClause or not, the appropriate discriminator sql is added.
     *
     * @param WhereClause
     * @return string The SQL.
     */
    public function walkWhereClause($whereClause)
    {
        $condSql  = null !== $whereClause ? $this->walkConditionalExpression($whereClause->conditionalExpression) : '';
        $discrSql = $this->_generateDiscriminatorColumnConditionSql($this->_rootAliases);

        if ($this->_em->hasFilters()) {
            $filterClauses = array();
            foreach ($this->_rootAliases as $dqlAlias) {
                $class = $this->_queryComponents[$dqlAlias]['metadata'];
                $tableAlias = $this->getSQLTableAlias($class->table['name'], $dqlAlias);

                if ($filterExpr = $this->generateFilterConditionSQL($class, $tableAlias)) {
                    $filterClauses[] = $filterExpr;
                }
            }

            if (count($filterClauses)) {
                if ($condSql) {
                    $condSql .= ' AND ';
                }

                $condSql .= implode(' AND ', $filterClauses);
            }
        }

        if ($condSql) {
            return ' WHERE ' . (( ! $discrSql) ? $condSql : '(' . $condSql . ') AND ' . $discrSql);
        }

        if ($discrSql) {
            return ' WHERE ' . $discrSql;
        }

        return '';
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
        if ( ! ($condExpr instanceof AST\ConditionalExpression)) {
            return $this->walkConditionalTerm($condExpr);
        }

        return implode(' OR ', array_map(array($this, 'walkConditionalTerm'), $condExpr->conditionalTerms));
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
        if ( ! ($condTerm instanceof AST\ConditionalTerm)) {
            return $this->walkConditionalFactor($condTerm);
        }

        return implode(' AND ', array_map(array($this, 'walkConditionalFactor'), $condTerm->conditionalFactors));
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
        }

        if ($primary->isConditionalExpression()) {
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

        $entityExpr   = $collMemberExpr->entityExpression;
        $collPathExpr = $collMemberExpr->collectionValuedPathExpression;

        $fieldName = $collPathExpr->field;
        $dqlAlias  = $collPathExpr->identificationVariable;

        $class = $this->_queryComponents[$dqlAlias]['metadata'];

        switch (true) {
            // InputParameter
            case ($entityExpr instanceof AST\InputParameter):
                $dqlParamKey = $entityExpr->name;
                $entity      = $this->_query->getParameter($dqlParamKey);
                $entitySql   = '?';
                break;

            // SingleValuedAssociationPathExpression | IdentificationVariable
            case ($entityExpr instanceof AST\PathExpression):
                $entitySql = $this->walkPathExpression($entityExpr);
                break;

            default:
                throw new \BadMethodCallException("Not implemented");
        }

        $assoc = $class->associationMappings[$fieldName];

        if ($assoc['type'] == ClassMetadata::ONE_TO_MANY) {
            $targetClass      = $this->_em->getClassMetadata($assoc['targetEntity']);
            $targetTableAlias = $this->getSQLTableAlias($targetClass->getTableName());
            $sourceTableAlias = $this->getSQLTableAlias($class->getTableName(), $dqlAlias);

            $sql .= $targetClass->getQuotedTableName($this->_platform) . ' ' . $targetTableAlias . ' WHERE ';

            $owningAssoc = $targetClass->associationMappings[$assoc['mappedBy']];
            $sqlParts    = array();

            foreach ($owningAssoc['targetToSourceKeyColumns'] as $targetColumn => $sourceColumn) {
                $targetColumn = $class->getQuotedColumnName($class->fieldNames[$targetColumn], $this->_platform);

                $sqlParts[] = $sourceTableAlias . '.' . $targetColumn . ' = ' . $targetTableAlias . '.' . $sourceColumn;
            }

            foreach ($targetClass->getQuotedIdentifierColumnNames($this->_platform) as $targetColumnName) {
                if (isset($dqlParamKey)) {
                    $this->_parserResult->addParameterMapping($dqlParamKey, $this->_sqlParamIndex++);
                }

                $sqlParts[] = $targetTableAlias . '.'  . $targetColumnName . ' = ' . $entitySql;
            }

            $sql .= implode(' AND ', $sqlParts);
        } else { // many-to-many
            $targetClass = $this->_em->getClassMetadata($assoc['targetEntity']);

            $owningAssoc = $assoc['isOwningSide'] ? $assoc : $targetClass->associationMappings[$assoc['mappedBy']];
            $joinTable = $owningAssoc['joinTable'];

            // SQL table aliases
            $joinTableAlias   = $this->getSQLTableAlias($joinTable['name']);
            $targetTableAlias = $this->getSQLTableAlias($targetClass->getTableName());
            $sourceTableAlias = $this->getSQLTableAlias($class->getTableName(), $dqlAlias);

            // join to target table
            $sql .= $targetClass->getQuotedJoinTableName($owningAssoc, $this->_platform) . ' ' . $joinTableAlias
                  . ' INNER JOIN ' . $targetClass->getQuotedTableName($this->_platform) . ' ' . $targetTableAlias . ' ON ';

            // join conditions
            $joinColumns  = $assoc['isOwningSide'] ? $joinTable['inverseJoinColumns'] : $joinTable['joinColumns'];
            $joinSqlParts = array();

            foreach ($joinColumns as $joinColumn) {
                $targetColumn = $targetClass->getQuotedColumnName(
                    $targetClass->fieldNames[$joinColumn['referencedColumnName']],
                    $this->_platform
                );

                $joinSqlParts[] = $joinTableAlias . '.' . $joinColumn['name'] . ' = ' . $targetTableAlias . '.' . $targetColumn;
            }

            $sql .= implode(' AND ', $joinSqlParts);
            $sql .= ' WHERE ';

            $joinColumns = $assoc['isOwningSide'] ? $joinTable['joinColumns'] : $joinTable['inverseJoinColumns'];
            $sqlParts    = array();

            foreach ($joinColumns as $joinColumn) {
                $targetColumn = $class->getQuotedColumnName(
                    $class->fieldNames[$joinColumn['referencedColumnName']],
                    $this->_platform
                );

                $sqlParts[] = $joinTableAlias . '.' . $joinColumn['name'] . ' = ' . $sourceTableAlias . '.' . $targetColumn;
            }

            foreach ($targetClass->getQuotedIdentifierColumnNames($this->_platform) as $targetColumnName) {
                if (isset($dqlParamKey)) {
                    $this->_parserResult->addParameterMapping($dqlParamKey, $this->_sqlParamIndex++);
                }

                $sqlParts[] = $targetTableAlias . '.' . $targetColumnName . ' = ' . $entitySql;
            }

            $sql .= implode(' AND ', $sqlParts);
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
        $sql = $this->walkArithmeticExpression($inExpr->expression) . ($inExpr->not ? ' NOT' : '') . ' IN (';

        $sql .= ($inExpr->subselect)
            ? $this->walkSubselect($inExpr->subselect)
            : implode(', ', array_map(array($this, 'walkInParameter'), $inExpr->literals));

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
            $sql .= $this->getSQLTableAlias($discrClass->getTableName(), $dqlAlias) . '.';
        }

        $sql .= $class->discriminatorColumn['name'] . ($instanceOfExpr->not ? ' NOT IN ' : ' IN ');

        $sqlParameterList = array();

        foreach ($instanceOfExpr->value as $parameter) {
            if ($parameter instanceof AST\InputParameter) {
                // We need to modify the parameter value to be its correspondent mapped value
                $dqlParamKey = $parameter->name;
                $paramValue  = $this->_query->getParameter($dqlParamKey);

                if ( ! ($paramValue instanceof \Doctrine\ORM\Mapping\ClassMetadata)) {
                    throw QueryException::invalidParameterType('ClassMetadata', get_class($paramValue));
                }

                $entityClassName = $paramValue->name;
            } else {
                // Get name from ClassMetadata to resolve aliases.
                $entityClassName = $this->_em->getClassMetadata($parameter)->name;
            }

            if ($entityClassName == $class->name) {
                $sqlParameterList[] = $this->_conn->quote($class->discriminatorValue);
            } else {
                $discrMap = array_flip($class->discriminatorMap);

                if (!isset($discrMap[$entityClassName])) {
                    throw QueryException::instanceOfUnrelatedClass($entityClassName, $class->rootEntityName);
                }

                $sqlParameterList[] = $this->_conn->quote($discrMap[$entityClassName]);
            }
        }

        $sql .= '(' . implode(', ', $sqlParameterList) . ')';

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
        return $inParam instanceof AST\InputParameter
            ? $this->walkInputParameter($inParam)
            : $this->walkLiteral($inParam);
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

                return $boolVal;

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
        $leftExpr  = $compExpr->leftExpression;
        $rightExpr = $compExpr->rightExpression;
        $sql       = '';

        $sql .= ($leftExpr instanceof AST\Node)
            ? $leftExpr->dispatch($this)
            : (is_numeric($leftExpr) ? $leftExpr : $this->_conn->quote($leftExpr));

        $sql .= ' ' . $compExpr->operator . ' ';

        $sql .= ($rightExpr instanceof AST\Node)
            ? $rightExpr->dispatch($this)
            : (is_numeric($rightExpr) ? $rightExpr : $this->_conn->quote($rightExpr));

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
        if ( ! ($simpleArithmeticExpr instanceof AST\SimpleArithmeticExpression)) {
            return $this->walkArithmeticTerm($simpleArithmeticExpr);
        }

        return implode(' ', array_map(array($this, 'walkArithmeticTerm'), $simpleArithmeticExpr->arithmeticTerms));
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
            return (isset($this->_queryComponents[$term]))
                ? $this->walkResultVariable($this->_queryComponents[$term]['token']['value'])
                : $term;
        }

        // Phase 2 AST optimization: Skip processment of ArithmeticTerm
        // if only one ArithmeticFactor is defined
        if ( ! ($term instanceof AST\ArithmeticTerm)) {
            return $this->walkArithmeticFactor($term);
        }

        return implode(' ', array_map(array($this, 'walkArithmeticFactor'), $term->arithmeticFactors));
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
        if ( ! ($factor instanceof AST\ArithmeticFactor)) {
            return $this->walkArithmeticPrimary($factor);
        }

        $sign = $factor->isNegativeSigned() ? '-' : ($factor->isPositiveSigned() ? '+' : '');

        return $sign . $this->walkArithmeticPrimary($factor->arithmeticPrimary);
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
        }

        if ($primary instanceof AST\Node) {
            return $primary->dispatch($this);
        }

        return $this->walkEntityIdentificationVariable($primary);
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

    /**
     * Walks down a ResultVriable that represents an AST node, thereby generating the appropriate SQL.
     *
     * @param string $resultVariable
     * @return string The SQL.
     */
    public function walkResultVariable($resultVariable)
    {
        $resultAlias = $this->_scalarResultAliasMap[$resultVariable];

        if (is_array($resultAlias)) {
            return implode(', ', $resultAlias);
        }

        return $resultAlias;
    }
}
