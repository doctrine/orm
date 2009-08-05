<?php
/*
 *  $Id$
 *
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

use Doctrine\ORM\Query,
    Doctrine\Common\DoctrineException;

/**
 * The SqlWalker is a TreeWalker that walks over a DQL AST and constructs
 * the corresponding SQL.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 * @todo Code review for identifier quoting.
 * @todo Code review for schema usage with table names.
 *       (Prepend schema name to tables IF schema is defined AND platform supports them)
 */
class SqlWalker implements TreeWalker
{
    /** The ResultSetMapping. */
    private $_rsm;
    
    /** Counter for generating unique column aliases. */
    private $_aliasCounter = 0;
    
    /** Counter for generating unique table aliases. */
    private $_tableAliasCounter = 0;
    private $_scalarResultCounter = 1;
    
    /** Counter for SQL parameter positions. */
    private $_sqlParamIndex = 1;
    
    /** The ParserResult. */
    private $_parserResult;
    
    /** The EntityManager. */
    private $_em;
    
    /** The Connection of the EntityManager. */
    private $_conn;
    
    /** The Query instance. */
    private $_query;
    private $_dqlToSqlAliasMap = array();
    
    /** Map of all components/classes that appear in the DQL query. */
    private $_queryComponents;
    
    /** A list of classes that appear in non-scalar SelectExpressions. */
    private $_selectedClasses = array();
    
    /**
     * The DQL alias of the root class of the currently traversed query.
     * TODO: May need to be turned into a stack for usage in subqueries
     */
    private $_currentRootAlias;
    
    /**
     * Flag that indicates whether to generate SQL table aliases in the SQL.
     * These should only be generated for SELECT queries.
     */
    private $_useSqlTableAliases = true;

