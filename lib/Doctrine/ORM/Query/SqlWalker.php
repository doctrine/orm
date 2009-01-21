<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SqlWalker
 *
 * @author robo
 */
class Doctrine_ORM_Query_SqlWalker
{
    /**
     * A simple array keys representing table aliases and values table alias
     * seeds. The seeds are used for generating short SQL table aliases.
     *
     * @var array $_tableAliasSeeds
     */
    private $_tableAliasSeeds = array();
    private $_parserResult;
    private $_em;
    private $_dqlToSqlAliasMap = array();
    private $_scalarAliasCounter = 0;

    public function __construct($em, $parserResult)
    {
        $this->_em = $em;
        $this->_parserResult = $parserResult;
        $sqlToDqlAliasMap = array();
        foreach ($parserResult->getQueryComponents() as $dqlAlias => $qComp) {
            if ($dqlAlias != 'dctrn') {
                $sqlAlias = $this->generateTableAlias($qComp['metadata']->getClassName());
                $sqlToDqlAliasMap[$sqlAlias] = $dqlAlias;
            }
        }
        // SQL => DQL alias stored in ParserResult, needed for hydration.
        $parserResult->setTableAliasMap($sqlToDqlAliasMap);
        // DQL => SQL alias stored only locally, needed for SQL construction.
        $this->_dqlToSqlAliasMap = array_flip($sqlToDqlAliasMap);
    }

    public function walkSelectStatement(Doctrine_ORM_Query_AST_SelectStatement $AST)
    {
        $sql = $this->walkSelectClause($AST->getSelectClause());
        $sql .= $this->walkFromClause($AST->getFromClause());
        $sql .= $AST->getWhereClause() ? $this->walkWhereClause($AST->getWhereClause()) : '';
        $sql .= $AST->getGroupByClause() ? $this->walkGroupByClause($AST->getGroupByClause()) : '';

        //... more clauses
        return $sql;
    }

    public function walkSelectClause($selectClause)
    {
        return 'SELECT ' . (($selectClause->isDistinct()) ? 'DISTINCT ' : '')
                . implode(', ', array_map(array(&$this, 'walkSelectExpression'),
                        $selectClause->getSelectExpressions()));
    }

    public function walkFromClause($fromClause)
    {
        $sql = ' FROM ';
        $identificationVarDecls = $fromClause->getIdentificationVariableDeclarations();
        $firstIdentificationVarDecl = $identificationVarDecls[0];
        $rangeDecl = $firstIdentificationVarDecl->getRangeVariableDeclaration();
        $sql .= $rangeDecl->getClassMetadata()->getTableName() . ' '
                . $this->_dqlToSqlAliasMap[$rangeDecl->getAliasIdentificationVariable()];

        foreach ($firstIdentificationVarDecl->getJoinVariableDeclarations() as $joinVarDecl) {
            $sql .= $this->walkJoinVariableDeclaration($joinVarDecl);
        }

        return $sql;
    }

    /**
     * Walks down a JoinVariableDeclaration AST node and creates the corresponding SQL.
     *
     * @param JoinVariableDeclaration $joinVarDecl
     * @return string
     */
    public function walkJoinVariableDeclaration($joinVarDecl)
    {
        $join = $joinVarDecl->getJoin();
        $joinType = $join->getJoinType();
        if ($joinType == Doctrine_ORM_Query_AST_Join::JOIN_TYPE_LEFT ||
                $joinType == Doctrine_ORM_Query_AST_Join::JOIN_TYPE_LEFTOUTER) {
            $sql = ' LEFT JOIN ';
        } else {
            $sql = ' INNER JOIN ';
        }

        $joinAssocPathExpr = $join->getJoinAssociationPathExpression();
        $sourceQComp = $this->_parserResult->getQueryComponent($joinAssocPathExpr->getIdentificationVariable());
        $targetQComp = $this->_parserResult->getQueryComponent($join->getAliasIdentificationVariable());
        $targetTableName = $targetQComp['metadata']->getTableName();
        $targetTableAlias = $this->_dqlToSqlAliasMap[$join->getAliasIdentificationVariable()];
        $sourceTableAlias = $this->_dqlToSqlAliasMap[$joinAssocPathExpr->getIdentificationVariable()];

        $sql .= $targetTableName . ' ' . $targetTableAlias . ' ON ';

        if ( ! $targetQComp['relation']->isOwningSide()) {
            $assoc = $targetQComp['metadata']->getAssociationMapping($targetQComp['relation']->getMappedByFieldName());
        } else {
            $assoc = $targetQComp['relation'];
        }

        if ($targetQComp['relation']->isOneToOne() || $targetQComp['relation']->isOneToMany()) {
            $joinColumns = $assoc->getSourceToTargetKeyColumns();
            $first = true;
            foreach ($joinColumns as $sourceColumn => $targetColumn) {
                if ( ! $first) $sql .= ' AND ';
                if ($targetQComp['relation']->isOwningSide()) {
                    $sql .= "$sourceTableAlias.$sourceColumn = $targetTableAlias.$targetColumn";
                } else {
                    $sql .= "$sourceTableAlias.$targetColumn = $targetTableAlias.$sourceColumn";
                }
            }
        } else { // ManyToMany
            //TODO
        }
        
        return $sql;
    }

