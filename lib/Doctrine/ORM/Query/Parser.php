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

use Doctrine\Common\DoctrineException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\Exec;

/**
 * An LL(*) parser for the context-free grammar of the Doctrine Query Language.
 * Parses a DQL query, reports any errors in it, and generates an AST.
 *
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 */
class Parser
{
    /** Maps registered string function names to class names. */
    private static $_STRING_FUNCTIONS = array(
        'concat' => 'Doctrine\ORM\Query\AST\Functions\ConcatFunction',
        'substring' => 'Doctrine\ORM\Query\AST\Functions\SubstringFunction',
        'trim' => 'Doctrine\ORM\Query\AST\Functions\TrimFunction',
        'lower' => 'Doctrine\ORM\Query\AST\Functions\LowerFunction',
        'upper' => 'Doctrine\ORM\Query\AST\Functions\UpperFunction'
    );

    /** Maps registered numeric function names to class names. */
    private static $_NUMERIC_FUNCTIONS = array(
        'length' => 'Doctrine\ORM\Query\AST\Functions\LengthFunction',
        'locate' => 'Doctrine\ORM\Query\AST\Functions\LocateFunction',
        'abs' => 'Doctrine\ORM\Query\AST\Functions\AbsFunction',
        'sqrt' => 'Doctrine\ORM\Query\AST\Functions\SqrtFunction',
        'mod' => 'Doctrine\ORM\Query\AST\Functions\ModFunction',
        'size' => 'Doctrine\ORM\Query\AST\Functions\SizeFunction'
    );

    /** Maps registered datetime function names to class names. */
    private static $_DATETIME_FUNCTIONS = array(
        'current_date' => 'Doctrine\ORM\Query\AST\Functions\CurrentDateFunction',
        'current_time' => 'Doctrine\ORM\Query\AST\Functions\CurrentTimeFunction',
        'current_timestamp' => 'Doctrine\ORM\Query\AST\Functions\CurrentTimestampFunction'
    );

    /**
     * The minimum number of tokens read after last detected error before
     * another error can be reported.
     *
     * @var int
     */
    //const MIN_ERROR_DISTANCE = 2;

    /**
     * Path expressions that were encountered during parsing of SelectExpressions
     * and still need to be validated.
     *
     * @var array
     */
    private $_deferredPathExpressionStacks = array();

    /**
     * The lexer.
     *
     * @var Doctrine\ORM\Query\Lexer
     */
    private $_lexer;

    /**
     * The parser result.
     *
     * @var Doctrine\ORM\Query\ParserResult
     */
    private $_parserResult;

    /**
     * The EntityManager.
     *
     * @var EnityManager
     */
    private $_em;

    /**
     * The Query to parse.
     *
     * @var Query
     */
    private $_query;

    /**
     * Map of declared query components in the parsed query.
     *
     * @var array
     */
    private $_queryComponents = array();
    
    /**
     * Sql tree walker
     *
     * @var SqlTreeWalker
     */
    private $_sqlTreeWalker;

    /**
     * Creates a new query parser object.
     *
     * @param Query $query The Query to parse.
     */
    public function __construct(Query $query)
    {
        $this->_query = $query;
        $this->_em = $query->getEntityManager();
        $this->_lexer = new Lexer($query->getDql());
        $this->_parserResult = new ParserResult;
    }

    /**
     * Attempts to match the given token with the current lookahead token.
     *
     * If they match, updates the lookahead token; otherwise raises a syntax
     * error.
     *
     * @param int|string token type or value
     * @return bool True, if tokens match; false otherwise.
     */
    public function match($token)
    {
        if (is_string($token)) {
            $isMatch = ($this->_lexer->lookahead['value'] === $token);
        } else {
            $isMatch = ($this->_lexer->lookahead['type'] === $token);
        }

        if ( ! $isMatch) {
            $this->syntaxError($this->_lexer->getLiteral($token));
        }

        $this->_lexer->moveNext();
    }

    /**
     * Free this parser enabling it to be reused
     *
     * @param boolean $deep     Whether to clean peek and reset errors
     * @param integer $position Position to reset
     */
    public function free($deep = false, $position = 0)
    {
        // WARNING! Use this method with care. It resets the scanner!
        $this->_lexer->resetPosition($position);

        // Deep = true cleans peek and also any previously defined errors
        if ($deep) {
            $this->_lexer->resetPeek();
            //$this->_errors = array();
        }

        $this->_lexer->token = null;
        $this->_lexer->lookahead = null;
        //$this->_errorDistance = self::MIN_ERROR_DISTANCE;
    }

    /**
     * Parses a query string.
     *
     * @return ParserResult
     */
    public function parse()
    {
        // Parse & build AST
        $AST = $this->QueryLanguage();

        // Check for end of string
        if ($this->_lexer->lookahead !== null) {
            //var_dump($this->_lexer->lookahead);
            $this->syntaxError('end of string');
        }

        // Create SqlWalker who creates the SQL from the AST
        $sqlWalker = $this->_sqlTreeWalker ?: new SqlWalker(
            $this->_query, $this->_parserResult, $this->_queryComponents
        );

        // Assign an SQL executor to the parser result
        $this->_parserResult->setSqlExecutor(Exec\AbstractExecutor::create($AST, $sqlWalker));

        return $this->_parserResult;
    }

    /**
     * Gets the lexer used by the parser.
     *
     * @return Doctrine\ORM\Query\Lexer
     */
    public function getLexer()
    {
        return $this->_lexer;
    }

    /**
     * Gets the ParserResult that is being filled with information during parsing.
     *
     * @return Doctrine\ORM\Query\ParserResult
     */
    public function getParserResult()
    {
        return $this->_parserResult;
    }

    /**
     * Generates a new syntax error.
     *
     * @param string $expected Optional expected string.
     * @param array $token Optional token.
     */
    public function syntaxError($expected = '', $token = null)
    {
        if ($token === null) {
            $token = $this->_lexer->lookahead;
        }

        $message = 'line 0, col ' . (isset($token['position']) ? $token['position'] : '-1') . ': Error: ';

        if ($expected !== '') {
            $message .= "Expected '$expected', got ";
        } else {
            $message .= 'Unexpected ';
        }

        if ($this->_lexer->lookahead === null) {
            $message .= 'end of string.';
        } else {
            $message .= "'{$this->_lexer->lookahead['value']}'";
        }

        throw QueryException::syntaxError($message);
    }

    /**
     * Generates a new semantical error.
     *
     * @param string $message Optional message.
     * @param array $token Optional token.
     */
    public function semanticalError($message = '', $token = null)
    {
        if ($token === null) {
            $token = $this->_lexer->lookahead;
        }
        
        // Find a position of a final word to display in error string
        $dql = $this->_query->getDql();
        $pos = strpos($dql, ' ', $token['position'] + 10);
        $length = ($pos !== false) ? $pos - $token['position'] : 10;
        
        // Building informative message
        $message = 'line 0, col ' . (isset($token['position']) ? $token['position'] : '-1') 
                 . " near '" . substr($dql, $token['position'], $length) . "'): Error: " . $message;

        throw QueryException::semanticalError($message);
    }

    /**
     * Logs new error entry.
     *
     * @param string $message Message to log.
     * @param array $token Token that it was processing.
     */
    /*protected function _logError($message = '', $token)
     {
         if ($this->_errorDistance >= self::MIN_ERROR_DISTANCE) {
             $message = 'line 0, col ' . $token['position'] . ': ' . $message;
             $this->_errors[] = $message;
         }

         $this->_errorDistance = 0;
     }*/

    /**
     * Gets the EntityManager used by the parser.
     *
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->_em;
    }

    /**
     * Checks if the next-next (after lookahead) token starts a function.
     *
     * @return boolean
     */
    private function _isFunction()
    {
        $next = $this->_lexer->glimpse();
        return $next['value'] === '(';
    }

    /**
     * Checks whether the next 2 tokens start a subselect.
     *
     * @return boolean TRUE if the next 2 tokens start a subselect, FALSE otherwise.
     */
    private function _isSubselect()
    {
        $la = $this->_lexer->lookahead;
        $next = $this->_lexer->glimpse();

        return ($la['value'] === '(' && $next['type'] === Lexer::T_SELECT);
    }

    /**
     * QueryLanguage ::= SelectStatement | UpdateStatement | DeleteStatement
     */
    public function QueryLanguage()
    {
        $this->_lexer->moveNext();

        switch ($this->_lexer->lookahead['type']) {
            case Lexer::T_SELECT:
                return $this->SelectStatement();

            case Lexer::T_UPDATE:
                return $this->UpdateStatement();

            case Lexer::T_DELETE:
                return $this->DeleteStatement();

            default:
                $this->syntaxError('SELECT, UPDATE or DELETE');
                break;
        }
    }