    /**
     * @inheritdoc
     */
    public function __construct($query, $parserResult, array $queryComponents)
    {
        $this->_rsm = $parserResult->getResultSetMapping();
        $this->_query = $query;
        $this->_em = $query->getEntityManager();
        $this->_conn = $this->_em->getConnection();
        $this->_parserResult = $parserResult;
        $this->_queryComponents = $queryComponents;
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
        return $this->_em->getConnection();
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
    
    public function getQueryComponent($dqlAlias)
    {
        return $this->_queryComponents[$dqlAlias];
    }

    /**
     * Walks down a SelectStatement AST node, thereby generating the appropriate SQL.
     *
     * @return string The SQL.
     */
    public function walkSelectStatement(AST\SelectStatement $AST)
    {
        $sql = $this->walkSelectClause($AST->getSelectClause());
        $sql .= $this->walkFromClause($AST->getFromClause());
        
        if ($whereClause = $AST->getWhereClause()) {
            $sql .= $this->walkWhereClause($whereClause);
        } else if ($discSql = $this->_generateDiscriminatorColumnConditionSql($this->_currentRootAlias)) {
            $sql .= ' WHERE ' . $discSql;
        }

        $sql .= $AST->getGroupByClause() ? $this->walkGroupByClause($AST->getGroupByClause()) : '';
        $sql .= $AST->getHavingClause() ? $this->walkHavingClause($AST->getHavingClause()) : '';
        $sql .= $AST->getOrderByClause() ? $this->walkOrderByClause($AST->getOrderByClause()) : '';

        $q = $this->getQuery();
        $sql = $this->getConnection()->getDatabasePlatform()->modifyLimitQuery(
            $sql, $q->getMaxResults(), $q->getFirstResult()
        );

        return $sql;
    }

    /**
     * Walks down a SelectClause AST node, thereby generating the appropriate SQL.
     *
     * @return string The SQL.
     */
    public function walkSelectClause($selectClause)
    {
        $sql = 'SELECT ' . (($selectClause->isDistinct()) ? 'DISTINCT ' : '') . implode(
            ', ', array_map(array($this, 'walkSelectExpression'), $selectClause->getSelectExpressions())
        );

        foreach ($this->_selectedClasses as $dqlAlias => $class) {        	
            if ($this->_queryComponents[$dqlAlias]['relation'] === null) {
                $this->_rsm->addEntityResult($class->name, $dqlAlias);
            } else {
                $this->_rsm->addJoinedEntityResult(
                    $class->name, $dqlAlias,
                    $this->_queryComponents[$dqlAlias]['parent'],
                    $this->_queryComponents[$dqlAlias]['relation']
                );
            }
            
            if ($class->isInheritanceTypeSingleTable() || $class->isInheritanceTypeJoined()) {
                $rootClass = $this->_em->getClassMetadata($class->rootEntityName);
                $tblAlias = $this->getSqlTableAlias($rootClass->getTableName(), $dqlAlias);
                $discrColumn = $rootClass->discriminatorColumn;
                $columnAlias = $this->getSqlColumnAlias($discrColumn['name']);
                $sql .= ", $tblAlias." . $discrColumn['name'] . ' AS ' . $columnAlias;
                
                $this->_rsm->setDiscriminatorColumn($dqlAlias, $columnAlias);
                $this->_rsm->addMetaResult($dqlAlias, $columnAlias, $discrColumn['fieldName']);
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
        $sql = ' FROM ';
        $identificationVarDecls = $fromClause->getIdentificationVariableDeclarations();
        $firstIdentificationVarDecl = $identificationVarDecls[0];
        $rangeDecl = $firstIdentificationVarDecl->getRangeVariableDeclaration();
        $dqlAlias = $rangeDecl->getAliasIdentificationVariable();

        $this->_currentRootAlias = $dqlAlias;
        $class = $rangeDecl->getClassMetadata();

        $sql .= $class->getTableName() . ' ' . $this->getSqlTableAlias($class->getTableName(), $dqlAlias);

        if ($class->isInheritanceTypeJoined()) {
            $sql .= $this->_generateClassTableInheritanceJoins($class, $dqlAlias);
        }

        foreach ($firstIdentificationVarDecl->getJoinVariableDeclarations() as $joinVarDecl) {
            $sql .= $this->walkJoinVariableDeclaration($joinVarDecl);
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
        // OrderByClause ::= "ORDER" "BY" OrderByItem {"," OrderByItem}*
        return ' ORDER BY ' . implode(
            ', ', array_map(array($this, 'walkOrderByItem'), $orderByClause->getOrderByItems())
        );
    }

    /**
     * Walks down an OrderByItem AST node, thereby generating the appropriate SQL.
     *
     * @param OrderByItem
     * @return string The SQL.
     */
    public function walkOrderByItem($orderByItem)
    {
        //TODO: support general SingleValuedPathExpression, not just state field
        $expr = $orderByItem->getExpression();
        $parts = $expr->getParts();
        $dqlAlias = $expr->getIdentificationVariable();
        $qComp = $this->_queryComponents[$dqlAlias];
        $columnName = $qComp['metadata']->getColumnName($parts[0]);
        
        $sql = $this->getSqlTableAlias($qComp['metadata']->getTableName(), $dqlAlias) . '.' . $columnName;
        $sql .= $orderByItem->isDesc() ? ' DESC' : ' ASC';

        return $sql;
    }

    /**
     * Walks down a HavingClause AST node, thereby generating the appropriate SQL.
     *
     * @param HavingClause
     * @return string The SQL.
     */
    public function walkHavingClause($havingClause)
    {
        $condExpr = $havingClause->getConditionalExpression();
        
        return ' HAVING ' . implode(
            ' OR ', array_map(array($this, 'walkConditionalTerm'), $condExpr->getConditionalTerms())
        );
    }

    /**
     * Walks down a JoinVariableDeclaration AST node and creates the corresponding SQL.
     *
     * @param JoinVariableDeclaration $joinVarDecl
     * @return string The SQL.
     */
    public function walkJoinVariableDeclaration($joinVarDecl)
    {
        $join = $joinVarDecl->getJoin();
        $joinType = $join->getJoinType();
        
        if ($joinType == AST\Join::JOIN_TYPE_LEFT || $joinType == AST\Join::JOIN_TYPE_LEFTOUTER) {
            $sql = ' LEFT JOIN ';
        } else {
            $sql = ' INNER JOIN ';
        }

        $joinAssocPathExpr = $join->getJoinAssociationPathExpression();
        $joinedDqlAlias = $join->getAliasIdentificationVariable();
        $sourceQComp = $this->_queryComponents[$joinAssocPathExpr->getIdentificationVariable()];
        $targetQComp = $this->_queryComponents[$joinedDqlAlias];
        
        $targetTableName = $targetQComp['metadata']->getTableName();
        $targetTableAlias = $this->getSqlTableAlias($targetTableName, $joinedDqlAlias);
        $sourceTableAlias = $this->getSqlTableAlias($sourceQComp['metadata']->getTableName(), $joinAssocPathExpr->getIdentificationVariable());

        // Ensure we got the owning side, since it has all mapping info
        if ( ! $targetQComp['relation']->isOwningSide()) {
            $assoc = $targetQComp['metadata']->getAssociationMapping($targetQComp['relation']->getMappedByFieldName());
        } else {
            $assoc = $targetQComp['relation'];
        }

        if ($assoc->isOneToOne()) {
            $sql .= $targetTableName . ' ' . $targetTableAlias . ' ON ';
            $joinColumns = $assoc->getSourceToTargetKeyColumns();
            $first = true;
            foreach ($joinColumns as $sourceColumn => $targetColumn) {
                if ( ! $first) {
                    $sql .= ' AND ';
                } else {
                    $first = false;
                }
                
                if ($targetQComp['relation']->isOwningSide()) {
                    $sql .= "$sourceTableAlias.$sourceColumn = $targetTableAlias.$targetColumn";
                } else {
                    $sql .= "$sourceTableAlias.$targetColumn = $targetTableAlias.$sourceColumn";
                }
            }
        } else if ($assoc->isManyToMany()) {
            // Join relation table
            $joinTable = $assoc->getJoinTable();
            $joinTableAlias = $this->getSqlTableAlias($joinTable['name']);
            $sql .= $joinTable['name'] . ' ' . $joinTableAlias . ' ON ';
            
            if ($targetQComp['relation']->isOwningSide) {
                $sourceToRelationJoinColumns = $assoc->getSourceToRelationKeyColumns();
                
                foreach ($sourceToRelationJoinColumns as $sourceColumn => $relationColumn) {
                    $sql .= "$sourceTableAlias.$sourceColumn = $joinTableAlias.$relationColumn";
                }
            } else {
                $targetToRelationJoinColumns = $assoc->getTargetToRelationKeyColumns();
                
                foreach ($targetToRelationJoinColumns as $targetColumn => $relationColumn) {
                    $sql .= "$sourceTableAlias.$targetColumn = $joinTableAlias.$relationColumn";
                }
            }

            // Join target table
            $sql .= (($joinType == AST\Join::JOIN_TYPE_LEFT || $joinType == AST\Join::JOIN_TYPE_LEFTOUTER)
            	? ' LEFT JOIN ' : ' INNER JOIN ') . $targetTableName . ' ' . $targetTableAlias . ' ON ';

            if ($targetQComp['relation']->isOwningSide) {
                $targetToRelationJoinColumns = $assoc->getTargetToRelationKeyColumns();
                
                foreach ($targetToRelationJoinColumns as $targetColumn => $relationColumn) {
                    $sql .= "$targetTableAlias.$targetColumn = $joinTableAlias.$relationColumn";
                }
            } else {
                $sourceToRelationJoinColumns = $assoc->getSourceToRelationKeyColumns();
                
                foreach ($sourceToRelationJoinColumns as $sourceColumn => $relationColumn) {
                    $sql .= "$targetTableAlias.$sourceColumn = $joinTableAlias.$relationColumn";
                }
            }
        }

        $discrSql = $this->_generateDiscriminatorColumnConditionSql($joinedDqlAlias);
        
        if ($discrSql) {
            $sql .= ' AND ' . $discrSql;
        }

        if ($targetQComp['metadata']->isInheritanceTypeJoined()) {
            $sql .= $this->_generateClassTableInheritanceJoins($targetQComp['metadata'], $joinedDqlAlias);
        }
        
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
        $sql = '';
        $expr = $selectExpression->getExpression();
        if ($expr instanceof AST\PathExpression) {
            if ($expr->getType() == AST\PathExpression::TYPE_STATE_FIELD) {
                $parts = $expr->getParts();
                $numParts = count($parts);
                $dqlAlias = $expr->getIdentificationVariable();
                $fieldName = $parts[$numParts-1];
                $qComp = $this->_queryComponents[$dqlAlias];
                $class = $qComp['metadata'];

                if ( ! isset($this->_selectedClasses[$dqlAlias])) {
                    $this->_selectedClasses[$dqlAlias] = $class;
                }

                $sqlTableAlias = $this->getSqlTableAlias($class->getTableName(), $dqlAlias);
                $columnName = $class->getColumnName($fieldName);
                $columnAlias = $this->getSqlColumnAlias($columnName);
                $sql .= $sqlTableAlias . '.' . $columnName . ' AS ' . $columnAlias;
                
                $this->_rsm->addFieldResult($dqlAlias, $columnAlias, $fieldName);                
            } else {
                throw DoctrineException::updateMe("Encountered invalid PathExpression during SQL construction.");
            }
        } else if ($expr instanceof AST\AggregateExpression) {
            if ( ! $selectExpression->getFieldIdentificationVariable()) {
                $resultAlias = $this->_scalarResultCounter++;
            } else {
                $resultAlias = $selectExpression->getFieldIdentificationVariable();
            }
            
            $columnAlias = 'sclr' . $this->_aliasCounter++;
            $sql .= $this->walkAggregateExpression($expr) . ' AS ' . $columnAlias;
            
            $this->_rsm->addScalarResult($columnAlias, $resultAlias);
        } else if ($expr instanceof AST\Subselect) {
            $sql .= $this->walkSubselect($expr);
        } else if ($expr instanceof AST\Functions\FunctionNode) {
            if ( ! $selectExpression->getFieldIdentificationVariable()) {
                $resultAlias = $this->_scalarResultCounter++;
            } else {
                $resultAlias = $selectExpression->getFieldIdentificationVariable();
            }
            
            $columnAlias = 'sclr' . $this->_aliasCounter++;
            $sql .= $this->walkFunction($expr) . ' AS ' . $columnAlias;
            
            $this->_rsm->addScalarResult($columnAlias, $resultAlias);
        } else {
            // IdentificationVariable
            $dqlAlias = $expr;
            $queryComp = $this->_queryComponents[$dqlAlias];
            $class = $queryComp['metadata'];

            if ( ! isset($this->_selectedClasses[$dqlAlias])) {
                $this->_selectedClasses[$dqlAlias] = $class;
            }

            $beginning = true;
            if ($class->isInheritanceTypeJoined()) {
                // Select all fields from the queried class
                foreach ($class->fieldMappings as $fieldName => $mapping) {
                    if (isset($mapping['inherited'])) {
                        $tableName = $this->_em->getClassMetadata($mapping['inherited'])->primaryTable['name'];
                    } else {
                        $tableName = $class->primaryTable['name'];
                    }
                    
                    if ($beginning) $beginning = false; else $sql .= ', ';
                    
                    $sqlTableAlias = $this->getSqlTableAlias($tableName, $dqlAlias);
                    $columnAlias = $this->getSqlColumnAlias($mapping['columnName']);
                    $sql .= $sqlTableAlias . '.' . $this->_conn->quoteIdentifier($mapping['columnName']) . ' AS ' . $columnAlias;
                    
                    $this->_rsm->addFieldResult($dqlAlias, $columnAlias, $fieldName);
                }

                // Add any additional fields of subclasses (not inherited fields)
                foreach ($class->subClasses as $subClassName) {
                    $subClass = $this->_em->getClassMetadata($subClassName);
                    
                    foreach ($subClass->fieldMappings as $fieldName => $mapping) {
                        if (isset($mapping['inherited'])) {
                            continue;
                        }
                        
                        if ($beginning) $beginning = false; else $sql .= ', ';
                        
                        $sqlTableAlias = $this->getSqlTableAlias($subClass->primaryTable['name'], $dqlAlias);
                        $columnAlias = $this->getSqlColumnAlias($mapping['columnName']);
                        $sql .= $sqlTableAlias . '.' . $this->_conn->quoteIdentifier($mapping['columnName']) . ' AS ' . $columnAlias;
                        
                        $this->_rsm->addFieldResult($dqlAlias, $columnAlias, $fieldName);
                    }
                }
            } else {
                $fieldMappings = $class->fieldMappings;
                
                foreach ($class->subClasses as $subclassName) {
                    $fieldMappings = array_merge(
                        $fieldMappings,
                        $this->_em->getClassMetadata($subclassName)->fieldMappings
                    );
                }
                
                $sqlTableAlias = $this->getSqlTableAlias($class->getTableName(), $dqlAlias);
                
                foreach ($fieldMappings as $fieldName => $mapping) {
                    if ($beginning) $beginning = false; else $sql .= ', ';
                    
                    $columnAlias = $this->getSqlColumnAlias($mapping['columnName']);
                    $sql .= $sqlTableAlias . '.' . $this->_conn->quoteIdentifier($mapping['columnName']) . ' AS ' . $columnAlias;
                    
                    $this->_rsm->addFieldResult($dqlAlias, $columnAlias, $fieldName);
                }
                
                // Append foreign keys if necessary.
                //FIXME: Evaluate HINT_INCLUDE_META_COLUMNS
                //FIXME: Needs to be done in the case of Class Table Inheritance, too
                //       (see upper block of the if/else)
                if (
                    ! $this->_em->getConfiguration()->getAllowPartialObjects() &&
                    ! $this->_query->getHint(Query::HINT_FORCE_PARTIAL_LOAD)
                ) {
                    foreach ($class->associationMappings as $assoc) {
                        if ($assoc->isOwningSide && $assoc->isOneToOne()) {
                            foreach ($assoc->targetToSourceKeyColumns as $srcColumn) {
                                $columnAlias = $this->getSqlColumnAlias($srcColumn);
                                $sql .= ', ' . $sqlTableAlias . '.' . $this->_conn->quoteIdentifier($srcColumn) . ' AS ' . $columnAlias;
                                
                                $this->_rsm->addMetaResult($dqlAlias, $columnAlias, $srcColumn);
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
        $sql = '';
        
        if ($qExpr->isAll()) $sql .= ' ALL';
        else if ($qExpr->isAny()) $sql .= ' ANY';
        else if ($qExpr->isSome()) $sql .= ' SOME';
        
        return $sql .= '(' . $this->walkSubselect($qExpr->getSubselect()) . ')';
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
        
        $sql = $this->walkSimpleSelectClause($subselect->getSimpleSelectClause());
        $sql .= $this->walkSubselectFromClause($subselect->getSubselectFromClause());
        $sql .= $subselect->getWhereClause() ? $this->walkWhereClause($subselect->getWhereClause()) : '';
        $sql .= $subselect->getGroupByClause() ? $this->walkGroupByClause($subselect->getGroupByClause()) : '';
        $sql .= $subselect->getHavingClause() ? $this->walkHavingClause($subselect->getHavingClause()) : '';
        $sql .= $subselect->getOrderByClause() ? $this->walkOrderByClause($subselect->getOrderByClause()) : '';
        
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
        $sql = ' FROM ';
        $identificationVarDecls = $subselectFromClause->getSubselectIdentificationVariableDeclarations();
        $firstIdentificationVarDecl = $identificationVarDecls[0];
        $rangeDecl = $firstIdentificationVarDecl->getRangeVariableDeclaration();
        $sql .= $rangeDecl->getClassMetadata()->getTableName() . ' '
             . $this->getSqlTableAlias($rangeDecl->getClassMetadata()->getTableName(), $rangeDecl->getAliasIdentificationVariable());

        foreach ($firstIdentificationVarDecl->getJoinVariableDeclarations() as $joinVarDecl) {
            $sql .= $this->walkJoinVariableDeclaration($joinVarDecl);
        }

        return $sql;
    }

    /**
     * Walks down a SimpleSelectClause AST node, thereby generating the appropriate SQL.
     *
     * @param SimpleSelectClause
     * @return string The SQL.
     */
    public function walkSimpleSelectClause($simpleSelectClause)
    {
        $sql = 'SELECT';
        
        if ($simpleSelectClause->isDistinct()) {
            $sql .= ' DISTINCT';
        }
        
        $sql .= $this->walkSimpleSelectExpression($simpleSelectClause->getSimpleSelectExpression());
        
        return $sql;
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
        $expr = $simpleSelectExpression->getExpression();
        
        if ($expr instanceof AST\PathExpression) {
            $sql .= ' ' . $this->walkPathExpression($expr);
            //...
        } else if ($expr instanceof AST\AggregateExpression) {
            if ( ! $simpleSelectExpression->getFieldIdentificationVariable()) {
                $alias = $this->_scalarAliasCounter++;
            } else {
                $alias = $simpleSelectExpression->getFieldIdentificationVariable();
            }
            
            $sql .= $this->walkAggregateExpression($expr) . ' AS dctrn__' . $alias;
        } else {
            // IdentificationVariable
            // FIXME: Composite key support, or select all columns? Does that make
            //       in a subquery?
            $class = $this->_queryComponents[$expr]['metadata'];
            $sql .= ' ' . $this->getSqlTableAlias($class->getTableName(), $expr) . '.';
            $sql .= $class->getColumnName($class->identifier[0]);
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
        $sql = '';
        $parts = $aggExpression->getPathExpression()->getParts();
        $dqlAlias = $aggExpression->getPathExpression()->getIdentificationVariable();
        $fieldName = $parts[0];

        $qComp = $this->_queryComponents[$dqlAlias];
        $columnName = $qComp['metadata']->getColumnName($fieldName);

        $sql .= $aggExpression->getFunctionName() . '(';
        
        if ($aggExpression->isDistinct()) $sql .= 'DISTINCT ';
        
        $sql .= $this->getSqlTableAlias($qComp['metadata']->getTableName(), $dqlAlias) . '.' . $columnName;
        $sql .= ')';
        
        return $sql;
    }

    /**
     * Walks down a GroupByClause AST node, thereby generating the appropriate SQL.
     *
     * @param GroupByClause
     * @return string The SQL.
     */
    public function walkGroupByClause($groupByClause)
    {
        return ' GROUP BY ' . implode(
            ', ', array_map(array($this, 'walkGroupByItem'), $groupByClause->getGroupByItems())
        );
    }

    /**
     * Walks down a GroupByItem AST node, thereby generating the appropriate SQL.
     *
     * @param GroupByItem
     * @return string The SQL.
     */
    public function walkGroupByItem(AST\PathExpression $pathExpr)
    {
        //TODO: support general SingleValuedPathExpression, not just state field
        $parts = $pathExpr->getParts();
        $dqlAlias = $pathExpr->getIdentificationVariable();
        $qComp = $this->_queryComponents[$dqlAlias];
        $columnName = $qComp['metadata']->getColumnName($parts[0]);
        
        return $this->getSqlTableAlias($qComp['metadata']->getTableName(), $dqlAlias) . '.' . $columnName;
    }

    /**
     * Walks down an UpdateStatement AST node, thereby generating the appropriate SQL.
     *
     * @param UpdateStatement
     * @return string The SQL.
     */
    public function walkUpdateStatement(AST\UpdateStatement $AST)
    {
        $this->_useSqlTableAliases = false; // TODO: Ask platform instead?
        $sql = $this->walkUpdateClause($AST->getUpdateClause());
        
        if ($whereClause = $AST->getWhereClause()) {
            $sql .= $this->walkWhereClause($whereClause);
        } else if ($discSql = $this->_generateDiscriminatorColumnConditionSql($this->_currentRootAlias)) {
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
        $this->_useSqlTableAliases = false; // TODO: Ask platform instead?
        $sql = $this->walkDeleteClause($AST->getDeleteClause());
        
        if ($whereClause = $AST->getWhereClause()) {
            $sql .= $this->walkWhereClause($whereClause);
        } else if ($discSql = $this->_generateDiscriminatorColumnConditionSql($this->_currentRootAlias)) {
            $sql .= ' WHERE ' . $discSql;
        }
        
        return $sql;
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
        $class = $this->_em->getClassMetadata($deleteClause->getAbstractSchemaName());
        $sql .= $class->getTableName();
        
        if ($this->_useSqlTableAliases) {
            $sql .= ' ' . $this->getSqlTableAlias($class->getTableName());
        }
        
        $this->_currentRootAlias = $deleteClause->getAliasIdentificationVariable();

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
        $class = $this->_em->getClassMetadata($updateClause->getAbstractSchemaName());
        $sql .= $class->getTableName();
        
        if ($this->_useSqlTableAliases) {
            $sql .= ' ' . $this->getSqlTableAlias($class->getTableName());
        }
        
        $this->_currentRootAlias = $updateClause->getAliasIdentificationVariable();

        $sql .= ' SET ' . implode(
            ', ', array_map(array($this, 'walkUpdateItem'), $updateClause->getUpdateItems())
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
        
        $sql = '';
        $dqlAlias = $updateItem->getIdentificationVariable();
        $qComp = $this->_queryComponents[$dqlAlias];

        if ($this->_useSqlTableAliases) {
            $sql .= $this->getSqlTableAlias($qComp['metadata']->getTableName()) . '.';
        }
        
        $sql .= $qComp['metadata']->getColumnName($updateItem->getField()) . ' = ';

        $newValue = $updateItem->getNewValue();

        if ($newValue instanceof AST\Node) {
            $sql .= $newValue->dispatch($this);
        } else if (is_string($newValue)) {
            if (strcasecmp($newValue, 'NULL') === 0) {
                $sql .= 'NULL';
            } else {
                $sql .= $this->_conn->quote($newValue);
            }
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
        $sql = ' WHERE ';
        $condExpr = $whereClause->getConditionalExpression();
        
        $sql .= implode(
            ' OR ', array_map(array($this, 'walkConditionalTerm'), $condExpr->getConditionalTerms())
        );

        $discrSql = $this->_generateDiscriminatorColumnConditionSql($this->_currentRootAlias);
        
        if ($discrSql) {
            if ($termsSql) $sql .= ' AND';
            
            $sql .= ' ' . $discrSql;
        }

        return $sql;
    }

    /**
     * Generates a discriminator column SQL condition for the class with the given DQL alias.
     *
     * @param string $dqlAlias
     * @return string
     */
    private function _generateDiscriminatorColumnConditionSql($dqlAlias)
    {
        $sql = '';
        if ($dqlAlias) {
            $class = $this->_queryComponents[$dqlAlias]['metadata'];
            
            if ($class->isInheritanceTypeSingleTable()) {
                $conn = $this->_em->getConnection();
                $values = array($conn->quote($class->discriminatorValue));
                
                foreach ($class->subClasses as $subclassName) {
                    $values[] = $conn->quote($this->_em->getClassMetadata($subclassName)->discriminatorValue);
                }
                
                $discrColumn = $class->discriminatorColumn;
                
                if ($this->_useSqlTableAliases) {
                    $sql .= $this->getSqlTableAlias($class->getTableName(), $dqlAlias) . '.';
                }
                
                $sql .= $discrColumn['name'] . ' IN (' . implode(', ', $values) . ')';
            }
        }
        
        return $sql;
    }

    /**
     * Walks down a ConditionalTerm AST node, thereby generating the appropriate SQL.
     *
     * @param ConditionalTerm
     * @return string The SQL.
     */
    public function walkConditionalTerm($condTerm)
    {
        return implode(
            ' AND ', array_map(array($this, 'walkConditionalFactor'), $condTerm->getConditionalFactors())
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
        $sql = '';
        
        if ($factor->isNot()) $sql .= 'NOT ';
        
        $primary = $factor->getConditionalPrimary();
        
        if ($primary->isSimpleConditionalExpression()) {
            $sql .= $primary->getSimpleConditionalExpression()->dispatch($this);
        } else if ($primary->isConditionalExpression()) {
            $condExpr = $primary->getConditionalExpression();
            $sql .= '(' . implode(
                ' OR ', array_map(array($this, 'walkConditionalTerm'), $condExpr->getConditionalTerms())
            ) . ')';
        }
        
        return $sql;
    }

    /**
     * Walks down an ExistsExpression AST node, thereby generating the appropriate SQL.
     *
     * @param ExistsExpression
     * @return string The SQL.
     */
    public function walkExistsExpression($existsExpr)
    {
        $sql = '';
        
        if ($existsExpr->isNot()) $sql .= ' NOT';
        
        $sql .= 'EXISTS (' . $this->walkSubselect($existsExpr->getSubselect()) . ')';
        
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
        $sql = $collMemberExpr->isNot ? 'NOT ' : '';
        $sql .= 'EXISTS (SELECT 1 FROM ';
        $entityExpr = $collMemberExpr->entityExpression;
        $collPathExpr = $collMemberExpr->collectionValuedPathExpression;
        $parts = $collPathExpr->getParts();
        $dqlAlias = $collPathExpr->getIdentificationVariable();
        
        $class = $this->_queryComponents[$dqlAlias]['metadata'];
        
        if ($entityExpr instanceof AST\InputParameter) {
            $dqlParamKey = $entityExpr->isNamed() ? $entityExpr->getName() : $entityExpr->getPosition();
            $entity = $this->_query->getParameter($dqlParamKey);
        } else {
            //TODO
            throw DoctrineException::notImplemented();
        }
        
        $assoc = $class->associationMappings[$parts[0]];
        if ($assoc->isOneToMany()) {
            $targetClass = $this->_em->getClassMetadata($assoc->targetEntityName);
            $targetTableAlias = $this->getSqlTableAlias($targetClass->primaryTable['name']);
            $sourceTableAlias = $this->getSqlTableAlias($class->primaryTable['name'], $dqlAlias);
            
            $sql .= $this->_conn->quoteIdentifier($targetClass->primaryTable['name'])
                    . ' ' . $targetTableAlias . ' WHERE ';
                    
            $owningAssoc = $targetClass->associationMappings[$assoc->mappedByFieldName];
            
            $first = true;
            
            foreach ($owningAssoc->targetToSourceKeyColumns as $targetColumn => $sourceColumn) {
                if ($first) $first = false; else $sql .= ' AND ';
                
                $sql .= $sourceTableAlias . '.' . $targetColumn . ' = ' . $targetTableAlias . '.' . $sourceColumn;
            }
            
            $sql .= ' AND ';
            $first = true;
            
            foreach ($targetClass->identifier as $idField) {
                if ($first) $first = false; else $sql .= ' AND ';
                
                $this->_parserResult->addParameterMapping($dqlParamKey, $this->_sqlParamIndex++);
                $sql .= $targetTableAlias . '.' . $targetClass->columnNames[$idField] . ' = ?';
            }
        } else { // many-to-many
            $targetClass = $this->_em->getClassMetadata($assoc->targetEntityName);
            $sourceTableAlias = $this->getSqlTableAlias($class->primaryTable['name'], $dqlAlias);
            $joinTable = $assoc->isOwningSide ? $assoc->joinTable : $targetClass->associationMappings[$assoc->mappedByFieldName]->joinTable;
            $joinTableAlias = $this->getSqlTableAlias($joinTable['name']);
            $targetTableAlias = $this->getSqlTableAlias($targetClass->primaryTable['name']);
            
            // join to target table
            $sql .= $this->_conn->quoteIdentifier($joinTable['name'])
                  . ' ' . $joinTableAlias . ' INNER JOIN '
                  . $this->_conn->quoteIdentifier($targetClass->primaryTable['name'])
                  . ' ' . $targetTableAlias . ' ON ';
            
            // join conditions
            $joinColumns = $assoc->isOwningSide ? $joinTable['joinColumns'] : $joinTable['inverseJoinColumns'];
            $first = true;
            
            foreach ($joinColumns as $joinColumn) {
                if ($first) $first = false; else $sql .= ' AND ';
                
                $sql .= $joinTableAlias . '.' . $joinColumn['name'] . ' = ' . $sourceTableAlias . '.' . $joinColumn['referencedColumnName'];
            }
            
            $sql .= ' WHERE ';
            
            $joinColumns = $assoc->isOwningSide ? $joinTable['inverseJoinColumns'] : $joinTable['joinColumns'];
            $first = true;
            
            foreach ($joinColumns as $joinColumn) {
                if ($first) $first = false; else $sql .= ' AND ';
                
                $sql .= $joinTableAlias . '.' . $joinColumn['name'] . ' = ' . $targetTableAlias . '.' . $joinColumn['referencedColumnName'];
            }
            
            $sql .= ' AND ';
            $first = true;
            
            foreach ($targetClass->identifier as $idField) {
                if ($first) $first = false; else $sql .= ' AND ';
                
                $this->_parserResult->addParameterMapping($dqlParamKey, $this->_sqlParamIndex++);
                $sql .= $targetTableAlias . '.' . $targetClass->columnNames[$idField] . ' = ?';
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
        $sizeFunc->setCollectionPathExpression($emptyCollCompExpr->getExpression());
        
        $sql  = $sizeFunc->getSql($this);
        $sql .= ($emptyCollCompExpr->isNot() ? ' > 0' : ' = 0');
        
        return $sql;
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
        $innerExpr = $nullCompExpr->getExpression();
        
        if ($innerExpr instanceof AST\InputParameter) {
            $dqlParamKey = $innerExpr->isNamed() ? $innerExpr->getName() : $innerExpr->getPosition();
            $this->_parserResult->addParameterMapping($dqlParamKey, $this->_sqlParamIndex++);
            $sql .= ' ?';// . ($innerExpr->isNamed() ? ':' . $innerExpr->getName() : '?');
        } else {
            $sql .= $this->walkPathExpression($innerExpr);
        }
        
        $sql .= ' IS' . ($nullCompExpr->isNot() ? ' NOT' : '') . ' NULL';
        
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
        $sql = $this->walkPathExpression($inExpr->getPathExpression());
        
        if ($inExpr->isNot()) $sql .= ' NOT';
        
        $sql .= ' IN (';
        
        if ($inExpr->getSubselect()) {
            $sql .= $this->walkSubselect($inExpr->getSubselect());
        } else {
            $sql .= implode(', ', array_map(array($this, 'walkLiteral'), $inExpr->getLiterals()));
        }
        
        $sql .= ')';
        
        return $sql;
    }

    /**
     * Walks down a literal that represents an AST node, thereby generating the appropriate SQL.
     *
     * @param mixed
     * @return string The SQL.
     */
    public function walkLiteral($literal)
    {
        if ($literal instanceof AST\InputParameter) {
            $dqlParamKey = $literal->isNamed() ? $literal->getName() : $literal->getPosition();
            $this->_parserResult->addParameterMapping($dqlParamKey, $this->_sqlParamIndex++);
            
            return '?';
        }
        
        return $literal; //TODO: quote() ?
    }

    /**
     * Walks down a BetweenExpression AST node, thereby generating the appropriate SQL.
     *
     * @param BetweenExpression
     * @return string The SQL.
     */
    public function walkBetweenExpression($betweenExpr)
    {
        $sql = $this->walkArithmeticExpression($betweenExpr->getBaseExpression());
        
        if ($betweenExpr->getNot()) $sql .= ' NOT';
        
        $sql .= ' BETWEEN ' . $this->walkArithmeticExpression($betweenExpr->getLeftBetweenExpression())
              . ' AND ' . $this->walkArithmeticExpression($betweenExpr->getRightBetweenExpression());
              
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
        $stringExpr = $likeExpr->getStringExpression();
        $sql = $stringExpr->dispatch($this);

        if ($likeExpr->isNot()) $sql .= ' NOT';
        
        $sql .= ' LIKE ';
        
        if ($likeExpr->getStringPattern() instanceof AST\InputParameter) {
            $inputParam = $likeExpr->getStringPattern();
            $dqlParamKey = $inputParam->isNamed() ? $inputParam->getName() : $inputParam->getPosition();
            $this->_parserResult->addParameterMapping($dqlParamKey, $this->_sqlParamIndex++);
            $sql .= '?';
        } else {
            $sql .= $this->_conn->quote($likeExpr->getStringPattern());
        }
        
        if ($likeExpr->getEscapeChar()) {
            $sql .= ' ESCAPE ' . $this->_conn->quote($likeExpr->getEscapeChar());
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
        $leftExpr = $compExpr->getLeftExpression();
        $rightExpr = $compExpr->getRightExpression();

        if ($leftExpr instanceof AST\Node) {
            $sql .= $leftExpr->dispatch($this);
        } else {
            $sql .= $this->_conn->quote($leftExpr);
        }
        
        $sql .= ' ' . $compExpr->getOperator() . ' ';

        if ($rightExpr instanceof AST\Node) {
            $sql .= $rightExpr->dispatch($this);
        } else {
            $sql .= $this->_conn->quote($rightExpr);
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
        $dqlParamKey = $inputParam->isNamed() ? $inputParam->getName() : $inputParam->getPosition();
        $this->_parserResult->addParameterMapping($dqlParamKey, $this->_sqlParamIndex++);
        
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
        $sql = '';
        
        if ($arithmeticExpr->isSimpleArithmeticExpression()) {
            foreach ($arithmeticExpr->getSimpleArithmeticExpression()->getArithmeticTerms() as $term) {
                $sql .= $this->walkArithmeticTerm($term);
            }
        } else {
            $sql .= $this->walkSubselect($arithmeticExpr->getSubselect());
        }
        
        return $sql;
    }

    /**
     * Walks down an ArithmeticTerm AST node, thereby generating the appropriate SQL.
     *
     * @param mixed
     * @return string The SQL.
     */
    public function walkArithmeticTerm($term)
    {
        if (is_string($term)) return $term;

        return implode(
            ' ', array_map(array($this, 'walkArithmeticFactor'), $term->getArithmeticFactors())
        );
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
     * Walks down an ArithmeticFactor that represents an AST node, thereby generating the appropriate SQL.
     *
     * @param mixed
     * @return string The SQL.
     */
    public function walkArithmeticFactor($factor)
    {
        if (is_string($factor)) return $factor;

        $sql = '';
        $primary = $factor->getArithmeticPrimary();
        
        if (is_numeric($primary)) {
            $sql .= $primary; //TODO: quote() ?
        } else if (is_string($primary)) {
            $sql .= $this->_conn->quote($primary);
        } else if ($primary instanceof AST\SimpleArithmeticExpression) {
            $sql .= '(' . $this->walkSimpleArithmeticExpression($primary) . ')';
        } else if ($primary instanceof AST\Node) {
            $sql .= $primary->dispatch($this);
        }

        return $sql;
    }

    /**
     * Walks down an SimpleArithmeticExpression AST node, thereby generating the appropriate SQL.
     *
     * @param SimpleArithmeticExpression
     * @return string The SQL.
     */
    public function walkSimpleArithmeticExpression($simpleArithmeticExpr)
    {
        return implode(
            ' ', array_map(array($this, 'walkArithmeticTerm'), $simpleArithmeticExpr->getArithmeticTerms())
        );
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
        $pathExprType = $pathExpr->getType();
        
        if ($pathExprType == AST\PathExpression::TYPE_STATE_FIELD) {
            $parts = $pathExpr->getParts();
            $numParts = count($parts);
            $dqlAlias = $pathExpr->getIdentificationVariable();
            $fieldName = $parts[$numParts-1];
            $qComp = $this->_queryComponents[$dqlAlias];
            $class = $qComp['metadata'];

            if ($this->_useSqlTableAliases) {
                if ($class->isInheritanceTypeJoined() && isset($class->fieldMappings[$fieldName]['inherited'])) {
                    $sql .= $this->getSqlTableAlias($this->_em->getClassMetadata(
                            $class->fieldMappings[$fieldName]['inherited'])->getTableName(), $dqlAlias) . '.';
                } else {
                    $sql .= $this->getSqlTableAlias($class->getTableName(), $dqlAlias) . '.';
                }
            }

            if (isset($class->associationMappings[$fieldName])) {
                //FIXME: Inverse side support
                //FIXME: Throw exception on composite key
                $sql .= $class->associationMappings[$fieldName]->joinColumns[0]['name'];
            } else {
                $sql .= $this->_conn->quoteIdentifier($class->getColumnName($fieldName));
            }
        } else if ($pathExprType == AST\PathExpression::TYPE_COLLECTION_VALUED_ASSOCIATION) {
            throw DoctrineException::updateMe("Not yet implemented.");
        } else {
            throw DoctrineException::updateMe("Encountered invalid PathExpression during SQL construction.");
        }
        
        return $sql;
    }

    /**
     * Generates a unique, short SQL table alias.
     *
     * @param string $dqlAlias The DQL alias.
     * @return string Generated table alias.
     */
    public function getSqlTableAlias($tableName, $dqlAlias = '')
    {
        $tableName .= $dqlAlias;
        
        if ( ! isset($this->_dqlToSqlAliasMap[$tableName])) {
            $this->_dqlToSqlAliasMap[$tableName] = strtolower(substr($tableName, 0, 1)) . $this->_tableAliasCounter++ . '_';
        }
        
        return $this->_dqlToSqlAliasMap[$tableName];
    }
    
    /**
     * Forces the SqlWalker to use a specific alias for a table name, rather than
     * generating an alias on its own.
     *
     * @param string $tableName
     * @param string $alias
     */
    public function setSqlTableAlias($tableName, $alias)
    {
        $this->_dqlToSqlAliasMap[$tableName] = $alias;
    }

    /**
     * Gets an SQL column alias for a column name.
     *
     * @param string $columnName
     * @return string
     */
    public function getSqlColumnAlias($columnName)
    {
        return trim($columnName, '`') . $this->_aliasCounter++;
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

        $baseTableAlias = $this->getSqlTableAlias($class->primaryTable['name'], $dqlAlias);
        $idColumns = $class->getIdentifierColumnNames();

        // INNER JOIN parent class tables
        foreach ($class->parentClasses as $parentClassName) {
            $parentClass = $this->_em->getClassMetadata($parentClassName);
            $tableAlias = $this->getSqlTableAlias($parentClass->primaryTable['name'], $dqlAlias);
            $sql .= ' INNER JOIN ' . $parentClass->primaryTable['name'] . ' ' . $tableAlias . ' ON ';
            $first = true;
            
            foreach ($idColumns as $idColumn) {
                if ($first) $first = false; else $sql .= ' AND ';
                
                $sql .= $baseTableAlias . '.' . $idColumn . ' = ' . $tableAlias . '.' . $idColumn;
            }
        }

        // LEFT JOIN subclass tables
        foreach ($class->subClasses as $subClassName) {
            $subClass = $this->_em->getClassMetadata($subClassName);
            $tableAlias = $this->getSqlTableAlias($subClass->primaryTable['name'], $dqlAlias);
            $sql .= ' LEFT JOIN ' . $subClass->primaryTable['name'] . ' ' . $tableAlias . ' ON ';
            $first = true;
            
            foreach ($idColumns as $idColumn) {
                if ($first) $first = false; else $sql .= ' AND ';
                
                $sql .= $baseTableAlias . '.' . $idColumn . ' = ' . $tableAlias . '.' . $idColumn;
            }
        }

        return $sql;
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
                $AST->getDeleteClause()->getAbstractSchemaName()
            );
            
            if ($primaryClass->isInheritanceTypeJoined()) {
                return new Exec\MultiTableDeleteExecutor($AST, $this);
            } else {
                return new Exec\SingleTableDeleteUpdateExecutor($AST, $this);
            }
        } else if ($isUpdateStatement) {
            $primaryClass = $this->_em->getClassMetadata(
                $AST->getUpdateClause()->getAbstractSchemaName()
            );
            
            if ($primaryClass->isInheritanceTypeJoined()) {
                return new Exec\MultiTableUpdateExecutor($AST, $this);
            } else {
                return new Exec\SingleTableDeleteUpdateExecutor($AST, $this);
            }
        } else {
            return new Exec\SingleSelectExecutor($AST, $this);
        }
    }
}