    /**
     * Walks down a SelectExpression AST node and generates the corresponding SQL.
     *
     * @param <type> $selectExpression
     * @return string
     */
    public function walkSelectExpression($selectExpression)
    {
        $sql = '';
        if ($selectExpression->getExpression() instanceof Doctrine_ORM_Query_AST_PathExpression) {
            $pathExpression = $selectExpression->getExpression();
            if ($pathExpression->isSimpleStateFieldPathExpression()) {
                $parts = $pathExpression->getParts();
                $numParts = count($parts);
                $dqlAlias = $parts[0];
                $fieldName = $parts[$numParts-1];
                $qComp = $this->_parserResult->getQueryComponent($dqlAlias);
                $class = $qComp['metadata'];

                if ($numParts > 2) {
                    for ($i = 1; $i < $numParts-1; ++$i) {
                        //TODO
                    }
                }

                $sqlTableAlias = $this->_dqlToSqlAliasMap[$dqlAlias];
                $sql .= $sqlTableAlias . '.' . $class->getColumnName($fieldName) .
                        ' AS ' . $sqlTableAlias . '__' . $class->getColumnName($fieldName);
            } else if ($pathExpression->isSimpleStateFieldAssociationPathExpression()) {
                throw new Doctrine_Exception("Not yet implemented.");
            } else {
                throw new Doctrine_ORM_Query_Exception("Encountered invalid PathExpression during SQL construction.");
            }
        }
        else if ($selectExpression->getExpression() instanceof Doctrine_ORM_Query_AST_AggregateExpression) {
            $aggExpr = $selectExpression->getExpression();

            if ( ! $selectExpression->getFieldIdentificationVariable()) {
                $alias = $this->_scalarAliasCounter++;
            } else {
                $alias = $selectExpression->getFieldIdentificationVariable();
            }

            $parts = $aggExpr->getPathExpression()->getParts();
            $dqlAlias = $parts[0];
            $fieldName = $parts[1];

            $qComp = $this->_parserResult->getQueryComponent($dqlAlias);
            $columnName = $qComp['metadata']->getColumnName($fieldName);
            
            $sql .= $aggExpr->getFunctionName() . '(';
            if ($aggExpr->isDistinct()) $sql .= 'DISTINCT ';
            $sql .= $this->_dqlToSqlAliasMap[$dqlAlias] . '.' . $columnName;
            $sql .= ') AS dctrn__' . $alias;
        }
        //TODO: else if Subselect
        else {
            $dqlAlias = $selectExpression->getExpression();
            $queryComp = $this->_parserResult->getQueryComponent($dqlAlias);
            $class = $queryComp['metadata'];

            $sqlTableAlias = $this->_dqlToSqlAliasMap[$dqlAlias];
            $beginning = true;
            foreach ($class->getFieldMappings() as $fieldName => $fieldMapping) {
                if ($beginning) {
                    $beginning = false;
                } else {
                    $sql .= ', ';
                }
                $sql .= $sqlTableAlias . '.' . $fieldMapping['columnName'] .
                        ' AS ' . $sqlTableAlias . '__' . $fieldMapping['columnName'];
            }
        }
        return $sql;
    }

    public function walkGroupByClause($groupByClause)
    {
        return ' GROUP BY ' 
                . implode(', ', array_map(array(&$this, 'walkGroupByItem'),
                $groupByClause->getGroupByItems()));
    }

    public function walkGroupByItem($pathExpr)
    {
        //TODO: support general SingleValuedPathExpression, not just state field
        $parts = $pathExpr->getParts();
        $qComp = $this->_parserResult->getQueryComponent($parts[0]);
        $columnName = $qComp['metadata']->getColumnName($parts[1]);
        return $this->_dqlToSqlAliasMap[$parts[0]] . '.' . $columnName;
    }

    public function walkUpdateStatement(Doctrine_ORM_Query_AST_UpdateStatement $AST)
    {

    }

    public function walkDeleteStatement(Doctrine_ORM_Query_AST_DeleteStatement $AST)
    {

    }

    public function walkWhereClause($whereClause)
    {
        $sql = ' WHERE ';
        $condExpr = $whereClause->getConditionalExpression();
        $sql .= implode(' OR ', array_map(array(&$this, 'walkConditionalTerm'),
                $condExpr->getConditionalTerms()));
        return $sql;
    }

    public function walkConditionalTerm($condTerm)
    {
        $sql = '';
        $sql .= implode(' AND ', array_map(array(&$this, 'walkConditionalFactor'),
                $condTerm->getConditionalFactors()));
        return $sql;
    }

