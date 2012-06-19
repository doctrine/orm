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
 * Interface for walkers of DQL ASTs (abstract syntax trees).
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
interface TreeWalker
{
    /**
     * Initializes TreeWalker with important information about the ASTs to be walked
     *
     * @param Query $query The parsed Query.
     * @param ParserResult $parserResult The result of the parsing process.
     * @param array $queryComponents Query components (symbol table)
     */
    public function __construct($query, $parserResult, array $queryComponents);

    /**
     * Walks down a SelectStatement AST node, thereby generating the appropriate SQL.
     *
     * @return string The SQL.
     */
    function walkSelectStatement(AST\SelectStatement $AST);

    /**
     * Walks down a SelectClause AST node, thereby generating the appropriate SQL.
     *
     * @return string The SQL.
     */
    function walkSelectClause($selectClause);

    /**
     * Walks down a FromClause AST node, thereby generating the appropriate SQL.
     *
     * @return string The SQL.
     */
    function walkFromClause($fromClause);

    /**
     * Walks down a FunctionNode AST node, thereby generating the appropriate SQL.
     *
     * @return string The SQL.
     */
    function walkFunction($function);

    /**
     * Walks down an OrderByClause AST node, thereby generating the appropriate SQL.
     *
     * @param OrderByClause
     * @return string The SQL.
     */
    function walkOrderByClause($orderByClause);

    /**
     * Walks down an OrderByItem AST node, thereby generating the appropriate SQL.
     *
     * @param OrderByItem
     * @return string The SQL.
     */
    function walkOrderByItem($orderByItem);

    /**
     * Walks down a HavingClause AST node, thereby generating the appropriate SQL.
     *
     * @param HavingClause
     * @return string The SQL.
     */
    function walkHavingClause($havingClause);

    /**
     * Walks down a Join AST node and creates the corresponding SQL.
     *
     * @param Join $joinVarDecl
     * @return string The SQL.
     */
    function walkJoin($join);

    /**
     * Walks down a SelectExpression AST node and generates the corresponding SQL.
     *
     * @param SelectExpression $selectExpression
     * @return string The SQL.
     */
    function walkSelectExpression($selectExpression);

    /**
     * Walks down a QuantifiedExpression AST node, thereby generating the appropriate SQL.
     *
     * @param QuantifiedExpression
     * @return string The SQL.
     */
    function walkQuantifiedExpression($qExpr);

    /**
     * Walks down a Subselect AST node, thereby generating the appropriate SQL.
     *
     * @param Subselect
     * @return string The SQL.
     */
    function walkSubselect($subselect);

    /**
     * Walks down a SubselectFromClause AST node, thereby generating the appropriate SQL.
     *
     * @param SubselectFromClause
     * @return string The SQL.
     */
    function walkSubselectFromClause($subselectFromClause);

    /**
     * Walks down a SimpleSelectClause AST node, thereby generating the appropriate SQL.
     *
     * @param SimpleSelectClause
     * @return string The SQL.
     */
    function walkSimpleSelectClause($simpleSelectClause);

    /**
     * Walks down a SimpleSelectExpression AST node, thereby generating the appropriate SQL.
     *
     * @param SimpleSelectExpression
     * @return string The SQL.
     */
    function walkSimpleSelectExpression($simpleSelectExpression);

    /**
     * Walks down an AggregateExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AggregateExpression
     * @return string The SQL.
     */
    function walkAggregateExpression($aggExpression);

    /**
     * Walks down a GroupByClause AST node, thereby generating the appropriate SQL.
     *
     * @param GroupByClause
     * @return string The SQL.
     */
    function walkGroupByClause($groupByClause);

    /**
     * Walks down a GroupByItem AST node, thereby generating the appropriate SQL.
     *
     * @param GroupByItem
     * @return string The SQL.
     */
    function walkGroupByItem($groupByItem);

    /**
     * Walks down an UpdateStatement AST node, thereby generating the appropriate SQL.
     *
     * @param UpdateStatement
     * @return string The SQL.
     */
    function walkUpdateStatement(AST\UpdateStatement $AST);

    /**
     * Walks down a DeleteStatement AST node, thereby generating the appropriate SQL.
     *
     * @param DeleteStatement
     * @return string The SQL.
     */
    function walkDeleteStatement(AST\DeleteStatement $AST);

    /**
     * Walks down a DeleteClause AST node, thereby generating the appropriate SQL.
     *
     * @param DeleteClause
     * @return string The SQL.
     */
    function walkDeleteClause(AST\DeleteClause $deleteClause);

    /**
     * Walks down an UpdateClause AST node, thereby generating the appropriate SQL.
     *
     * @param UpdateClause
     * @return string The SQL.
     */
    function walkUpdateClause($updateClause);

    /**
     * Walks down an UpdateItem AST node, thereby generating the appropriate SQL.
     *
     * @param UpdateItem
     * @return string The SQL.
     */
    function walkUpdateItem($updateItem);

    /**
     * Walks down a WhereClause AST node, thereby generating the appropriate SQL.
     *
     * @param WhereClause
     * @return string The SQL.
     */
    function walkWhereClause($whereClause);

