<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

use Doctrine\Deprecations\Deprecation;
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
     * {@inheritDoc}
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
     * {@inheritDoc}
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

    public function walkSelectStatement(AST\SelectStatement $AST)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkSelectStatement($AST);

            $this->queryComponents = $walker->getQueryComponents();
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkSelectClause($selectClause)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkSelectClause($selectClause);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkFromClause($fromClause)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkFromClause($fromClause);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkFunction($function)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkFunction($function);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkOrderByClause($orderByClause)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkOrderByClause($orderByClause);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkOrderByItem($orderByItem)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkOrderByItem($orderByItem);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkHavingClause($havingClause)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkHavingClause($havingClause);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkJoin($join)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkJoin($join);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkSelectExpression($selectExpression)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkSelectExpression($selectExpression);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkQuantifiedExpression($qExpr)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkQuantifiedExpression($qExpr);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkSubselect($subselect)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkSubselect($subselect);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkSubselectFromClause($subselectFromClause)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkSubselectFromClause($subselectFromClause);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkSimpleSelectClause($simpleSelectClause)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkSimpleSelectClause($simpleSelectClause);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkSimpleSelectExpression($simpleSelectExpression)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkSimpleSelectExpression($simpleSelectExpression);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkAggregateExpression($aggExpression)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkAggregateExpression($aggExpression);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkGroupByClause($groupByClause)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkGroupByClause($groupByClause);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkGroupByItem($groupByItem)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkGroupByItem($groupByItem);
        }
    }

    public function walkUpdateStatement(AST\UpdateStatement $AST)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkUpdateStatement($AST);
        }
    }

    public function walkDeleteStatement(AST\DeleteStatement $AST)
    {
        foreach ($this->getWalkers() as $walker) {
            $walker->walkDeleteStatement($AST);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkDeleteClause(AST\DeleteClause $deleteClause)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkDeleteClause($deleteClause);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkUpdateClause($updateClause)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkUpdateClause($updateClause);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkUpdateItem($updateItem)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkUpdateItem($updateItem);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkWhereClause($whereClause)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkWhereClause($whereClause);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkConditionalExpression($condExpr)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkConditionalExpression($condExpr);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkConditionalTerm($condTerm)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkConditionalTerm($condTerm);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkConditionalFactor($factor)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkConditionalFactor($factor);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkConditionalPrimary($condPrimary)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkConditionalPrimary($condPrimary);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkExistsExpression($existsExpr)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkExistsExpression($existsExpr);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkCollectionMemberExpression($collMemberExpr)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkCollectionMemberExpression($collMemberExpr);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkEmptyCollectionComparisonExpression($emptyCollCompExpr)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkEmptyCollectionComparisonExpression($emptyCollCompExpr);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkNullComparisonExpression($nullCompExpr)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkNullComparisonExpression($nullCompExpr);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkInExpression($inExpr)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkInExpression($inExpr);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkInstanceOfExpression($instanceOfExpr)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkInstanceOfExpression($instanceOfExpr);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkLiteral($literal)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkLiteral($literal);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkBetweenExpression($betweenExpr)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkBetweenExpression($betweenExpr);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkLikeExpression($likeExpr)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkLikeExpression($likeExpr);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkStateFieldPathExpression($stateFieldPathExpression)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkStateFieldPathExpression($stateFieldPathExpression);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkComparisonExpression($compExpr)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkComparisonExpression($compExpr);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkInputParameter($inputParam)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkInputParameter($inputParam);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkArithmeticExpression($arithmeticExpr)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkArithmeticExpression($arithmeticExpr);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkArithmeticTerm($term)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkArithmeticTerm($term);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkStringPrimary($stringPrimary)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkStringPrimary($stringPrimary);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkArithmeticFactor($factor)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkArithmeticFactor($factor);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkSimpleArithmeticExpression($simpleArithmeticExpr)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkSimpleArithmeticExpression($simpleArithmeticExpr);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkPathExpression($pathExpr)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkPathExpression($pathExpr);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkResultVariable($resultVariable)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        foreach ($this->getWalkers() as $walker) {
            $walker->walkResultVariable($resultVariable);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function getExecutor($AST)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );

        return null;
    }

    /** @psalm-return Generator<int, TreeWalker> */
    private function getWalkers(): Generator
    {
        foreach ($this->walkers as $walkerClass) {
            yield new $walkerClass($this->query, $this->parserResult, $this->queryComponents);
        }
    }
}