    /**
     * SelectStatement ::= SelectClause FromClause [WhereClause] [GroupByClause] [HavingClause] [OrderByClause]
     */
    public function SelectStatement()
    {
        $this->_beginDeferredPathExpressionStack();
        $selectClause = $this->SelectClause();
        $fromClause = $this->FromClause();
        $this->_processDeferredPathExpressionStack();

        $whereClause = $this->_lexer->isNextToken(Lexer::T_WHERE)
            ? $this->WhereClause() : null;

        $groupByClause = $this->_lexer->isNextToken(Lexer::T_GROUP)
            ? $this->GroupByClause() : null;

        $havingClause = $this->_lexer->isNextToken(Lexer::T_HAVING)
            ? $this->HavingClause() : null;

        $orderByClause = $this->_lexer->isNextToken(Lexer::T_ORDER)
            ? $this->OrderByClause() : null;

        return new AST\SelectStatement(
            $selectClause, $fromClause, $whereClause, $groupByClause, $havingClause, $orderByClause
        );
    }

    /**
     * Begins a new stack of deferred path expressions.
     */
    private function _beginDeferredPathExpressionStack()
    {
        $this->_deferredPathExpressionStacks[] = array();
    }

    /**
     * Processes the topmost stack of deferred path expressions.
     * These will be validated to make sure they are indeed
     * valid <tt>StateFieldPathExpression</tt>s and additional information
     * is attached to their AST nodes.
     */
    private function _processDeferredPathExpressionStack()
    {
        $exprStack = array_pop($this->_deferredPathExpressionStacks);
        $qComps = $this->_queryComponents;

        foreach ($exprStack as $expr) {
            switch ($expr->getType()) {
                case AST\PathExpression::TYPE_STATE_FIELD:
                    $this->_validateStateFieldPathExpression($expr);
                    break;

                case AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION:
                    $this->_validateSingleValuedAssociationPathExpression($expr);
                    break;

                case AST\PathExpression::TYPE_COLLECTION_VALUED_ASSOCIATION:
                    $this->_validateCollectionValuedAssociationPathExpression($expr);
                    break;
                    
                case AST\PathExpression::TYPE_SINGLE_VALUED_PATH_EXPRESSION:
                	$this->_validateSingleValuedPathExpression($expr);
                	break;

                default:
                    $this->semanticalError('Encountered invalid PathExpression.');
                    break;
            }
        }
    }

    /**
     * UpdateStatement ::= UpdateClause [WhereClause]
     */
    public function UpdateStatement()
    {
        $updateStatement = new AST\UpdateStatement($this->UpdateClause());
        $updateStatement->setWhereClause(
            $this->_lexer->isNextToken(Lexer::T_WHERE) ? $this->WhereClause() : null
        );

        return $updateStatement;
    }

    /**
     * UpdateClause ::= "UPDATE" AbstractSchemaName [["AS"] AliasIdentificationVariable] "SET" UpdateItem {"," UpdateItem}*
     */
    public function UpdateClause()
    {
        $this->match(Lexer::T_UPDATE);
        $abstractSchemaName = $this->AbstractSchemaName();
        $aliasIdentificationVariable = null;

        if ($this->_lexer->isNextToken(Lexer::T_AS)) {
            $this->match(Lexer::T_AS);
        }

        if ($this->_lexer->isNextToken(Lexer::T_IDENTIFIER)) {
            $this->match(Lexer::T_IDENTIFIER);
            $aliasIdentificationVariable = $this->_lexer->token['value'];
        } else {
            $aliasIdentificationVariable = $abstractSchemaName;
        }

        $this->match(Lexer::T_SET);
        $updateItems = array();
        $updateItems[] = $this->UpdateItem();

        while ($this->_lexer->isNextToken(',')) {
            $this->match(',');
            $updateItems[] = $this->UpdateItem();
        }

        $classMetadata = $this->_em->getClassMetadata($abstractSchemaName);

        // Building queryComponent
        $queryComponent = array(
            'metadata' => $classMetadata,
            'parent'   => null,
            'relation' => null,
            'map'      => null
        );
        $this->_queryComponents[$aliasIdentificationVariable] = $queryComponent;

        $updateClause = new AST\UpdateClause($abstractSchemaName, $updateItems);
        $updateClause->setAliasIdentificationVariable($aliasIdentificationVariable);

        return $updateClause;
    }

    /**
     * UpdateItem ::= [IdentificationVariable "."] {StateField | SingleValuedAssociationField} "=" NewValue
     */
    public function UpdateItem()
    {
        $peek = $this->_lexer->glimpse();
        $identVariable = null;

        if ($peek['value'] == '.') {
            $this->match(Lexer::T_IDENTIFIER);
            $identVariable = $this->_lexer->token['value'];
            $this->match('.');
        } else {
            throw QueryException::missingAliasQualifier();
        }

        $this->match(Lexer::T_IDENTIFIER);
        $field = $this->_lexer->token['value'];
        $this->match('=');
        $newValue = $this->NewValue();

        $updateItem = new AST\UpdateItem($field, $newValue);
        $updateItem->setIdentificationVariable($identVariable);

        return $updateItem;
    }

    /**
     * NewValue ::= SimpleArithmeticExpression | StringPrimary | DatetimePrimary | BooleanPrimary |
     *      EnumPrimary | SimpleEntityExpression | "NULL"
     *
     * @todo Implementation still incomplete.
     */
    public function NewValue()
    {
        if ($this->_lexer->isNextToken(Lexer::T_NULL)) {
            $this->match(Lexer::T_NULL);
            return null;
        } else if ($this->_lexer->isNextToken(Lexer::T_INPUT_PARAMETER)) {
            $this->match(Lexer::T_INPUT_PARAMETER);
            return new AST\InputParameter($this->_lexer->token['value']);
        } else if ($this->_lexer->isNextToken(Lexer::T_STRING)) {
            //TODO: Can be StringPrimary or EnumPrimary
            return $this->StringPrimary();
        } else {
            $this->syntaxError('Not yet implemented-1.');
        }
    }

    /**
     * DeleteStatement ::= DeleteClause [WhereClause]
     */
    public function DeleteStatement()
    {
        $deleteStatement = new AST\DeleteStatement($this->DeleteClause());
        $deleteStatement->setWhereClause(
            $this->_lexer->isNextToken(Lexer::T_WHERE) ? $this->WhereClause() : null
        );

        return $deleteStatement;
    }

    /**
     * DeleteClause ::= "DELETE" ["FROM"] AbstractSchemaName [["AS"] AliasIdentificationVariable]
     */
    public function DeleteClause()
    {
        $this->match(Lexer::T_DELETE);

        if ($this->_lexer->isNextToken(Lexer::T_FROM)) {
            $this->match(Lexer::T_FROM);
        }

        $deleteClause = new AST\DeleteClause($this->AbstractSchemaName());

        if ($this->_lexer->isNextToken(Lexer::T_AS)) {
            $this->match(Lexer::T_AS);
        }

        if ($this->_lexer->isNextToken(Lexer::T_IDENTIFIER)) {
            $this->match(Lexer::T_IDENTIFIER);
            $deleteClause->setAliasIdentificationVariable($this->_lexer->token['value']);
        } else {
            $deleteClause->setAliasIdentificationVariable($deleteClause->getAbstractSchemaName());
        }

        $classMetadata = $this->_em->getClassMetadata($deleteClause->getAbstractSchemaName());
        $queryComponent = array(
            'metadata' => $classMetadata
        );
        $this->_queryComponents[$deleteClause->getAliasIdentificationVariable()] = $queryComponent;

        return $deleteClause;
    }

    /**
     * SelectClause ::= "SELECT" ["DISTINCT"] SelectExpression {"," SelectExpression}
     */
    public function SelectClause()
    {
        $isDistinct = false;
        $this->match(Lexer::T_SELECT);

        // Check for DISTINCT
        if ($this->_lexer->isNextToken(Lexer::T_DISTINCT)) {
            $this->match(Lexer::T_DISTINCT);
            $isDistinct = true;
        }

        // Process SelectExpressions (1..N)
        $selectExpressions = array();
        $selectExpressions[] = $this->SelectExpression();

        while ($this->_lexer->isNextToken(',')) {
            $this->match(',');
            $selectExpressions[] = $this->SelectExpression();
        }

        return new AST\SelectClause($selectExpressions, $isDistinct);
    }