    /**
     * Walks down a ConditionalExpression AST node, thereby generating the appropriate SQL.
     *
     * @param ConditionalExpression
     * @return string The SQL.
     */
    function walkConditionalExpression($condExpr);

    /**
     * Walks down a ConditionalTerm AST node, thereby generating the appropriate SQL.
     *
     * @param ConditionalTerm
     * @return string The SQL.
     */
    function walkConditionalTerm($condTerm);

    /**
     * Walks down a ConditionalFactor AST node, thereby generating the appropriate SQL.
     *
     * @param ConditionalFactor
     * @return string The SQL.
     */
    function walkConditionalFactor($factor);

    /**
     * Walks down a ConditionalPrimary AST node, thereby generating the appropriate SQL.
     *
     * @param ConditionalPrimary
     * @return string The SQL.
     */
    function walkConditionalPrimary($primary);

    /**
     * Walks down an ExistsExpression AST node, thereby generating the appropriate SQL.
     *
     * @param ExistsExpression
     * @return string The SQL.
     */
    function walkExistsExpression($existsExpr);

    /**
     * Walks down a CollectionMemberExpression AST node, thereby generating the appropriate SQL.
     *
     * @param CollectionMemberExpression
     * @return string The SQL.
     */
    function walkCollectionMemberExpression($collMemberExpr);

    /**
     * Walks down an EmptyCollectionComparisonExpression AST node, thereby generating the appropriate SQL.
     *
     * @param EmptyCollectionComparisonExpression
     * @return string The SQL.
     */
    function walkEmptyCollectionComparisonExpression($emptyCollCompExpr);

    /**
     * Walks down a NullComparisonExpression AST node, thereby generating the appropriate SQL.
     *
     * @param NullComparisonExpression
     * @return string The SQL.
     */
    function walkNullComparisonExpression($nullCompExpr);

    /**
     * Walks down an InExpression AST node, thereby generating the appropriate SQL.
     *
     * @param InExpression
     * @return string The SQL.
     */
    function walkInExpression($inExpr);

    /**
     * Walks down an InstanceOfExpression AST node, thereby generating the appropriate SQL.
     *
     * @param InstanceOfExpression
     * @return string The SQL.
     */
    function walkInstanceOfExpression($instanceOfExpr);

    /**
     * Walks down a literal that represents an AST node, thereby generating the appropriate SQL.
     *
     * @param mixed
     * @return string The SQL.
     */
    function walkLiteral($literal);

    /**
     * Walks down a BetweenExpression AST node, thereby generating the appropriate SQL.
     *
     * @param BetweenExpression
     * @return string The SQL.
     */
    function walkBetweenExpression($betweenExpr);

    /**
     * Walks down a LikeExpression AST node, thereby generating the appropriate SQL.
     *
     * @param LikeExpression
     * @return string The SQL.
     */
    function walkLikeExpression($likeExpr);

    /**
     * Walks down a StateFieldPathExpression AST node, thereby generating the appropriate SQL.
     *
     * @param StateFieldPathExpression
     * @return string The SQL.
     */
    function walkStateFieldPathExpression($stateFieldPathExpression);

    /**
     * Walks down a ComparisonExpression AST node, thereby generating the appropriate SQL.
     *
     * @param ComparisonExpression
     * @return string The SQL.
     */
    function walkComparisonExpression($compExpr);

    /**
     * Walks down an InputParameter AST node, thereby generating the appropriate SQL.
     *
     * @param InputParameter
     * @return string The SQL.
     */
    function walkInputParameter($inputParam);

    /**
     * Walks down an ArithmeticExpression AST node, thereby generating the appropriate SQL.
     *
     * @param ArithmeticExpression
     * @return string The SQL.
     */
    function walkArithmeticExpression($arithmeticExpr);

    /**
     * Walks down an ArithmeticTerm AST node, thereby generating the appropriate SQL.
     *
     * @param mixed
     * @return string The SQL.
     */
    function walkArithmeticTerm($term);

    /**
     * Walks down a StringPrimary that represents an AST node, thereby generating the appropriate SQL.
     *
     * @param mixed
     * @return string The SQL.
     */
    function walkStringPrimary($stringPrimary);

    /**
     * Walks down an ArithmeticFactor that represents an AST node, thereby generating the appropriate SQL.
     *
     * @param mixed
     * @return string The SQL.
     */
    function walkArithmeticFactor($factor);

    /**
     * Walks down an SimpleArithmeticExpression AST node, thereby generating the appropriate SQL.
     *
     * @param SimpleArithmeticExpression
     * @return string The SQL.
     */
    function walkSimpleArithmeticExpression($simpleArithmeticExpr);

    /**
     * Walks down an PathExpression AST node, thereby generating the appropriate SQL.
     *
     * @param mixed
     * @return string The SQL.
     */
    function walkPathExpression($pathExpr);

    /**
     * Walks down an ResultVariable AST node, thereby generating the appropriate SQL.
     *
     * @param string $resultVariable
     * @return string The SQL.
     */
    function walkResultVariable($resultVariable);

    /**
     * Gets an executor that can be used to execute the result of this walker.
     *
     * @return AbstractExecutor
     */
    function getExecutor($AST);
}
