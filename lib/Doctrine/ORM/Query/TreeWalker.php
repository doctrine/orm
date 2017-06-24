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
 * Interface for walkers of DQL ASTs (abstract syntax trees).
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
interface TreeWalker
{
    /**
     * Initializes TreeWalker with important information about the ASTs to be walked.
     *
     * @param \Doctrine\ORM\AbstractQuery      $query           The parsed Query.
     * @param \Doctrine\ORM\Query\ParserResult $parserResult    The result of the parsing process.
     * @param array                            $queryComponents The query components (symbol table).
     */
    public function __construct(\Doctrine\ORM\AbstractQuery $query, \Doctrine\ORM\Query\ParserResult $parserResult, array $queryComponents);

    /**
     * Returns internal queryComponents array.
     *
     * @return array
     */
    public function getQueryComponents(): array;

    /**
     * Sets or overrides a query component for a given dql alias.
     *
     * @param string $dqlAlias       The DQL alias.
     * @param array  $queryComponent
     *
     * @return void
     */
    public function setQueryComponent(string $dqlAlias, array $queryComponent): void;

    /**
     * Walks down a SelectStatement AST node, thereby generating the appropriate SQL.
     *
     * @param AST\SelectStatement $AST
     *
     * @return string The SQL.
     */
    function walkSelectStatement(AST\SelectStatement $AST): string;

    /**
     * Walks down a SelectClause AST node, thereby generating the appropriate SQL.
     *
     * @param AST\SelectClause $selectClause
     *
     * @return string The SQL.
     */
    function walkSelectClause(AST\SelectClause $selectClause): string;

    /**
     * Walks down a FromClause AST node, thereby generating the appropriate SQL.
     *
     * @param AST\FromClause $fromClause
     *
     * @return string The SQL.
     */
    function walkFromClause(AST\FromClause $fromClause): string;

    /**
     * Walks down a FunctionNode AST node, thereby generating the appropriate SQL.
     *
     * @param AST\Functions\FunctionNode $function
     *
     * @return string The SQL.
     */
    function walkFunction(AST\Functions\FunctionNode $function): string;

    /**
     * Walks down an OrderByClause AST node, thereby generating the appropriate SQL.
     *
     * @param AST\OrderByClause $orderByClause
     *
     * @return string The SQL.
     */
    function walkOrderByClause(AST\OrderByClause $orderByClause): string;

    /**
     * Walks down an OrderByItem AST node, thereby generating the appropriate SQL.
     *
     * @param AST\OrderByItem $orderByItem
     *
     * @return string The SQL.
     */
    function walkOrderByItem(AST\OrderByItem $orderByItem): string;

    /**
     * Walks down a HavingClause AST node, thereby generating the appropriate SQL.
     *
     * @param AST\HavingClause $havingClause
     *
     * @return string The SQL.
     */
    function walkHavingClause(AST\HavingClause $havingClause): string;

    /**
     * Walks down a Join AST node and creates the corresponding SQL.
     *
     * @param AST\Join $join
     *
     * @return string The SQL.
     */
    function walkJoin(AST\Join $join): string;

    /**
     * Walks down a SelectExpression AST node and generates the corresponding SQL.
     *
     * @param AST\SelectExpression $selectExpression
     *
     * @return string The SQL.
     */
    function walkSelectExpression(AST\SelectExpression $selectExpression): string;

    /**
     * Walks down a QuantifiedExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\QuantifiedExpression $qExpr
     *
     * @return string The SQL.
     */
    function walkQuantifiedExpression(AST\QuantifiedExpression $qExpr): string;

    /**
     * Walks down a Subselect AST node, thereby generating the appropriate SQL.
     *
     * @param AST\Subselect $subselect
     *
     * @return string The SQL.
     */
    function walkSubselect(AST\Subselect $subselect): string;

    /**
     * Walks down a SubselectFromClause AST node, thereby generating the appropriate SQL.
     *
     * @param AST\SubselectFromClause $subselectFromClause
     *
     * @return string The SQL.
     */
    function walkSubselectFromClause(AST\SubselectFromClause $subselectFromClause): string;