    /**
     * FromClause ::= "FROM" IdentificationVariableDeclaration {"," IdentificationVariableDeclaration}*
     */
    public function FromClause()
    {
        $this->match(Lexer::T_FROM);
        $identificationVariableDeclarations = array();
        $identificationVariableDeclarations[] = $this->IdentificationVariableDeclaration();

        while ($this->_lexer->isNextToken(',')) {
            $this->match(',');
            $identificationVariableDeclarations[] = $this->IdentificationVariableDeclaration();
        }

        return new AST\FromClause($identificationVariableDeclarations);
    }

    /**
     * SelectExpression ::=
     *      IdentificationVariable | StateFieldPathExpression |
     *      (AggregateExpression | "(" Subselect ")") [["AS"] FieldAliasIdentificationVariable] |
     *      Function
     */
    public function SelectExpression()
    {
        $expression = null;
        $fieldIdentificationVariable = null;
        $peek = $this->_lexer->glimpse();

        // First we recognize for an IdentificationVariable (DQL class alias)
        if ($peek['value'] != '.' && $peek['value'] != '(' && $this->_lexer->lookahead['type'] === Lexer::T_IDENTIFIER) {
            $expression = $this->IdentificationVariable();
        } else if (($isFunction = $this->_isFunction()) !== false || $this->_isSubselect()) {
            if ($isFunction) {
                if ($this->isAggregateFunction($this->_lexer->lookahead['type'])) {
                    $expression = $this->AggregateExpression();
                } else {
                    $expression = $this->_Function();
                }
            } else {
                $this->match('(');
                $expression = $this->Subselect();
                $this->match(')');
            }

            if ($this->_lexer->isNextToken(Lexer::T_AS)) {
                $this->match(Lexer::T_AS);
            }

            if ($this->_lexer->isNextToken(Lexer::T_IDENTIFIER)) {
                $this->match(Lexer::T_IDENTIFIER);
                $fieldIdentificationVariable = $this->_lexer->token['value'];
            }
        } else {
            // Deny hydration of partial objects if doctrine.forcePartialLoad query hint not defined 
            if (
                $this->_query->getHydrationMode() == Query::HYDRATE_OBJECT &&
                ! $this->_em->getConfiguration()->getAllowPartialObjects() &&
                ! $this->_query->getHint('doctrine.forcePartialLoad')
            ) {
            	throw DoctrineException::partialObjectsAreDangerous();
            }

            $expression = $this->StateFieldPathExpression();
        }

        return new AST\SelectExpression($expression, $fieldIdentificationVariable);
    }

    /**
     * IdentificationVariable ::= identifier
     */
    public function IdentificationVariable()
    {
        $this->match(Lexer::T_IDENTIFIER);

        return $this->_lexer->token['value'];
    }

    /**
     * IdentificationVariableDeclaration ::= RangeVariableDeclaration [IndexBy] {JoinVariableDeclaration}*
     */
    public function IdentificationVariableDeclaration()
    {
        $rangeVariableDeclaration = $this->RangeVariableDeclaration();
        $indexBy = $this->_lexer->isNextToken(Lexer::T_INDEX) ? $this->IndexBy() : null;
        $joinVariableDeclarations = array();

        while (
            $this->_lexer->isNextToken(Lexer::T_LEFT) ||
            $this->_lexer->isNextToken(Lexer::T_INNER) ||
            $this->_lexer->isNextToken(Lexer::T_JOIN)
        ) {
            $joinVariableDeclarations[] = $this->JoinVariableDeclaration();
        }

        return new AST\IdentificationVariableDeclaration(
            $rangeVariableDeclaration, $indexBy, $joinVariableDeclarations
        );
    }

    /**
     * RangeVariableDeclaration ::= AbstractSchemaName ["AS"] AliasIdentificationVariable
     */
    public function RangeVariableDeclaration()
    {
        $abstractSchemaName = $this->AbstractSchemaName();

        if ($this->_lexer->isNextToken(Lexer::T_AS)) {
            $this->match(Lexer::T_AS);
        }

        $aliasIdentificationVariable = $this->AliasIdentificationVariable();
        $classMetadata = $this->_em->getClassMetadata($abstractSchemaName);

        // Building queryComponent
        $queryComponent = array(
            'metadata' => $classMetadata,
            'parent'   => null,
            'relation' => null,
            'map'      => null
        );
        $this->_queryComponents[$aliasIdentificationVariable] = $queryComponent;

        return new AST\RangeVariableDeclaration(
            $classMetadata, $aliasIdentificationVariable
        );
    }

    /**
     * AbstractSchemaName ::= identifier
     */
    public function AbstractSchemaName()
    {
        $this->match(Lexer::T_IDENTIFIER);

        return $this->_lexer->token['value'];
    }

    /**
     * AliasIdentificationVariable = identifier
     */
    public function AliasIdentificationVariable()
    {
        $this->match(Lexer::T_IDENTIFIER);

        return $this->_lexer->token['value'];
    }

    /**
     * JoinVariableDeclaration ::= Join [IndexBy]
     */
    public function JoinVariableDeclaration()
    {
        $join = $this->Join();
        $indexBy = $this->_lexer->isNextToken(Lexer::T_INDEX)
            ? $this->IndexBy() : null;

        return new AST\JoinVariableDeclaration($join, $indexBy);
    }

    /**
     * Join ::= ["LEFT" ["OUTER"] | "INNER"] "JOIN" JoinAssociationPathExpression
     *          ["AS"] AliasIdentificationVariable [("ON" | "WITH") ConditionalExpression]
     */
    public function Join()
    {
        // Check Join type
        $joinType = AST\Join::JOIN_TYPE_INNER;

        if ($this->_lexer->isNextToken(Lexer::T_LEFT)) {
            $this->match(Lexer::T_LEFT);

            // Possible LEFT OUTER join
            if ($this->_lexer->isNextToken(Lexer::T_OUTER)) {
                $this->match(Lexer::T_OUTER);
                $joinType = AST\Join::JOIN_TYPE_LEFTOUTER;
            } else {
                $joinType = AST\Join::JOIN_TYPE_LEFT;
            }
        } else if ($this->_lexer->isNextToken(Lexer::T_INNER)) {
            $this->match(Lexer::T_INNER);
        }

        $this->match(Lexer::T_JOIN);
        $joinPathExpression = $this->JoinPathExpression();

        if ($this->_lexer->isNextToken(Lexer::T_AS)) {
            $this->match(Lexer::T_AS);
        }

        $aliasIdentificationVariable = $this->AliasIdentificationVariable();

        // Verify that the association exists.
        $parentClass = $this->_queryComponents[$joinPathExpression->getIdentificationVariable()]['metadata'];
        $assocField = $joinPathExpression->getAssociationField();

        if ( ! $parentClass->hasAssociation($assocField)) {
            $this->semanticalError(
                "Class " . $parentClass->name . " has no association named '$assocField'."
            );
        }

        $targetClassName = $parentClass->getAssociationMapping($assocField)->getTargetEntityName();

        // Building queryComponent
        $joinQueryComponent = array(
            'metadata' => $this->_em->getClassMetadata($targetClassName),
            'parent'   => $joinPathExpression->getIdentificationVariable(),
            'relation' => $parentClass->getAssociationMapping($assocField),
            'map'      => null
        );
        $this->_queryComponents[$aliasIdentificationVariable] = $joinQueryComponent;

        // Create AST node
        $join = new AST\Join($joinType, $joinPathExpression, $aliasIdentificationVariable);

        // Check for ad-hoc Join conditions
        if ($this->_lexer->isNextToken(Lexer::T_ON) || $this->_lexer->isNextToken(Lexer::T_WITH)) {
            if ($this->_lexer->isNextToken(Lexer::T_ON)) {
                $this->match(Lexer::T_ON);
                $join->setWhereType(AST\Join::JOIN_WHERE_ON);
            } else {
                $this->match(Lexer::T_WITH);
            }

            $join->setConditionalExpression($this->ConditionalExpression());
        }

        return $join;
    }

    /**
     * JoinPathExpression ::= IdentificationVariable "." (CollectionValuedAssociationField | SingleValuedAssociationField)
     */
    public function JoinPathExpression()
    {
        $identificationVariable = $this->IdentificationVariable();
        $this->match('.');
        $this->match(Lexer::T_IDENTIFIER);

        return new AST\JoinPathExpression(
            $identificationVariable, $this->_lexer->token['value']
        );
    }

