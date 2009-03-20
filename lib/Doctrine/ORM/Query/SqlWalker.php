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

use Doctrine\ORM\Query\AST;
use Doctrine\Common\DoctrineException;

/**
 * The SqlWalker walks over an AST that represents a DQL query and constructs
 * the corresponding SQL. The walking can start at any node, not only at some root
 * node. Therefore it is possible to only generate SQL parts by simply walking over
 * certain subtrees of the AST.
 *
 * @author robo
 * @since 2.0
 */
class SqlWalker
{
    private $_tableAliasCounter = 0;
    private $_parserResult;
    private $_em;
    private $_dqlToSqlAliasMap = array();
    private $_scalarAliasCounter = 0;

    /**
     * Initializes a new SqlWalker instance.
     */
    public function __construct($em, $parserResult)
    {
        $this->_em = $em;
        $this->_parserResult = $parserResult;
        $sqlToDqlAliasMap = array();
        foreach ($parserResult->getQueryComponents() as $dqlAlias => $qComp) {
            if ($dqlAlias != 'dctrn') {
                $sqlAlias = $this->generateSqlTableAlias($qComp['metadata']->getTableName());
                $sqlToDqlAliasMap[$sqlAlias] = $dqlAlias;
            }
        }
        // SQL => DQL alias stored in ParserResult, needed for hydration.
        $parserResult->setTableAliasMap($sqlToDqlAliasMap);
        // DQL => SQL alias stored only locally, needed for SQL construction.
        $this->_dqlToSqlAliasMap = array_flip($sqlToDqlAliasMap);
    }

