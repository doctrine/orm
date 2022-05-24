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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