    /**
     * IndexBy ::= "INDEX" "BY" SimpleStateFieldPathExpression
     */
    public function IndexBy()
    {
        $this->match(Lexer::T_INDEX);
        $this->match(Lexer::T_BY);
        $pathExp = $this->SimpleStateFieldPathExpression();

        // Add the INDEX BY info to the query component
        $parts = $pathExp->getParts();
        $this->_queryComponents[$pathExp->getIdentificationVariable()]['map'] = $parts[0];

        return $pathExp;
    }

    /**
     * StateFieldPathExpression ::= SimpleStateFieldPathExpression | SimpleStateFieldAssociationPathExpression
     */
    public function StateFieldPathExpression()
    {
        $pathExpr = $this->PathExpression(AST\PathExpression::TYPE_STATE_FIELD);

        if ( ! empty($this->_deferredPathExpressionStacks)) {
            $exprStack = array_pop($this->_deferredPathExpressionStacks);
            $exprStack[] = $pathExpr;
            array_push($this->_deferredPathExpressionStacks, $exprStack);

            return $pathExpr;
        }

        $this->_validateStateFieldPathExpression($pathExpr);

        return $pathExpr;
    }

    /**
     * SimpleStateFieldPathExpression ::= IdentificationVariable "." StateField
     */
    public function SimpleStateFieldPathExpression()
    {
        $pathExpr = $this->PathExpression(AST\PathExpression::TYPE_STATE_FIELD);

        // @TODO It seems PathExpression already checks for the minimum amount of parts
        // if (count($pathExpr->getParts()) > 1) {
        //     $this->syntaxError('SimpleStateFieldPathExpression');
        // }

        if ( ! empty($this->_deferredPathExpressionStacks)) {
            $exprStack = array_pop($this->_deferredPathExpressionStacks);
            $exprStack[] = $pathExpr;
            array_push($this->_deferredPathExpressionStacks, $exprStack);

            return $pathExpr;
        }

        $this->_validateStateFieldPathExpression($pathExpr);

        return $pathExpr;
    }

    /**
     * SingleValuedAssociationPathExpression ::= IdentificationVariable "." {SingleValuedAssociationField "."}* SingleValuedAssociationField
     */
    public function SingleValuedAssociationPathExpression()
    {
        $pathExpr = $this->PathExpression(AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION);
        
        if ( ! empty($this->_deferredPathExpressionStacks)) {
            $exprStack = array_pop($this->_deferredPathExpressionStacks);
            $exprStack[] = $pathExpr;
            array_push($this->_deferredPathExpressionStacks, $exprStack);

            return $pathExpr;
        }

        $this->_validateSingleValuedAssociationPathExpression($pathExpr);

        return $pathExpr;
    }

    /**
     * CollectionValuedPathExpression ::= IdentificationVariable "." {SingleValuedAssociationField "."}* CollectionValuedAssociationField
     */
    public function CollectionValuedPathExpression()
    {
        $pathExpr = $this->PathExpression(AST\PathExpression::TYPE_COLLECTION_VALUED_ASSOCIATION);

        if ( ! empty($this->_deferredPathExpressionStacks)) {
            $exprStack = array_pop($this->_deferredPathExpressionStacks);
            $exprStack[] = $pathExpr;
            array_push($this->_deferredPathExpressionStacks, $exprStack);

            return $pathExpr;
        }

        $this->_validateCollectionValuedPathExpression($pathExpr);

        return $pathExpr;
    }
    
    /**
     * SingleValuedPathExpression ::= StateFieldPathExpression | SingleValuedAssociationPathExpression
     */
    public function SingleValuedPathExpression()
    {
        $pathExpr = $this->PathExpression(AST\PathExpression::TYPE_SINGLE_VALUED_PATH_EXPRESSION);
        
        if ( ! empty($this->_deferredPathExpressionStacks)) {
            $exprStack = array_pop($this->_deferredPathExpressionStacks);
            $exprStack[] = $pathExpr;
            array_push($this->_deferredPathExpressionStacks, $exprStack);

            return $pathExpr;
        }

        $this->_validateSingleValuedPathExpression($pathExpr);

        return $pathExpr;
    }

    /**
     * Validates that the given <tt>PathExpression</tt> is a semantically correct
     * CollectionValuedPathExpression as specified in the grammar.
     *
     * @param PathExpression $pathExpression
     */
    private function _validateCollectionValuedPathExpression(AST\PathExpression $pathExpression)
    {
        $class = $this->_queryComponents[$pathExpression->getIdentificationVariable()]['metadata'];
        $collectionValuedField = null;

        foreach ($pathExpression->getParts() as $field) {
            if ($collectionValuedField !== null) {
                $this->semanticalError('Can not navigate through collection-valued field named ' . $collectionValuedField);
            }

            if ( ! isset($class->associationMappings[$field])) {
                $this->semanticalError('Class ' . $class->name . ' has no association field named ' . $lastItem);
            }

            if ($class->associationMappings[$field]->isOneToOne()) {
                $class = $this->_em->getClassMetadata($class->associationMappings[$field]->targetEntityName);
            } else {
                $collectionValuedField = $field;
            }
        }

        if ($collectionValuedField === null) {
            $this->syntaxError('SingleValuedAssociationField or CollectionValuedAssociationField');
        }
    }

    /**
     * Validates that the given <tt>PathExpression</tt> is a semantically correct
     * SingleValuedAssociationPathExpression as specified in the grammar.
     *
     * @param PathExpression $pathExpression
     */
    private function _validateSingleValuedAssociationPathExpression(AST\PathExpression $pathExpression)
    {
        $class = $this->_queryComponents[$pathExpression->getIdentificationVariable()]['metadata'];

        foreach ($pathExpression->getParts() as $field) {
            if ( ! isset($class->associationMappings[$field])) {
                $this->semanticalError('Class ' . $class->name . ' has no association field named ' . $field);
            }

            if ($class->associationMappings[$field]->isOneToOne()) {
                $class = $this->_em->getClassMetadata($class->associationMappings[$field]->targetEntityName);
            } else {
                $this->syntaxError('SingleValuedAssociationField');
            }
        }
    }

    /**
     * Validates that the given <tt>PathExpression</tt> is a semantically correct
     * StateFieldPathExpression as specified in the grammar.
     *
     * @param PathExpression $pathExpression
     */
    private function _validateStateFieldPathExpression(AST\PathExpression $pathExpression)
    {
        $stateFieldSeen = $this->_validateSingleValuedPathExpression($pathExpression);

        if ( ! $stateFieldSeen) {
            $this->semanticalError('Invalid StateFieldPathExpression. Must end in a state field.');
        }
    }
    
    /**
     * Validates that the given <tt>PathExpression</tt> is a semantically correct
     * SingleValuedPathExpression as specified in the grammar.
     *
     * @param PathExpression $pathExpression
     * @return boolean
     */
    private function _validateSingleValuedPathExpression(AST\PathExpression $pathExpression)
    {
        $class = $this->_queryComponents[$pathExpression->getIdentificationVariable()]['metadata'];
        $stateFieldSeen = false;

        foreach ($pathExpression->getParts() as $field) {
            if ($stateFieldSeen) {
                $this->semanticalError('Cannot navigate through state field.');
            }

            if (isset($class->associationMappings[$field]) && $class->associationMappings[$field]->isOneToOne()) {
                $class = $this->_em->getClassMetadata($class->associationMappings[$field]->targetEntityName);
            } else if (isset($class->fieldMappings[$field])) {
                $stateFieldSeen = true;
            } else {
                $this->semanticalError('SingleValuedAssociationField or StateField expected.');
            }
        }
        
        // We need to force the type in PathExpression since SingleValuedPathExpression is in a 
        // state of recognition of what's is the dealed type (StateField or SingleValuedAssociation)
        $pathExpression->setType(
        	($stateFieldSeen) 
        		? AST\PathExpression::TYPE_STATE_FIELD 
        		: AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION
        );

        return $stateFieldSeen;
    }

    /**
     * Parses an arbitrary path expression without any semantic validation.
     *
     * PathExpression ::= IdentificationVariable "." {identifier "."}* identifier
     *
     * @return PathExpression
     */
    public function PathExpression($type)
    {
        $identificationVariable = $this->IdentificationVariable();
        
        $parts = array();

        do {
            $this->match('.');
            $this->match(Lexer::T_IDENTIFIER);
            
            $parts[] = $this->_lexer->token['value'];
        } while ($this->_lexer->isNextToken('.'));
        
        return new AST\PathExpression($type, $identificationVariable, $parts);
    }