    /**
     * Walks down a SimpleSelectClause AST node, thereby generating the appropriate SQL.
     *
     * @param AST\SimpleSelectClause $simpleSelectClause
     *
     * @return string The SQL.
     */
    function walkSimpleSelectClause(AST\SimpleSelectClause $simpleSelectClause): string;

    /**
     * Walks down a SimpleSelectExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\SimpleSelectExpression $simpleSelectExpression
     *
     * @return string The SQL.
     */
    function walkSimpleSelectExpression(AST\SimpleSelectExpression $simpleSelectExpression): string;

    /**
     * Walks down an AggregateExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\AggregateExpression $aggExpression
     *
     * @return string The SQL.
     */
    function walkAggregateExpression(AST\AggregateExpression $aggExpression): string;

    /**
     * Walks down a GroupByClause AST node, thereby generating the appropriate SQL.
     *
     * @param AST\GroupByClause $groupByClause
     *
     * @return string The SQL.
     */
    function walkGroupByClause(AST\GroupByClause $groupByClause): string;

    /**
     * Walks down a GroupByItem AST node, thereby generating the appropriate SQL.
     *
     * @param AST\PathExpression|string $groupByItem
     *
     * @return string The SQL.
     */
    function walkGroupByItem($groupByItem): string;

    /**
     * Walks down an UpdateStatement AST node, thereby generating the appropriate SQL.
     *
     * @param AST\UpdateStatement $AST
     *
     * @return string The SQL.
     */
    function walkUpdateStatement(AST\UpdateStatement $AST): string;

    /**
     * Walks down a DeleteStatement AST node, thereby generating the appropriate SQL.
     *
     * @param AST\DeleteStatement $AST
     *
     * @return string The SQL.
     */
    function walkDeleteStatement(AST\DeleteStatement $AST): string;

    /**
     * Walks down a DeleteClause AST node, thereby generating the appropriate SQL.
     *
     * @param AST\DeleteClause $deleteClause
     *
     * @return string The SQL.
     */
    function walkDeleteClause(AST\DeleteClause $deleteClause): string;

    /**
     * Walks down an UpdateClause AST node, thereby generating the appropriate SQL.
     *
     * @param AST\UpdateClause $updateClause
     *
     * @return string The SQL.
     */
    function walkUpdateClause(AST\UpdateClause $updateClause): string;

    /**
     * Walks down an UpdateItem AST node, thereby generating the appropriate SQL.
     *
     * @param AST\UpdateItem $updateItem
     *
     * @return string The SQL.
     */
    function walkUpdateItem(AST\UpdateItem $updateItem): string;

    /**
     * Walks down a WhereClause AST node, thereby generating the appropriate SQL.
     * WhereClause or not, the appropriate discriminator sql is added.
     *
     * @param AST\WhereClause $whereClause
     *
     * @return string The SQL.
     */
    function walkWhereClause(AST\WhereClause $whereClause): string;

    /**
     * Walk down a ConditionalExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\ConditionalExpression $condExpr
     *
     * @return string The SQL.
     */
    function walkConditionalExpression(AST\ConditionalExpression $condExpr): string;

    /**
     * Walks down a ConditionalTerm AST node, thereby generating the appropriate SQL.
     *
     * @param AST\ConditionalTerm $condTerm
     *
     * @return string The SQL.
     */
    function walkConditionalTerm(AST\ConditionalTerm $condTerm): string;

    /**
     * Walks down a ConditionalFactor AST node, thereby generating the appropriate SQL.
     *
     * @param AST\ConditionalFactor $factor
     *
     * @return string The SQL.
     */
    function walkConditionalFactor(AST\ConditionalFactor $factor): string;

    /**
     * Walks down a ConditionalPrimary AST node, thereby generating the appropriate SQL.
     *
     * @param AST\ConditionalPrimary $primary
     *
     * @return string The SQL.
     */
    function walkConditionalPrimary(AST\ConditionalPrimary $primary): string;

    /**
     * Walks down an ExistsExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\ExistsExpression $existsExpr
     *
     * @return string The SQL.
     */
    function walkExistsExpression(AST\ExistsExpression $existsExpr): string;

    /**
     * Walks down a CollectionMemberExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\CollectionMemberExpression $collMemberExpr
     *
     * @return string The SQL.
     */
    function walkCollectionMemberExpression(AST\CollectionMemberExpression $collMemberExpr): string;

