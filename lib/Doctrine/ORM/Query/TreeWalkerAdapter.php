<?php declare(strict_types=1);

/*
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
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Query;

/**
 * An adapter implementation of the TreeWalker interface. The methods in this class
 * are empty. ﻿This class exists as convenience for creating tree walkers.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
abstract class TreeWalkerAdapter implements TreeWalker
{
    /**
     * The original Query.
     *
     * @var \Doctrine\ORM\AbstractQuery
     */
    private $_query;

    /**
     * The ParserResult of the original query that was produced by the Parser.
     *
     * @var \Doctrine\ORM\Query\ParserResult
     */
    private $_parserResult;

    /**
     * The query components of the original query (the "symbol table") that was produced by the Parser.
     *
     * @var array
     */
    private $_queryComponents;

    /**
     * {@inheritdoc}
     */
    public function __construct($query, $parserResult, array $queryComponents)
    {
        $this->_query = $query;
        $this->_parserResult = $parserResult;
        $this->_queryComponents = $queryComponents;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryComponents()
    {
        return $this->_queryComponents;
    }

    /**
     * {@inheritdoc}
     */
    public function setQueryComponent($dqlAlias, array $queryComponent): void
    {
        $requiredKeys = ['metadata', 'parent', 'relation', 'map', 'nestingLevel', 'token'];

        if (array_diff($requiredKeys, array_keys($queryComponent))) {
            throw QueryException::invalidQueryComponent($dqlAlias);
        }

        $this->_queryComponents[$dqlAlias] = $queryComponent;
    }

    /**
     * @return array
     */
    protected function _getQueryComponents(): array
    {
        return $this->_queryComponents;
    }

    /**
     * Retrieves the Query Instance responsible for the current walkers execution.
     *
     * @return \Doctrine\ORM\AbstractQuery
     */
    protected function _getQuery(): \Doctrine\ORM\AbstractQuery
    {
        return $this->_query;
    }

    /**
     * Retrieves the ParserResult.
     *
     * @return \Doctrine\ORM\Query\ParserResult
     */
    protected function _getParserResult(): \Doctrine\ORM\Query\ParserResult
    {
        return $this->_parserResult;
    }

    /**
     * {@inheritdoc}
     */
    public function walkSelectStatement(AST\SelectStatement $AST): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkSelectClause($selectClause): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkFromClause($fromClause): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkFunction($function): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkOrderByClause($orderByClause): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkOrderByItem($orderByItem): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkHavingClause($havingClause): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkJoin($join): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkSelectExpression($selectExpression): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkQuantifiedExpression($qExpr): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkSubselect($subselect): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkSubselectFromClause($subselectFromClause): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkSimpleSelectClause($simpleSelectClause): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkSimpleSelectExpression($simpleSelectExpression): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkAggregateExpression($aggExpression): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkGroupByClause($groupByClause): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkGroupByItem($groupByItem): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkUpdateStatement(AST\UpdateStatement $AST): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkDeleteStatement(AST\DeleteStatement $AST): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkDeleteClause(AST\DeleteClause $deleteClause): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkUpdateClause($updateClause): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkUpdateItem($updateItem): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkWhereClause($whereClause): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalExpression($condExpr): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalTerm($condTerm): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalFactor($factor): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalPrimary($primary): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkExistsExpression($existsExpr): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkCollectionMemberExpression($collMemberExpr): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkEmptyCollectionComparisonExpression($emptyCollCompExpr): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkNullComparisonExpression($nullCompExpr): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkInExpression($inExpr): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkInstanceOfExpression($instanceOfExpr): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkLiteral($literal): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkBetweenExpression($betweenExpr): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkLikeExpression($likeExpr): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkStateFieldPathExpression($stateFieldPathExpression): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkComparisonExpression($compExpr): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkInputParameter($inputParam): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkArithmeticExpression($arithmeticExpr): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkArithmeticTerm($term): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkStringPrimary($stringPrimary): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkArithmeticFactor($factor): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkSimpleArithmeticExpression($simpleArithmeticExpr): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkPathExpression($pathExpr): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkResultVariable($resultVariable): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutor($AST): void
    {
    }
}