    /**
     * EntityExpression ::= SingleValuedAssociationPathExpression | SimpleEntityExpression
     */
    public function EntityExpression()
    {
        $glimpse = $this->_lexer->glimpse();
        
        if ($this->_lexer->isNextToken(Lexer::T_IDENTIFIER) && $glimpse['value'] === '.') {
            return $this->SingleValuedAssociationPathExpression();
        }
        
        return $this->SimpleEntityExpression();
    }
    
    /**
     * SimpleEntityExpression ::= IdentificationVariable | InputParameter
     */
    public function SimpleEntityExpression()
    {
        if ($this->_lexer->isNextToken(Lexer::T_INPUT_PARAMETER)) {
            $this->match(Lexer::T_INPUT_PARAMETER);
            return new AST\InputParameter($this->_lexer->token['value']);
        }
        
        return $this->IdentificationVariable();
    }

    /**
     * NullComparisonExpression ::= (SingleValuedPathExpression | InputParameter) "IS" ["NOT"] "NULL"
     */
    public function NullComparisonExpression()
    {
        if ($this->_lexer->isNextToken(Lexer::T_INPUT_PARAMETER)) {
            $this->match(Lexer::T_INPUT_PARAMETER);
            $expr = new AST\InputParameter($this->_lexer->token['value']);
        } else {
            $expr = $this->SingleValuedPathExpression();
        }

        $nullCompExpr = new AST\NullComparisonExpression($expr);
        $this->match(Lexer::T_IS);

        if ($this->_lexer->isNextToken(Lexer::T_NOT)) {
            $this->match(Lexer::T_NOT);
            $nullCompExpr->setNot(true);
        }

        $this->match(Lexer::T_NULL);

        return $nullCompExpr;
    }

    /**
     * AggregateExpression ::=
     *  ("AVG" | "MAX" | "MIN" | "SUM") "(" ["DISTINCT"] StateFieldPathExpression ")" |
     *  "COUNT" "(" ["DISTINCT"] (IdentificationVariable | SingleValuedPathExpression) ")"
     */
    public function AggregateExpression()
    {
        $isDistinct = false;
        $functionName = '';

        if ($this->_lexer->isNextToken(Lexer::T_COUNT)) {
            $this->match(Lexer::T_COUNT);
            $functionName = $this->_lexer->token['value'];
            $this->match('(');

            if ($this->_lexer->isNextToken(Lexer::T_DISTINCT)) {
                $this->match(Lexer::T_DISTINCT);
                $isDistinct = true;
            }

            $pathExp = $this->SingleValuedPathExpression();
            $this->match(')');
        } else {
            if ($this->_lexer->isNextToken(Lexer::T_AVG)) {
                $this->match(Lexer::T_AVG);
            } else if ($this->_lexer->isNextToken(Lexer::T_MAX)) {
                $this->match(Lexer::T_MAX);
            } else if ($this->_lexer->isNextToken(Lexer::T_MIN)) {
                $this->match(Lexer::T_MIN);
            } else if ($this->_lexer->isNextToken(Lexer::T_SUM)) {
                $this->match(Lexer::T_SUM);
            } else {
                $this->syntaxError('One of: MAX, MIN, AVG, SUM, COUNT');
            }

            $functionName = $this->_lexer->token['value'];
            $this->match('(');
            $pathExp = $this->StateFieldPathExpression();
            $this->match(')');
        }

        return new AST\AggregateExpression($functionName, $pathExp, $isDistinct);
    }

    /**
     * GroupByClause ::= "GROUP" "BY" GroupByItem {"," GroupByItem}*
     * GroupByItem ::= IdentificationVariable | SingleValuedPathExpression
     * @todo Implementation incomplete for GroupByItem.
     */
    public function GroupByClause()
    {
        $this->match(Lexer::T_GROUP);
        $this->match(Lexer::T_BY);

        $groupByItems = array();
        $groupByItems[] = $this->StateFieldPathExpression();

        while ($this->_lexer->isNextToken(',')) {
            $this->match(',');
            $groupByItems[] = $this->StateFieldPathExpression();
        }

        return new AST\GroupByClause($groupByItems);
    }

    /**
     * HavingClause ::= "HAVING" ConditionalExpression
     */
    public function HavingClause()
    {
        $this->match(Lexer::T_HAVING);

        return new AST\HavingClause($this->ConditionalExpression());
    }

    /**
     * OrderByClause ::= "ORDER" "BY" OrderByItem {"," OrderByItem}*
     */
    public function OrderByClause()
    {
        $this->match(Lexer::T_ORDER);
        $this->match(Lexer::T_BY);

        $orderByItems = array();
        $orderByItems[] = $this->OrderByItem();

        while ($this->_lexer->isNextToken(',')) {
            $this->match(',');
            $orderByItems[] = $this->OrderByItem();
        }

        return new AST\OrderByClause($orderByItems);
    }

    /**
     * OrderByItem ::= ResultVariable | StateFieldPathExpression ["ASC" | "DESC"]
     *
     * @todo Implementation incomplete for OrderByItem.
     */
    public function OrderByItem()
    {
        $item = new AST\OrderByItem($this->StateFieldPathExpression());

        if ($this->_lexer->isNextToken(Lexer::T_ASC)) {
            $this->match(Lexer::T_ASC);
            $item->setAsc(true);
        } else if ($this->_lexer->isNextToken(Lexer::T_DESC)) {
            $this->match(Lexer::T_DESC);
            $item->setDesc(true);
        } else {
            $item->setDesc(true);
        }

        return $item;
    }

    /**
     * WhereClause ::= "WHERE" ConditionalExpression
     */
    public function WhereClause()
    {
        $this->match(Lexer::T_WHERE);

        return new AST\WhereClause($this->ConditionalExpression());
    }

    /**
     * ConditionalExpression ::= ConditionalTerm {"OR" ConditionalTerm}*
     */
    public function ConditionalExpression()
    {
        $conditionalTerms = array();
        $conditionalTerms[] = $this->ConditionalTerm();

        while ($this->_lexer->isNextToken(Lexer::T_OR)) {
            $this->match(Lexer::T_OR);
            $conditionalTerms[] = $this->ConditionalTerm();
        }

        return new AST\ConditionalExpression($conditionalTerms);
    }

    /**
     * ConditionalTerm ::= ConditionalFactor {"AND" ConditionalFactor}*
     */
    public function ConditionalTerm()
    {
        $conditionalFactors = array();
        $conditionalFactors[] = $this->ConditionalFactor();

        while ($this->_lexer->isNextToken(Lexer::T_AND)) {
            $this->match(Lexer::T_AND);
            $conditionalFactors[] = $this->ConditionalFactor();
        }

        return new AST\ConditionalTerm($conditionalFactors);
    }

    /**
     * ConditionalFactor ::= ["NOT"] ConditionalPrimary
     */
    public function ConditionalFactor()
    {
        $not = false;

        if ($this->_lexer->isNextToken(Lexer::T_NOT)) {
            $this->match(Lexer::T_NOT);
            $not = true;
        }

        return new AST\ConditionalFactor($this->ConditionalPrimary(), $not);
    }

    /**
     * ConditionalPrimary ::= SimpleConditionalExpression | "(" ConditionalExpression ")"
     * @todo Implementation incomplete: Recognition of SimpleConditionalExpression is incomplete.
     */
    public function ConditionalPrimary()
    {
        $condPrimary = new AST\ConditionalPrimary;

        if ($this->_lexer->isNextToken('(')) {
            // Peek beyond the matching closing paranthesis ')'
            $numUnmatched = 1;
            $peek = $this->_lexer->peek();

            while ($numUnmatched > 0) {
                if ($peek['value'] == ')') {
                    --$numUnmatched;
                } else if ($peek['value'] == '(') {
                    ++$numUnmatched;
                }

                $peek = $this->_lexer->peek();
            }

            $this->_lexer->resetPeek();

            //TODO: This is not complete, what about LIKE/BETWEEN/...etc?
            $comparisonOps = array("=",  "<", "<=", "<>", ">", ">=", "!=");

            if (in_array($peek['value'], $comparisonOps)) {
                $condPrimary->setSimpleConditionalExpression($this->SimpleConditionalExpression());
            } else {
                $this->match('(');
                $conditionalExpression = $this->ConditionalExpression();
                $this->match(')');
                $condPrimary->setConditionalExpression($conditionalExpression);
            }
        } else {
            $condPrimary->setSimpleConditionalExpression($this->SimpleConditionalExpression());
        }

        return $condPrimary;
    }

