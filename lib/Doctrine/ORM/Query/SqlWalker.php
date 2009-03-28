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

use Doctrine\ORM\Query\Parser;
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
    const SQLALIAS_SEPARATOR = '__';

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
            if ($dqlAlias != Parser::SCALAR_QUERYCOMPONENT_ALIAS) {
                $sqlAlias = $this->generateSqlTableAlias($qComp['metadata']->getTableName());
                $sqlToDqlAliasMap[$sqlAlias] = $dqlAlias;
            }
        }
        // SQL => DQL alias stored in ParserResult, needed for hydration.
        $parserResult->setTableAliasMap($sqlToDqlAliasMap);
        // DQL => SQL alias stored only locally, needed for SQL construction.
        $this->_dqlToSqlAliasMap = array_flip($sqlToDqlAliasMap);
    }

    public function getConnection()
    {
        return $this->_em->getConnection();
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
        $sql .= $AST->getWhereClause() ? $this->walkWhereClause($AST->getWhereClause()) : '';
        $sql .= $AST->getGroupByClause() ? $this->walkGroupByClause($AST->getGroupByClause()) : '';
        $sql .= $AST->getHavingClause() ? $this->walkHavingClause($AST->getHavingClause()) : '';
        $sql .= $AST->getOrderByClause() ? $this->walkOrderByClause($AST->getOrderByClause()) : '';

        //... more clauses
        return $sql;
    }

    /**
     * Walks down a SelectClause AST node, thereby generating the appropriate SQL.
     *
     * @return string The SQL.
     */
    public function walkSelectClause($selectClause)
    {
        return 'SELECT ' . (($selectClause->isDistinct()) ? 'DISTINCT ' : '')
                . implode(', ', array_map(array($this, 'walkSelectExpression'),
                        $selectClause->getSelectExpressions()));
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
        $sql .= $rangeDecl->getClassMetadata()->getTableName() . ' '
                . $this->_dqlToSqlAliasMap[$rangeDecl->getAliasIdentificationVariable()];

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
        return ' ORDER BY '
                . implode(', ', array_map(array($this, 'walkOrderByItem'),
                $orderByClause->getOrderByItems()));
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
        $pathExpr = $orderByItem->getStateFieldPathExpression();
        $parts = $pathExpr->getParts();
        $qComp = $this->_parserResult->getQueryComponent($parts[0]);
        $columnName = $qComp['metadata']->getColumnName($parts[1]);
        $sql = $this->_dqlToSqlAliasMap[$parts[0]] . '.' . $columnName;
        $sql .= $orderByItem->isAsc() ? ' ASC' : ' DESC';
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
        // HavingClause ::= "HAVING" ConditionalExpression
        return ' HAVING ' . implode(' OR ', array_map(array($this, 'walkConditionalTerm'),
                $havingClause->getConditionalExpression()->getConditionalTerms()));
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
        if ($joinType == AST\Join::JOIN_TYPE_LEFT || $joinType == AST\Join::JOIN_TYPE_LEFTOUTER) {
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
     * @param SelectExpression $selectExpression
     * @return string The SQL.
     */
    public function walkSelectExpression($selectExpression)
    {
        $sql = '';
        if ($selectExpression->getExpression() instanceof AST\StateFieldPathExpression) {
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
                throw DoctrineException::updateMe("Not yet implemented.");
            } else {
                throw DoctrineException::updateMe("Encountered invalid PathExpression during SQL construction.");
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
        } else if ($selectExpression->getExpression() instanceof AST\Functions\FunctionNode) {
            $funcExpr = $selectExpression->getExpression();
            if ( ! $selectExpression->getFieldIdentificationVariable()) {
                $alias = $this->_scalarAliasCounter++;
            } else {
                $alias = $selectExpression->getFieldIdentificationVariable();
            }
            $sql .= $this->walkFunction($selectExpression->getExpression()) . ' AS dctrn__' . $alias;
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
        $sql = $this->walkSimpleSelectClause($subselect->getSimpleSelectClause());
        $sql .= $this->walkSubselectFromClause($subselect->getSubselectFromClause());
        $sql .= $subselect->getWhereClause() ? $this->walkWhereClause($subselect->getWhereClause()) : '';
        $sql .= $subselect->getGroupByClause() ? $this->walkGroupByClause($subselect->getGroupByClause()) : '';
        $sql .= $subselect->getHavingClause() ? $this->walkHavingClause($subselect->getHavingClause()) : '';
        $sql .= $subselect->getOrderByClause() ? $this->walkOrderByClause($subselect->getOrderByClause()) : '';

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
                . $this->_dqlToSqlAliasMap[$rangeDecl->getAliasIdentificationVariable()];

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
        if ($expr instanceof AST\StateFieldPathExpression) {
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
            // $expr is IdentificationVariable
            //...
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

    /**
     * Walks down a GroupByClause AST node, thereby generating the appropriate SQL.
     *
     * @param GroupByClause
     * @return string The SQL.
     */
    public function walkGroupByClause($groupByClause)
    {
        return ' GROUP BY ' 
                . implode(', ', array_map(array($this, 'walkGroupByItem'),
                $groupByClause->getGroupByItems()));
    }

    /**
     * Walks down a GroupByItem AST node, thereby generating the appropriate SQL.
     *
     * @param GroupByItem
     * @return string The SQL.
     */
    public function walkGroupByItem($pathExpr)
    {
        //TODO: support general SingleValuedPathExpression, not just state field
        $parts = $pathExpr->getParts();
        $qComp = $this->_parserResult->getQueryComponent($parts[0]);
        $columnName = $qComp['metadata']->getColumnName($parts[1]);
        return $this->_dqlToSqlAliasMap[$parts[0]] . '.' . $columnName;
    }

    /**
     * Walks down an UpdateStatement AST node, thereby generating the appropriate SQL.
     *
     * @param UpdateStatement
     * @return string The SQL.
     */
    public function walkUpdateStatement(AST\UpdateStatement $AST)
    {
        $sql = $this->walkUpdateClause($AST->getUpdateClause());
        $sql .= $AST->getWhereClause() ? $this->walkWhereClause($AST->getWhereClause()) : '';
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
        $sql = $this->walkDeleteClause($AST->getDeleteClause());
        $sql .= $AST->getWhereClause() ? $this->walkWhereClause($AST->getWhereClause()) : '';
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
        if ($deleteClause->getAliasIdentificationVariable()) {
            $sql .= ' ' . $this->_dqlToSqlAliasMap[$deleteClause->getAliasIdentificationVariable()];
        }
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
        if ($updateClause->getAliasIdentificationVariable()) {
            $sql .= ' ' . $this->_dqlToSqlAliasMap[$updateClause->getAliasIdentificationVariable()];
        }
        $sql .= ' SET ' . implode(', ', array_map(array($this, 'walkUpdateItem'),
                $updateClause->getUpdateItems()));
        
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
        $sql = '';
        $dqlAlias = $updateItem->getIdentificationVariable() ?
                $updateItem->getIdentificationVariable() :
                $this->_parserResult->getDefaultQueryComponentAlias();
        $qComp = $this->_parserResult->getQueryComponent($dqlAlias);

        $sql .= $this->_dqlToSqlAliasMap[$dqlAlias] . '.'
                . $qComp['metadata']->getColumnName($updateItem->getField())
                . ' = ';

        $newValue = $updateItem->getNewValue();

        if ($newValue instanceof AST\Node) {
            $sql .= $newValue->dispatch($this);
        } else if (is_string($newValue)) {
            if (strcasecmp($newValue, 'NULL') === 0) {
                $sql .= 'NULL';
            } else {
                $sql .= $newValue; //TODO: quote()
            }
        }

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
        $sql .= implode(' OR ', array_map(array($this, 'walkConditionalTerm'),
                $condExpr->getConditionalTerms()));
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
        return implode(' AND ', array_map(array($this, 'walkConditionalFactor'),
                $condTerm->getConditionalFactors()));
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
            $sql .= '(' . implode(' OR ', array_map(array($this, 'walkConditionalTerm'),
                    $primary->getConditionalExpression()->getConditionalTerms())) . ')';
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
        $sql .= ' EXISTS (' . $this->walkSubselect($existsExpr->getSubselect()) . ')';
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
            $sql .= ' ' . ($innerExpr->isNamed() ? ':' . $innerExpr->getName() : '?');
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
            return ($literal->isNamed() ? ':' . $literal->getName() : '?');
        } else {
            return $literal; //TODO: quote() ?
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
        $sql = '';
        $stringExpr = $likeExpr->getStringExpression();

        $sql .= $stringExpr->dispatch($this);

        if ($likeExpr->isNot()) $sql .= ' NOT';
        $sql .= ' LIKE ';
        
        if ($likeExpr->getStringPattern() instanceof AST\InputParameter) {
            $inputParam = $likeExpr->getStringPattern();
            $sql .= $inputParam->isNamed() ? ':' . $inputParam->getName() : '?';
        } else {
            $sql .= $likeExpr->getStringPattern();
        }
        if ($likeExpr->getEscapeChar()) {
            $sql .= ' ESCAPE ' . $likeExpr->getEscapeChar();
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
            $sql .= $leftExpr; //TODO: quote()
        }
        
        $sql .= ' ' . $compExpr->getOperator() . ' ';

        if ($rightExpr instanceof AST\Node) {
            $sql .= $rightExpr->dispatch($this);
        } else {
            $sql .= $rightExpr; //TODO: quote()
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
        return $inputParam->isNamed() ? ':' . $inputParam->getName() : '?';
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

        return implode(' ', array_map(array($this, 'walkArithmeticFactor'),
                $term->getArithmeticFactors()));
    }

    /**
     * Walks down a StringPrimary that represents an AST node, thereby generating the appropriate SQL.
     *
     * @param mixed
     * @return string The SQL.
     */
    public function walkStringPrimary($stringPrimary)
    {
        if (is_string($stringPrimary)) {
            return $stringPrimary;
        } else {
            return $stringPrimary->dispatch($this);
        }
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
            //TODO: quote string according to platform
            $sql .= $primary;
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
        return implode(' ', array_map(array($this, 'walkArithmeticTerm'),
                $simpleArithmeticExpr->getArithmeticTerms()));
    }

    /**
     * Walks down an PathExpression AST node, thereby generating the appropriate SQL.
     *
     * @param mixed
     * @return string The SQL.
     */
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
            throw DoctrineException::updateMe("Not yet implemented.");
        } else {
            throw DoctrineException::updateMe("Encountered invalid PathExpression during SQL construction.");
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