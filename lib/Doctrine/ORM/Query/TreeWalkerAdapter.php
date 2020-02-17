<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

use Doctrine\ORM\AbstractQuery;
use function array_diff;
use function array_keys;

/**
 * An adapter implementation of the TreeWalker interface. The methods in this class
 * are empty. ﻿This class exists as convenience for creating tree walkers.
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
     * @var mixed[][]
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
     * Retrieves the Query Instance responsible for the current walkers execution.
     *
     * @return AbstractQuery
     */
    protected function getQuery()
    {
        return $this->query;
    }

    /**
     * Retrieves the ParserResult.
     *
     * @return ParserResult
     */
    protected function getParserResult()
    {
        return $this->parserResult;
    }

    /**
     * {@inheritdoc}
     */
    public function walkSelectStatement(AST\SelectStatement $AST)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkSelectClause($selectClause)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkFromClause($fromClause)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkFunction($function)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkOrderByClause($orderByClause)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkOrderByItem($orderByItem)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkHavingClause($havingClause)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkJoin($join)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkSelectExpression($selectExpression)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkQuantifiedExpression($qExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkSubselect($subselect)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkSubselectFromClause($subselectFromClause)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkSimpleSelectClause($simpleSelectClause)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkSimpleSelectExpression($simpleSelectExpression)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkAggregateExpression($aggExpression)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkGroupByClause($groupByClause)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkGroupByItem($groupByItem)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkUpdateStatement(AST\UpdateStatement $AST)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkDeleteStatement(AST\DeleteStatement $AST)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkDeleteClause(AST\DeleteClause $deleteClause)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkUpdateClause($updateClause)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkUpdateItem($updateItem)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkWhereClause($whereClause)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalExpression($condExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalTerm($condTerm)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalFactor($factor)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalPrimary($primary)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkExistsExpression($existsExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkCollectionMemberExpression($collMemberExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkEmptyCollectionComparisonExpression($emptyCollCompExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkNullComparisonExpression($nullCompExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkInExpression($inExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkInstanceOfExpression($instanceOfExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkLiteral($literal)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkBetweenExpression($betweenExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkLikeExpression($likeExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkStateFieldPathExpression($stateFieldPathExpression)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkComparisonExpression($compExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkInputParameter($inputParam)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkArithmeticExpression($arithmeticExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkArithmeticTerm($term)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkStringPrimary($stringPrimary)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkArithmeticFactor($factor)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkSimpleArithmeticExpression($simpleArithmeticExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkPathExpression($pathExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkResultVariable($resultVariable)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutor($AST)
    {
    }
}
