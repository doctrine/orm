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
    private $rsm;

    /**
     * Counters for generating unique column aliases.
     *
     * @var integer
     */
    private $aliasCounter = 0;

    /**
     * Counters for generating unique table aliases.
     *
     * @var integer
     */
    private $tableAliasCounter = 0;

    /**
     * Counters for generating unique scalar result.
     *
     * @var integer
     */
    private $scalarResultCounter = 1;

    /**
     * Counters for generating unique parameter indexes.
     *
     * @var integer
     */
    private $sqlParamIndex = 0;

    /**
     * @var ParserResult
     */
    private $parserResult;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * @var AbstractQuery
     */
    private $query;

    /**
     * @var array
     */
    private $tableAliasMap = array();

    /**
     * Map from result variable names to their SQL column alias names.
     *
     * @var array
     */
    private $scalarResultAliasMap = array();

    /**
     * Map from DQL-Alias + Field-Name to SQL Column Alias
     *
     * @var array
     */
    private $scalarFields = array();

    /**
     * Map of all components/classes that appear in the DQL query.
     *
     * @var array
     */
    private $queryComponents;

    /**
     * A list of classes that appear in non-scalar SelectExpressions.
     *
     * @var array
     */
    private $selectedClasses = array();

    /**
     * The DQL alias of the root class of the currently traversed query.
     * 
     * @var array
     */
    private $rootAliases = array();

    /**
     * Flag that indicates whether to generate SQL table aliases in the SQL.
     * These should only be generated for SELECT queries, not for UPDATE/DELETE.
     *
     * @var boolean
     */
    private $useSqlTableAliases = true;

    /**
     * The database platform abstraction.
     *
     * @var AbstractPlatform
     */
    private $platform;

    /**
     * The quote strategy.
     *
     * @var \Doctrine\ORM\Mapping\QuoteStrategy
     */
    private $quoteStrategy;

    /**
     * {@inheritDoc}
     */
    public function __construct($query, $parserResult, array $queryComponents)
    {
        $this->query            = $query;
        $this->parserResult     = $parserResult;
        $this->queryComponents  = $queryComponents;
        $this->rsm              = $parserResult->getResultSetMapping();
        $this->em               = $query->getEntityManager();
        $this->conn             = $this->em->getConnection();
        $this->platform         = $this->conn->getDatabasePlatform();
        $this->quoteStrategy    = $this->em->getConfiguration()->getQuoteStrategy();
    }

    /**
     * Gets the Query instance used by the walker.
     *
     * @return Query.
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Gets the Connection used by the walker.
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * Gets the EntityManager used by the walker.
     *
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->em;
    }

    /**
     * Gets the information about a single query component.
     *
     * @param string $dqlAlias The DQL alias.
     * @return array
     */
    public function getQueryComponent($dqlAlias)
    {
        return $this->queryComponents[$dqlAlias];
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
                $primaryClass = $this->em->getClassMetadata($AST->deleteClause->abstractSchemaName);

                return ($primaryClass->isInheritanceTypeJoined())
                    ? new Exec\MultiTableDeleteExecutor($AST, $this)
                    : new Exec\SingleTableDeleteUpdateExecutor($AST, $this);

            case ($AST instanceof AST\UpdateStatement):
                $primaryClass = $this->em->getClassMetadata($AST->updateClause->abstractSchemaName);

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

        if ( ! isset($this->tableAliasMap[$tableName])) {
            $this->tableAliasMap[$tableName] = strtolower(substr($tableName, 0, 1)) . $this->tableAliasCounter++ . '_';
        }

        return $this->tableAliasMap[$tableName];
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

        $this->tableAliasMap[$tableName] = $alias;

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
        return $this->quoteStrategy->getColumnAlias($columnName, $this->aliasCounter++, $this->platform);
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
            $parentClass = $this->em->getClassMetadata($parentClassName);
            $tableAlias  = $this->getSQLTableAlias($parentClass->getTableName(), $dqlAlias);

            // If this is a joined association we must use left joins to preserve the correct result.
            $sql .= isset($this->queryComponents[$dqlAlias]['relation']) ? ' LEFT ' : ' INNER ';
            $sql .= 'JOIN ' . $this->quoteStrategy->getTableName($parentClass, $this->platform) . ' ' . $tableAlias . ' ON ';

            $sqlParts = array();

            foreach ($this->quoteStrategy->getIdentifierColumnNames($class, $this->platform) as $columnName) {
                $sqlParts[] = $baseTableAlias . '.' . $columnName . ' = ' . $tableAlias . '.' . $columnName;
            }

            // Add filters on the root class
            if ($filterSql = $this->generateFilterConditionSQL($parentClass, $tableAlias)) {
                $sqlParts[] = $filterSql;
            }

            $sql .= implode(' AND ', $sqlParts);
        }

        // Ignore subclassing inclusion if partial objects is disallowed
        if ($this->query->getHint(Query::HINT_FORCE_PARTIAL_LOAD)) {
            return $sql;
        }

        // LEFT JOIN child class tables
        foreach ($class->subClasses as $subClassName) {
            $subClass   = $this->em->getClassMetadata($subClassName);
            $tableAlias = $this->getSQLTableAlias($subClass->getTableName(), $dqlAlias);

            $sql .= ' LEFT JOIN ' . $this->quoteStrategy->getTableName($subClass, $this->platform) . ' ' . $tableAlias . ' ON ';

            $sqlParts = array();

            foreach ($this->quoteStrategy->getIdentifierColumnNames($subClass, $this->platform) as $columnName) {
                $sqlParts[] = $baseTableAlias . '.' . $columnName . ' = ' . $tableAlias . '.' . $columnName;
            }

            $sql .= implode(' AND ', $sqlParts);
        }

        return $sql;
    }

    private function _generateOrderedCollectionOrderByItems()
    {
        $sqlParts = array();

        foreach ($this->selectedClasses as $selectedClass) {
            $dqlAlias = $selectedClass['dqlAlias'];
            $qComp    = $this->queryComponents[$dqlAlias];

            if ( ! isset($qComp['relation']['orderBy'])) continue;

            foreach ($qComp['relation']['orderBy'] as $fieldName => $orientation) {
                $columnName = $this->quoteStrategy->getColumnName($fieldName, $qComp['metadata'], $this->platform);
                $tableName  = ($qComp['metadata']->isInheritanceTypeJoined())
                    ? $this->em->getUnitOfWork()->getEntityPersister($qComp['metadata']->name)->getOwningTable($fieldName)
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
            $class = $this->queryComponents[$dqlAlias]['metadata'];

            if ( ! $class->isInheritanceTypeSingleTable()) continue;

            $conn   = $this->em->getConnection();
            $values = array();

            if ($class->discriminatorValue !== null) { // discrimnators can be 0
                $values[] = $conn->quote($class->discriminatorValue);
            }

            foreach ($class->subClasses as $subclassName) {
                $values[] = $conn->quote($this->em->getClassMetadata($subclassName)->discriminatorValue);
            }

            $sqlParts[] = (($this->useSqlTableAliases) ? $this->getSQLTableAlias($class->getTableName(), $dqlAlias) . '.' : '')
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
        if (!$this->em->hasFilters()) {
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
                $targetEntity = $this->em->getClassMetadata($targetEntity->rootEntityName);
                break;
            default:
                //@todo: throw exception?
                return '';
            break;
        }

        $filterClauses = array();
        foreach ($this->em->getFilters()->getEnabledFilters() as $filter) {
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

        $sql = $this->platform->modifyLimitQuery(
            $sql, $this->query->getMaxResults(), $this->query->getFirstResult()
        );

        if (($lockMode = $this->query->getHint(Query::HINT_LOCK_MODE)) !== false) {
            switch ($lockMode) {
                case LockMode::PESSIMISTIC_READ:
                    $sql .= ' ' . $this->platform->getReadLockSQL();
                    break;

                case LockMode::PESSIMISTIC_WRITE:
                    $sql .= ' ' . $this->platform->getWriteLockSQL();
                    break;

                case LockMode::OPTIMISTIC:
                    foreach ($this->selectedClasses as $selectedClass) {
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
        $this->useSqlTableAliases = false;

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
        $this->useSqlTableAliases = false;

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
        $class      = $this->queryComponents[$identVariable]['metadata'];
        $tableAlias = $this->getSQLTableAlias($class->getTableName(), $identVariable);
        $sqlParts   = array();

        foreach ($this->quoteStrategy->getIdentifierColumnNames($class, $this->platform) as $columnName) {
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
        $class = $this->queryComponents[$identificationVariable]['metadata'];

        if (
            $fieldName !== null && $class->isInheritanceTypeJoined() &&
            isset($class->fieldMappings[$fieldName]['inherited'])
        ) {
            $class = $this->em->getClassMetadata($class->fieldMappings[$fieldName]['inherited']);
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
                $class = $this->queryComponents[$dqlAlias]['metadata'];

                if ($this->useSqlTableAliases) {
                    $sql .= $this->walkIdentificationVariable($dqlAlias, $fieldName) . '.';
                }

                $sql .= $this->quoteStrategy->getColumnName($fieldName, $class, $this->platform);
                break;

            case AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION:
                // 1- the owning side:
                //    Just use the foreign key, i.e. u.group_id
                $fieldName = $pathExpr->field;
                $dqlAlias = $pathExpr->identificationVariable;
                $class = $this->queryComponents[$dqlAlias]['metadata'];

                if (isset($class->associationMappings[$fieldName]['inherited'])) {
                    $class = $this->em->getClassMetadata($class->associationMappings[$fieldName]['inherited']);
                }

                $assoc = $class->associationMappings[$fieldName];

                if ( ! $assoc['isOwningSide']) {
                    throw QueryException::associationPathInverseSideNotSupported();
                }

                // COMPOSITE KEYS NOT (YET?) SUPPORTED
                if (count($assoc['sourceToTargetKeyColumns']) > 1) {
                    throw QueryException::associationPathCompositeKeyNotSupported();
                }

                if ($this->useSqlTableAliases) {
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

        if ($this->query->getHint(Query::HINT_INTERNAL_ITERATION) == true && $selectClause->isDistinct) {
            $this->query->setHint(self::HINT_DISTINCT, true);
        }

        $addMetaColumns = ! $this->query->getHint(Query::HINT_FORCE_PARTIAL_LOAD) &&
                $this->query->getHydrationMode() == Query::HYDRATE_OBJECT
                ||
                $this->query->getHydrationMode() != Query::HYDRATE_OBJECT &&
                $this->query->getHint(Query::HINT_INCLUDE_META_COLUMNS);

        foreach ($this->selectedClasses as $selectedClass) {
            $class       = $selectedClass['class'];
            $dqlAlias    = $selectedClass['dqlAlias'];
            $resultAlias = $selectedClass['resultAlias'];

            // Register as entity or joined entity result
            if ($this->queryComponents[$dqlAlias]['relation'] === null) {
                $this->rsm->addEntityResult($class->name, $dqlAlias, $resultAlias);
            } else {
                $this->rsm->addJoinedEntityResult(
                    $class->name,
                    $dqlAlias,
                    $this->queryComponents[$dqlAlias]['parent'],
                    $this->queryComponents[$dqlAlias]['relation']['fieldName']
                );
            }

            if ($class->isInheritanceTypeSingleTable() || $class->isInheritanceTypeJoined()) {
                // Add discriminator columns to SQL
                $rootClass   = $this->em->getClassMetadata($class->rootEntityName);
                $tblAlias    = $this->getSQLTableAlias($rootClass->getTableName(), $dqlAlias);
                $discrColumn = $rootClass->discriminatorColumn;
                $columnAlias = $this->getSQLColumnAlias($discrColumn['name']);

                $sqlSelectExpressions[] = $tblAlias . '.' . $discrColumn['name'] . ' AS ' . $columnAlias;

                $this->rsm->setDiscriminatorColumn($dqlAlias, $columnAlias);
                $this->rsm->addMetaResult($dqlAlias, $columnAlias, $discrColumn['fieldName']);
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

                $owningClass   = (isset($assoc['inherited'])) ? $this->em->getClassMetadata($assoc['inherited']) : $class;
                $sqlTableAlias = $this->getSQLTableAlias($owningClass->getTableName(), $dqlAlias);

                foreach ($assoc['targetToSourceKeyColumns'] as $srcColumn) {
                    $columnAlias = $this->getSQLColumnAlias($srcColumn);

                    $sqlSelectExpressions[] = $sqlTableAlias . '.' . $srcColumn . ' AS ' . $columnAlias;

                    $this->rsm->addMetaResult($dqlAlias, $columnAlias, $srcColumn, (isset($assoc['id']) && $assoc['id'] === true));
                }
            }

            // Add foreign key columns to SQL, if necessary
            if ( ! $addMetaColumns) {
                continue;
            }

            // Add foreign key columns of subclasses
            foreach ($class->subClasses as $subClassName) {
                $subClass      = $this->em->getClassMetadata($subClassName);
                $sqlTableAlias = $this->getSQLTableAlias($subClass->getTableName(), $dqlAlias);

                foreach ($subClass->associationMappings as $assoc) {
                    // Skip if association is inherited
                    if (isset($assoc['inherited'])) continue;

                    if ( ! ($assoc['isOwningSide'] && $assoc['type'] & ClassMetadata::TO_ONE)) continue;

                    foreach ($assoc['targetToSourceKeyColumns'] as $srcColumn) {
                        $columnAlias = $this->getSQLColumnAlias($srcColumn);

                        $sqlSelectExpressions[] = $sqlTableAlias . '.' . $srcColumn . ' AS ' . $columnAlias;

                        $this->rsm->addMetaResult($dqlAlias, $columnAlias, $srcColumn);
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
            $sql = $this->platform->appendLockHint(
                $this->walkRangeVariableDeclaration($identificationVariableDecl->rangeVariableDeclaration),
                $this->query->getHint(Query::HINT_LOCK_MODE)
            );

            foreach ($identificationVariableDecl->joins as $join) {
                $sql .= $this->walkJoin($join);
            }

            if ($identificationVariableDecl->indexBy) {
                $alias = $identificationVariableDecl->indexBy->simpleStateFieldPathExpression->identificationVariable;
                $field = $identificationVariableDecl->indexBy->simpleStateFieldPathExpression->field;

                if (isset($this->scalarFields[$alias][$field])) {
                    $this->rsm->addIndexByScalar($this->scalarFields[$alias][$field]);
                } else {
                    $this->rsm->addIndexBy(
                        $identificationVariableDecl->indexBy->simpleStateFieldPathExpression->identificationVariable,
                        $identificationVariableDecl->indexBy->simpleStateFieldPathExpression->field
                    );
                }
            }

            $sqlParts[] = $sql;
        }

        return ' FROM ' . implode(', ', $sqlParts);
    }

    /**
     * Walks down a RangeVariableDeclaration AST node, thereby generating the appropriate SQL.
     *
     * @return string
     */
    public function walkRangeVariableDeclaration($rangeVariableDeclaration)
    {
        $class    = $this->em->getClassMetadata($rangeVariableDeclaration->abstractSchemaName);
        $dqlAlias = $rangeVariableDeclaration->aliasIdentificationVariable;

        $this->rootAliases[] = $dqlAlias;

        $sql = $class->getQuotedTableName($this->platform) . ' '
             . $this->getSQLTableAlias($class->getTableName(), $dqlAlias);

        if ($class->isInheritanceTypeJoined()) {
            $sql .= $this->_generateClassTableInheritanceJoins($class, $dqlAlias);
        }

        return $sql;
    }

    /**
     * Walks down a JoinAssociationDeclaration AST node, thereby generating the appropriate SQL.
     *
     * @param AST\JoinAssociationDeclaration $joinAssociationDeclaration
     * @param int                            $joinType
     * @param AST\ConditionalExpression      $condExpr
     *
     * @return string
     */
    public function walkJoinAssociationDeclaration($joinAssociationDeclaration, $joinType = AST\Join::JOIN_TYPE_INNER, $condExpr = null)
    {
        $sql = '';

        $associationPathExpression = $joinAssociationDeclaration->joinAssociationPathExpression;
        $joinedDqlAlias            = $joinAssociationDeclaration->aliasIdentificationVariable;
        $indexBy                   = $joinAssociationDeclaration->indexBy;

        $relation        = $this->queryComponents[$joinedDqlAlias]['relation'];
        $targetClass     = $this->em->getClassMetadata($relation['targetEntity']);
        $sourceClass     = $this->em->getClassMetadata($relation['sourceEntity']);
        $targetTableName = $targetClass->getQuotedTableName($this->platform);

        $targetTableAlias = $this->getSQLTableAlias($targetClass->getTableName(), $joinedDqlAlias);
        $sourceTableAlias = $this->getSQLTableAlias($sourceClass->getTableName(), $associationPathExpression->identificationVariable);

        // Ensure we got the owning side, since it has all mapping info
        $assoc = ( ! $relation['isOwningSide']) ? $targetClass->associationMappings[$relation['mappedBy']] : $relation;

        if ($this->query->getHint(Query::HINT_INTERNAL_ITERATION) == true && (!$this->query->getHint(self::HINT_DISTINCT) || isset($this->selectedClasses[$joinedDqlAlias]))) {
            if ($relation['type'] == ClassMetadata::ONE_TO_MANY || $relation['type'] == ClassMetadata::MANY_TO_MANY) {
                throw QueryException::iterateWithFetchJoinNotAllowed($assoc);
            }
        }

        // This condition is not checking ClassMetadata::MANY_TO_ONE, because by definition it cannot
        // be the owning side and previously we ensured that $assoc is always the owning side of the associations.
        // The owning side is necessary at this point because only it contains the JoinColumn information.
        switch (true) {
            case ($assoc['type'] & ClassMetadata::TO_ONE):
                $conditions = array();

                 foreach ($assoc['joinColumns'] as $joinColumn) {
                    $quotedSourceColumn = $this->quoteStrategy->getJoinColumnName($joinColumn, $targetClass, $this->platform);
                    $quotedTargetColumn = $this->quoteStrategy->getReferencedJoinColumnName($joinColumn, $targetClass, $this->platform);

                    if ($relation['isOwningSide']) {
                        $conditions[] = $sourceTableAlias . '.' . $quotedSourceColumn . ' = ' . $targetTableAlias . '.' . $quotedTargetColumn;

                        continue;
                    }

                    $conditions[] = $sourceTableAlias . '.' . $quotedTargetColumn . ' = ' . $targetTableAlias . '.' . $quotedSourceColumn;
                }

                // Apply remaining inheritance restrictions
                $discrSql = $this->_generateDiscriminatorColumnConditionSQL(array($joinedDqlAlias));

                if ($discrSql) {
                    $conditions[] = $discrSql;
                }

                // Apply the filters
                $filterExpr = $this->generateFilterConditionSQL($targetClass, $targetTableAlias);

                if ($filterExpr) {
                    $conditions[] = $filterExpr;
                }

                $sql .= $targetTableName . ' ' . $targetTableAlias . ' ON ' . implode(' AND ', $conditions);
                break;

            case ($assoc['type'] == ClassMetadata::MANY_TO_MANY):
                // Join relation table
                $joinTable      = $assoc['joinTable'];
                $joinTableAlias = $this->getSQLTableAlias($joinTable['name'], $joinedDqlAlias);
                $joinTableName  = $sourceClass->getQuotedJoinTableName($assoc, $this->platform);

                $conditions      = array();
                $relationColumns = ($relation['isOwningSide'])
                    ? $assoc['joinTable']['joinColumns']
                    : $assoc['joinTable']['inverseJoinColumns'];

                foreach ($relationColumns as $joinColumn) {
                    $quotedSourceColumn = $this->quoteStrategy->getJoinColumnName($joinColumn, $targetClass, $this->platform);
                    $quotedTargetColumn = $this->quoteStrategy->getReferencedJoinColumnName($joinColumn, $targetClass, $this->platform);

                    $conditions[] = $sourceTableAlias . '.' . $quotedTargetColumn . ' = ' . $joinTableAlias . '.' . $quotedSourceColumn;
                }

                $sql .= $joinTableName . ' ' . $joinTableAlias . ' ON ' . implode(' AND ', $conditions);

                // Join target table
                $sql .= ($joinType == AST\Join::JOIN_TYPE_LEFT || $joinType == AST\Join::JOIN_TYPE_LEFTOUTER) ? ' LEFT JOIN ' : ' INNER JOIN ';

                $conditions      = array();
                $relationColumns = ($relation['isOwningSide'])
                    ? $assoc['joinTable']['inverseJoinColumns']
                    : $assoc['joinTable']['joinColumns'];

                foreach ($relationColumns as $joinColumn) {
                    $quotedSourceColumn = $this->quoteStrategy->getJoinColumnName($joinColumn, $targetClass, $this->platform);
                    $quotedTargetColumn = $this->quoteStrategy->getReferencedJoinColumnName($joinColumn, $targetClass, $this->platform);

                    $conditions[] = $targetTableAlias . '.' . $quotedTargetColumn . ' = ' . $joinTableAlias . '.' . $quotedSourceColumn;
                }

                // Apply remaining inheritance restrictions
                $discrSql = $this->_generateDiscriminatorColumnConditionSQL(array($joinedDqlAlias));

                if ($discrSql) {
                    $conditions[] = $discrSql;
                }

                // Apply the filters
                $filterExpr = $this->generateFilterConditionSQL($targetClass, $targetTableAlias);

                if ($filterExpr) {
                    $conditions[] = $filterExpr;
                }

                $sql .= $targetTableName . ' ' . $targetTableAlias . ' ON ' . implode(' AND ', $conditions);
                break;
        }

        // Handle WITH clause
        if ($condExpr !== null) {
            // Phase 2 AST optimization: Skip processing of ConditionalExpression
            // if only one ConditionalTerm is defined
            $sql .= ' AND (' . $this->walkConditionalExpression($condExpr) . ')';
        }

        // FIXME: these should either be nested or all forced to be left joins (DDC-XXX)
        if ($targetClass->isInheritanceTypeJoined()) {
            $sql .= $this->_generateClassTableInheritanceJoins($targetClass, $joinedDqlAlias);
        }

        // Apply the indexes
        if ($indexBy) {
            // For Many-To-One or One-To-One associations this obviously makes no sense, but is ignored silently.
            $this->rsm->addIndexBy(
                $indexBy->simpleStateFieldPathExpression->identificationVariable,
                $indexBy->simpleStateFieldPathExpression->field
            );
        } else if (isset($relation['indexBy'])) {
            $this->rsm->addIndexBy($joinedDqlAlias, $relation['indexBy']);
        }

        return $sql;
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
        $sql  = ($expr instanceof AST\Node)
            ? $expr->dispatch($this)
            : $this->walkResultVariable($this->queryComponents[$expr]['token']['value']);

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
     * Walks down a Join AST node and creates the corresponding SQL.
     *
     * @return string The SQL.
     */
    public function walkJoin($join)
    {
        $joinType        = $join->joinType;
        $joinDeclaration = $join->joinAssociationDeclaration;

        $sql = ($joinType == AST\Join::JOIN_TYPE_LEFT || $joinType == AST\Join::JOIN_TYPE_LEFTOUTER)
            ? ' LEFT JOIN '
            : ' INNER JOIN ';

        switch (true) {
            case ($joinDeclaration instanceof \Doctrine\ORM\Query\AST\RangeVariableDeclaration):
                $class = $this->em->getClassMetadata($joinDeclaration->abstractSchemaName);
                $condExprConjunction = $class->isInheritanceTypeJoined() && $joinType != AST\Join::JOIN_TYPE_LEFT && $joinType != AST\Join::JOIN_TYPE_LEFTOUTER
                    ? ' AND '
                    : ' ON ';

                $sql .= $this->walkRangeVariableDeclaration($joinDeclaration)
                      . $condExprConjunction . '(' . $this->walkConditionalExpression($join->conditionalExpression) . ')';
                break;

            case ($joinDeclaration instanceof \Doctrine\ORM\Query\AST\JoinAssociationDeclaration):
                $sql .= $this->walkJoinAssociationDeclaration($joinDeclaration, $joinType, $join->conditionalExpression);
                break;
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
            ? $this->conn->quote($nullIfExpression->firstExpression)
            : $this->walkSimpleArithmeticExpression($nullIfExpression->firstExpression);

        $secondExpression = is_string($nullIfExpression->secondExpression)
            ? $this->conn->quote($nullIfExpression->secondExpression)
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
                    throw QueryException::invalidPathExpression($expr);
                }

                $fieldName = $expr->field;
                $dqlAlias  = $expr->identificationVariable;
                $qComp     = $this->queryComponents[$dqlAlias];
                $class     = $qComp['metadata'];

                $resultAlias = $selectExpression->fieldIdentificationVariable ?: $fieldName;
                $tableName   = ($class->isInheritanceTypeJoined())
                    ? $this->em->getUnitOfWork()->getEntityPersister($class->name)->getOwningTable($fieldName)
                    : $class->getTableName();

                $sqlTableAlias = $this->getSQLTableAlias($tableName, $dqlAlias);
                $columnName    = $this->quoteStrategy->getColumnName($fieldName, $class, $this->platform);
                $columnAlias   = $this->getSQLColumnAlias($class->fieldMappings[$fieldName]['columnName']);

                $col = $sqlTableAlias . '.' . $columnName;

                $fieldType = $class->getTypeOfField($fieldName);

                if (isset($class->fieldMappings[$fieldName]['requireSQLConversion'])) {
                    $type = Type::getType($fieldType);
                    $col  = $type->convertToPHPValueSQL($col, $this->conn->getDatabasePlatform());
                }

                $sql .= $col . ' AS ' . $columnAlias;

                $this->scalarResultAliasMap[$resultAlias] = $columnAlias;

                if ( ! $hidden) {
                    $this->rsm->addScalarResult($columnAlias, $resultAlias, $fieldType);
                    $this->scalarFields[$dqlAlias][$fieldName] = $columnAlias;
                }
                break;

            case ($expr instanceof AST\AggregateExpression):
            case ($expr instanceof AST\Functions\FunctionNode):
            case ($expr instanceof AST\SimpleArithmeticExpression):
            case ($expr instanceof AST\ArithmeticTerm):
            case ($expr instanceof AST\ArithmeticFactor):
            case ($expr instanceof AST\Literal):
            case ($expr instanceof AST\NullIfExpression):
            case ($expr instanceof AST\CoalesceExpression):
            case ($expr instanceof AST\GeneralCaseExpression):
            case ($expr instanceof AST\SimpleCaseExpression):
                $columnAlias = $this->getSQLColumnAlias('sclr');
                $resultAlias = $selectExpression->fieldIdentificationVariable ?: $this->scalarResultCounter++;

                $sql .= $expr->dispatch($this) . ' AS ' . $columnAlias;

                $this->scalarResultAliasMap[$resultAlias] = $columnAlias;

                if ( ! $hidden) {
                    // We cannot resolve field type here; assume 'string'.
                    $this->rsm->addScalarResult($columnAlias, $resultAlias, 'string');
                }
                break;

            case ($expr instanceof AST\Subselect):
                $columnAlias = $this->getSQLColumnAlias('sclr');
                $resultAlias = $selectExpression->fieldIdentificationVariable ?: $this->scalarResultCounter++;

                $sql .= '(' . $this->walkSubselect($expr) . ') AS ' . $columnAlias;

                $this->scalarResultAliasMap[$resultAlias] = $columnAlias;

                if ( ! $hidden) {
                    // We cannot resolve field type here; assume 'string'.
                    $this->rsm->addScalarResult($columnAlias, $resultAlias, 'string');
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

                $queryComp   = $this->queryComponents[$dqlAlias];
                $class       = $queryComp['metadata'];
                $resultAlias = $selectExpression->fieldIdentificationVariable ?: null;

                if ( ! isset($this->selectedClasses[$dqlAlias])) {
                    $this->selectedClasses[$dqlAlias] = array(
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
                        ? $this->em->getClassMetadata($mapping['inherited'])->getTableName()
                        : $class->getTableName();

                    $sqlTableAlias    = $this->getSQLTableAlias($tableName, $dqlAlias);
                    $columnAlias      = $this->getSQLColumnAlias($mapping['columnName']);
                    $quotedColumnName = $this->quoteStrategy->getColumnName($fieldName, $class, $this->platform);

                    $col = $sqlTableAlias . '.' . $quotedColumnName;

                    if (isset($class->fieldMappings[$fieldName]['requireSQLConversion'])) {
                        $type = Type::getType($class->getTypeOfField($fieldName));
                        $col = $type->convertToPHPValueSQL($col, $this->platform);
                    }

                    $sqlParts[] = $col . ' AS '. $columnAlias;

                    $this->scalarResultAliasMap[$resultAlias][] = $columnAlias;

                    $this->rsm->addFieldResult($dqlAlias, $columnAlias, $fieldName, $class->name);
                }

                // Add any additional fields of subclasses (excluding inherited fields)
                // 1) on Single Table Inheritance: always, since its marginal overhead
                // 2) on Class Table Inheritance only if partial objects are disallowed,
                //    since it requires outer joining subtables.
                if ($class->isInheritanceTypeSingleTable() || ! $this->query->getHint(Query::HINT_FORCE_PARTIAL_LOAD)) {
                    foreach ($class->subClasses as $subClassName) {
                        $subClass      = $this->em->getClassMetadata($subClassName);
                        $sqlTableAlias = $this->getSQLTableAlias($subClass->getTableName(), $dqlAlias);

                        foreach ($subClass->fieldMappings as $fieldName => $mapping) {
                            if (isset($mapping['inherited']) || $partialFieldSet && !in_array($fieldName, $partialFieldSet)) {
                                continue;
                            }

                            $columnAlias      = $this->getSQLColumnAlias($mapping['columnName']);
                            $quotedColumnName = $this->quoteStrategy->getColumnName($fieldName, $subClass, $this->platform);

                            $col = $sqlTableAlias . '.' . $quotedColumnName;

                            if (isset($subClass->fieldMappings[$fieldName]['requireSQLConversion'])) {
                                $type = Type::getType($subClass->getTypeOfField($fieldName));
                                $col = $type->convertToPHPValueSQL($col, $this->platform);
                            }

                            $sqlParts[] = $col . ' AS ' . $columnAlias;

                            $this->scalarResultAliasMap[$resultAlias][] = $columnAlias;

                            $this->rsm->addFieldResult($dqlAlias, $columnAlias, $fieldName, $subClassName);
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
        $useAliasesBefore  = $this->useSqlTableAliases;
        $rootAliasesBefore = $this->rootAliases;

        $this->rootAliases = array(); // reset the rootAliases for the subselect
        $this->useSqlTableAliases = true;

        $sql  = $this->walkSimpleSelectClause($subselect->simpleSelectClause);
        $sql .= $this->walkSubselectFromClause($subselect->subselectFromClause);
        $sql .= $this->walkWhereClause($subselect->whereClause);

        $sql .= $subselect->groupByClause ? $this->walkGroupByClause($subselect->groupByClause) : '';
        $sql .= $subselect->havingClause ? $this->walkHavingClause($subselect->havingClause) : '';
        $sql .= $subselect->orderByClause ? $this->walkOrderByClause($subselect->orderByClause) : '';

        $this->rootAliases        = $rootAliasesBefore; // put the main aliases back
        $this->useSqlTableAliases = $useAliasesBefore;

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
            $sql = $this->platform->appendLockHint(
                $this->walkRangeVariableDeclaration($subselectIdVarDecl->rangeVariableDeclaration),
                $this->query->getHint(Query::HINT_LOCK_MODE)
            );

            foreach ($subselectIdVarDecl->joins as $join) {
                $sql .= $this->walkJoin($join);
            }

            $sqlParts[] = $sql;
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
                $alias = $simpleSelectExpression->fieldIdentificationVariable ?: $this->scalarResultCounter++;

                $sql .= $this->walkAggregateExpression($expr) . ' AS dctrn__' . $alias;
                break;

            case ($expr instanceof AST\Subselect):
                $alias = $simpleSelectExpression->fieldIdentificationVariable ?: $this->scalarResultCounter++;

                $columnAlias = 'sclr' . $this->aliasCounter++;
                $this->scalarResultAliasMap[$alias] = $columnAlias;

                $sql .= '(' . $this->walkSubselect($expr) . ') AS ' . $columnAlias;
                break;

            case ($expr instanceof AST\Functions\FunctionNode):
            case ($expr instanceof AST\SimpleArithmeticExpression):
            case ($expr instanceof AST\ArithmeticTerm):
            case ($expr instanceof AST\ArithmeticFactor):
            case ($expr instanceof AST\Literal):
            case ($expr instanceof AST\NullIfExpression):
            case ($expr instanceof AST\CoalesceExpression):
            case ($expr instanceof AST\GeneralCaseExpression):
            case ($expr instanceof AST\SimpleCaseExpression):
                $alias = $simpleSelectExpression->fieldIdentificationVariable ?: $this->scalarResultCounter++;

                $columnAlias = $this->getSQLColumnAlias('sclr');
                $this->scalarResultAliasMap[$alias] = $columnAlias;

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

        foreach ($groupByClause->groupByItems as $groupByItem) {
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
        if (isset($this->queryComponents[$groupByItem]['resultVariable'])) {
            return $this->walkResultVariable($groupByItem);
        }

        // IdentificationVariable
        $sqlParts = array();

        foreach ($this->queryComponents[$groupByItem]['metadata']->fieldNames as $field) {
            $item       = new AST\PathExpression(AST\PathExpression::TYPE_STATE_FIELD, $groupByItem, $field);
            $item->type = AST\PathExpression::TYPE_STATE_FIELD;

            $sqlParts[] = $this->walkPathExpression($item);
        }

        foreach ($this->queryComponents[$groupByItem]['metadata']->associationMappings as $mapping) {
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
        $class     = $this->em->getClassMetadata($deleteClause->abstractSchemaName);
        $tableName = $class->getTableName();
        $sql       = 'DELETE FROM ' . $this->quoteStrategy->getTableName($class, $this->platform);

        $this->setSQLTableAlias($tableName, $tableName, $deleteClause->aliasIdentificationVariable);
        $this->rootAliases[] = $deleteClause->aliasIdentificationVariable;

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
        $class     = $this->em->getClassMetadata($updateClause->abstractSchemaName);
        $tableName = $class->getTableName();
        $sql       = 'UPDATE ' . $this->quoteStrategy->getTableName($class, $this->platform);

        $this->setSQLTableAlias($tableName, $tableName, $updateClause->aliasIdentificationVariable);
        $this->rootAliases[] = $updateClause->aliasIdentificationVariable;

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
        $useTableAliasesBefore = $this->useSqlTableAliases;
        $this->useSqlTableAliases = false;

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
                $sql .= $this->conn->quote($newValue);
                break;
        }

        $this->useSqlTableAliases = $useTableAliasesBefore;

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
        $discrSql = $this->_generateDiscriminatorColumnConditionSql($this->rootAliases);

        if ($this->em->hasFilters()) {
            $filterClauses = array();
            foreach ($this->rootAliases as $dqlAlias) {
                $class = $this->queryComponents[$dqlAlias]['metadata'];
                $tableAlias = $this->getSQLTableAlias($class->table['name'], $dqlAlias);

                if ($filterExpr = $this->generateFilterConditionSQL($class, $tableAlias)) {
                    $filterClauses[] = $filterExpr;
                }
            }

            if (count($filterClauses)) {
                if ($condSql) {
                    $condSql = '(' . $condSql . ') AND ';
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

        $class = $this->queryComponents[$dqlAlias]['metadata'];

        switch (true) {
            // InputParameter
            case ($entityExpr instanceof AST\InputParameter):
                $dqlParamKey = $entityExpr->name;
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
            $targetClass      = $this->em->getClassMetadata($assoc['targetEntity']);
            $targetTableAlias = $this->getSQLTableAlias($targetClass->getTableName());
            $sourceTableAlias = $this->getSQLTableAlias($class->getTableName(), $dqlAlias);

            $sql .= $this->quoteStrategy->getTableName($targetClass, $this->platform) . ' ' . $targetTableAlias . ' WHERE ';

            $owningAssoc = $targetClass->associationMappings[$assoc['mappedBy']];
            $sqlParts    = array();

            foreach ($owningAssoc['targetToSourceKeyColumns'] as $targetColumn => $sourceColumn) {
                $targetColumn = $this->quoteStrategy->getColumnName($class->fieldNames[$targetColumn], $class, $this->platform);

                $sqlParts[] = $sourceTableAlias . '.' . $targetColumn . ' = ' . $targetTableAlias . '.' . $sourceColumn;
            }

            foreach ($this->quoteStrategy->getIdentifierColumnNames($targetClass, $this->platform) as $targetColumnName) {
                if (isset($dqlParamKey)) {
                    $this->parserResult->addParameterMapping($dqlParamKey, $this->sqlParamIndex++);
                }

                $sqlParts[] = $targetTableAlias . '.'  . $targetColumnName . ' = ' . $entitySql;
            }

            $sql .= implode(' AND ', $sqlParts);
        } else { // many-to-many
            $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);

            $owningAssoc = $assoc['isOwningSide'] ? $assoc : $targetClass->associationMappings[$assoc['mappedBy']];
            $joinTable = $owningAssoc['joinTable'];

            // SQL table aliases
            $joinTableAlias   = $this->getSQLTableAlias($joinTable['name']);
            $targetTableAlias = $this->getSQLTableAlias($targetClass->getTableName());
            $sourceTableAlias = $this->getSQLTableAlias($class->getTableName(), $dqlAlias);

            // join to target table
            $sql .= $this->quoteStrategy->getJoinTableName($owningAssoc, $targetClass, $this->platform) . ' ' . $joinTableAlias
                  . ' INNER JOIN ' . $this->quoteStrategy->getTableName($targetClass, $this->platform) . ' ' . $targetTableAlias . ' ON ';

            // join conditions
            $joinColumns  = $assoc['isOwningSide'] ? $joinTable['inverseJoinColumns'] : $joinTable['joinColumns'];
            $joinSqlParts = array();

            foreach ($joinColumns as $joinColumn) {
                $targetColumn = $this->quoteStrategy->getColumnName($targetClass->fieldNames[$joinColumn['referencedColumnName']], $targetClass, $this->platform);

                $joinSqlParts[] = $joinTableAlias . '.' . $joinColumn['name'] . ' = ' . $targetTableAlias . '.' . $targetColumn;
            }

            $sql .= implode(' AND ', $joinSqlParts);
            $sql .= ' WHERE ';

            $joinColumns = $assoc['isOwningSide'] ? $joinTable['joinColumns'] : $joinTable['inverseJoinColumns'];
            $sqlParts    = array();

            foreach ($joinColumns as $joinColumn) {
                $targetColumn = $this->quoteStrategy->getColumnName($class->fieldNames[$joinColumn['referencedColumnName']], $class, $this->platform);

                $sqlParts[] = $joinTableAlias . '.' . $joinColumn['name'] . ' = ' . $sourceTableAlias . '.' . $targetColumn;
            }

            foreach ($this->quoteStrategy->getIdentifierColumnNames($targetClass, $this->platform) as $targetColumnName) {
                if (isset($dqlParamKey)) {
                    $this->parserResult->addParameterMapping($dqlParamKey, $this->sqlParamIndex++);
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
            $this->parserResult->addParameterMapping($dqlParamKey, $this->sqlParamIndex++);
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
        $discrClass = $class = $this->queryComponents[$dqlAlias]['metadata'];
        
        if ($class->discriminatorColumn) {
            $discrClass = $this->em->getClassMetadata($class->rootEntityName);
        }

        if ($this->useSqlTableAliases) {
            $sql .= $this->getSQLTableAlias($discrClass->getTableName(), $dqlAlias) . '.';
        }

        $sql .= $class->discriminatorColumn['name'] . ($instanceOfExpr->not ? ' NOT IN ' : ' IN ');

        $sqlParameterList = array();

        foreach ($instanceOfExpr->value as $parameter) {
            if ($parameter instanceof AST\InputParameter) {
                $sqlParameterList[] = $this->walkInputParameter($parameter);
            } else {
                // Get name from ClassMetadata to resolve aliases.
                $entityClassName = $this->em->getClassMetadata($parameter)->name;

                if ($entityClassName == $class->name) {
                    $sqlParameterList[] = $this->conn->quote($class->discriminatorValue);
                } else {
                    $discrMap = array_flip($class->discriminatorMap);

                    if (!isset($discrMap[$entityClassName])) {
                        throw QueryException::instanceOfUnrelatedClass($entityClassName, $class->rootEntityName);
                    }

                    $sqlParameterList[] = $this->conn->quote($discrMap[$entityClassName]);
                }
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
                return $this->conn->quote($literal->value);

            case AST\Literal::BOOLEAN:
                $bool = strtolower($literal->value) == 'true' ? true : false;
                $boolVal = $this->conn->getDatabasePlatform()->convertBooleans($bool);

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
            $this->parserResult->addParameterMapping($dqlParamKey, $this->sqlParamIndex++);
            $sql .= '?';
        } elseif ($likeExpr->stringPattern instanceof AST\Functions\FunctionNode ) {
            $sql .= $this->walkFunction($likeExpr->stringPattern);
        } elseif ($likeExpr->stringPattern instanceof AST\PathExpression) {
            $sql .= $this->walkPathExpression($likeExpr->stringPattern);
        } else {
            $sql .= $this->walkLiteral($likeExpr->stringPattern);
        }

        if ($likeExpr->escapeChar) {
            $sql .= ' ESCAPE ' . $this->walkLiteral($likeExpr->escapeChar);
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
            : (is_numeric($leftExpr) ? $leftExpr : $this->conn->quote($leftExpr));

        $sql .= ' ' . $compExpr->operator . ' ';

        $sql .= ($rightExpr instanceof AST\Node)
            ? $rightExpr->dispatch($this)
            : (is_numeric($rightExpr) ? $rightExpr : $this->conn->quote($rightExpr));

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
        $this->parserResult->addParameterMapping($inputParam->name, $this->sqlParamIndex++);

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
            return (isset($this->queryComponents[$term]))
                ? $this->walkResultVariable($this->queryComponents[$term]['token']['value'])
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
            ? $this->conn->quote($stringPrimary)
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
        $resultAlias = $this->scalarResultAliasMap[$resultVariable];

        if (is_array($resultAlias)) {
            return implode(', ', $resultAlias);
        }

        return $resultAlias;
    }
}