    public function walkSelectStatement(AST\SelectStatement $AST)
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
        if ($joinType == AST\Join::JOIN_TYPE_LEFT ||
                $joinType == AST\Join::JOIN_TYPE_LEFTOUTER) {
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
        if ($selectExpression->getExpression() instanceof AST\PathExpression) {
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
                \Doctrine\Common\DoctrineException::updateMe("Not yet implemented.");
            } else {
                \Doctrine\Common\DoctrineException::updateMe("Encountered invalid PathExpression during SQL construction.");
            }
        }
        else if ($selectExpression->getExpression() instanceof AST\AggregateExpression) {
            $aggExpr = $selectExpression->getExpression();
            if ( ! $selectExpression->getFieldIdentificationVariable()) {
                $alias = $this->_scalarAliasCounter++;
            } else {
                $alias = $selectExpression->getFieldIdentificationVariable();
            }
            $sql .= $this->walkAggregateExpression($aggExpr) . ' AS dctrn__' . $alias;
        }
        else if ($selectExpression->getExpression() instanceof AST\Subselect) {
            $sql .= $this->walkSubselect($selectExpression->getExpression());
        } else {
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

    public function walkSubselect($subselect)
    {
        $sql = $this->walkSimpleSelectClause($subselect->getSimpleSelectClause());
        $sql .= $this->walkSubselectFromClause($subselect->getSubselectFromClause());
        $sql .= $subselect->getWhereClause() ? $this->walkWhereClause($subselect->getWhereClause()) : '';
        $sql .= $subselect->getGroupByClause() ? $this->walkGroupByClause($subselect->getGroupByClause()) : '';

        //... more clauses
        return $sql;
    }

    public function walkSubselectFromClause($subselectFromClause)
    {
        $sql = ' FROM ';
        $identificationVarDecls = $subselectFromClause->getSubselectIdentificationVariableDeclarations();
        $firstIdentificationVarDecl = $identificationVarDecls[0];
        $rangeDecl = $firstIdentificationVarDecl->getRangeVariableDeclaration();
        $sql .= $rangeDecl->getClassMetadata()->getTableName() . ' '
                . $this->_dqlToSqlAliasMap[$rangeDecl->getAliasIdentificationVariable()];

        foreach ($firstIdentificationVarDecl->getJoinVariableDeclarations() as $joinVarDecl) {
            $sql .= $this->walkJoinVariableDeclaration($joinVarDecl);
        }

        return $sql;
    }

    public function walkSimpleSelectClause($simpleSelectClause)
    {
        $sql = 'SELECT';
        if ($simpleSelectClause->isDistinct()) {
            $sql .= ' DISTINCT';
        }
        $sql .= $this->walkSimpleSelectExpression($simpleSelectClause->getSimpleSelectExpression());
        return $sql;
    }

    public function walkSimpleSelectExpression($simpleSelectExpression)
    {
        $sql = '';
        $expr = $simpleSelectExpression->getExpression();
        if ($expr instanceof AST\PathExpression) {
            //...
        } else if ($expr instanceof AST\AggregateExpression) {
            if ( ! $simpleSelectExpression->getFieldIdentificationVariable()) {
                $alias = $this->_scalarAliasCounter++;
            } else {
                $alias = $simpleSelectExpression->getFieldIdentificationVariable();
            }
            $sql .= $this->walkAggregateExpression($expr) . ' AS dctrn__' . $alias;
        } else {
            // $expr is IdentificationVariable
            //...
        }
        return $sql;
    }

    public function walkAggregateExpression($aggExpression)
    {
        $sql = '';
        $parts = $aggExpression->getPathExpression()->getParts();
        $dqlAlias = $parts[0];
        $fieldName = $parts[1];

        $qComp = $this->_parserResult->getQueryComponent($dqlAlias);
        $columnName = $qComp['metadata']->getColumnName($fieldName);

        $sql .= $aggExpression->getFunctionName() . '(';
        if ($aggExpression->isDistinct()) $sql .= 'DISTINCT ';
        $sql .= $this->_dqlToSqlAliasMap[$dqlAlias] . '.' . $columnName;
        $sql .= ')';
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

    public function walkUpdateStatement(AST\UpdateStatement $AST)
    {

    }

    public function walkDeleteStatement(AST\DeleteStatement $AST)
    {
        $sql = $this->walkDeleteClause($AST->getDeleteClause());
        $sql .= $AST->getWhereClause() ? $this->walkWhereClause($AST->getWhereClause()) : '';
        return $sql;
    }

    public function walkDeleteClause(AST\DeleteClause $deleteClause)
    {
        $sql = 'DELETE FROM ';
        $class = $this->_em->getClassMetadata($deleteClause->getAbstractSchemaName());
        $sql .= $class->getTableName();
        if ($deleteClause->getAliasIdentificationVariable()) {
            $sql .= ' ' . $this->_dqlToSqlAliasMap[$deleteClause->getAliasIdentificationVariable()];
        }
        return $sql;
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
        return implode(' AND ', array_map(array(&$this, 'walkConditionalFactor'),
                $condTerm->getConditionalFactors()));
    }

    public function walkConditionalFactor($factor)
    {
        $sql = '';
        if ($factor->isNot()) $sql .= 'NOT ';
        $primary = $factor->getConditionalPrimary();
        if ($primary->isSimpleConditionalExpression()) {
            $simpleCond = $primary->getSimpleConditionalExpression();
            if ($simpleCond instanceof AST\ComparisonExpression) {
                $sql .= $this->walkComparisonExpression($simpleCond);
            }
            else if ($simpleCond instanceof AST\LikeExpression) {
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
        if ($stringExpr instanceof AST\PathExpression) {
            $sql .= $this->walkPathExpression($stringExpr);
        } //TODO else...
        $sql .= ' LIKE ' . $likeExpr->getStringPattern();
        return $sql;
    }

    public function walkComparisonExpression($compExpr)
    {
        $sql = '';
        if ($compExpr->getLeftExpression() instanceof AST\ArithmeticExpression) {
            $sql .= $this->walkArithmeticExpression($compExpr->getLeftExpression());
        } // else...
        $sql .= ' ' . $compExpr->getOperator() . ' ';
        if ($compExpr->getRightExpression() instanceof AST\ArithmeticExpression) {
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
        } else if (is_string($primary)) {
            //TODO: quote string according to platform
            $sql .= $primary;
        } else if ($primary instanceof AST\PathExpression) {
            $sql .= $this->walkPathExpression($primary);
        } else if ($primary instanceof AST\InputParameter) {
            if ($primary->isNamed()) {
                $sql .= ':' . $primary->getName();
            } else {
                $sql .= '?';
            }
        } else if ($primary instanceof AST\SimpleArithmeticExpression) {
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
            \Doctrine\Common\DoctrineException::updateMe("Not yet implemented.");
        } else {
            \Doctrine\Common\DoctrineException::updateMe("Encountered invalid PathExpression during SQL construction.");
        }
        return $sql;
    }

    /**
     * Generates a unique, short SQL table alias.
     *
     * @param string $tableName Table name.
     * @return string Generated table alias.
     */
    public function generateSqlTableAlias($tableName)
    {
        return strtolower(substr($tableName, 0, 1)) . $this->_tableAliasCounter++;
    }
}