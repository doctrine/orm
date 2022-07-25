<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Mapping\ClassMetadata;
use LogicException;

use function array_diff;
use function array_keys;
use function debug_backtrace;
use function is_a;
use function sprintf;

use const DEBUG_BACKTRACE_IGNORE_ARGS;

/**
 * An adapter implementation of the TreeWalker interface. The methods in this class
 * are empty. This class exists as convenience for creating tree walkers.
 *
 * @psalm-import-type QueryComponent from Parser
 */
abstract class TreeWalkerAdapter implements TreeWalker
{
    /**
     * The original Query.
     *
     * @var AbstractQuery
     */
    private $query;

    /**
     * The ParserResult of the original query that was produced by the Parser.
     *
     * @var ParserResult
     */
    private $parserResult;

    /**
     * The query components of the original query (the "symbol table") that was produced by the Parser.
     *
     * @psalm-var array<string, QueryComponent>
     */
    private $queryComponents;

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
     * {@inheritdoc}
     */
    public function getQueryComponents()
    {
        return $this->queryComponents;
    }

    /**
     * Sets or overrides a query component for a given dql alias.
     *
     * @internal This method will be protected in 3.0.
     *
     * @param string               $dqlAlias       The DQL alias.
     * @param array<string, mixed> $queryComponent
     * @psalm-param QueryComponent $queryComponent
     *
     * @return void
     */
    public function setQueryComponent($dqlAlias, array $queryComponent)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        if (! isset($trace[1]['class']) || ! is_a($trace[1]['class'], self::class, true)) {
            Deprecation::trigger(
                'doctrine/orm',
                'https://github.com/doctrine/orm/pull/9551',
                'Method %s will be protected in 3.0. Calling it publicly is deprecated.',
                __METHOD__
            );
        }

        $requiredKeys = ['metadata', 'parent', 'relation', 'map', 'nestingLevel', 'token'];

        if (array_diff($requiredKeys, array_keys($queryComponent))) {
            throw QueryException::invalidQueryComponent($dqlAlias);
        }

        $this->queryComponents[$dqlAlias] = $queryComponent;
    }

    /**
     * Returns internal queryComponents array.
     *
     * @deprecated Call {@see getQueryComponents()} instead.
     *
     * @return array<string, array<string, mixed>>
     * @psalm-return array<string, QueryComponent>
     */
    protected function _getQueryComponents()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method %s is deprecated, call getQueryComponents() instead.',
            __METHOD__
        );

        return $this->queryComponents;
    }

    /**
     * Retrieves the Query Instance responsible for the current walkers execution.
     *
     * @return AbstractQuery
     */
    protected function _getQuery()
    {
        return $this->query;
    }

    /**
     * Retrieves the ParserResult.
     *
     * @return ParserResult
     */
    protected function _getParserResult()
    {
        return $this->parserResult;
    }

    public function walkSelectStatement(AST\SelectStatement $AST)
    {
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
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkAggregateExpression($aggExpression)
    {
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
    }

    public function walkUpdateStatement(AST\UpdateStatement $AST)
    {
    }

    public function walkDeleteStatement(AST\DeleteStatement $AST)
    {
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
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated This method will be removed in 3.0.
     */
    public function walkConditionalPrimary($primary)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9551',
            'Method "%s" is deprecated and will be removed in ORM 3.0 without replacement.',
            __METHOD__
        );
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

    final protected function getMetadataForDqlAlias(string $dqlAlias): ClassMetadata
    {
        $metadata = $this->_getQueryComponents()[$dqlAlias]['metadata'] ?? null;

        if ($metadata === null) {
            throw new LogicException(sprintf('No metadata for DQL alias: %s', $dqlAlias));
        }

        return $metadata;
    }
}
