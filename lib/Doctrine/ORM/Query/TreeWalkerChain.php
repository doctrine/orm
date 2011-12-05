<?php
/*
 *  $Id$
 *
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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Query;

/**
 * Represents a chain of tree walkers that modify an AST and finally emit output.
 * Only the last walker in the chain can emit output. Any previous walkers can modify
 * the AST to influence the final output produced by the last walker.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
class TreeWalkerChain implements TreeWalker
{
    /** The tree walkers. */
    private $_walkers = array();
    /** The original Query. */
    private $_query;
    /** The ParserResult of the original query that was produced by the Parser. */
    private $_parserResult;
    /** The query components of the original query (the "symbol table") that was produced by the Parser. */
    private $_queryComponents;

    /**
     * @inheritdoc
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
     */
    public function addTreeWalker($walkerClass)
    {
        $this->_walkers[] = new $walkerClass($this->_query, $this->_parserResult, $this->_queryComponents);
    }

    /**
     * Walks down a SelectStatement AST node, thereby generating the appropriate SQL.
     *
     * @return string The SQL.
     */
    public function walkSelectStatement(AST\SelectStatement $AST)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkSelectStatement($AST);
        }
    }

    /**
     * Walks down a SelectClause AST node, thereby generating the appropriate SQL.
     *
     * @return string The SQL.
     */
    public function walkSelectClause($selectClause)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkSelectClause($selectClause);
        }
    }

    /**
     * Walks down a FromClause AST node, thereby generating the appropriate SQL.
     *
     * @return string The SQL.
     */
    public function walkFromClause($fromClause)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkFromClause($fromClause);
        }
    }

    /**
     * Walks down a FunctionNode AST node, thereby generating the appropriate SQL.
     *
     * @return string The SQL.
     */
    public function walkFunction($function)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkFunction($function);
        }
    }

    /**
     * Walks down an OrderByClause AST node, thereby generating the appropriate SQL.
     *
     * @param OrderByClause
     * @return string The SQL.
     */
    public function walkOrderByClause($orderByClause)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkOrderByClause($orderByClause);
        }
    }

    /**
     * Walks down an OrderByItem AST node, thereby generating the appropriate SQL.
     *
     * @param OrderByItem
     * @return string The SQL.
     */
    public function walkOrderByItem($orderByItem)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkOrderByItem($orderByItem);
        }
    }

    /**
     * Walks down a HavingClause AST node, thereby generating the appropriate SQL.
     *
     * @param HavingClause
     * @return string The SQL.
     */
    public function walkHavingClause($havingClause)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkHavingClause($havingClause);
        }
    }

    /**
     * Walks down a JoinVariableDeclaration AST node and creates the corresponding SQL.
     *
     * @param JoinVariableDeclaration $joinVarDecl
     * @return string The SQL.
     */
    public function walkJoinVariableDeclaration($joinVarDecl)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkJoinVariableDeclaration($joinVarDecl);
        }
    }

    /**
     * Walks down a SelectExpression AST node and generates the corresponding SQL.
     *
     * @param SelectExpression $selectExpression
     * @return string The SQL.
     */
    public function walkSelectExpression($selectExpression)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkSelectExpression($selectExpression);
        }
    }

    /**
     * Walks down a QuantifiedExpression AST node, thereby generating the appropriate SQL.
     *
     * @param QuantifiedExpression
     * @return string The SQL.
     */
    public function walkQuantifiedExpression($qExpr)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkQuantifiedExpression($qExpr);
        }
    }

    /**
     * Walks down a Subselect AST node, thereby generating the appropriate SQL.
     *
     * @param Subselect
     * @return string The SQL.
     */
    public function walkSubselect($subselect)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkSubselect($subselect);
        }
    }

    /**
     * Walks down a SubselectFromClause AST node, thereby generating the appropriate SQL.
     *
     * @param SubselectFromClause
     * @return string The SQL.
     */
    public function walkSubselectFromClause($subselectFromClause)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkSubselectFromClause($subselectFromClause);
        }
    }

    /**
     * Walks down a SimpleSelectClause AST node, thereby generating the appropriate SQL.
     *
     * @param SimpleSelectClause
     * @return string The SQL.
     */
    public function walkSimpleSelectClause($simpleSelectClause)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkSimpleSelectClause($simpleSelectClause);
        }
    }

    /**
     * Walks down a SimpleSelectExpression AST node, thereby generating the appropriate SQL.
     *
     * @param SimpleSelectExpression
     * @return string The SQL.
     */
    public function walkSimpleSelectExpression($simpleSelectExpression)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkSimpleSelectExpression($simpleSelectExpression);
        }
    }

    /**
     * Walks down an AggregateExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AggregateExpression
     * @return string The SQL.
     */
    public function walkAggregateExpression($aggExpression)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkAggregateExpression($aggExpression);
        }
    }

    /**
     * Walks down a GroupByClause AST node, thereby generating the appropriate SQL.
     *
     * @param GroupByClause
     * @return string The SQL.
     */
    public function walkGroupByClause($groupByClause)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkGroupByClause($groupByClause);
        }
    }

    /**
     * Walks down a GroupByItem AST node, thereby generating the appropriate SQL.
     *
     * @param GroupByItem
     * @return string The SQL.
     */
    public function walkGroupByItem($groupByItem)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkGroupByItem($groupByItem);
        }
    }

    /**
     * Walks down an UpdateStatement AST node, thereby generating the appropriate SQL.
     *
     * @param UpdateStatement
     * @return string The SQL.
     */
    public function walkUpdateStatement(AST\UpdateStatement $AST)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkUpdateStatement($AST);
        }
    }

    /**
     * Walks down a DeleteStatement AST node, thereby generating the appropriate SQL.
     *
     * @param DeleteStatement
     * @return string The SQL.
     */
    public function walkDeleteStatement(AST\DeleteStatement $AST)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkDeleteStatement($AST);
        }
    }

    /**
     * Walks down a DeleteClause AST node, thereby generating the appropriate SQL.
     *
     * @param DeleteClause
     * @return string The SQL.
     */
    public function walkDeleteClause(AST\DeleteClause $deleteClause)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkDeleteClause($deleteClause);
        }
    }

    /**
     * Walks down an UpdateClause AST node, thereby generating the appropriate SQL.
     *
     * @param UpdateClause
     * @return string The SQL.
     */
    public function walkUpdateClause($updateClause)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkUpdateClause($updateClause);
        }
    }

    /**
     * Walks down an UpdateItem AST node, thereby generating the appropriate SQL.
     *
     * @param UpdateItem
     * @return string The SQL.
     */
    public function walkUpdateItem($updateItem)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkUpdateItem($updateItem);
        }
    }

    /**
     * Walks down a WhereClause AST node, thereby generating the appropriate SQL.
     *
     * @param WhereClause
     * @return string The SQL.
     */
    public function walkWhereClause($whereClause)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkWhereClause($whereClause);
        }
    }

    /**
     * Walks down a ConditionalExpression AST node, thereby generating the appropriate SQL.
     *
     * @param ConditionalExpression
     * @return string The SQL.
     */
    public function walkConditionalExpression($condExpr)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkConditionalExpression($condExpr);
        }
    }

    /**
     * Walks down a ConditionalTerm AST node, thereby generating the appropriate SQL.
     *
     * @param ConditionalTerm
     * @return string The SQL.
     */
    public function walkConditionalTerm($condTerm)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkConditionalTerm($condTerm);
        }
    }

    /**
     * Walks down a ConditionalFactor AST node, thereby generating the appropriate SQL.
     *
     * @param ConditionalFactor
     * @return string The SQL.
     */
    public function walkConditionalFactor($factor)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkConditionalFactor($factor);
        }
    }

    /**
     * Walks down a ConditionalPrimary AST node, thereby generating the appropriate SQL.
     *
     * @param ConditionalPrimary
     * @return string The SQL.
     */
    public function walkConditionalPrimary($condPrimary)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkConditionalPrimary($condPrimary);
        }
    }

    /**
     * Walks down an ExistsExpression AST node, thereby generating the appropriate SQL.
     *
     * @param ExistsExpression
     * @return string The SQL.
     */
    public function walkExistsExpression($existsExpr)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkExistsExpression($existsExpr);
        }
    }

    /**
     * Walks down a CollectionMemberExpression AST node, thereby generating the appropriate SQL.
     *
     * @param CollectionMemberExpression
     * @return string The SQL.
     */
    public function walkCollectionMemberExpression($collMemberExpr)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkCollectionMemberExpression($collMemberExpr);
        }
    }

    /**
     * Walks down an EmptyCollectionComparisonExpression AST node, thereby generating the appropriate SQL.
     *
     * @param EmptyCollectionComparisonExpression
     * @return string The SQL.
     */
    public function walkEmptyCollectionComparisonExpression($emptyCollCompExpr)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkEmptyCollectionComparisonExpression($emptyCollCompExpr);
        }
    }

    /**
     * Walks down a NullComparisonExpression AST node, thereby generating the appropriate SQL.
     *
     * @param NullComparisonExpression
     * @return string The SQL.
     */
    public function walkNullComparisonExpression($nullCompExpr)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkNullComparisonExpression($nullCompExpr);
        }
    }

    /**
     * Walks down an InExpression AST node, thereby generating the appropriate SQL.
     *
     * @param InExpression
     * @return string The SQL.
     */
    public function walkInExpression($inExpr)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkInExpression($inExpr);
        }
    }

    /**
     * Walks down an InstanceOfExpression AST node, thereby generating the appropriate SQL.
     *
     * @param InstanceOfExpression
     * @return string The SQL.
     */
    function walkInstanceOfExpression($instanceOfExpr)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkInstanceOfExpression($instanceOfExpr);
        }
    }

    /**
     * Walks down a literal that represents an AST node, thereby generating the appropriate SQL.
     *
     * @param mixed
     * @return string The SQL.
     */
    public function walkLiteral($literal)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkLiteral($literal);
        }
    }

    /**
     * Walks down a BetweenExpression AST node, thereby generating the appropriate SQL.
     *
     * @param BetweenExpression
     * @return string The SQL.
     */
    public function walkBetweenExpression($betweenExpr)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkBetweenExpression($betweenExpr);
        }
    }

    /**
     * Walks down a LikeExpression AST node, thereby generating the appropriate SQL.
     *
     * @param LikeExpression
     * @return string The SQL.
     */
    public function walkLikeExpression($likeExpr)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkLikeExpression($likeExpr);
        }
    }

    /**
     * Walks down a StateFieldPathExpression AST node, thereby generating the appropriate SQL.
     *
     * @param StateFieldPathExpression
     * @return string The SQL.
     */
    public function walkStateFieldPathExpression($stateFieldPathExpression)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkStateFieldPathExpression($stateFieldPathExpression);
        }
    }

    /**
     * Walks down a ComparisonExpression AST node, thereby generating the appropriate SQL.
     *
     * @param ComparisonExpression
     * @return string The SQL.
     */
    public function walkComparisonExpression($compExpr)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkComparisonExpression($compExpr);
        }
    }

    /**
     * Walks down an InputParameter AST node, thereby generating the appropriate SQL.
     *
     * @param InputParameter
     * @return string The SQL.
     */
    public function walkInputParameter($inputParam)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkInputParameter($inputParam);
        }
    }

    /**
     * Walks down an ArithmeticExpression AST node, thereby generating the appropriate SQL.
     *
     * @param ArithmeticExpression
     * @return string The SQL.
     */
    public function walkArithmeticExpression($arithmeticExpr)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkArithmeticExpression($arithmeticExpr);
        }
    }

    /**
     * Walks down an ArithmeticTerm AST node, thereby generating the appropriate SQL.
     *
     * @param mixed
     * @return string The SQL.
     */
    public function walkArithmeticTerm($term)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkArithmeticTerm($term);
        }
    }

    /**
     * Walks down a StringPrimary that represents an AST node, thereby generating the appropriate SQL.
     *
     * @param mixed
     * @return string The SQL.
     */
    public function walkStringPrimary($stringPrimary)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkStringPrimary($stringPrimary);
        }
    }

    /**
     * Walks down an ArithmeticFactor that represents an AST node, thereby generating the appropriate SQL.
     *
     * @param mixed
     * @return string The SQL.
     */
    public function walkArithmeticFactor($factor)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkArithmeticFactor($factor);
        }
    }

    /**
     * Walks down an SimpleArithmeticExpression AST node, thereby generating the appropriate SQL.
     *
     * @param SimpleArithmeticExpression
     * @return string The SQL.
     */
    public function walkSimpleArithmeticExpression($simpleArithmeticExpr)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkSimpleArithmeticExpression($simpleArithmeticExpr);
        }
    }

    /**
     * Walks down an PathExpression AST node, thereby generating the appropriate SQL.
     *
     * @param mixed
     * @return string The SQL.
     */
    public function walkPathExpression($pathExpr)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkPathExpression($pathExpr);
        }
    }

    /**
     * Walks down an ResultVariable AST node, thereby generating the appropriate SQL.
     *
     * @param string $resultVariable
     * @return string The SQL.
     */
    public function walkResultVariable($resultVariable)
    {
        foreach ($this->_walkers as $walker) {
            $walker->walkResultVariable($resultVariable);
        }
    }

    /**
     * Gets an executor that can be used to execute the result of this walker.
     *
     * @return AbstractExecutor
     */
    public function getExecutor($AST)
    {}
}