<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

use Doctrine\ORM\AbstractQuery;
use Generator;

use function array_diff;
use function array_keys;

/**
 * Represents a chain of tree walkers that modify an AST and finally emit output.
 * Only the last walker in the chain can emit output. Any previous walkers can modify
 * the AST to influence the final output produced by the last walker.
 *
 * @psalm-import-type QueryComponent from Parser
 */
class TreeWalkerChain implements TreeWalker
{
    /**
     * The tree walkers.
     *
     * @var string[]
     * @psalm-var list<class-string<TreeWalker>>
     */
    private $walkers = [];

    /** @var AbstractQuery */
    private $query;

    /** @var ParserResult */
    private $parserResult;

    /**
     * The query components of the original query (the "symbol table") that was produced by the Parser.
     *
     * @var array<string, array<string, mixed>>
     * @psalm-var array<string, QueryComponent>
     */
    private $queryComponents;

    /**
     * Returns the internal queryComponents array.
     *
     * {@inheritDoc}
     */
    public function getQueryComponents()
    {
        return $this->queryComponents;
    }

    /**
     * {@inheritdoc}
     *
     * @return void
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
        $this->query           = $query;
        $this->parserResult    = $parserResult;
        $this->queryComponents = $queryComponents;
    }

    /**
     * Adds a tree walker to the chain.
     *
     * @param string $walkerClass The class of the walker to instantiate.
     * @psalm-param class-string<TreeWalker> $walkerClass
     *
     * @return void
     */
    public function addTreeWalker($walkerClass)
    {
        $this->walkers[] = $walkerClass;
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkSelectStatement(AST\SelectStatement $AST)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkSelectStatement($AST);

            $this->queryComponents = $walker->getQueryComponents();
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkSelectClause($selectClause)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkSelectClause($selectClause);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkFromClause($fromClause)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkFromClause($fromClause);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkFunction($function)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkFunction($function);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkOrderByClause($orderByClause)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkOrderByClause($orderByClause);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkOrderByItem($orderByItem)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkOrderByItem($orderByItem);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkHavingClause($havingClause)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkHavingClause($havingClause);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkJoin($join)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkJoin($join);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkSelectExpression($selectExpression)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkSelectExpression($selectExpression);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkQuantifiedExpression($qExpr)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkQuantifiedExpression($qExpr);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkSubselect($subselect)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkSubselect($subselect);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkSubselectFromClause($subselectFromClause)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkSubselectFromClause($subselectFromClause);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkSimpleSelectClause($simpleSelectClause)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkSimpleSelectClause($simpleSelectClause);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkSimpleSelectExpression($simpleSelectExpression)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkSimpleSelectExpression($simpleSelectExpression);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkAggregateExpression($aggExpression)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkAggregateExpression($aggExpression);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkGroupByClause($groupByClause)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkGroupByClause($groupByClause);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkGroupByItem($groupByItem)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkGroupByItem($groupByItem);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkUpdateStatement(AST\UpdateStatement $AST)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkUpdateStatement($AST);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkDeleteStatement(AST\DeleteStatement $AST)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkDeleteStatement($AST);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkDeleteClause(AST\DeleteClause $deleteClause)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkDeleteClause($deleteClause);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkUpdateClause($updateClause)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkUpdateClause($updateClause);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkUpdateItem($updateItem)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkUpdateItem($updateItem);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkWhereClause($whereClause)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkWhereClause($whereClause);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkConditionalExpression($condExpr)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkConditionalExpression($condExpr);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkConditionalTerm($condTerm)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkConditionalTerm($condTerm);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkConditionalFactor($factor)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkConditionalFactor($factor);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkConditionalPrimary($condPrimary)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkConditionalPrimary($condPrimary);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkExistsExpression($existsExpr)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkExistsExpression($existsExpr);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkCollectionMemberExpression($collMemberExpr)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkCollectionMemberExpression($collMemberExpr);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkEmptyCollectionComparisonExpression($emptyCollCompExpr)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkEmptyCollectionComparisonExpression($emptyCollCompExpr);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkNullComparisonExpression($nullCompExpr)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkNullComparisonExpression($nullCompExpr);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkInExpression($inExpr)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkInExpression($inExpr);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkInstanceOfExpression($instanceOfExpr)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkInstanceOfExpression($instanceOfExpr);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkLiteral($literal)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkLiteral($literal);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkBetweenExpression($betweenExpr)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkBetweenExpression($betweenExpr);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkLikeExpression($likeExpr)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkLikeExpression($likeExpr);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkStateFieldPathExpression($stateFieldPathExpression)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkStateFieldPathExpression($stateFieldPathExpression);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkComparisonExpression($compExpr)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkComparisonExpression($compExpr);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkInputParameter($inputParam)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkInputParameter($inputParam);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkArithmeticExpression($arithmeticExpr)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkArithmeticExpression($arithmeticExpr);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkArithmeticTerm($term)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkArithmeticTerm($term);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkStringPrimary($stringPrimary)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkStringPrimary($stringPrimary);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkArithmeticFactor($factor)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkArithmeticFactor($factor);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkSimpleArithmeticExpression($simpleArithmeticExpr)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkSimpleArithmeticExpression($simpleArithmeticExpr);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkPathExpression($pathExpr)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkPathExpression($pathExpr);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function walkResultVariable($resultVariable)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkResultVariable($resultVariable);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function getExecutor($AST)
    {
    }

    /**
     * @psalm-return Generator<int, TreeWalker>
     */
    private function getWalkers(): Generator
    {
        foreach ($this->walkers as $walkerClass) {
            yield new $walkerClass($this->query, $this->parserResult, $this->queryComponents);
        }
    }
}
