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
 * Represents a chain of tree walkers that modify an AST and finally emit output.
 * Only the last walker in the chain can emit output. Any previous walkers can modify
 * the AST to influence the final output produced by the last walker.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since  2.0
 */
class TreeWalkerChain implements TreeWalker
{
    /**
     * The tree walkers.
     *
     * @var TreeWalker[]
     */
    private $_walkers;

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
     * Returns the internal queryComponents array.
     *
     * @return array
     */
    public function getQueryComponents(): array
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
     * {@inheritdoc}
     */
    public function __construct($query, $parserResult, array $queryComponents)
    {
        $this->_query = $query;
        $this->_parserResult = $parserResult;
        $this->_queryComponents = $queryComponents;
        $this->_walkers = new TreeWalkerChainIterator($this, $query, $parserResult);
    }

    /**
     * Adds a tree walker to the chain.
     *
     * @param string $walkerClass The class of the walker to instantiate.
     *
     * @return void
     */
    public function addTreeWalker(string $walkerClass): void
    {
        $this->_walkers[] = $walkerClass;
    }

    /**
     * {@inheritdoc}
     */
    public function walkSelectStatement(AST\SelectStatement $AST): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkSelectStatement($AST);

            $this->_queryComponents = $walker->getQueryComponents();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkSelectClause($selectClause): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkSelectClause($selectClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkFromClause($fromClause): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkFromClause($fromClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkFunction($function): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkFunction($function);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkOrderByClause($orderByClause): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkOrderByClause($orderByClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkOrderByItem($orderByItem): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkOrderByItem($orderByItem);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkHavingClause($havingClause): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkHavingClause($havingClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkJoin($join): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkJoin($join);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkSelectExpression($selectExpression): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkSelectExpression($selectExpression);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkQuantifiedExpression($qExpr): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkQuantifiedExpression($qExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkSubselect($subselect): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkSubselect($subselect);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkSubselectFromClause($subselectFromClause): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkSubselectFromClause($subselectFromClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkSimpleSelectClause($simpleSelectClause): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkSimpleSelectClause($simpleSelectClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkSimpleSelectExpression($simpleSelectExpression): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkSimpleSelectExpression($simpleSelectExpression);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkAggregateExpression($aggExpression): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkAggregateExpression($aggExpression);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkGroupByClause($groupByClause): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkGroupByClause($groupByClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkGroupByItem($groupByItem): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkGroupByItem($groupByItem);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkUpdateStatement(AST\UpdateStatement $AST): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkUpdateStatement($AST);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkDeleteStatement(AST\DeleteStatement $AST): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkDeleteStatement($AST);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkDeleteClause(AST\DeleteClause $deleteClause): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkDeleteClause($deleteClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkUpdateClause($updateClause): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkUpdateClause($updateClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkUpdateItem($updateItem): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkUpdateItem($updateItem);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkWhereClause($whereClause): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkWhereClause($whereClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalExpression($condExpr): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkConditionalExpression($condExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalTerm($condTerm): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkConditionalTerm($condTerm);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalFactor($factor): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkConditionalFactor($factor);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalPrimary($condPrimary): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkConditionalPrimary($condPrimary);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkExistsExpression($existsExpr): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkExistsExpression($existsExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkCollectionMemberExpression($collMemberExpr): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkCollectionMemberExpression($collMemberExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkEmptyCollectionComparisonExpression($emptyCollCompExpr): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkEmptyCollectionComparisonExpression($emptyCollCompExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkNullComparisonExpression($nullCompExpr): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkNullComparisonExpression($nullCompExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkInExpression($inExpr): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkInExpression($inExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkInstanceOfExpression($instanceOfExpr): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkInstanceOfExpression($instanceOfExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkLiteral($literal): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkLiteral($literal);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkBetweenExpression($betweenExpr): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkBetweenExpression($betweenExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkLikeExpression($likeExpr): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkLikeExpression($likeExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkStateFieldPathExpression($stateFieldPathExpression): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkStateFieldPathExpression($stateFieldPathExpression);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkComparisonExpression($compExpr): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkComparisonExpression($compExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkInputParameter($inputParam): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkInputParameter($inputParam);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkArithmeticExpression($arithmeticExpr): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkArithmeticExpression($arithmeticExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkArithmeticTerm($term): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkArithmeticTerm($term);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkStringPrimary($stringPrimary): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkStringPrimary($stringPrimary);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkArithmeticFactor($factor): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkArithmeticFactor($factor);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkSimpleArithmeticExpression($simpleArithmeticExpr): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkSimpleArithmeticExpression($simpleArithmeticExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkPathExpression($pathExpr): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkPathExpression($pathExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkResultVariable($resultVariable): void
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkResultVariable($resultVariable);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutor($AST): void
    {
    }
}
