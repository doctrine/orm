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
    Doctrine\ORM\Query\QueryException,
    Doctrine\Common\DoctrineException;

/**
 * The SqlWalker is a TreeWalker that walks over a DQL AST and constructs
 * the corresponding SQL.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
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
    
    /**
     * @var AbstractQuery
     */
    private $_query;

    private $_dqlToSqlAliasMap = array();
    
    /** Map from result variable names to their SQL column alias names. */
    private $_scalarResultAliasMap = array();
    
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
     * The database platform abstraction.
     * 
     * @var AbstractPlatform
     */
    private $_platform;

    /**
     * @inheritdoc
     */
    public function __construct($query, $parserResult, array $queryComponents)
    {
        $this->_rsm = $parserResult->getResultSetMapping();
        $this->_query = $query;
        $this->_em = $query->getEntityManager();
        $this->_conn = $this->_em->getConnection();
        $this->_platform = $this->_conn->getDatabasePlatform();
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
        
        return $alias;
    }

    /**
     * Gets an SQL column alias for a column name.
     *
     * @param string $columnName
     * @return string
     */
    public function getSqlColumnAlias($columnName)
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

        $baseTableAlias = $this->getSqlTableAlias($class->primaryTable['name'], $dqlAlias);

        // INNER JOIN parent class tables
        foreach ($class->parentClasses as $parentClassName) {
            $parentClass = $this->_em->getClassMetadata($parentClassName);
            $tableAlias = $this->getSqlTableAlias($parentClass->primaryTable['name'], $dqlAlias);
            $sql .= ' INNER JOIN ' . $parentClass->getQuotedTableName($this->_platform) 
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

        // LEFT JOIN subclass tables, if partial objects disallowed
        if ( ! $this->_query->getHint(Query::HINT_FORCE_PARTIAL_LOAD)) {
            foreach ($class->subClasses as $subClassName) {
                $subClass = $this->_em->getClassMetadata($subClassName);
                $tableAlias = $this->getSqlTableAlias($subClass->primaryTable['name'], $dqlAlias);
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
                
                $sql .= (($this->_useSqlTableAliases) 
                    ? $this->getSqlTableAlias($class->primaryTable['name'], $dqlAlias) . '.' : ''
                ) . $class->getQuotedDiscriminatorColumnName($this->_platform)  
                . ' IN (' . implode(', ', $values) . ')';
            }
        }
        
        return $sql;
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
        } else if (($discSql = $this->_generateDiscriminatorColumnConditionSql($this->_currentRootAlias)) !== '') {
            $sql .= ' WHERE ' . $discSql;
        }

        $sql .= $AST->groupByClause ? $this->walkGroupByClause($AST->groupByClause) : '';
        $sql .= $AST->havingClause ? $this->walkHavingClause($AST->havingClause) : '';
        $sql .= $AST->orderByClause ? $this->walkOrderByClause($AST->orderByClause) : '';

        $q = $this->getQuery();
        $sql = $this->_platform->modifyLimitQuery(
            $sql, $q->getMaxResults(), $q->getFirstResult()
        );

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
        } else if (($discSql = $this->_generateDiscriminatorColumnConditionSql($this->_currentRootAlias)) !== '') {
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
        } else if (($discSql = $this->_generateDiscriminatorColumnConditionSql($this->_currentRootAlias)) !== '') {
            $sql .= ' WHERE ' . $discSql;
        }
        
        return $sql;
    }
    
    
    /**
     * Walks down an IdentificationVariable (no AST node associated), thereby generating the SQL.
     *
     * @param string $identificationVariable
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
 
        return $this->getSqlTableAlias($class->primaryTable['name'], $identificationVariable);
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
        $pathExprType = $pathExpr->type;
        
        switch ($pathExpr->type) {
            case AST\PathExpression::TYPE_STATE_FIELD:
                $parts = $pathExpr->parts;
                $numParts = count($parts);
                $dqlAlias = $pathExpr->identificationVariable;
                $fieldName = $parts[$numParts - 1];
                $qComp = $this->_queryComponents[$dqlAlias];
                $class = $qComp['metadata'];

                if ($this->_useSqlTableAliases) {
                    $sql .= $this->walkIdentificationVariable($dqlAlias, $fieldName) . '.';
                }

                $sql .= $class->getQuotedColumnName($fieldName, $this->_platform);      
                break;
            
            case AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION:
                // "u.Group" should be converted to:
                // 1- IdentificationVariable is the owning side:
                //    Just append the condition: u.group_id = ?
                /*$parts = $pathExpr->parts;
                $numParts = count($parts);
                $dqlAlias = $pathExpr->identificationVariable;
                $fieldName = $parts[$numParts - 1];
                $qComp = $this->_queryComponents[$dqlAlias];
                $class = $qComp['metadata'];
                $assoc = $class->associationMappings[$fieldName];
                
                if ($assoc->isOwningSide) {
                    foreach ($assoc->)
                    $sql .= $this->walkIdentificationVariable($dqlAlias, $fieldName) . '.';
                    
                }
                
                // 2- IdentificationVariable is the inverse side:
                //    Join required: INNER JOIN u.Group g
                //    Append condition: g.id = ?
                break;*/
                
            case AST\PathExpression::TYPE_COLLECTION_VALUED_ASSOCIATION:
                throw DoctrineException::notImplemented();
        
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
                    $this->_queryComponents[$dqlAlias]['relation']->sourceFieldName
                );
            }
            
            if ($class->isInheritanceTypeSingleTable() || $class->isInheritanceTypeJoined()) {
                // Add discriminator columns to SQL
                $rootClass = $this->_em->getClassMetadata($class->rootEntityName);
                $tblAlias = $this->getSqlTableAlias($rootClass->primaryTable['name'], $dqlAlias);
                $discrColumn = $rootClass->discriminatorColumn;
                $columnAlias = $this->getSqlColumnAlias($discrColumn['name']);
                $sql .= ", $tblAlias." . $rootClass->getQuotedDiscriminatorColumnName($this->_platform)
                      . ' AS ' . $columnAlias;
                
                $columnAlias = $this->_platform->getSqlResultCasing($columnAlias);
                $this->_rsm->setDiscriminatorColumn($dqlAlias, $columnAlias);
                $this->_rsm->addMetaResult($dqlAlias, $this->_platform->getSqlResultCasing($columnAlias), $discrColumn['fieldName']);
                
                // Add foreign key columns to SQL, if necessary
                if ($addMetaColumns) {
                    //FIXME: Include foreign key columns of child classes also!!??
                    foreach ($class->associationMappings as $assoc) {
                        if ($assoc->isOwningSide && $assoc->isOneToOne()) {
                            if (isset($class->inheritedAssociationFields[$assoc->sourceFieldName])) {
                                $owningClass = $this->_em->getClassMetadata($class->inheritedAssociationFields[$assoc->sourceFieldName]);
                                $sqlTableAlias = $this->getSqlTableAlias($owningClass->primaryTable['name'], $dqlAlias);
                            } else {
                                $sqlTableAlias = $this->getSqlTableAlias($class->primaryTable['name'], $dqlAlias);
                            }
                            
                            foreach ($assoc->targetToSourceKeyColumns as $srcColumn) {
                                $columnAlias = $this->getSqlColumnAlias($srcColumn);
                                $sql .= ", $sqlTableAlias." . $assoc->getQuotedJoinColumnName($srcColumn, $this->_platform) 
                                      . ' AS ' . $columnAlias;
                                $columnAlias = $this->_platform->getSqlResultCasing($columnAlias);
                                $this->_rsm->addMetaResult($dqlAlias, $this->_platform->getSqlResultCasing($columnAlias), $srcColumn);
                            }
                        }
                    }
                }
            } else {
                // Add foreign key columns to SQL, if necessary            
                if ($addMetaColumns) {
                    $sqlTableAlias = $this->getSqlTableAlias($class->primaryTable['name'], $dqlAlias);
                    foreach ($class->associationMappings as $assoc) {
                        if ($assoc->isOwningSide && $assoc->isOneToOne()) {
                            foreach ($assoc->targetToSourceKeyColumns as $srcColumn) {
                                $columnAlias = $this->getSqlColumnAlias($srcColumn);
                                $sql .= ', ' . $sqlTableAlias . '.' . $assoc->getQuotedJoinColumnName($srcColumn, $this->_platform) . ' AS ' . $columnAlias;
                                $columnAlias = $this->_platform->getSqlResultCasing($columnAlias);
                                $this->_rsm->addMetaResult($dqlAlias, $this->_platform->getSqlResultCasing($columnAlias), $srcColumn);
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
        $sql = ' FROM ';
        $identificationVarDecls = $fromClause->identificationVariableDeclarations;
        $firstIdentificationVarDecl = $identificationVarDecls[0];
        $rangeDecl = $firstIdentificationVarDecl->rangeVariableDeclaration;
        $dqlAlias = $rangeDecl->aliasIdentificationVariable;

        $this->_currentRootAlias = $dqlAlias;
        
        $class = $this->_em->getClassMetadata($rangeDecl->abstractSchemaName);
        $sql .= $class->getQuotedTableName($this->_platform) . ' ' 
              . $this->getSqlTableAlias($class->primaryTable['name'], $dqlAlias);

        if ($class->isInheritanceTypeJoined()) {
            $sql .= $this->_generateClassTableInheritanceJoins($class, $dqlAlias);
        }

        foreach ($firstIdentificationVarDecl->joinVariableDeclarations as $joinVarDecl) {
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
            ', ', array_map(array($this, 'walkOrderByItem'), $orderByClause->orderByItems)
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
        $expr = $orderByItem->expression;
        if ($expr instanceof AST\PathExpression) {
            $parts = $expr->parts;
            $dqlAlias = $expr->identificationVariable;
            $class = $this->_queryComponents[$dqlAlias]['metadata'];
            $columnName = $class->getQuotedColumnName($parts[0], $this->_platform);
            
            return $this->getSqlTableAlias($class->getTableName(), $dqlAlias) . '.' 
                    . $columnName . ' ' . strtoupper($orderByItem->type);
        } else {
            $columnName = $this->_queryComponents[$expr]['token']['value'];

            return $this->_scalarResultAliasMap[$columnName] . ' ' . strtoupper($orderByItem->type);
        }
    }

    /**
     * Walks down a HavingClause AST node, thereby generating the appropriate SQL.
     *
     * @param HavingClause
     * @return string The SQL.
     */
    public function walkHavingClause($havingClause)
    {
        $condExpr = $havingClause->conditionalExpression;
        
        return ' HAVING ' . implode(
            ' OR ', array_map(array($this, 'walkConditionalTerm'), $condExpr->conditionalTerms)
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
        $join = $joinVarDecl->join;
        $joinType = $join->joinType;
        
        if ($joinType == AST\Join::JOIN_TYPE_LEFT || $joinType == AST\Join::JOIN_TYPE_LEFTOUTER) {
            $sql = ' LEFT JOIN ';
        } else {
            $sql = ' INNER JOIN ';
        }

        $joinAssocPathExpr = $join->joinAssociationPathExpression;
        $joinedDqlAlias = $join->aliasIdentificationVariable;
        $targetQComp = $this->_queryComponents[$joinedDqlAlias];
        $targetClass = $targetQComp['metadata'];
        $relation = $targetQComp['relation'];
        $sourceClass = $this->_queryComponents[$joinAssocPathExpr->identificationVariable]['metadata'];
        
        $targetTableName = $targetClass->getQuotedTableName($this->_platform);
        $targetTableAlias = $this->getSqlTableAlias($targetClass->getTableName(), $joinedDqlAlias);
        $sourceTableAlias = $this->getSqlTableAlias(
            $sourceClass->getTableName(), $joinAssocPathExpr->identificationVariable
        );

        // Ensure we got the owning side, since it has all mapping info
        if ( ! $relation->isOwningSide) {
            $assoc = $targetClass->associationMappings[$relation->mappedByFieldName];
        } else {
            $assoc = $relation;
        }

        if ($this->_query->getHint(Query::HINT_INTERNAL_ITERATION) == true) {
            if ($relation->isOneToMany() || $relation->isManyToMany()) {
                throw QueryException::iterateWithFetchJoinNotAllowed($assoc);
            }
        }

        if ($assoc->isOneToOne()) {
            $sql .= $targetTableName . ' ' . $targetTableAlias . ' ON ';
            $first = true;

            foreach ($assoc->sourceToTargetKeyColumns as $sourceColumn => $targetColumn) {
                if ( ! $first) $sql .= ' AND '; else $first = false;
                
                $quotedSourceColumn = $assoc->getQuotedJoinColumnName($sourceColumn, $this->_platform);
                
                if ($relation->isOwningSide) {
                    $quotedTargetColumn = $targetClass->getQuotedColumnName($targetClass->fieldNames[$targetColumn], $this->_platform);
                    $sql .= $sourceTableAlias . '.' . $quotedSourceColumn
                          . ' = ' 
                          . $targetTableAlias . '.' . $quotedTargetColumn;
                } else {
                    $quotedTargetColumn = $sourceClass->getQuotedColumnName($sourceClass->fieldNames[$targetColumn], $this->_platform);
                    $sql .= $sourceTableAlias . '.' . $quotedTargetColumn
                          . ' = ' 
                          . $targetTableAlias . '.' . $quotedSourceColumn;
                }
            }
        } else if ($assoc->isManyToMany()) {
            // Join relation table
            $joinTable = $assoc->getJoinTable();
            $joinTableAlias = $this->getSqlTableAlias($joinTable['name']);
            $sql .= $assoc->getQuotedJoinTableName($this->_platform) . ' ' . $joinTableAlias . ' ON ';
            
            if ($relation->isOwningSide) {
                foreach ($assoc->relationToSourceKeyColumns as $relationColumn => $sourceColumn) {
                    $sql .= $sourceTableAlias . '.' . $sourceClass->getQuotedColumnName($sourceClass->fieldNames[$sourceColumn], $this->_platform)
                          . ' = '
                          . $joinTableAlias . '.' . $assoc->getQuotedJoinColumnName($relationColumn, $this->_platform);
                }
            } else {
                foreach ($assoc->relationToTargetKeyColumns as $relationColumn => $targetColumn) {
                    $sql .= $sourceTableAlias . '.' . $targetClass->getQuotedColumnName($targetClass->fieldNames[$targetColumn], $this->_platform)
                          . ' = '
                          . $joinTableAlias . '.' . $assoc->getQuotedJoinColumnName($relationColumn, $this->_platform);
                }
            }

            // Join target table
            $sql .= ($joinType == AST\Join::JOIN_TYPE_LEFT || $joinType == AST\Join::JOIN_TYPE_LEFTOUTER)
            	? ' LEFT JOIN ' : ' INNER JOIN ';
            $sql .= $targetTableName . ' ' . $targetTableAlias . ' ON ';

            if ($relation->isOwningSide) {
                foreach ($assoc->relationToTargetKeyColumns as $relationColumn => $targetColumn) {
                    $sql .= $targetTableAlias . '.' . $targetClass->getQuotedColumnName($targetClass->fieldNames[$targetColumn], $this->_platform)
                          . ' = '
                          . $joinTableAlias . '.' . $assoc->getQuotedJoinColumnName($relationColumn, $this->_platform);
                }
            } else {
                foreach ($assoc->relationToSourceKeyColumns as $relationColumn => $sourceColumn) {
                    $sql .= $targetTableAlias . '.' . $sourceClass->getQuotedColumnName($sourceClass->fieldNames[$sourceColumn], $this->_platform)
                          . ' = '
                          . $joinTableAlias . '.' . $assoc->getQuotedJoinColumnName($relationColumn, $this->_platform);
                }
            }
        }

        $discrSql = $this->_generateDiscriminatorColumnConditionSql($joinedDqlAlias);
        
        if ($discrSql) {
            $sql .= ' AND ' . $discrSql;
        }

        if ($targetClass->isInheritanceTypeJoined()) {
            $sql .= $this->_generateClassTableInheritanceJoins($targetClass, $joinedDqlAlias);
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
        $expr = $selectExpression->expression;
        
        if ($expr instanceof AST\PathExpression) {
            if ($expr->type == AST\PathExpression::TYPE_STATE_FIELD) {
                $parts = $expr->parts;
                $numParts = count($parts);
                $dqlAlias = $expr->identificationVariable;
                $fieldName = $parts[$numParts - 1];
                $qComp = $this->_queryComponents[$dqlAlias];
                $class = $qComp['metadata'];

                if ( ! isset($this->_selectedClasses[$dqlAlias])) {
                    $this->_selectedClasses[$dqlAlias] = $class;
                }

                $sqlTableAlias = $this->getSqlTableAlias($class->getTableName(), $dqlAlias);
                $columnName = $class->getQuotedColumnName($fieldName, $this->_platform);
                $columnAlias = $this->getSqlColumnAlias($class->columnNames[$fieldName]);
                $sql .= $sqlTableAlias . '.' . $columnName . ' AS ' . $columnAlias;
                
                $columnAlias = $this->_platform->getSqlResultCasing($columnAlias);
                $this->_rsm->addFieldResult($dqlAlias, $columnAlias, $fieldName, $class->name);                
            } else {
                throw DoctrineException::invalidPathExpression($expr->type);
            }
        } else if ($expr instanceof AST\AggregateExpression) {
            if ( ! $selectExpression->fieldIdentificationVariable) {
                $resultAlias = $this->_scalarResultCounter++;
            } else {
                $resultAlias = $selectExpression->fieldIdentificationVariable;
            }
            
            $columnAlias = 'sclr' . $this->_aliasCounter++;
            $sql .= $this->walkAggregateExpression($expr) . ' AS ' . $columnAlias;
            $this->_scalarResultAliasMap[$resultAlias] = $columnAlias;
            
            $columnAlias = $this->_platform->getSqlResultCasing($columnAlias);
            $this->_rsm->addScalarResult($columnAlias, $resultAlias);
        } else if ($expr instanceof AST\Subselect) {
            $sql .= $this->walkSubselect($expr);
        } else if ($expr instanceof AST\Functions\FunctionNode) {
            if ( ! $selectExpression->fieldIdentificationVariable) {
                $resultAlias = $this->_scalarResultCounter++;
            } else {
                $resultAlias = $selectExpression->fieldIdentificationVariable;
            }

            $columnAlias = 'sclr' . $this->_aliasCounter++;
            $sql .= $this->walkFunction($expr) . ' AS ' . $columnAlias;
            $this->_scalarResultAliasMap[$resultAlias] = $columnAlias;
            
            $columnAlias = $this->_platform->getSqlResultCasing($columnAlias);
            $this->_rsm->addScalarResult($columnAlias, $resultAlias);
        } else {
            // $expr == IdentificationVariable
            $dqlAlias = $expr;
            $queryComp = $this->_queryComponents[$dqlAlias];
            $class = $queryComp['metadata'];

            if ( ! isset($this->_selectedClasses[$dqlAlias])) {
                $this->_selectedClasses[$dqlAlias] = $class;
            }

            $beginning = true;
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
                $sql .= $sqlTableAlias . '.' . $class->getQuotedColumnName($fieldName, $this->_platform)
                      . ' AS ' . $columnAlias;
                
                $columnAlias = $this->_platform->getSqlResultCasing($columnAlias);
                $this->_rsm->addFieldResult($dqlAlias, $columnAlias, $fieldName, $class->name);
            }

            // Add any additional fields of subclasses (excluding inherited fields)
            // 1) on Single Table Inheritance: always, since its marginal overhead
            // 2) on Class Table Inheritance only if partial objects are disallowed,
            //    since it requires outer joining subtables.
            if ($class->isInheritanceTypeSingleTable() || ! $this->_query->getHint(Query::HINT_FORCE_PARTIAL_LOAD)) {
                foreach ($class->subClasses as $subClassName) {
                    $subClass = $this->_em->getClassMetadata($subClassName);
                    $sqlTableAlias = $this->getSqlTableAlias($subClass->primaryTable['name'], $dqlAlias);
                    foreach ($subClass->fieldMappings as $fieldName => $mapping) {
                        if (isset($mapping['inherited'])) {
                            continue;
                        }

                        if ($beginning) $beginning = false; else $sql .= ', ';

                        $columnAlias = $this->getSqlColumnAlias($mapping['columnName']);
                        $sql .= $sqlTableAlias . '.' . $subClass->getQuotedColumnName($fieldName, $this->_platform)
                                . ' AS ' . $columnAlias;

                        $columnAlias = $this->_platform->getSqlResultCasing($columnAlias);
                        $this->_rsm->addFieldResult($dqlAlias, $columnAlias, $fieldName, $subClassName);
                    }
                    
                    // Add join columns (foreign keys) of the subclass
                    //TODO: Probably better do this in walkSelectClause to honor the INCLUDE_META_COLUMNS hint
                    foreach ($subClass->associationMappings as $fieldName => $assoc) {
                        if ($assoc->isOwningSide && $assoc->isOneToOne() && ! isset($subClass->inheritedAssociationFields[$fieldName])) {
                            foreach ($assoc->targetToSourceKeyColumns as $srcColumn) {
                                if ($beginning) $beginning = false; else $sql .= ', ';
                                $columnAlias = $this->getSqlColumnAlias($srcColumn);
                                $sql .= $sqlTableAlias . '.' . $assoc->getQuotedJoinColumnName($srcColumn, $this->_platform)
                                        . ' AS ' . $columnAlias;
                                $this->_rsm->addMetaResult($dqlAlias, $this->_platform->getSqlResultCasing($columnAlias), $srcColumn);
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
        $firstIdentificationVarDecl = $identificationVarDecls[0];
        $rangeDecl = $firstIdentificationVarDecl->rangeVariableDeclaration;
        $class = $this->_em->getClassMetadata($rangeDecl->abstractSchemaName);
        $dqlAlias = $rangeDecl->aliasIdentificationVariable;
        
        $sql = ' FROM ' . $class->getQuotedTableName($this->_platform) . ' '
             . $this->getSqlTableAlias($class->primaryTable['name'], $dqlAlias);

        foreach ($firstIdentificationVarDecl->joinVariableDeclarations as $joinVarDecl) {
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
            $sql .= ' ' . $this->walkPathExpression($expr);
        } else if ($expr instanceof AST\AggregateExpression) {
            if ( ! $simpleSelectExpression->fieldIdentificationVariable) {
                $alias = $this->_scalarAliasCounter++;
            } else {
                $alias = $simpleSelectExpression->fieldIdentificationVariable;
            }
            
            $sql .= $this->walkAggregateExpression($expr) . ' AS dctrn__' . $alias;
        } else {
            // IdentificationVariable
            // FIXME: Composite key support, or select all columns? Does that make sense
            //        in a subquery?
            $class = $this->_queryComponents[$expr]['metadata'];
            $sql .= ' ' . $this->getSqlTableAlias($class->getTableName(), $expr) . '.'
                  . $class->getQuotedColumnName($class->identifier[0], $this->_platform);
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
        $parts = $aggExpression->pathExpression->parts;
        $dqlAlias = $aggExpression->pathExpression->identificationVariable;
        $fieldName = $parts[0];

        $qComp = $this->_queryComponents[$dqlAlias];
        $columnName = $qComp['metadata']->getQuotedColumnName($fieldName, $this->_platform);

        return $aggExpression->functionName . '(' . ($aggExpression->isDistinct ? 'DISTINCT ' : '')
             . $this->getSqlTableAlias($qComp['metadata']->getTableName(), $dqlAlias) . '.' 
             . $columnName . ')';
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
            ', ', array_map(array($this, 'walkGroupByItem'), $groupByClause->groupByItems)
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
        $parts = $pathExpr->parts;
        $dqlAlias = $pathExpr->identificationVariable;
        $qComp = $this->_queryComponents[$dqlAlias];
        $columnName = $qComp['metadata']->getQuotedColumnName($parts[0], $this->_platform);
        
        return $this->getSqlTableAlias($qComp['metadata']->getTableName(), $dqlAlias) . '.' . $columnName;
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
        
        if ($this->_useSqlTableAliases) {
            $sql .= ' ' . $this->getSqlTableAlias($class->getTableName());
        }
        
        $this->_currentRootAlias = $deleteClause->aliasIdentificationVariable;

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
        
        if ($this->_useSqlTableAliases) {
            $sql .= ' ' . $this->getSqlTableAlias($class->getTableName());
        }
        
        $this->_currentRootAlias = $updateClause->aliasIdentificationVariable;

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
        
        $sql = '';
        $dqlAlias = $updateItem->identificationVariable;
        $qComp = $this->_queryComponents[$dqlAlias];

        if ($this->_useSqlTableAliases) {
            $sql .= $this->getSqlTableAlias($qComp['metadata']->getTableName()) . '.';
        }
        
        $sql .= $qComp['metadata']->getQuotedColumnName($updateItem->field, $this->_platform) . ' = ';

        $newValue = $updateItem->newValue;

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
        $condExpr = $whereClause->conditionalExpression;
        
        $sql .= implode(
            ' OR ', array_map(array($this, 'walkConditionalTerm'), $condExpr->conditionalTerms)
        );

        $discrSql = $this->_generateDiscriminatorColumnConditionSql($this->_currentRootAlias);
        
        if ($discrSql) {
            $sql .= ' AND ' . $discrSql;
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
        $sql = ($factor->not) ? 'NOT ' : '';
        
        $primary = $factor->conditionalPrimary;
        
        if ($primary->isSimpleConditionalExpression()) {
            $sql .= $primary->simpleConditionalExpression->dispatch($this);
        } else if ($primary->isConditionalExpression()) {
            $condExpr = $primary->conditionalExpression;
            
            $sql .= '(' . implode(
                ' OR ', array_map(array($this, 'walkConditionalTerm'), $condExpr->conditionalTerms)
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
        $parts = $collPathExpr->parts;
        $dqlAlias = $collPathExpr->identificationVariable;
        
        $class = $this->_queryComponents[$dqlAlias]['metadata'];
        
        if ($entityExpr instanceof AST\InputParameter) {
            $dqlParamKey = $entityExpr->name;
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
            
            $sql .= $targetClass->getQuotedTableName($this->_platform)
                  . ' ' . $targetTableAlias . ' WHERE ';
                    
            $owningAssoc = $targetClass->associationMappings[$assoc->mappedByFieldName];
            
            $first = true;
            
            foreach ($owningAssoc->targetToSourceKeyColumns as $targetColumn => $sourceColumn) {
                if ($first) $first = false; else $sql .= ' AND ';
                
                $sql .= $sourceTableAlias . '.' . $class->getQuotedColumnName($class->fieldNames[$targetColumn], $this->_platform) 
                      . ' = ' 
                      . $targetTableAlias . '.' . $owningAssoc->getQuotedJoinColumnName($sourceColumn, $this->_platform);
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
            $targetClass = $this->_em->getClassMetadata($assoc->targetEntityName);
            
            $owningAssoc = $assoc->isOwningSide ? $assoc : $targetClass->associationMappings[$assoc->mappedByFieldName];
            $joinTable = $assoc->isOwningSide ? $assoc->joinTable : $owningAssoc->joinTable;
            
            // SQL table aliases
            $joinTableAlias = $this->getSqlTableAlias($joinTable['name']);
            $targetTableAlias = $this->getSqlTableAlias($targetClass->primaryTable['name']);
            $sourceTableAlias = $this->getSqlTableAlias($class->primaryTable['name'], $dqlAlias);
            
            // join to target table
            $sql .= $assoc->getQuotedJoinTableName($this->_platform)
                  . ' ' . $joinTableAlias . ' INNER JOIN '
                  . $targetClass->getQuotedTableName($this->_platform)
                  . ' ' . $targetTableAlias . ' ON ';
            
            // join conditions
            $joinColumns = $assoc->isOwningSide 
                ? $joinTable['joinColumns'] 
                : $joinTable['inverseJoinColumns'];
            
            $referencedColumnClass = $assoc->isOwningSide ? $class : $targetClass; 
            $first = true;
            foreach ($joinColumns as $joinColumn) {
                if ($first) $first = false; else $sql .= ' AND ';

                $sql .= $joinTableAlias . '.' . $owningAssoc->getQuotedJoinColumnName(
                                $joinColumn['name'], $this->_platform)
                        . ' = '
                        . $sourceTableAlias . '.' . $referencedColumnClass->getQuotedColumnName(
                                $referencedColumnClass->fieldNames[$joinColumn['referencedColumnName']],
                                $this->_platform);
            }
            
            $sql .= ' WHERE ';
            
            $joinColumns = $assoc->isOwningSide 
                ? $joinTable['inverseJoinColumns'] 
                : $joinTable['joinColumns'];
            
            $first = true;
            foreach ($joinColumns as $joinColumn) {
                if ($first) $first = false; else $sql .= ' AND ';
                
                $sql .= $joinTableAlias . '.' . $owningAssoc->getQuotedJoinColumnName($joinColumn['name'], $this->_platform) 
                      . ' = ' 
                      . $targetTableAlias . '.' . $referencedColumnClass->getQuotedColumnName(
                              $referencedColumnClass->fieldNames[$joinColumn['referencedColumnName']],
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
        	: $this->walkSubselect($arithmeticExpr->subselect);
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
            ' ', array_map(array($this, 'walkArithmeticFactor'), $term->arithmeticFactors)
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

        $sql = ($factor->isNegativeSigned() ? '-' : ($factor->isPositiveSigned() ? '+' : ''));
        $primary = $factor->arithmeticPrimary;
        
        if ($primary instanceof AST\SimpleArithmeticExpression) {
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
            ' ', array_map(array($this, 'walkArithmeticTerm'), $simpleArithmeticExpr->arithmeticTerms)
        );
    }
}