    public function walkConditionalFactor($factor)
    {
        $sql = '';
        if ($factor->isNot()) $sql .= ' NOT ';
        $primary = $factor->getConditionalPrimary();
        if ($primary->isSimpleConditionalExpression()) {
            $simpleCond = $primary->getSimpleConditionalExpression();
            if ($simpleCond instanceof Doctrine_ORM_Query_AST_ComparisonExpression) {
                $sql .= $this->walkComparisonExpression($simpleCond);
            }
            else if ($simpleCond instanceof Doctrine_ORM_Query_AST_LikeExpression) {
                $sql .= $this->walkLikeExpression($simpleCond);
            }
            // else if ...
        } else if ($primary->isConditionalExpression()) {
            $sql .= '(' . implode(' OR ', array_map(array(&$this, 'walkConditionalTerm'),
                    $primary->getConditionalExpression()->getConditionalTerms())) . ')';
        }
        return $sql;
    }

    public function walkLikeExpression($likeExpr)
    {
        $sql = '';
        $stringExpr = $likeExpr->getStringExpression();
        if ($stringExpr instanceof Doctrine_ORM_Query_AST_PathExpression) {
            $sql .= $this->walkPathExpression($stringExpr);
        } //TODO else...
        $sql .= ' LIKE ' . $likeExpr->getStringPattern();
        return $sql;
    }

    public function walkComparisonExpression($compExpr)
    {
        $sql = '';
        if ($compExpr->getLeftExpression() instanceof Doctrine_ORM_Query_AST_ArithmeticExpression) {
            $sql .= $this->walkArithmeticExpression($compExpr->getLeftExpression());
        } // else...
        $sql .= ' ' . $compExpr->getOperator() . ' ';
        if ($compExpr->getRightExpression() instanceof Doctrine_ORM_Query_AST_ArithmeticExpression) {
            $sql .= $this->walkArithmeticExpression($compExpr->getRightExpression());
        }
        return $sql;
    }

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

    public function walkArithmeticTerm($term)
    {
        if (is_string($term)) return $term;
        return implode(' ', array_map(array(&$this, 'walkArithmeticFactor'),
                $term->getArithmeticFactors()));
    }

    public function walkArithmeticFactor($factor)
    {
        if (is_string($factor)) return $factor;
        $sql = '';
        $primary = $factor->getArithmeticPrimary();
        if (is_numeric($primary)) {
            $sql .= $primary;
        } else if ($primary instanceof Doctrine_ORM_Query_AST_PathExpression) {
            $sql .= $this->walkPathExpression($primary);
        } else if ($primary instanceof Doctrine_ORM_Query_AST_InputParameter) {
            if ($primary->isNamed()) {
                $sql .= ':' . $primary->getName();
            } else {
                $sql .= '?';
            }
        } else if ($primary instanceof Doctrine_ORM_Query_AST_SimpleArithmeticExpression) {
            $sql .= '(' . $this->walkSimpleArithmeticExpression($primary) . ')';
        }
         
        // else...

        return $sql;
    }

    public function walkSimpleArithmeticExpression($simpleArithmeticExpr)
    {
        return implode(' ', array_map(array(&$this, 'walkArithmeticTerm'),
                $simpleArithmeticExpr->getArithmeticTerms()));
    }

    public function walkPathExpression($pathExpr)
    {
        $sql = '';
        if ($pathExpr->isSimpleStateFieldPathExpression()) {
            $parts = $pathExpr->getParts();
            $numParts = count($parts);
            $dqlAlias = $parts[0];
            $fieldName = $parts[$numParts-1];
            $qComp = $this->_parserResult->getQueryComponent($dqlAlias);
            $class = $qComp['metadata'];

            if ($numParts > 2) {
                for ($i = 1; $i < $numParts-1; ++$i) {
                    //TODO
                }
            }

            $sqlTableAlias = $this->_dqlToSqlAliasMap[$dqlAlias];
            $sql .= $sqlTableAlias . '.' . $class->getColumnName($fieldName);
        } else if ($pathExpr->isSimpleStateFieldAssociationPathExpression()) {
            throw new Doctrine_Exception("Not yet implemented.");
        } else {
            throw new Doctrine_ORM_Query_Exception("Encountered invalid PathExpression during SQL construction.");
        }
        return $sql;
    }

    /**
     * Generates an SQL table alias from given table name and associates
     * it with given component alias
     *
     * @param string $componentName Component name to be associated with generated table alias
     * @return string               Generated table alias
     */
    public function generateTableAlias($componentName)
    {
        $baseAlias = strtolower(preg_replace('/[^A-Z]/', '\\1', $componentName));

        // We may have a situation where we have all chars are lowercased
        if ($baseAlias == '') {
            // We simply grab the first 2 chars of component name
            $baseAlias = substr($componentNam, 0, 2);
        }

        $alias = $baseAlias;

        if ( ! isset($this->_tableAliasSeeds[$baseAlias])) {
            $this->_tableAliasSeeds[$baseAlias] = 1;
        } else {
            $alias .= $this->_tableAliasSeeds[$baseAlias]++;
        }

        return $alias;
    }
}