    /**
     * SimpleConditionalExpression ::=
     *      ComparisonExpression | BetweenExpression | LikeExpression |
     *      InExpression | NullComparisonExpression | ExistsExpression |
     *      EmptyCollectionComparisonExpression | CollectionMemberExpression
     */
    public function SimpleConditionalExpression()
    {
        if ($this->_lexer->isNextToken(Lexer::T_NOT)) {
            $token = $this->_lexer->glimpse();
        } else {
            $token = $this->_lexer->lookahead;
        }

        if ($token['type'] === Lexer::T_EXISTS) {
            return $this->ExistsExpression();
        }

        $pathExprOrInputParam = false;

        if ($token['type'] === Lexer::T_IDENTIFIER || $token['type'] === Lexer::T_INPUT_PARAMETER) {
            // Peek beyond the PathExpression
            $pathExprOrInputParam = true;
            $peek = $this->_lexer->peek();

            while ($peek['value'] === '.') {
                $this->_lexer->peek();
                $peek = $this->_lexer->peek();
            }

            // Also peek beyond a NOT if there is one
            if ($peek['type'] === Lexer::T_NOT) {
                $peek = $this->_lexer->peek();
            }

            $this->_lexer->resetPeek();
            $token = $peek;
        }

        if ($pathExprOrInputParam) {            
            switch ($token['type']) {
                case Lexer::T_NONE:
                    return $this->ComparisonExpression();

                case Lexer::T_BETWEEN:
                    return $this->BetweenExpression();

                case Lexer::T_LIKE:
                    return $this->LikeExpression();

                case Lexer::T_IN:
                    return $this->InExpression();

                case Lexer::T_IS:
                    return $this->NullComparisonExpression();

                case Lexer::T_MEMBER:
                    return $this->CollectionMemberExpression();

                default:
                    $this->syntaxError();
            }
        } else {
            return $this->ComparisonExpression();
        }
    }
    
    /**
     * CollectionMemberExpression ::= EntityExpression ["NOT"] "MEMBER" ["OF"] CollectionValuedPathExpression
     * 
     * EntityExpression ::= SingleValuedAssociationPathExpression | SimpleEntityExpression
     * SimpleEntityExpression ::= IdentificationVariable | InputParameter
     * 
     * @return AST\CollectionMemberExpression
     */
    public function CollectionMemberExpression()
    {
        $isNot = false;

        $entityExpr = $this->EntityExpression(); 

        if ($this->_lexer->isNextToken(Lexer::T_NOT)) {
            $isNot = true;
            $this->match(Lexer::T_NOT);
        }

        $this->match(Lexer::T_MEMBER);

        if ($this->_lexer->isNextToken(Lexer::T_OF)) {
            $this->match(Lexer::T_OF);
        }

        return new AST\CollectionMemberExpression(
            $entityExpr, $this->CollectionValuedPathExpression(), $isNot
        );
    }

    /**
     * ComparisonExpression ::= ArithmeticExpression ComparisonOperator ( QuantifiedExpression | ArithmeticExpression )
     *
     * @return AST\ComparisonExpression
     * @todo Semantical checks whether $leftExpr $operator and $rightExpr are compatible.
     */
    public function ComparisonExpression()
    {
        $peek = $this->_lexer->glimpse();

        $leftExpr = $this->ArithmeticExpression();
        $operator = $this->ComparisonOperator();

        if ($this->_isNextAllAnySome()) {
            $rightExpr = $this->QuantifiedExpression();
        } else {
            $rightExpr = $this->ArithmeticExpression();
        }

        return new AST\ComparisonExpression($leftExpr, $operator, $rightExpr);
    }

    /**
     * Checks whether the current lookahead token of the lexer has the type
     * T_ALL, T_ANY or T_SOME.
     *
     * @return boolean
     */
    private function _isNextAllAnySome()
    {
        return $this->_lexer->lookahead['type'] === Lexer::T_ALL ||
               $this->_lexer->lookahead['type'] === Lexer::T_ANY ||
               $this->_lexer->lookahead['type'] === Lexer::T_SOME;
    }

    /**
     * Function ::= FunctionsReturningStrings | FunctionsReturningNumerics | FunctionsReturningDatetime
     */
    public function _Function()
    {
        $funcName = $this->_lexer->lookahead['value'];

        if ($this->isStringFunction($funcName)) {
            return $this->FunctionsReturningStrings();
        } else if ($this->isNumericFunction($funcName)) {
            return $this->FunctionsReturningNumerics();
        } else if ($this->isDatetimeFunction($funcName)) {
            return $this->FunctionsReturningDatetime();
        }
        
        $this->syntaxError('Known function.');
    }

    /**
     * Checks whether the function with the given name is a string function
     * (a function that returns strings).
     */
    public function isStringFunction($funcName)
    {
        return isset(self::$_STRING_FUNCTIONS[strtolower($funcName)]);
    }

    /**
     * Checks whether the function with the given name is a numeric function
     * (a function that returns numerics).
     */
    public function isNumericFunction($funcName)
    {
        return isset(self::$_NUMERIC_FUNCTIONS[strtolower($funcName)]);
    }

    /**
     * Checks whether the function with the given name is a datetime function
     * (a function that returns date/time values).
     */
    public function isDatetimeFunction($funcName)
    {
        return isset(self::$_DATETIME_FUNCTIONS[strtolower($funcName)]);
    }

    /**
     * Peeks beyond the specified token and returns the first token after that one.
     */
    private function _peekBeyond($token)
    {
        $peek = $this->_lexer->peek();

        while ($peek['value'] != $token) {
            $peek = $this->_lexer->peek();
        }

        $peek = $this->_lexer->peek();
        $this->_lexer->resetPeek();

        return $peek;
    }

    /**
     * Checks whether the given token is a comparison operator.
     */
    public function isComparisonOperator($token)
    {
        $value = $token['value'];

        return $value == '=' || $value == '<' || 
               $value == '<=' || $value == '<>' ||
               $value == '>' || $value == '>=' || 
               $value == '!=';
    }

    /**
     * ArithmeticExpression ::= SimpleArithmeticExpression | "(" Subselect ")"
     */
    public function ArithmeticExpression()
    {
        $expr = new AST\ArithmeticExpression;

        if ($this->_lexer->lookahead['value'] === '(') {
            $peek = $this->_lexer->glimpse();

            if ($peek['type'] === Lexer::T_SELECT) {
                $this->match('(');
                $expr->setSubselect($this->Subselect());
                $this->match(')');

                return $expr;
            }
        }

        $expr->setSimpleArithmeticExpression($this->SimpleArithmeticExpression());

        return $expr;
    }

    /**
     * SimpleArithmeticExpression ::= ArithmeticTerm {("+" | "-") ArithmeticTerm}*
     */
    public function SimpleArithmeticExpression()
    {
        $terms = array();
        $terms[] = $this->ArithmeticTerm();

        while ($this->_lexer->lookahead['value'] == '+' || $this->_lexer->lookahead['value'] == '-') {
            if ($this->_lexer->lookahead['value'] == '+') {
                $this->match('+');
            } else {
                $this->match('-');
            }

            $terms[] = $this->_lexer->token['value'];
            $terms[] = $this->ArithmeticTerm();
        }

        return new AST\SimpleArithmeticExpression($terms);
    }

    /**
     * ArithmeticTerm ::= ArithmeticFactor {("*" | "/") ArithmeticFactor}*
     */
    public function ArithmeticTerm()
    {
        $factors = array();
        $factors[] = $this->ArithmeticFactor();

        while ($this->_lexer->lookahead['value'] == '*' || $this->_lexer->lookahead['value'] == '/') {
            if ($this->_lexer->lookahead['value'] == '*') {
                $this->match('*');
            } else {
                $this->match('/');
            }

            $factors[] = $this->_lexer->token['value'];
            $factors[] = $this->ArithmeticFactor();
        }

        return new AST\ArithmeticTerm($factors);
    }

    /**
     * ArithmeticFactor ::= [("+" | "-")] ArithmeticPrimary
     */
    public function ArithmeticFactor()
    {
        $pSign = $nSign = false;

        if ($this->_lexer->lookahead['value'] == '+') {
            $this->match('+');
            $pSign = true;
        } else if ($this->_lexer->lookahead['value'] == '-') {
            $this->match('-');
            $nSign = true;
        }

        return new AST\ArithmeticFactor($this->ArithmeticPrimary(), $pSign, $nSign);
    }

