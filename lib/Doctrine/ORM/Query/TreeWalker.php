<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

use Doctrine\ORM\AbstractQuery;

/**
 * Interface for walkers of DQL ASTs (abstract syntax trees).
 *
 * @psalm-import-type QueryComponent from Parser
 */
interface TreeWalker
{
    /**
     * Initializes TreeWalker with important information about the ASTs to be walked.
     *
     * @param AbstractQuery $query           The parsed Query.
     * @param ParserResult  $parserResult    The result of the parsing process.
     * @param mixed[]       $queryComponents The query components (symbol table).
     * @psalm-param array<string, QueryComponent> $queryComponents The query components (symbol table).
     */
    public function __construct($query, $parserResult, array $queryComponents);

    /**
     * Returns internal queryComponents array.
     *
     * @return array<string, array<string, mixed>>
     * @psalm-return array<string, QueryComponent>
     */
    public function getQueryComponents();

    /**
     * Sets or overrides a query component for a given dql alias.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param string               $dqlAlias       The DQL alias.
     * @param array<string, mixed> $queryComponent
     * @psalm-param QueryComponent $queryComponent
     *
     * @return void
     */
    public function setQueryComponent($dqlAlias, array $queryComponent);

    /**
     * Walks down a SelectStatement AST node.
     *
     * @return void
     */
    public function walkSelectStatement(AST\SelectStatement $AST);

    /**
     * Walks down a SelectClause AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\SelectClause $selectClause
     *
     * @return void
     */
    public function walkSelectClause($selectClause);

    /**
     * Walks down a FromClause AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\FromClause $fromClause
     *
     * @return void
     */
    public function walkFromClause($fromClause);

    /**
     * Walks down a FunctionNode AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\Functions\FunctionNode $function
     *
     * @return void
     */
    public function walkFunction($function);

    /**
     * Walks down an OrderByClause AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\OrderByClause $orderByClause
     *
     * @return void
     */
    public function walkOrderByClause($orderByClause);

    /**
     * Walks down an OrderByItem AST node, thereby generating the appropriate SQL.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\OrderByItem $orderByItem
     *
     * @return void
     */
    public function walkOrderByItem($orderByItem);

    /**
     * Walks down a HavingClause AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\HavingClause $havingClause
     *
     * @return void
     */
    public function walkHavingClause($havingClause);

    /**
     * Walks down a Join AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\Join $join
     *
     * @return void
     */
    public function walkJoin($join);

    /**
     * Walks down a SelectExpression AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\SelectExpression $selectExpression
     *
     * @return void
     */
    public function walkSelectExpression($selectExpression);

    /**
     * Walks down a QuantifiedExpression AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\QuantifiedExpression $qExpr
     *
     * @return void
     */
    public function walkQuantifiedExpression($qExpr);

    /**
     * Walks down a Subselect AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\Subselect $subselect
     *
     * @return void
     */
    public function walkSubselect($subselect);

    /**
     * Walks down a SubselectFromClause AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\SubselectFromClause $subselectFromClause
     *
     * @return void
     */
    public function walkSubselectFromClause($subselectFromClause);

    /**
     * Walks down a SimpleSelectClause AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\SimpleSelectClause $simpleSelectClause
     *
     * @return void
     */
    public function walkSimpleSelectClause($simpleSelectClause);

    /**
     * Walks down a SimpleSelectExpression AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\SimpleSelectExpression $simpleSelectExpression
     *
     * @return void
     */
    public function walkSimpleSelectExpression($simpleSelectExpression);

    /**
     * Walks down an AggregateExpression AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\AggregateExpression $aggExpression
     *
     * @return void
     */
    public function walkAggregateExpression($aggExpression);

    /**
     * Walks down a GroupByClause AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\GroupByClause $groupByClause
     *
     * @return void
     */
    public function walkGroupByClause($groupByClause);

    /**
     * Walks down a GroupByItem AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\PathExpression|string $groupByItem
     *
     * @return void
     */
    public function walkGroupByItem($groupByItem);

    /**
     * Walks down an UpdateStatement AST node.
     *
     * @return void
     */
    public function walkUpdateStatement(AST\UpdateStatement $AST);

    /**
     * Walks down a DeleteStatement AST node.
     *
     * @return void
     */
    public function walkDeleteStatement(AST\DeleteStatement $AST);

    /**
     * Walks down a DeleteClause AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @return void
     */
    public function walkDeleteClause(AST\DeleteClause $deleteClause);

    /**
     * Walks down an UpdateClause AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\UpdateClause $updateClause
     *
     * @return void
     */
    public function walkUpdateClause($updateClause);

