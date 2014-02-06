<?php
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
     * @var string[]
     */
    private $_walkersClasses = array();

    /**
     * The tree walkers.
     *
     * @var TreeWalker[]
     */
    private $_walkers = array();

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
    public function getQueryComponents()
    {
        return $this->_queryComponents;
    }

    /**
     * {@inheritdoc}
     */
    public function setQueryComponent($dqlAlias, array $queryComponent)
    {
        $requiredKeys = array('metadata', 'parent', 'relation', 'map', 'nestingLevel', 'token');

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
    }

    /**
     * Adds a tree walker to the chain.
     *
     * @param string $walkerClass The class of the walker to instantiate.
     *
     * @return void
     */
    public function addTreeWalker($walkerClass)
    {
        $this->_walkersClasses[] = $walkerClass;
    }

    /**
     * {@inheritdoc}
     */
    public function walkSelectStatement(AST\SelectStatement $AST)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkSelectStatement($AST);

            $this->_queryComponents = $walker->getQueryComponents();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkSelectClause($selectClause)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkSelectClause($selectClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkFromClause($fromClause)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkFromClause($fromClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkFunction($function)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkFunction($function);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkOrderByClause($orderByClause)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkOrderByClause($orderByClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkOrderByItem($orderByItem)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkOrderByItem($orderByItem);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkHavingClause($havingClause)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkHavingClause($havingClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkJoin($join)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkJoin($join);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkSelectExpression($selectExpression)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkSelectExpression($selectExpression);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkQuantifiedExpression($qExpr)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkQuantifiedExpression($qExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkSubselect($subselect)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkSubselect($subselect);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkSubselectFromClause($subselectFromClause)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkSubselectFromClause($subselectFromClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkSimpleSelectClause($simpleSelectClause)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkSimpleSelectClause($simpleSelectClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkSimpleSelectExpression($simpleSelectExpression)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkSimpleSelectExpression($simpleSelectExpression);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkAggregateExpression($aggExpression)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkAggregateExpression($aggExpression);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkGroupByClause($groupByClause)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkGroupByClause($groupByClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkGroupByItem($groupByItem)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkGroupByItem($groupByItem);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkUpdateStatement(AST\UpdateStatement $AST)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkUpdateStatement($AST);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkDeleteStatement(AST\DeleteStatement $AST)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkDeleteStatement($AST);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkDeleteClause(AST\DeleteClause $deleteClause)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkDeleteClause($deleteClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkUpdateClause($updateClause)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkUpdateClause($updateClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkUpdateItem($updateItem)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkUpdateItem($updateItem);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkWhereClause($whereClause)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkWhereClause($whereClause);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalExpression($condExpr)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkConditionalExpression($condExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalTerm($condTerm)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkConditionalTerm($condTerm);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalFactor($factor)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkConditionalFactor($factor);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalPrimary($condPrimary)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkConditionalPrimary($condPrimary);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkExistsExpression($existsExpr)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkExistsExpression($existsExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkCollectionMemberExpression($collMemberExpr)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkCollectionMemberExpression($collMemberExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkEmptyCollectionComparisonExpression($emptyCollCompExpr)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkEmptyCollectionComparisonExpression($emptyCollCompExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkNullComparisonExpression($nullCompExpr)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkNullComparisonExpression($nullCompExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkInExpression($inExpr)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkInExpression($inExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    function walkInstanceOfExpression($instanceOfExpr)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkInstanceOfExpression($instanceOfExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkLiteral($literal)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkLiteral($literal);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkBetweenExpression($betweenExpr)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkBetweenExpression($betweenExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkLikeExpression($likeExpr)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkLikeExpression($likeExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkStateFieldPathExpression($stateFieldPathExpression)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkStateFieldPathExpression($stateFieldPathExpression);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkComparisonExpression($compExpr)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkComparisonExpression($compExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkInputParameter($inputParam)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkInputParameter($inputParam);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkArithmeticExpression($arithmeticExpr)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkArithmeticExpression($arithmeticExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkArithmeticTerm($term)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walkerClass->walkArithmeticTerm($term);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkStringPrimary($stringPrimary)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkStringPrimary($stringPrimary);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkArithmeticFactor($factor)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkArithmeticFactor($factor);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkSimpleArithmeticExpression($simpleArithmeticExpr)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkSimpleArithmeticExpression($simpleArithmeticExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkPathExpression($pathExpr)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkPathExpression($pathExpr);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkResultVariable($resultVariable)
    {
        foreach ($this->_walkersClasses as $walkerClass) {
            $walker = $this->getWalker($walkerClass);
            $walker->walkResultVariable($resultVariable);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutor($AST)
    {
    }

    /**
     * @param $walkerClass
     *
     * @return TreeWalker
     */
    private function getWalker($walkerClass)
    {
        $key = array_search($walkerClass, $this->_walkersClasses);
        if (!isset($this->_walkers[$key])) {
            $this->_walkers[$key] = new $walkerClass($this->_query, $this->_parserResult, $this->_queryComponents);
        }

        return $this->_walkers[$key];
    }
}