    /**
     * InExpression ::= StateFieldPathExpression ["NOT"] "IN" "(" (Literal {"," Literal}* | Subselect) ")"
     */
    public function InExpression()
    {
        $inExpression = new AST\InExpression($this->StateFieldPathExpression());

        if ($this->_lexer->isNextToken(Lexer::T_NOT)) {
            $this->match(Lexer::T_NOT);
            $inExpression->setNot(true);
        }

        $this->match(Lexer::T_IN);
        $this->match('(');

        if ($this->_lexer->isNextToken(Lexer::T_SELECT)) {
            $inExpression->setSubselect($this->Subselect());
        } else {
            $literals = array();
            $literals[] = $this->Literal();

            while ($this->_lexer->isNextToken(',')) {
                $this->match(',');
                $literals[] = $this->Literal();
            }

            $inExpression->setLiterals($literals);
        }

        $this->match(')');

        return $inExpression;
    }

    /**
     * ExistsExpression ::= ["NOT"] "EXISTS" "(" Subselect ")"
     */
    public function ExistsExpression()
    {
        $not = false;

        if ($this->_lexer->isNextToken(Lexer::T_NOT)) {
            $this->match(Lexer::T_NOT);
            $not = true;
        }

        $this->match(Lexer::T_EXISTS);
        $this->match('(');
        $existsExpression = new AST\ExistsExpression($this->Subselect());
        $this->match(')');
        $existsExpression->setNot($not);

        return $existsExpression;
    }

    /**
     * QuantifiedExpression ::= ("ALL" | "ANY" | "SOME") "(" Subselect ")"
     */
    public function QuantifiedExpression()
    {
        $all = $any = $some = false;

        if ($this->_lexer->isNextToken(Lexer::T_ALL)) {
            $this->match(Lexer::T_ALL);
            $all = true;
        } else if ($this->_lexer->isNextToken(Lexer::T_ANY)) {
            $this->match(Lexer::T_ANY);
            $any = true;
        } else if ($this->_lexer->isNextToken(Lexer::T_SOME)) {
            $this->match(Lexer::T_SOME);
            $some = true;
        } else {
            $this->syntaxError('ALL, ANY or SOME');
        }

        $this->match('(');
        $qExpr = new AST\QuantifiedExpression($this->Subselect());
        $this->match(')');

        $qExpr->setAll($all);
        $qExpr->setAny($any);
        $qExpr->setSome($some);

        return $qExpr;
    }

    /**
     * Subselect ::= SimpleSelectClause SubselectFromClause [WhereClause] [GroupByClause] [HavingClause] [OrderByClause]
     */
    public function Subselect()
    {
        $this->_beginDeferredPathExpressionStack();
        $subselect = new AST\Subselect($this->SimpleSelectClause(), $this->SubselectFromClause());
        $this->_processDeferredPathExpressionStack();

        $subselect->setWhereClause(
            $this->_lexer->isNextToken(Lexer::T_WHERE) ? $this->WhereClause() : null
        );

        $subselect->setGroupByClause(
            $this->_lexer->isNextToken(Lexer::T_GROUP) ? $this->GroupByClause() : null
        );

        $subselect->setHavingClause(
            $this->_lexer->isNextToken(Lexer::T_HAVING) ? $this->HavingClause() : null
        );

        $subselect->setOrderByClause(
            $this->_lexer->isNextToken(Lexer::T_ORDER) ? $this->OrderByClause() : null
        );

        return $subselect;
    }

    /**
     * SimpleSelectClause ::= "SELECT" ["DISTINCT"] SimpleSelectExpression
     */
    public function SimpleSelectClause()
    {
        $distinct = false;
        $this->match(Lexer::T_SELECT);

        if ($this->_lexer->isNextToken(Lexer::T_DISTINCT)) {
            $this->match(Lexer::T_DISTINCT);
            $distinct = true;
        }

        $simpleSelectClause = new AST\SimpleSelectClause($this->SimpleSelectExpression());
        $simpleSelectClause->setDistinct($distinct);

        return $simpleSelectClause;
    }

    /**
     * SubselectFromClause ::= "FROM" SubselectIdentificationVariableDeclaration {"," SubselectIdentificationVariableDeclaration}*
     */
    public function SubselectFromClause()
    {
        $this->match(Lexer::T_FROM);
        $identificationVariables = array();
        $identificationVariables[] = $this->SubselectIdentificationVariableDeclaration();

        while ($this->_lexer->isNextToken(',')) {
            $this->match(',');
            $identificationVariables[] = $this->SubselectIdentificationVariableDeclaration();
        }

        return new AST\SubselectFromClause($identificationVariables);
    }

    /**
     * SubselectIdentificationVariableDeclaration ::= IdentificationVariableDeclaration | (AssociationPathExpression ["AS"] AliasIdentificationVariable)
     */
    public function SubselectIdentificationVariableDeclaration()
    {
        $peek = $this->_lexer->glimpse();

        if ($peek['value'] == '.') {
            $subselectIdentificationVarDecl = new AST\SubselectIdentificationVariableDeclaration;
            $subselectIdentificationVarDecl->setAssociationPathExpression($this->AssociationPathExpression());
            $this->match(Lexer::T_AS);
            $this->match(Lexer::T_IDENTIFIER);
            $subselectIdentificationVarDecl->setAliasIdentificationVariable($this->_lexer->token['value']);

            return $subselectIdentificationVarDecl;
        }

        return $this->IdentificationVariableDeclaration();
    }

    /**
     * SimpleSelectExpression ::= StateFieldPathExpression | IdentificationVariable | (AggregateExpression [["AS"] FieldAliasIdentificationVariable])
     */
    public function SimpleSelectExpression()
    {
        if ($this->_lexer->isNextToken(Lexer::T_IDENTIFIER)) {
            // SingleValuedPathExpression | IdentificationVariable
            $peek = $this->_lexer->glimpse();

            if ($peek['value'] == '.') {
                return new AST\SimpleSelectExpression($this->StateFieldPathExpression());
            }

            $this->match($this->_lexer->lookahead['value']);

            return new AST\SimpleSelectExpression($this->_lexer->token['value']);
        } else {
            $expr = new AST\SimpleSelectExpression($this->AggregateExpression());

            if ($this->_lexer->isNextToken(Lexer::T_AS)) {
                $this->match(Lexer::T_AS);
            }

            if ($this->_lexer->isNextToken(Lexer::T_IDENTIFIER)) {
                $this->match(Lexer::T_IDENTIFIER);
                $expr->setFieldIdentificationVariable($this->_lexer->token['value']);
            }

            return $expr;
        }
    }

    /**
     * Literal ::= string | char | integer | float | boolean | InputParameter
     *
     * @todo Rip out InputParameter. Thats not a literal.
     */
    public function Literal()
    {
        switch ($this->_lexer->lookahead['type']) {
            case Lexer::T_INPUT_PARAMETER:
                $this->match($this->_lexer->lookahead['value']);

                return new AST\InputParameter($this->_lexer->token['value']);

            case Lexer::T_STRING:
            case Lexer::T_INTEGER:
            case Lexer::T_FLOAT:
                $this->match($this->_lexer->lookahead['value']);

                return $this->_lexer->token['value'];

            default:
                $this->syntaxError('Literal');
        }
    }

    /**
     * BetweenExpression ::= ArithmeticExpression ["NOT"] "BETWEEN" ArithmeticExpression "AND" ArithmeticExpression
     */
    public function BetweenExpression()
    {
        $not = false;
        $arithExpr1 = $this->ArithmeticExpression();

        if ($this->_lexer->isNextToken(Lexer::T_NOT)) {
            $this->match(Lexer::T_NOT);
            $not = true;
        }

        $this->match(Lexer::T_BETWEEN);
        $arithExpr2 = $this->ArithmeticExpression();
        $this->match(Lexer::T_AND);
        $arithExpr3 = $this->ArithmeticExpression();

        $betweenExpr = new AST\BetweenExpression($arithExpr1, $arithExpr2, $arithExpr3);
        $betweenExpr->setNot($not);

        return $betweenExpr;
    }

