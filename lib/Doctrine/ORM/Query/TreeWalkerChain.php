<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

use function array_diff;
use function array_keys;

/**
 * Represents a chain of tree walkers that modify an AST and finally emit output.
 * Only the last walker in the chain can emit output. Any previous walkers can modify
 * the AST to influence the final output produced by the last walker.
 */
class TreeWalkerChain implements TreeWalker
{
    /**
     * The tree walkers.
     *
     * @var TreeWalker[]
     */
    private $walkers;

    /**
     * The query components of the original query (the "symbol table") that was produced by the Parser.
     *
     * @var mixed[][]
     */
    private $queryComponents;

    /**
     * Returns the internal queryComponents array.
     *
     * @return mixed[][]
     */
    public function getQueryComponents()
    {
        return $this->queryComponents;
    }

    /**
     * {@inheritdoc}
     */
    public function setQueryComponent($dqlAlias, array $queryComponent)
    {
        $requiredKeys = ['metadata', 'parent', 'relation', 'map', 'nestingLevel', 'token'];

        if (array_diff($requiredKeys, array_keys($queryComponent))) {
            throw QueryException::invalidQueryComponent($dqlAlias);
        }

        $this->queryComponents[$dqlAlias] = $queryComponent;
    }

    /**
     * {@inheritdoc}
     */
    public function __construct($query, $parserResult, array $queryComponents)
    {
        $this->queryComponents = $queryComponents;
        $this->walkers         = new TreeWalkerChainIterator($this, $query, $parserResult);
    }

    /**
     * Adds a tree walker to the chain.
     *
     * @param string $walkerClass The class of the walker to instantiate.
     */
    public function addTreeWalker($walkerClass)
    {
        $this->walkers[] = $walkerClass;
    }

    /**
     * {@inheritdoc}
     */
    public function walkSelectStatement(AST\SelectStatement $AST)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkSelectStatement($AST);

            $this->queryComponents = $walker->getQueryComponents();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkSelectClause($selectClause)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkSelectClause($selectClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkFromClause($fromClause)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkFromClause($fromClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkFunction($function)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkFunction($function);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkOrderByClause($orderByClause)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkOrderByClause($orderByClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkOrderByItem($orderByItem)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkOrderByItem($orderByItem);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkHavingClause($havingClause)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkHavingClause($havingClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkJoin($join)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkJoin($join);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkSelectExpression($selectExpression)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkSelectExpression($selectExpression);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkQuantifiedExpression($qExpr)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkQuantifiedExpression($qExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkSubselect($subselect)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkSubselect($subselect);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkSubselectFromClause($subselectFromClause)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkSubselectFromClause($subselectFromClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkSimpleSelectClause($simpleSelectClause)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkSimpleSelectClause($simpleSelectClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkSimpleSelectExpression($simpleSelectExpression)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkSimpleSelectExpression($simpleSelectExpression);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkAggregateExpression($aggExpression)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkAggregateExpression($aggExpression);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkGroupByClause($groupByClause)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkGroupByClause($groupByClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkGroupByItem($groupByItem)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkGroupByItem($groupByItem);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkUpdateStatement(AST\UpdateStatement $AST)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkUpdateStatement($AST);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkDeleteStatement(AST\DeleteStatement $AST)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkDeleteStatement($AST);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkDeleteClause(AST\DeleteClause $deleteClause)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkDeleteClause($deleteClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkUpdateClause($updateClause)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkUpdateClause($updateClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkUpdateItem($updateItem)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkUpdateItem($updateItem);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkWhereClause($whereClause)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkWhereClause($whereClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalExpression($condExpr)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkConditionalExpression($condExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalTerm($condTerm)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkConditionalTerm($condTerm);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalFactor($factor)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkConditionalFactor($factor);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalPrimary($condPrimary)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkConditionalPrimary($condPrimary);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkExistsExpression($existsExpr)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkExistsExpression($existsExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkCollectionMemberExpression($collMemberExpr)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkCollectionMemberExpression($collMemberExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkEmptyCollectionComparisonExpression($emptyCollCompExpr)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkEmptyCollectionComparisonExpression($emptyCollCompExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkNullComparisonExpression($nullCompExpr)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkNullComparisonExpression($nullCompExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkInExpression($inExpr)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkInExpression($inExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkInstanceOfExpression($instanceOfExpr)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkInstanceOfExpression($instanceOfExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkLiteral($literal)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkLiteral($literal);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkBetweenExpression($betweenExpr)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkBetweenExpression($betweenExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkLikeExpression($likeExpr)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkLikeExpression($likeExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkStateFieldPathExpression($stateFieldPathExpression)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkStateFieldPathExpression($stateFieldPathExpression);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkComparisonExpression($compExpr)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkComparisonExpression($compExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkInputParameter($inputParam)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkInputParameter($inputParam);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkArithmeticExpression($arithmeticExpr)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkArithmeticExpression($arithmeticExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkArithmeticTerm($term)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkArithmeticTerm($term);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkStringPrimary($stringPrimary)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkStringPrimary($stringPrimary);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkArithmeticFactor($factor)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkArithmeticFactor($factor);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkSimpleArithmeticExpression($simpleArithmeticExpr)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkSimpleArithmeticExpression($simpleArithmeticExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkPathExpression($pathExpr)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkPathExpression($pathExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkResultVariable($resultVariable)
    {
        foreach ($this->walkers as $walker) {
            $walker->walkResultVariable($resultVariable);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutor($AST)
    {
    }
}