    /**
     * Walks down an EmptyCollectionComparisonExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\EmptyCollectionComparisonExpression $emptyCollCompExpr
     *
     * @return string The SQL.
     */
    function walkEmptyCollectionComparisonExpression(AST\EmptyCollectionComparisonExpression $emptyCollCompExpr): string;

    /**
     * Walks down a NullComparisonExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\NullComparisonExpression $nullCompExpr
     *
     * @return string The SQL.
     */
    function walkNullComparisonExpression(AST\NullComparisonExpression $nullCompExpr): string;

    /**
     * Walks down an InExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\InExpression $inExpr
     *
     * @return string The SQL.
     */
    function walkInExpression(AST\InExpression $inExpr): string;

    /**
     * Walks down an InstanceOfExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\InstanceOfExpression $instanceOfExpr
     *
     * @return string The SQL.
     */
    function walkInstanceOfExpression(AST\InstanceOfExpression $instanceOfExpr): string;

    /**
     * Walks down a literal that represents an AST node, thereby generating the appropriate SQL.
     *
     * @param mixed $literal
     *
     * @return string The SQL.
     */
    function walkLiteral($literal): string;

    /**
     * Walks down a BetweenExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\BetweenExpression $betweenExpr
     *
     * @return string The SQL.
     */
    function walkBetweenExpression(AST\BetweenExpression $betweenExpr): string;

    /**
     * Walks down a LikeExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\LikeExpression $likeExpr
     *
     * @return string The SQL.
     */
    function walkLikeExpression(AST\LikeExpression $likeExpr): string;

    /**
     * Walks down a StateFieldPathExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\PathExpression $stateFieldPathExpression
     *
     * @return string The SQL.
     */
    function walkStateFieldPathExpression(AST\PathExpression $stateFieldPathExpression): string;

    /**
     * Walks down a ComparisonExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\ComparisonExpression $compExpr
     *
     * @return string The SQL.
     */
    function walkComparisonExpression(AST\ComparisonExpression $compExpr): string;

    /**
     * Walks down an InputParameter AST node, thereby generating the appropriate SQL.
     *
     * @param AST\InputParameter $inputParam
     *
     * @return string The SQL.
     */
    function walkInputParameter(AST\InputParameter $inputParam): string;

    /**
     * Walks down an ArithmeticExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\ArithmeticExpression $arithmeticExpr
     *
     * @return string The SQL.
     */
    function walkArithmeticExpression(AST\ArithmeticExpression $arithmeticExpr): string;

    /**
     * Walks down an ArithmeticTerm AST node, thereby generating the appropriate SQL.
     *
     * @param mixed $term
     *
     * @return string The SQL.
     */
    function walkArithmeticTerm($term): string;

    /**
     * Walks down a StringPrimary that represents an AST node, thereby generating the appropriate SQL.
     *
     * @param mixed $stringPrimary
     *
     * @return string The SQL.
     */
    function walkStringPrimary($stringPrimary): string;

    /**
     * Walks down an ArithmeticFactor that represents an AST node, thereby generating the appropriate SQL.
     *
     * @param mixed $factor
     *
     * @return string The SQL.
     */
    function walkArithmeticFactor($factor): string;

    /**
     * Walks down an SimpleArithmeticExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\SimpleArithmeticExpression $simpleArithmeticExpr
     *
     * @return string The SQL.
     */
    function walkSimpleArithmeticExpression(AST\SimpleArithmeticExpression $simpleArithmeticExpr): string;

    /**
     * Walks down a PathExpression AST node, thereby generating the appropriate SQL.
     *
     * @param mixed $pathExpr
     *
     * @return string The SQL.
     */
    function walkPathExpression($pathExpr): string;

    /**
     * Walks down a ResultVariable that represents an AST node, thereby generating the appropriate SQL.
     *
     * @param string $resultVariable
     *
     * @return string The SQL.
     */
    function walkResultVariable(string $resultVariable): string;

    /**
     * Gets an executor that can be used to execute the result of this walker.
     *
     * @param AST\DeleteStatement|AST\UpdateStatement|AST\SelectStatement $AST
     *
     * @return Exec\AbstractSqlExecutor
     */
    function getExecutor($AST): Exec\AbstractSqlExecutor;
}