    /**
     * ArithmeticPrimary ::= SingleValuedPathExpression | Literal | "(" SimpleArithmeticExpression ")"
     *          | FunctionsReturningNumerics | AggregateExpression | FunctionsReturningStrings
     *          | FunctionsReturningDatetime | IdentificationVariable
     */
    public function ArithmeticPrimary()
    {
        if ($this->_lexer->lookahead['value'] === '(') {
            $this->match('(');
            $expr = $this->SimpleArithmeticExpression();
            $this->match(')');

            return $expr;
        }

        switch ($this->_lexer->lookahead['type']) {
            case Lexer::T_IDENTIFIER:
                $peek = $this->_lexer->glimpse();

                if ($peek['value'] == '(') {
                    return $this->_Function();
                }

                if ($peek['value'] == '.') {
                    return $this->SingleValuedPathExpression();
                }

                return $this->IdentificationVariable();

            case Lexer::T_INPUT_PARAMETER:
                $this->match($this->_lexer->lookahead['value']);

                return new AST\InputParameter($this->_lexer->token['value']);

            case Lexer::T_STRING:
            case Lexer::T_INTEGER:
            case Lexer::T_FLOAT:
                $this->match($this->_lexer->lookahead['value']);

                return $this->_lexer->token['value'];

            default:
                $peek = $this->_lexer->glimpse();

                if ($peek['value'] == '(') {
                    if ($this->isAggregateFunction($this->_lexer->lookahead['type'])) {
                        return $this->AggregateExpression();
                    }

                    return $this->FunctionsReturningStrings();
                } else {
                    $this->syntaxError();
                }
        }
    }

    /**
     * FunctionsReturningStrings ::=
     *   "CONCAT" "(" StringPrimary "," StringPrimary ")" |
     *   "SUBSTRING" "(" StringPrimary "," SimpleArithmeticExpression "," SimpleArithmeticExpression ")" |
     *   "TRIM" "(" [["LEADING" | "TRAILING" | "BOTH"] [char] "FROM"] StringPrimary ")" |
     *   "LOWER" "(" StringPrimary ")" |
     *   "UPPER" "(" StringPrimary ")"
     */
    public function FunctionsReturningStrings()
    {
        $funcNameLower = strtolower($this->_lexer->lookahead['value']);
        $funcClass = self::$_STRING_FUNCTIONS[$funcNameLower];
        $function = new $funcClass($funcNameLower);
        $function->parse($this);

        return $function;
    }

    /**
     * FunctionsReturningNumerics ::=
     *      "LENGTH" "(" StringPrimary ")" |
     *      "LOCATE" "(" StringPrimary "," StringPrimary ["," SimpleArithmeticExpression]")" |
     *      "ABS" "(" SimpleArithmeticExpression ")" |
     *      "SQRT" "(" SimpleArithmeticExpression ")" |
     *      "MOD" "(" SimpleArithmeticExpression "," SimpleArithmeticExpression ")" |
     *      "SIZE" "(" CollectionValuedPathExpression ")"
     */
    public function FunctionsReturningNumerics()
    {
        $funcNameLower = strtolower($this->_lexer->lookahead['value']);
        $funcClass = self::$_NUMERIC_FUNCTIONS[$funcNameLower];
        $function = new $funcClass($funcNameLower);
        $function->parse($this);

        return $function;
    }

    /**
     * FunctionsReturningDateTime ::= "CURRENT_DATE" | "CURRENT_TIME" | "CURRENT_TIMESTAMP"
     */
    public function FunctionsReturningDatetime()
    {
        $funcNameLower = strtolower($this->_lexer->lookahead['value']);
        $funcClass = self::$_DATETIME_FUNCTIONS[$funcNameLower];
        $function = new $funcClass($funcNameLower);
        $function->parse($this);

        return $function;
    }

    /**
     * Checks whether the given token type indicates an aggregate function.
     */
    public function isAggregateFunction($tokenType)
    {
        return $tokenType == Lexer::T_AVG || $tokenType == Lexer::T_MIN ||
               $tokenType == Lexer::T_MAX || $tokenType == Lexer::T_SUM ||
               $tokenType == Lexer::T_COUNT;
    }

    /**
     * ComparisonOperator ::= "=" | "<" | "<=" | "<>" | ">" | ">=" | "!="
     */
    public function ComparisonOperator()
    {
        switch ($this->_lexer->lookahead['value']) {
            case '=':
                $this->match('=');

                return '=';

            case '<':
                $this->match('<');
                $operator = '<';

                if ($this->_lexer->isNextToken('=')) {
                    $this->match('=');
                    $operator .= '=';
                } else if ($this->_lexer->isNextToken('>')) {
                    $this->match('>');
                    $operator .= '>';
                }

                return $operator;

            case '>':
                $this->match('>');
                $operator = '>';

                if ($this->_lexer->isNextToken('=')) {
                    $this->match('=');
                    $operator .= '=';
                }

                return $operator;

            case '!':
                $this->match('!');
                $this->match('=');

                return '<>';

            default:
                $this->syntaxError('=, <, <=, <>, >, >=, !=');
        }
    }

    /**
     * LikeExpression ::= StringExpression ["NOT"] "LIKE" (string | input_parameter) ["ESCAPE" char]
     */
    public function LikeExpression()
    {
        $stringExpr = $this->StringExpression();
        $isNot = false;

        if ($this->_lexer->lookahead['type'] === Lexer::T_NOT) {
            $this->match(Lexer::T_NOT);
            $isNot = true;
        }

        $this->match(Lexer::T_LIKE);

        if ($this->_lexer->isNextToken(Lexer::T_INPUT_PARAMETER)) {
            $this->match(Lexer::T_INPUT_PARAMETER);
            $stringPattern = new AST\InputParameter($this->_lexer->token['value']);
        } else {
            $this->match(Lexer::T_STRING);
            $stringPattern = $this->_lexer->token['value'];
        }

        $escapeChar = null;

        if ($this->_lexer->lookahead['type'] === Lexer::T_ESCAPE) {
            $this->match(Lexer::T_ESCAPE);
            $this->match(Lexer::T_STRING);
            $escapeChar = $this->_lexer->token['value'];
        }

        return new AST\LikeExpression($stringExpr, $stringPattern, $isNot, $escapeChar);
    }

    /**
     * StringExpression ::= StringPrimary | "(" Subselect ")"
     */
    public function StringExpression()
    {
        if ($this->_lexer->lookahead['value'] === '(') {
            $peek = $this->_lexer->glimpse();

            if ($peek['type'] === Lexer::T_SELECT) {
                $this->match('(');
                $expr = $this->Subselect();
                $this->match(')');

                return $expr;
            }
        }

        return $this->StringPrimary();
    }

    /**
     * StringPrimary ::= StateFieldPathExpression | string | InputParameter | FunctionsReturningStrings | AggregateExpression
     */
    public function StringPrimary()
    {
        if ($this->_lexer->lookahead['type'] === Lexer::T_IDENTIFIER) {
            $peek = $this->_lexer->glimpse();

            if ($peek['value'] == '.') {
                return $this->StateFieldPathExpression();
            } else if ($peek['value'] == '(') {
                return $this->FunctionsReturningStrings();
            } else {
                $this->syntaxError("'.' or '('");
            }
        } else if ($this->_lexer->lookahead['type'] === Lexer::T_STRING) {
            $this->match(Lexer::T_STRING);

            return $this->_lexer->token['value'];
        } else if ($this->_lexer->lookahead['type'] === Lexer::T_INPUT_PARAMETER) {
            $this->match(Lexer::T_INPUT_PARAMETER);

            return new AST\InputParameter($this->_lexer->token['value']);
        } else if ($this->isAggregateFunction($this->_lexer->lookahead['type'])) {
            return $this->AggregateExpression();
        }

        $this->syntaxError('StateFieldPathExpression | string | InputParameter | FunctionsReturningStrings | AggregateExpression');
    }
    
    /**
     * Sets the list of custom tree walkers.
     * 
     * @param array $treeWalkers
     */
    public function setSqlTreeWalker($treeWalker)
    {
        $this->_treeWalker = $treeWalker;
    }

    /**
     * Registers a custom function that returns strings.
     *
     * @param string $name The function name.
     * @param string $class The class name of the function implementation.
     */
    public static function registerStringFunction($name, $class)
    {
        self::$_STRING_FUNCTIONS[$name] = $class;
    }

    /**
     * Registers a custom function that returns numerics.
     *
     * @param string $name The function name.
     * @param string $class The class name of the function implementation.
     */
    public static function registerNumericFunction($name, $class)
    {
        self::$_NUMERIC_FUNCTIONS[$name] = $class;
    }

    /**
     * Registers a custom function that returns date/time values.
     *
     * @param string $name The function name.
     * @param string $class The class name of the function implementation.
     */
    public static function registerDatetimeFunction($name, $class)
    {
        self::$_DATETIME_FUNCTIONS[$name] = $class;
    }
}