    /**
     * Walks down an UpdateItem AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\UpdateItem $updateItem
     *
     * @return void
     */
    public function walkUpdateItem($updateItem);

    /**
     * Walks down a WhereClause AST node.
     *
     * WhereClause or not, the appropriate discriminator sql is added.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\WhereClause $whereClause
     *
     * @return void
     */
    public function walkWhereClause($whereClause);

    /**
     * Walk down a ConditionalExpression AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\ConditionalExpression $condExpr
     *
     * @return void
     */
    public function walkConditionalExpression($condExpr);

    /**
     * Walks down a ConditionalTerm AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\ConditionalTerm $condTerm
     *
     * @return void
     */
    public function walkConditionalTerm($condTerm);

    /**
     * Walks down a ConditionalFactor AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\ConditionalFactor $factor
     *
     * @return void
     */
    public function walkConditionalFactor($factor);

    /**
     * Walks down a ConditionalPrimary AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\ConditionalPrimary $primary
     *
     * @return void
     */
    public function walkConditionalPrimary($primary);

    /**
     * Walks down an ExistsExpression AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\ExistsExpression $existsExpr
     *
     * @return void
     */
    public function walkExistsExpression($existsExpr);

    /**
     * Walks down a CollectionMemberExpression AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\CollectionMemberExpression $collMemberExpr
     *
     * @return void
     */
    public function walkCollectionMemberExpression($collMemberExpr);

    /**
     * Walks down an EmptyCollectionComparisonExpression AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\EmptyCollectionComparisonExpression $emptyCollCompExpr
     *
     * @return void
     */
    public function walkEmptyCollectionComparisonExpression($emptyCollCompExpr);

    /**
     * Walks down a NullComparisonExpression AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\NullComparisonExpression $nullCompExpr
     *
     * @return void
     */
    public function walkNullComparisonExpression($nullCompExpr);

    /**
     * Walks down an InExpression AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\InExpression $inExpr
     *
     * @return void
     */
    public function walkInExpression($inExpr);

    /**
     * Walks down an InstanceOfExpression AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\InstanceOfExpression $instanceOfExpr
     *
     * @return void
     */
    public function walkInstanceOfExpression($instanceOfExpr);

    /**
     * Walks down a literal that represents an AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\Literal $literal
     *
     * @return void
     */
    public function walkLiteral($literal);

    /**
     * Walks down a BetweenExpression AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\BetweenExpression $betweenExpr
     *
     * @return void
     */
    public function walkBetweenExpression($betweenExpr);

    /**
     * Walks down a LikeExpression AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\LikeExpression $likeExpr
     *
     * @return void
     */
    public function walkLikeExpression($likeExpr);

    /**
     * Walks down a StateFieldPathExpression AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\PathExpression $stateFieldPathExpression
     *
     * @return void
     */
    public function walkStateFieldPathExpression($stateFieldPathExpression);

    /**
     * Walks down a ComparisonExpression AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\ComparisonExpression $compExpr
     *
     * @return void
     */
    public function walkComparisonExpression($compExpr);

    /**
     * Walks down an InputParameter AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\InputParameter $inputParam
     *
     * @return void
     */
    public function walkInputParameter($inputParam);

    /**
     * Walks down an ArithmeticExpression AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\ArithmeticExpression $arithmeticExpr
     *
     * @return void
     */
    public function walkArithmeticExpression($arithmeticExpr);

    /**
     * Walks down an ArithmeticTerm AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param mixed $term
     *
     * @return void
     */
    public function walkArithmeticTerm($term);

    /**
     * Walks down a StringPrimary that represents an AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param mixed $stringPrimary
     *
     * @return void
     */
    public function walkStringPrimary($stringPrimary);

    /**
     * Walks down an ArithmeticFactor that represents an AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param mixed $factor
     *
     * @return void
     */
    public function walkArithmeticFactor($factor);

    /**
     * Walks down an SimpleArithmeticExpression AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\SimpleArithmeticExpression $simpleArithmeticExpr
     *
     * @return void
     */
    public function walkSimpleArithmeticExpression($simpleArithmeticExpr);

    /**
     * Walks down a PathExpression AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\PathExpression $pathExpr
     *
     * @return void
     */
    public function walkPathExpression($pathExpr);

    /**
     * Walks down a ResultVariable that represents an AST node.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param string $resultVariable
     *
     * @return void
     */
    public function walkResultVariable($resultVariable);

    /**
     * Gets an executor that can be used to execute the result of this walker.
     *
     * @deprecated This method will be removed from the interface in 3.0.
     *
     * @param AST\DeleteStatement|AST\UpdateStatement|AST\SelectStatement $AST
     *
     * @return Exec\AbstractSqlExecutor
     */
    public function getExecutor($AST);
}
