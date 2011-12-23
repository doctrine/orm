<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHARNTABILITY AND FITNESS FOR
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

use Doctrine\ORM\Query;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * An LL(*) recursive-descent parser for the context-free grammar of the Doctrine Query Language.
 * Parses a DQL query, reports any errors in it, and generates an AST.
 *
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 * @author  Janne Vanhala <jpvanhal@cc.hut.fi>
 */
class Parser
{
    /** READ-ONLY: Maps BUILT-IN string function names to AST class names. */
    private static $_STRING_FUNCTIONS = array(
        'concat'    => 'Doctrine\ORM\Query\AST\Functions\ConcatFunction',
        'substring' => 'Doctrine\ORM\Query\AST\Functions\SubstringFunction',
        'trim'      => 'Doctrine\ORM\Query\AST\Functions\TrimFunction',
        'lower'     => 'Doctrine\ORM\Query\AST\Functions\LowerFunction',
        'upper'     => 'Doctrine\ORM\Query\AST\Functions\UpperFunction',
        'identity'  => 'Doctrine\ORM\Query\AST\Functions\IdentityFunction',
    );

    /** READ-ONLY: Maps BUILT-IN numeric function names to AST class names. */
    private static $_NUMERIC_FUNCTIONS = array(
        'length'    => 'Doctrine\ORM\Query\AST\Functions\LengthFunction',
        'locate'    => 'Doctrine\ORM\Query\AST\Functions\LocateFunction',
        'abs'       => 'Doctrine\ORM\Query\AST\Functions\AbsFunction',
        'sqrt'      => 'Doctrine\ORM\Query\AST\Functions\SqrtFunction',
        'mod'       => 'Doctrine\ORM\Query\AST\Functions\ModFunction',
        'size'      => 'Doctrine\ORM\Query\AST\Functions\SizeFunction',
        'date_diff' => 'Doctrine\ORM\Query\AST\Functions\DateDiffFunction',
        'bit_and'   => 'Doctrine\ORM\Query\AST\Functions\BitAndFunction',
        'bit_or'    => 'Doctrine\ORM\Query\AST\Functions\BitOrFunction',
    );

    /** READ-ONLY: Maps BUILT-IN datetime function names to AST class names. */
    private static $_DATETIME_FUNCTIONS = array(
        'current_date'      => 'Doctrine\ORM\Query\AST\Functions\CurrentDateFunction',
        'current_time'      => 'Doctrine\ORM\Query\AST\Functions\CurrentTimeFunction',
        'current_timestamp' => 'Doctrine\ORM\Query\AST\Functions\CurrentTimestampFunction',
        'date_add'          => 'Doctrine\ORM\Query\AST\Functions\DateAddFunction',
        'date_sub'          => 'Doctrine\ORM\Query\AST\Functions\DateSubFunction',
    );

    /**
     * Expressions that were encountered during parsing of identifiers and expressions
     * and still need to be validated.
     */
    private $_deferredIdentificationVariables = array();
    private $_deferredPartialObjectExpressions = array();
    private $_deferredPathExpressions = array();
    private $_deferredResultVariables = array();

    /**
     * The lexer.
     *
     * @var \Doctrine\ORM\Query\Lexer
     */
    private $_lexer;

    /**
     * The parser result.
     *
     * @var \Doctrine\ORM\Query\ParserResult
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
     * Keeps the nesting level of defined ResultVariables
     *
     * @var integer
     */
    private $_nestingLevel = 0;

    /**
     * Any additional custom tree walkers that modify the AST.
     *
     * @var array
     */
    private $_customTreeWalkers = array();

    /**
     * The custom last tree walker, if any, that is responsible for producing the output.
     *
     * @var TreeWalker
     */
    private $_customOutputWalker;

    /**
     * @var array
     */
    private $_identVariableExpressions = array();

    /**
     * Creates a new query parser object.
     *
     * @param Query $query The Query to parse.
     */
    public function __construct(Query $query)
    {
        $this->_query        = $query;
        $this->_em           = $query->getEntityManager();
        $this->_lexer        = new Lexer($query->getDql());
        $this->_parserResult = new ParserResult();
    }

    /**
     * Sets a custom tree walker that produces output.
     * This tree walker will be run last over the AST, after any other walkers.
     *
     * @param string $className
     */
    public function setCustomOutputTreeWalker($className)
    {
        $this->_customOutputWalker = $className;
    }

    /**
     * Adds a custom tree walker for modifying the AST.
     *
     * @param string $className
     */
    public function addCustomTreeWalker($className)
    {
        $this->_customTreeWalkers[] = $className;
    }

    /**
     * Gets the lexer used by the parser.
     *
     * @return \Doctrine\ORM\Query\Lexer
     */
    public function getLexer()
    {
        return $this->_lexer;
    }

    /**
     * Gets the ParserResult that is being filled with information during parsing.
     *
     * @return \Doctrine\ORM\Query\ParserResult
     */
    public function getParserResult()
    {
        return $this->_parserResult;
    }

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
     * Parse and build AST for the given Query.
     *
     * @return \Doctrine\ORM\Query\AST\SelectStatement |
     *         \Doctrine\ORM\Query\AST\UpdateStatement |
     *         \Doctrine\ORM\Query\AST\DeleteStatement
     */
    public function getAST()
    {
        // Parse & build AST
        $AST = $this->QueryLanguage();

        // Process any deferred validations of some nodes in the AST.
        // This also allows post-processing of the AST for modification purposes.
        $this->_processDeferredIdentificationVariables();

        if ($this->_deferredPartialObjectExpressions) {
            $this->_processDeferredPartialObjectExpressions();
        }

        if ($this->_deferredPathExpressions) {
            $this->_processDeferredPathExpressions($AST);
        }

        if ($this->_deferredResultVariables) {
            $this->_processDeferredResultVariables();
        }

        $this->_processRootEntityAliasSelected();

        // TODO: Is there a way to remove this? It may impact the mixed hydration resultset a lot!
        $this->fixIdentificationVariableOrder($AST);

        return $AST;
    }

    /**
     * Attempts to match the given token with the current lookahead token.
     *
     * If they match, updates the lookahead token; otherwise raises a syntax
     * error.
     *
     * @param int token type
     * @return void
     * @throws QueryException If the tokens dont match.
     */
    public function match($token)
    {
        $lookaheadType = $this->_lexer->lookahead['type'];

        // short-circuit on first condition, usually types match
        if ($lookaheadType !== $token && $token !== Lexer::T_IDENTIFIER && $lookaheadType <= Lexer::T_IDENTIFIER) {
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
        }

        $this->_lexer->token = null;
        $this->_lexer->lookahead = null;
    }

    /**
     * Parses a query string.
     *
     * @return ParserResult
     */
    public function parse()
    {
        $AST = $this->getAST();

        if (($customWalkers = $this->_query->getHint(Query::HINT_CUSTOM_TREE_WALKERS)) !== false) {
            $this->_customTreeWalkers = $customWalkers;
        }

        if (($customOutputWalker = $this->_query->getHint(Query::HINT_CUSTOM_OUTPUT_WALKER)) !== false) {
            $this->_customOutputWalker = $customOutputWalker;
        }

        // Run any custom tree walkers over the AST
        if ($this->_customTreeWalkers) {
            $treeWalkerChain = new TreeWalkerChain($this->_query, $this->_parserResult, $this->_queryComponents);

            foreach ($this->_customTreeWalkers as $walker) {
                $treeWalkerChain->addTreeWalker($walker);
            }

            switch (true) {
                case ($AST instanceof AST\UpdateStatement):
                    $treeWalkerChain->walkUpdateStatement($AST);
                    break;

                case ($AST instanceof AST\DeleteStatement):
                    $treeWalkerChain->walkDeleteStatement($AST);
                    break;

                case ($AST instanceof AST\SelectStatement):
                default:
                    $treeWalkerChain->walkSelectStatement($AST);
            }
        }

        $outputWalkerClass = $this->_customOutputWalker ?: __NAMESPACE__ . '\SqlWalker';
        $outputWalker      = new $outputWalkerClass($this->_query, $this->_parserResult, $this->_queryComponents);

        // Assign an SQL executor to the parser result
        $this->_parserResult->setSqlExecutor($outputWalker->getExecutor($AST));

        return $this->_parserResult;
    }

    /**
     * Fix order of identification variables.
     *
     * They have to appear in the select clause in the same order as the
     * declarations (from ... x join ... y join ... z ...) appear in the query
     * as the hydration process relies on that order for proper operation.
     *
     * @param AST\SelectStatement|AST\DeleteStatement|AST\UpdateStatement $AST
     * @return void
     */
    private function fixIdentificationVariableOrder($AST)
    {
        if (count($this->_identVariableExpressions) <= 1) {
            return;
        }

        foreach ($this->_queryComponents as $dqlAlias => $qComp) {
            if ( ! isset($this->_identVariableExpressions[$dqlAlias])) {
                continue;
            }

            $expr = $this->_identVariableExpressions[$dqlAlias];
            $key  = array_search($expr, $AST->selectClause->selectExpressions);

            unset($AST->selectClause->selectExpressions[$key]);

            $AST->selectClause->selectExpressions[] = $expr;
        }
    }

    /**
     * Generates a new syntax error.
     *
     * @param string $expected Expected string.
     * @param array $token Got token.
     *
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function syntaxError($expected = '', $token = null)
    {
        if ($token === null) {
            $token = $this->_lexer->lookahead;
        }

        $tokenPos = (isset($token['position'])) ? $token['position'] : '-1';

        $message  = "line 0, col {$tokenPos}: Error: ";
        $message .= ($expected !== '') ? "Expected {$expected}, got " : 'Unexpected ';
        $message .= ($this->_lexer->lookahead === null) ? 'end of string.' : "'{$token['value']}'";

        throw QueryException::syntaxError($message);
    }

    /**
     * Generates a new semantical error.
     *
     * @param string $message Optional message.
     * @param array $token Optional token.
     *
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function semanticalError($message = '', $token = null)
    {
        if ($token === null) {
            $token = $this->_lexer->lookahead;
        }

        // Minimum exposed chars ahead of token
        $distance = 12;

        // Find a position of a final word to display in error string
        $dql    = $this->_query->getDql();
        $length = strlen($dql);
        $pos    = $token['position'] + $distance;
        $pos    = strpos($dql, ' ', ($length > $pos) ? $pos : $length);
        $length = ($pos !== false) ? $pos - $token['position'] : $distance;

        $tokenPos = (isset($token['position']) && $token['position'] > 0) ? $token['position'] : '-1';
        $tokenStr = substr($dql, $token['position'], $length);

        // Building informative message
        $message = 'line 0, col ' . $tokenPos . " near '" . $tokenStr . "': Error: " . $message;

        throw QueryException::semanticalError($message);
    }

    /**
     * Peeks beyond the specified token and returns the first token after that one.
     *
     * @param array $token
     * @return array
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
     * Peek beyond the matched closing parenthesis and return the first token after that one.
     *
     * @return array
     */
    private function _peekBeyondClosingParenthesis()
    {
        $token = $this->_lexer->peek();
        $numUnmatched = 1;

        while ($numUnmatched > 0 && $token !== null) {
            switch ($token['type']) {
                case Lexer::T_OPEN_PARENTHESIS:
                    ++$numUnmatched;
                    break;

                case Lexer::T_CLOSE_PARENTHESIS:
                    --$numUnmatched;
                    break;

                default:
                    // Do nothing
            }

            $token = $this->_lexer->peek();
        }

        $this->_lexer->resetPeek();

        return $token;
    }

    /**
     * Checks if the given token indicates a mathematical operator.
     *
     * @return boolean TRUE if the token is a mathematical operator, FALSE otherwise.
     */
    private function _isMathOperator($token)
    {
        return in_array($token['type'], array(Lexer::T_PLUS, Lexer::T_MINUS, Lexer::T_DIVIDE, Lexer::T_MULTIPLY));
    }

    /**
     * Checks if the next-next (after lookahead) token starts a function.
     *
     * @return boolean TRUE if the next-next tokens start a function, FALSE otherwise.
     */
    private function _isFunction()
    {
        $peek     = $this->_lexer->peek();
        $nextpeek = $this->_lexer->peek();

        $this->_lexer->resetPeek();

        // We deny the COUNT(SELECT * FROM User u) here. COUNT won't be considered a function
        return ($peek['type'] === Lexer::T_OPEN_PARENTHESIS && $nextpeek['type'] !== Lexer::T_SELECT);
    }

    /**
     * Checks whether the given token type indicates an aggregate function.
     *
     * @return boolean TRUE if the token type is an aggregate function, FALSE otherwise.
     */
    private function _isAggregateFunction($tokenType)
    {
        return in_array($tokenType, array(Lexer::T_AVG, Lexer::T_MIN, Lexer::T_MAX, Lexer::T_SUM, Lexer::T_COUNT));
    }

    /**
     * Checks whether the current lookahead token of the lexer has the type T_ALL, T_ANY or T_SOME.
     *
     * @return boolean
     */
    private function _isNextAllAnySome()
    {
        return in_array($this->_lexer->lookahead['type'], array(Lexer::T_ALL, Lexer::T_ANY, Lexer::T_SOME));
    }

    /**
     * Validates that the given <tt>IdentificationVariable</tt> is semantically correct.
     * It must exist in query components list.
     *
     * @return void
     */
    private function _processDeferredIdentificationVariables()
    {
        foreach ($this->_deferredIdentificationVariables as $deferredItem) {
            $identVariable = $deferredItem['expression'];

            // Check if IdentificationVariable exists in queryComponents
            if ( ! isset($this->_queryComponents[$identVariable])) {
                $this->semanticalError(
                    "'$identVariable' is not defined.", $deferredItem['token']
                );
            }

            $qComp = $this->_queryComponents[$identVariable];

            // Check if queryComponent points to an AbstractSchemaName or a ResultVariable
            if ( ! isset($qComp['metadata'])) {
                $this->semanticalError(
                    "'$identVariable' does not point to a Class.", $deferredItem['token']
                );
            }

            // Validate if identification variable nesting level is lower or equal than the current one
            if ($qComp['nestingLevel'] > $deferredItem['nestingLevel']) {
                $this->semanticalError(
                    "'$identVariable' is used outside the scope of its declaration.", $deferredItem['token']
                );
            }
        }
    }

    /**
     * Validates that the given <tt>PartialObjectExpression</tt> is semantically correct.
     * It must exist in query components list.
     *
     * @return void
     */
    private function _processDeferredPartialObjectExpressions()
    {
        foreach ($this->_deferredPartialObjectExpressions as $deferredItem) {
            $expr = $deferredItem['expression'];
            $class = $this->_queryComponents[$expr->identificationVariable]['metadata'];

            foreach ($expr->partialFieldSet as $field) {
                if (isset($class->fieldMappings[$field])) {
                	continue;
                }

                $this->semanticalError(
                    "There is no mapped field named '$field' on class " . $class->name . ".", $deferredItem['token']
                );
            }

            if (array_intersect($class->identifier, $expr->partialFieldSet) != $class->identifier) {
                $this->semanticalError(
                    "The partial field selection of class " . $class->name . " must contain the identifier.",
                    $deferredItem['token']
                );
            }
        }
    }

    /**
     * Validates that the given <tt>ResultVariable</tt> is semantically correct.
     * It must exist in query components list.
     *
     * @return void
     */
    private function _processDeferredResultVariables()
    {
        foreach ($this->_deferredResultVariables as $deferredItem) {
            $resultVariable = $deferredItem['expression'];

            // Check if ResultVariable exists in queryComponents
            if ( ! isset($this->_queryComponents[$resultVariable])) {
                $this->semanticalError(
                    "'$resultVariable' is not defined.", $deferredItem['token']
                );
            }

            $qComp = $this->_queryComponents[$resultVariable];

            // Check if queryComponent points to an AbstractSchemaName or a ResultVariable
            if ( ! isset($qComp['resultVariable'])) {
                $this->semanticalError(
                    "'$identVariable' does not point to a ResultVariable.", $deferredItem['token']
                );
            }

            // Validate if identification variable nesting level is lower or equal than the current one
            if ($qComp['nestingLevel'] > $deferredItem['nestingLevel']) {
                $this->semanticalError(
                    "'$resultVariable' is used outside the scope of its declaration.", $deferredItem['token']
                );
            }
        }
    }

    /**
     * Validates that the given <tt>PathExpression</tt> is semantically correct for grammar rules:
     *
     * AssociationPathExpression             ::= CollectionValuedPathExpression | SingleValuedAssociationPathExpression
     * SingleValuedPathExpression            ::= StateFieldPathExpression | SingleValuedAssociationPathExpression
     * StateFieldPathExpression              ::= IdentificationVariable "." StateField
     * SingleValuedAssociationPathExpression ::= IdentificationVariable "." SingleValuedAssociationField
     * CollectionValuedPathExpression        ::= IdentificationVariable "." CollectionValuedAssociationField
     *
     * @param array $deferredItem
     * @param mixed $AST
     */
    private function _processDeferredPathExpressions($AST)
    {
        foreach ($this->_deferredPathExpressions as $deferredItem) {
            $pathExpression = $deferredItem['expression'];

            $qComp = $this->_queryComponents[$pathExpression->identificationVariable];
            $class = $qComp['metadata'];

            if (($field = $pathExpression->field) === null) {
                $field = $pathExpression->field = $class->identifier[0];
            }

            // Check if field or association exists
            if ( ! isset($class->associationMappings[$field]) && ! isset($class->fieldMappings[$field])) {
                $this->semanticalError(
                    'Class ' . $class->name . ' has no field or association named ' . $field,
                    $deferredItem['token']
                );
            }

            $fieldType = AST\PathExpression::TYPE_STATE_FIELD;

            if (isset($class->associationMappings[$field])) {
                $assoc = $class->associationMappings[$field];

                $fieldType = ($assoc['type'] & ClassMetadata::TO_ONE)
                	? AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION
                	: AST\PathExpression::TYPE_COLLECTION_VALUED_ASSOCIATION;
            }

            // Validate if PathExpression is one of the expected types
            $expectedType = $pathExpression->expectedType;

            if ( ! ($expectedType & $fieldType)) {
                // We need to recognize which was expected type(s)
                $expectedStringTypes = array();

                // Validate state field type
                if ($expectedType & AST\PathExpression::TYPE_STATE_FIELD) {
                    $expectedStringTypes[] = 'StateFieldPathExpression';
                }

                // Validate single valued association (*-to-one)
                if ($expectedType & AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION) {
                    $expectedStringTypes[] = 'SingleValuedAssociationField';
                }

                // Validate single valued association (*-to-many)
                if ($expectedType & AST\PathExpression::TYPE_COLLECTION_VALUED_ASSOCIATION) {
                    $expectedStringTypes[] = 'CollectionValuedAssociationField';
                }

                // Build the error message
                $semanticalError  = 'Invalid PathExpression. ';
                $semanticalError .= (count($expectedStringTypes) == 1)
                    ? 'Must be a ' . $expectedStringTypes[0] . '.'
                    : implode(' or ', $expectedStringTypes) . ' expected.';

                $this->semanticalError($semanticalError, $deferredItem['token']);
            }

            // We need to force the type in PathExpression
            $pathExpression->type = $fieldType;
        }
    }

    private function _processRootEntityAliasSelected()
    {
        if ( ! count($this->_identVariableExpressions)) {
            return;
        }

        $foundRootEntity = false;

        foreach ($this->_identVariableExpressions AS $dqlAlias => $expr) {
            if (isset($this->_queryComponents[$dqlAlias]) && $this->_queryComponents[$dqlAlias]['parent'] === null) {
                $foundRootEntity = true;
            }
        }

        if ( ! $foundRootEntity) {
            $this->semanticalError('Cannot select entity through identification variables without choosing at least one root entity alias.');
        }
    }

    /**
     * QueryLanguage ::= SelectStatement | UpdateStatement | DeleteStatement
     *
     * @return \Doctrine\ORM\Query\AST\SelectStatement |
     *         \Doctrine\ORM\Query\AST\UpdateStatement |
     *         \Doctrine\ORM\Query\AST\DeleteStatement
     */
    public function QueryLanguage()
    {
        $this->_lexer->moveNext();

        switch ($this->_lexer->lookahead['type']) {
            case Lexer::T_SELECT:
                $statement = $this->SelectStatement();
                break;

            case Lexer::T_UPDATE:
                $statement = $this->UpdateStatement();
                break;

            case Lexer::T_DELETE:
                $statement = $this->DeleteStatement();
                break;

            default:
                $this->syntaxError('SELECT, UPDATE or DELETE');
                break;
        }

        // Check for end of string
        if ($this->_lexer->lookahead !== null) {
            $this->syntaxError('end of string');
        }

        return $statement;
    }

    /**
     * SelectStatement ::= SelectClause FromClause [WhereClause] [GroupByClause] [HavingClause] [OrderByClause]
     *
     * @return \Doctrine\ORM\Query\AST\SelectStatement
     */
    public function SelectStatement()
    {
        $selectStatement = new AST\SelectStatement($this->SelectClause(), $this->FromClause());

        $selectStatement->whereClause   = $this->_lexer->isNextToken(Lexer::T_WHERE) ? $this->WhereClause() : null;
        $selectStatement->groupByClause = $this->_lexer->isNextToken(Lexer::T_GROUP) ? $this->GroupByClause() : null;
        $selectStatement->havingClause  = $this->_lexer->isNextToken(Lexer::T_HAVING) ? $this->HavingClause() : null;
        $selectStatement->orderByClause = $this->_lexer->isNextToken(Lexer::T_ORDER) ? $this->OrderByClause() : null;

        return $selectStatement;
    }

    /**
     * UpdateStatement ::= UpdateClause [WhereClause]
     *
     * @return \Doctrine\ORM\Query\AST\UpdateStatement
     */
    public function UpdateStatement()
    {
        $updateStatement = new AST\UpdateStatement($this->UpdateClause());

        $updateStatement->whereClause = $this->_lexer->isNextToken(Lexer::T_WHERE) ? $this->WhereClause() : null;

        return $updateStatement;
    }

    /**
     * DeleteStatement ::= DeleteClause [WhereClause]
     *
     * @return \Doctrine\ORM\Query\AST\DeleteStatement
     */
    public function DeleteStatement()
    {
        $deleteStatement = new AST\DeleteStatement($this->DeleteClause());

        $deleteStatement->whereClause = $this->_lexer->isNextToken(Lexer::T_WHERE) ? $this->WhereClause() : null;

        return $deleteStatement;
    }

    /**
     * IdentificationVariable ::= identifier
     *
     * @return string
     */
    public function IdentificationVariable()
    {
        $this->match(Lexer::T_IDENTIFIER);

        $identVariable = $this->_lexer->token['value'];

        $this->_deferredIdentificationVariables[] = array(
            'expression'   => $identVariable,
            'nestingLevel' => $this->_nestingLevel,
            'token'        => $this->_lexer->token,
        );

        return $identVariable;
    }

    /**
     * AliasIdentificationVariable = identifier
     *
     * @return string
     */
    public function AliasIdentificationVariable()
    {
        $this->match(Lexer::T_IDENTIFIER);

        $aliasIdentVariable = $this->_lexer->token['value'];
        $exists = isset($this->_queryComponents[$aliasIdentVariable]);

        if ($exists) {
            $this->semanticalError("'$aliasIdentVariable' is already defined.", $this->_lexer->token);
        }

        return $aliasIdentVariable;
    }

    /**
     * AbstractSchemaName ::= identifier
     *
     * @return string
     */
    public function AbstractSchemaName()
    {
        $this->match(Lexer::T_IDENTIFIER);

        $schemaName = ltrim($this->_lexer->token['value'], '\\');

        if (strrpos($schemaName, ':') !== false) {
            list($namespaceAlias, $simpleClassName) = explode(':', $schemaName);

            $schemaName = $this->_em->getConfiguration()->getEntityNamespace($namespaceAlias) . '\\' . $simpleClassName;
        }

        $exists = class_exists($schemaName, true);

        if ( ! $exists) {
            $this->semanticalError("Class '$schemaName' is not defined.", $this->_lexer->token);
        }

        return $schemaName;
    }

    /**
     * AliasResultVariable ::= identifier
     *
     * @return string
     */
    public function AliasResultVariable()
    {
        $this->match(Lexer::T_IDENTIFIER);

        $resultVariable = $this->_lexer->token['value'];
        $exists = isset($this->_queryComponents[$resultVariable]);

        if ($exists) {
            $this->semanticalError("'$resultVariable' is already defined.", $this->_lexer->token);
        }

        return $resultVariable;
    }

    /**
     * ResultVariable ::= identifier
     *
     * @return string
     */
    public function ResultVariable()
    {
        $this->match(Lexer::T_IDENTIFIER);

        $resultVariable = $this->_lexer->token['value'];

        // Defer ResultVariable validation
        $this->_deferredResultVariables[] = array(
            'expression'   => $resultVariable,
            'nestingLevel' => $this->_nestingLevel,
            'token'        => $this->_lexer->token,
        );

        return $resultVariable;
    }

    /**
     * JoinAssociationPathExpression ::= IdentificationVariable "." (CollectionValuedAssociationField | SingleValuedAssociationField)
     *
     * @return \Doctrine\ORM\Query\AST\JoinAssociationPathExpression
     */
    public function JoinAssociationPathExpression()
    {
        $token         = $this->_lexer->lookahead;
        $identVariable = $this->IdentificationVariable();

        if ( ! isset($this->_queryComponents[$identVariable])) {
            $this->semanticalError(
                'Identification Variable ' . $identVariable .' used in join path expression but was not defined before.'
            );
        }

        $this->match(Lexer::T_DOT);
        $this->match(Lexer::T_IDENTIFIER);

        $field = $this->_lexer->token['value'];

        // Validate association field
        $qComp = $this->_queryComponents[$identVariable];
        $class = $qComp['metadata'];

        if ( ! isset($class->associationMappings[$field])) {
            $this->semanticalError('Class ' . $class->name . ' has no association named ' . $field);
        }

        return new AST\JoinAssociationPathExpression($identVariable, $field);
    }

    /**
     * Parses an arbitrary path expression and defers semantical validation
     * based on expected types.
     *
     * PathExpression ::= IdentificationVariable "." identifier
     *
     * @param integer $expectedTypes
     * @return \Doctrine\ORM\Query\AST\PathExpression
     */
    public function PathExpression($expectedTypes)
    {
        $token = $this->_lexer->lookahead;
        $identVariable = $this->IdentificationVariable();
        $field = null;

        if ($this->_lexer->isNextToken(Lexer::T_DOT)) {
            $this->match(Lexer::T_DOT);
            $this->match(Lexer::T_IDENTIFIER);

            $field = $this->_lexer->token['value'];
        }

        // Creating AST node
        $pathExpr = new AST\PathExpression($expectedTypes, $identVariable, $field);

        // Defer PathExpression validation if requested to be defered
        $this->_deferredPathExpressions[] = array(
            'expression'   => $pathExpr,
            'nestingLevel' => $this->_nestingLevel,
            'token'        => $this->_lexer->token,
        );

        return $pathExpr;
    }

    /**
     * AssociationPathExpression ::= CollectionValuedPathExpression | SingleValuedAssociationPathExpression
     *
     * @return \Doctrine\ORM\Query\AST\PathExpression
     */
    public function AssociationPathExpression()
    {
        return $this->PathExpression(
            AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION |
            AST\PathExpression::TYPE_COLLECTION_VALUED_ASSOCIATION
        );
    }

    /**
     * SingleValuedPathExpression ::= StateFieldPathExpression | SingleValuedAssociationPathExpression
     *
     * @return \Doctrine\ORM\Query\AST\PathExpression
     */
    public function SingleValuedPathExpression()
    {
        return $this->PathExpression(
            AST\PathExpression::TYPE_STATE_FIELD |
            AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION
        );
    }

    /**
     * StateFieldPathExpression ::= IdentificationVariable "." StateField
     *
     * @return \Doctrine\ORM\Query\AST\PathExpression
     */
    public function StateFieldPathExpression()
    {
        return $this->PathExpression(AST\PathExpression::TYPE_STATE_FIELD);
    }

    /**
     * SingleValuedAssociationPathExpression ::= IdentificationVariable "." SingleValuedAssociationField
     *
     * @return \Doctrine\ORM\Query\AST\PathExpression
     */
    public function SingleValuedAssociationPathExpression()
    {
        return $this->PathExpression(AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION);
    }

    /**
     * CollectionValuedPathExpression ::= IdentificationVariable "." CollectionValuedAssociationField
     *
     * @return \Doctrine\ORM\Query\AST\PathExpression
     */
    public function CollectionValuedPathExpression()
    {
        return $this->PathExpression(AST\PathExpression::TYPE_COLLECTION_VALUED_ASSOCIATION);
    }

    /**
     * SelectClause ::= "SELECT" ["DISTINCT"] SelectExpression {"," SelectExpression}
     *
     * @return \Doctrine\ORM\Query\AST\SelectClause
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

        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);

            $selectExpressions[] = $this->SelectExpression();
        }

        return new AST\SelectClause($selectExpressions, $isDistinct);
    }

    /**
     * SimpleSelectClause ::= "SELECT" ["DISTINCT"] SimpleSelectExpression
     *
     * @return \Doctrine\ORM\Query\AST\SimpleSelectClause
     */
    public function SimpleSelectClause()
    {
        $isDistinct = false;
        $this->match(Lexer::T_SELECT);

        if ($this->_lexer->isNextToken(Lexer::T_DISTINCT)) {
            $this->match(Lexer::T_DISTINCT);

            $isDistinct = true;
        }

        return new AST\SimpleSelectClause($this->SimpleSelectExpression(), $isDistinct);
    }

    /**
     * UpdateClause ::= "UPDATE" AbstractSchemaName ["AS"] AliasIdentificationVariable "SET" UpdateItem {"," UpdateItem}*
     *
     * @return \Doctrine\ORM\Query\AST\UpdateClause
     */
    public function UpdateClause()
    {
        $this->match(Lexer::T_UPDATE);
        $token = $this->_lexer->lookahead;
        $abstractSchemaName = $this->AbstractSchemaName();

        if ($this->_lexer->isNextToken(Lexer::T_AS)) {
            $this->match(Lexer::T_AS);
        }

        $aliasIdentificationVariable = $this->AliasIdentificationVariable();

        $class = $this->_em->getClassMetadata($abstractSchemaName);

        // Building queryComponent
        $queryComponent = array(
            'metadata'     => $class,
            'parent'       => null,
            'relation'     => null,
            'map'          => null,
            'nestingLevel' => $this->_nestingLevel,
            'token'        => $token,
        );

        $this->_queryComponents[$aliasIdentificationVariable] = $queryComponent;

        $this->match(Lexer::T_SET);

        $updateItems = array();
        $updateItems[] = $this->UpdateItem();

        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);

            $updateItems[] = $this->UpdateItem();
        }

        $updateClause = new AST\UpdateClause($abstractSchemaName, $updateItems);
        $updateClause->aliasIdentificationVariable = $aliasIdentificationVariable;

        return $updateClause;
    }

    /**
     * DeleteClause ::= "DELETE" ["FROM"] AbstractSchemaName ["AS"] AliasIdentificationVariable
     *
     * @return \Doctrine\ORM\Query\AST\DeleteClause
     */
    public function DeleteClause()
    {
        $this->match(Lexer::T_DELETE);

        if ($this->_lexer->isNextToken(Lexer::T_FROM)) {
            $this->match(Lexer::T_FROM);
        }

        $token = $this->_lexer->lookahead;
        $deleteClause = new AST\DeleteClause($this->AbstractSchemaName());

        if ($this->_lexer->isNextToken(Lexer::T_AS)) {
            $this->match(Lexer::T_AS);
        }

        $aliasIdentificationVariable = $this->AliasIdentificationVariable();

        $deleteClause->aliasIdentificationVariable = $aliasIdentificationVariable;
        $class = $this->_em->getClassMetadata($deleteClause->abstractSchemaName);

        // Building queryComponent
        $queryComponent = array(
            'metadata'     => $class,
            'parent'       => null,
            'relation'     => null,
            'map'          => null,
            'nestingLevel' => $this->_nestingLevel,
            'token'        => $token,
        );

        $this->_queryComponents[$aliasIdentificationVariable] = $queryComponent;

        return $deleteClause;
    }

    /**
     * FromClause ::= "FROM" IdentificationVariableDeclaration {"," IdentificationVariableDeclaration}*
     *
     * @return \Doctrine\ORM\Query\AST\FromClause
     */
    public function FromClause()
    {
        $this->match(Lexer::T_FROM);

        $identificationVariableDeclarations = array();
        $identificationVariableDeclarations[] = $this->IdentificationVariableDeclaration();

        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);

            $identificationVariableDeclarations[] = $this->IdentificationVariableDeclaration();
        }

        return new AST\FromClause($identificationVariableDeclarations);
    }

    /**
     * SubselectFromClause ::= "FROM" SubselectIdentificationVariableDeclaration {"," SubselectIdentificationVariableDeclaration}*
     *
     * @return \Doctrine\ORM\Query\AST\SubselectFromClause
     */
    public function SubselectFromClause()
    {
        $this->match(Lexer::T_FROM);

        $identificationVariables = array();
        $identificationVariables[] = $this->SubselectIdentificationVariableDeclaration();

        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);

            $identificationVariables[] = $this->SubselectIdentificationVariableDeclaration();
        }

        return new AST\SubselectFromClause($identificationVariables);
    }

    /**
     * WhereClause ::= "WHERE" ConditionalExpression
     *
     * @return \Doctrine\ORM\Query\AST\WhereClause
     */
    public function WhereClause()
    {
        $this->match(Lexer::T_WHERE);

        return new AST\WhereClause($this->ConditionalExpression());
    }

    /**
     * HavingClause ::= "HAVING" ConditionalExpression
     *
     * @return \Doctrine\ORM\Query\AST\HavingClause
     */
    public function HavingClause()
    {
        $this->match(Lexer::T_HAVING);

        return new AST\HavingClause($this->ConditionalExpression());
    }

    /**
     * GroupByClause ::= "GROUP" "BY" GroupByItem {"," GroupByItem}*
     *
     * @return \Doctrine\ORM\Query\AST\GroupByClause
     */
    public function GroupByClause()
    {
        $this->match(Lexer::T_GROUP);
        $this->match(Lexer::T_BY);

        $groupByItems = array($this->GroupByItem());

        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);

            $groupByItems[] = $this->GroupByItem();
        }

        return new AST\GroupByClause($groupByItems);
    }

    /**
     * OrderByClause ::= "ORDER" "BY" OrderByItem {"," OrderByItem}*
     *
     * @return \Doctrine\ORM\Query\AST\OrderByClause
     */
    public function OrderByClause()
    {
        $this->match(Lexer::T_ORDER);
        $this->match(Lexer::T_BY);

        $orderByItems = array();
        $orderByItems[] = $this->OrderByItem();

        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);

            $orderByItems[] = $this->OrderByItem();
        }

        return new AST\OrderByClause($orderByItems);
    }

    /**
     * Subselect ::= SimpleSelectClause SubselectFromClause [WhereClause] [GroupByClause] [HavingClause] [OrderByClause]
     *
     * @return \Doctrine\ORM\Query\AST\Subselect
     */
    public function Subselect()
    {
        // Increase query nesting level
        $this->_nestingLevel++;

        $subselect = new AST\Subselect($this->SimpleSelectClause(), $this->SubselectFromClause());

        $subselect->whereClause   = $this->_lexer->isNextToken(Lexer::T_WHERE) ? $this->WhereClause() : null;
        $subselect->groupByClause = $this->_lexer->isNextToken(Lexer::T_GROUP) ? $this->GroupByClause() : null;
        $subselect->havingClause  = $this->_lexer->isNextToken(Lexer::T_HAVING) ? $this->HavingClause() : null;
        $subselect->orderByClause = $this->_lexer->isNextToken(Lexer::T_ORDER) ? $this->OrderByClause() : null;

        // Decrease query nesting level
        $this->_nestingLevel--;

        return $subselect;
    }

    /**
     * UpdateItem ::= SingleValuedPathExpression "=" NewValue
     *
     * @return \Doctrine\ORM\Query\AST\UpdateItem
     */
    public function UpdateItem()
    {
        $pathExpr = $this->SingleValuedPathExpression();

        $this->match(Lexer::T_EQUALS);

        $updateItem = new AST\UpdateItem($pathExpr, $this->NewValue());

        return $updateItem;
    }

    /**
     * GroupByItem ::= IdentificationVariable | ResultVariable | SingleValuedPathExpression
     *
     * @return string | \Doctrine\ORM\Query\AST\PathExpression
     */
    public function GroupByItem()
    {
        // We need to check if we are in a IdentificationVariable or SingleValuedPathExpression
        $glimpse = $this->_lexer->glimpse();

        if ($glimpse['type'] === Lexer::T_DOT) {
            return $this->SingleValuedPathExpression();
        }

        // Still need to decide between IdentificationVariable or ResultVariable
        $lookaheadValue = $this->_lexer->lookahead['value'];

        if ( ! isset($this->_queryComponents[$lookaheadValue])) {
            $this->semanticalError('Cannot group by undefined identification or result variable.');
        }

        return (isset($this->_queryComponents[$lookaheadValue]['metadata']))
            ? $this->IdentificationVariable()
            : $this->ResultVariable();
    }

    /**
     * OrderByItem ::= (ResultVariable | SingleValuedPathExpression) ["ASC" | "DESC"]
     *
     * @return \Doctrine\ORM\Query\AST\OrderByItem
     */
    public function OrderByItem()
    {
        $type = 'ASC';

        // We need to check if we are in a ResultVariable or StateFieldPathExpression
        $glimpse = $this->_lexer->glimpse();
        $expr    = ($glimpse['type'] != Lexer::T_DOT) ? $this->ResultVariable() : $this->SingleValuedPathExpression();

        $item = new AST\OrderByItem($expr);

        switch (true) {
            case ($this->_lexer->isNextToken(Lexer::T_DESC)):
                $this->match(Lexer::T_DESC);
                $type = 'DESC';
                break;

            case ($this->_lexer->isNextToken(Lexer::T_ASC)):
                $this->match(Lexer::T_ASC);
                break;

            default:
                // Do nothing
        }

        $item->type = $type;

        return $item;
    }

    /**
     * NewValue ::= SimpleArithmeticExpression | StringPrimary | DatetimePrimary | BooleanPrimary |
     *      EnumPrimary | SimpleEntityExpression | "NULL"
     *
     * NOTE: Since it is not possible to correctly recognize individual types, here is the full
     * grammar that needs to be supported:
     *
     * NewValue ::= SimpleArithmeticExpression | "NULL"
     *
     * SimpleArithmeticExpression covers all *Primary grammar rules and also SimplEntityExpression
     */
    public function NewValue()
    {
        if ($this->_lexer->isNextToken(Lexer::T_NULL)) {
            $this->match(Lexer::T_NULL);

            return null;
        }

        if ($this->_lexer->isNextToken(Lexer::T_INPUT_PARAMETER)) {
            $this->match(Lexer::T_INPUT_PARAMETER);

            return new AST\InputParameter($this->_lexer->token['value']);
        }

        return $this->SimpleArithmeticExpression();
    }

    /**
     * IdentificationVariableDeclaration ::= RangeVariableDeclaration [IndexBy] {JoinVariableDeclaration}*
     *
     * @return \Doctrine\ORM\Query\AST\IdentificationVariableDeclaration
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
     * SubselectIdentificationVariableDeclaration ::= IdentificationVariableDeclaration | (AssociationPathExpression ["AS"] AliasIdentificationVariable)
     *
     * @return \Doctrine\ORM\Query\AST\SubselectIdentificationVariableDeclaration |
     *         \Doctrine\ORM\Query\AST\IdentificationVariableDeclaration
     */
    public function SubselectIdentificationVariableDeclaration()
    {
        $glimpse = $this->_lexer->glimpse();

        /* NOT YET IMPLEMENTED!

        if ($glimpse['type'] == Lexer::T_DOT) {
            $subselectIdVarDecl = new AST\SubselectIdentificationVariableDeclaration();
            $subselectIdVarDecl->associationPathExpression = $this->AssociationPathExpression();
            $this->match(Lexer::T_AS);
            $subselectIdVarDecl->aliasIdentificationVariable = $this->AliasIdentificationVariable();

            return $subselectIdVarDecl;
        }
        */

        return $this->IdentificationVariableDeclaration();
    }

    /**
     * JoinVariableDeclaration ::= Join [IndexBy]
     *
     * @return \Doctrine\ORM\Query\AST\JoinVariableDeclaration
     */
    public function JoinVariableDeclaration()
    {
        $join    = $this->Join();
        $indexBy = $this->_lexer->isNextToken(Lexer::T_INDEX) ? $this->IndexBy() : null;

        return new AST\JoinVariableDeclaration($join, $indexBy);
    }

    /**
     * RangeVariableDeclaration ::= AbstractSchemaName ["AS"] AliasIdentificationVariable
     *
     * @return \Doctrine\ORM\Query\AST\RangeVariableDeclaration
     */
    public function RangeVariableDeclaration()
    {
        $abstractSchemaName = $this->AbstractSchemaName();

        if ($this->_lexer->isNextToken(Lexer::T_AS)) {
            $this->match(Lexer::T_AS);
        }

        $token = $this->_lexer->lookahead;
        $aliasIdentificationVariable = $this->AliasIdentificationVariable();
        $classMetadata = $this->_em->getClassMetadata($abstractSchemaName);

        // Building queryComponent
        $queryComponent = array(
            'metadata'     => $classMetadata,
            'parent'       => null,
            'relation'     => null,
            'map'          => null,
            'nestingLevel' => $this->_nestingLevel,
            'token'        => $token
        );

        $this->_queryComponents[$aliasIdentificationVariable] = $queryComponent;

        return new AST\RangeVariableDeclaration($abstractSchemaName, $aliasIdentificationVariable);
    }

    /**
     * PartialObjectExpression ::= "PARTIAL" IdentificationVariable "." PartialFieldSet
     * PartialFieldSet ::= "{" SimpleStateField {"," SimpleStateField}* "}"
     *
     * @return array
     */
    public function PartialObjectExpression()
    {
        $this->match(Lexer::T_PARTIAL);

        $partialFieldSet = array();

        $identificationVariable = $this->IdentificationVariable();

        $this->match(Lexer::T_DOT);
        $this->match(Lexer::T_OPEN_CURLY_BRACE);
        $this->match(Lexer::T_IDENTIFIER);

        $partialFieldSet[] = $this->_lexer->token['value'];

        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $this->match(Lexer::T_IDENTIFIER);

            $partialFieldSet[] = $this->_lexer->token['value'];
        }

        $this->match(Lexer::T_CLOSE_CURLY_BRACE);

        $partialObjectExpression = new AST\PartialObjectExpression($identificationVariable, $partialFieldSet);

        // Defer PartialObjectExpression validation
        $this->_deferredPartialObjectExpressions[] = array(
            'expression'   => $partialObjectExpression,
            'nestingLevel' => $this->_nestingLevel,
            'token'        => $this->_lexer->token,
        );

        return $partialObjectExpression;
    }

    /**
     * Join ::= ["LEFT" ["OUTER"] | "INNER"] "JOIN" JoinAssociationPathExpression
     *          ["AS"] AliasIdentificationVariable ["WITH" ConditionalExpression]
     *
     * @return \Doctrine\ORM\Query\AST\Join
     */
    public function Join()
    {
        // Check Join type
        $joinType = AST\Join::JOIN_TYPE_INNER;

        switch (true) {
            case ($this->_lexer->isNextToken(Lexer::T_LEFT)):
                $this->match(Lexer::T_LEFT);

                $joinType = AST\Join::JOIN_TYPE_LEFT;

                // Possible LEFT OUTER join
                if ($this->_lexer->isNextToken(Lexer::T_OUTER)) {
                    $this->match(Lexer::T_OUTER);

                    $joinType = AST\Join::JOIN_TYPE_LEFTOUTER;
                }
                break;

            case ($this->_lexer->isNextToken(Lexer::T_INNER)):
                $this->match(Lexer::T_INNER);
                break;

            default:
                // Do nothing
        }

        $this->match(Lexer::T_JOIN);

        $joinPathExpression = $this->JoinAssociationPathExpression();

        if ($this->_lexer->isNextToken(Lexer::T_AS)) {
            $this->match(Lexer::T_AS);
        }

        $token = $this->_lexer->lookahead;
        $aliasIdentificationVariable = $this->AliasIdentificationVariable();

        // Verify that the association exists.
        $parentClass = $this->_queryComponents[$joinPathExpression->identificationVariable]['metadata'];
        $assocField  = $joinPathExpression->associationField;

        if ( ! $parentClass->hasAssociation($assocField)) {
            $this->semanticalError(
                "Class " . $parentClass->name . " has no association named '$assocField'."
            );
        }

        $targetClassName = $parentClass->associationMappings[$assocField]['targetEntity'];

        // Building queryComponent
        $joinQueryComponent = array(
            'metadata'     => $this->_em->getClassMetadata($targetClassName),
            'parent'       => $joinPathExpression->identificationVariable,
            'relation'     => $parentClass->getAssociationMapping($assocField),
            'map'          => null,
            'nestingLevel' => $this->_nestingLevel,
            'token'        => $token
        );

        $this->_queryComponents[$aliasIdentificationVariable] = $joinQueryComponent;

        // Create AST node
        $join = new AST\Join($joinType, $joinPathExpression, $aliasIdentificationVariable);

        // Check for ad-hoc Join conditions
        if ($this->_lexer->isNextToken(Lexer::T_WITH)) {
            $this->match(Lexer::T_WITH);

            $join->conditionalExpression = $this->ConditionalExpression();
        }

        return $join;
    }

    /**
     * IndexBy ::= "INDEX" "BY" StateFieldPathExpression
     *
     * @return \Doctrine\ORM\Query\AST\IndexBy
     */
    public function IndexBy()
    {
        $this->match(Lexer::T_INDEX);
        $this->match(Lexer::T_BY);
        $pathExpr = $this->StateFieldPathExpression();

        // Add the INDEX BY info to the query component
        $this->_queryComponents[$pathExpr->identificationVariable]['map'] = $pathExpr->field;

        return new AST\IndexBy($pathExpr);
    }

    /**
     * ScalarExpression ::= SimpleArithmeticExpression | StringPrimary | DateTimePrimary |
     *                      StateFieldPathExpression | BooleanPrimary | CaseExpression |
     *                      InstanceOfExpression
     *
     * @return mixed One of the possible expressions or subexpressions.
     */
    public function ScalarExpression()
    {
        $lookahead = $this->_lexer->lookahead['type'];

        switch ($lookahead) {
            case Lexer::T_IDENTIFIER:
                $this->_lexer->peek(); // lookahead => '.'
                $this->_lexer->peek(); // lookahead => token after '.'
                $peek = $this->_lexer->peek(); // lookahead => token after the token after the '.'
                $this->_lexer->resetPeek();

                if ($this->_isMathOperator($peek)) {
                    return $this->SimpleArithmeticExpression();
                }

                return $this->StateFieldPathExpression();

            case Lexer::T_INTEGER:
            case Lexer::T_FLOAT:
                return $this->SimpleArithmeticExpression();

            case Lexer::T_STRING:
                return $this->StringPrimary();

            case Lexer::T_TRUE:
            case Lexer::T_FALSE:
                $this->match($lookahead);

                return new AST\Literal(AST\Literal::BOOLEAN, $this->_lexer->token['value']);

            case Lexer::T_INPUT_PARAMETER:
                return $this->InputParameter();

            case Lexer::T_CASE:
            case Lexer::T_COALESCE:
            case Lexer::T_NULLIF:
                // Since NULLIF and COALESCE can be identified as a function,
                // we need to check if before check for FunctionDeclaration
                return $this->CaseExpression();

            default:
                if ( ! ($this->_isFunction() || $this->_isAggregateFunction($lookahead))) {
                    $this->syntaxError();
                }

                // We may be in an ArithmeticExpression (find the matching ")" and inspect for Math operator)
                $this->_lexer->peek(); // "("
                $peek = $this->_peekBeyondClosingParenthesis();

                if ($this->_isMathOperator($peek)) {
                    return $this->SimpleArithmeticExpression();
                }

                if ($this->_isAggregateFunction($this->_lexer->lookahead['type'])) {
                    return $this->AggregateExpression();
                }

                return $this->FunctionDeclaration();
        }
    }

    /**
     * CaseExpression ::= GeneralCaseExpression | SimpleCaseExpression | CoalesceExpression | NullifExpression
     * GeneralCaseExpression ::= "CASE" WhenClause {WhenClause}* "ELSE" ScalarExpression "END"
     * WhenClause ::= "WHEN" ConditionalExpression "THEN" ScalarExpression
     * SimpleCaseExpression ::= "CASE" CaseOperand SimpleWhenClause {SimpleWhenClause}* "ELSE" ScalarExpression "END"
     * CaseOperand ::= StateFieldPathExpression | TypeDiscriminator
     * SimpleWhenClause ::= "WHEN" ScalarExpression "THEN" ScalarExpression
     * CoalesceExpression ::= "COALESCE" "(" ScalarExpression {"," ScalarExpression}* ")"
     * NullifExpression ::= "NULLIF" "(" ScalarExpression "," ScalarExpression ")"
     *
     * @return mixed One of the possible expressions or subexpressions.
     */
    public function CaseExpression()
    {
        $lookahead = $this->_lexer->lookahead['type'];

        switch ($lookahead) {
            case Lexer::T_NULLIF:
                return $this->NullIfExpression();

            case Lexer::T_COALESCE:
                return $this->CoalesceExpression();

            case Lexer::T_CASE:
                $this->_lexer->resetPeek();
                $peek = $this->_lexer->peek();

                if ($peek['type'] === Lexer::T_WHEN) {
                    return $this->GeneralCaseExpression();
                }

                return $this->SimpleCaseExpression();

            default:
                // Do nothing
                break;
        }

        $this->syntaxError();
    }

    /**
     * CoalesceExpression ::= "COALESCE" "(" ScalarExpression {"," ScalarExpression}* ")"
     *
     * @return \Doctrine\ORM\Query\AST\CoalesceExpression
     */
    public function CoalesceExpression()
    {
        $this->match(Lexer::T_COALESCE);
        $this->match(Lexer::T_OPEN_PARENTHESIS);

        // Process ScalarExpressions (1..N)
        $scalarExpressions = array();
        $scalarExpressions[] = $this->ScalarExpression();

        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);

            $scalarExpressions[] = $this->ScalarExpression();
        }

        $this->match(Lexer::T_CLOSE_PARENTHESIS);

        return new AST\CoalesceExpression($scalarExpressions);
    }

    /**
     * NullIfExpression ::= "NULLIF" "(" ScalarExpression "," ScalarExpression ")"
     *
     * @return \Doctrine\ORM\Query\AST\NullIfExpression
     */
    public function NullIfExpression()
    {
        $this->match(Lexer::T_NULLIF);
        $this->match(Lexer::T_OPEN_PARENTHESIS);

        $firstExpression = $this->ScalarExpression();
        $this->match(Lexer::T_COMMA);
        $secondExpression = $this->ScalarExpression();

        $this->match(Lexer::T_CLOSE_PARENTHESIS);

        return new AST\NullIfExpression($firstExpression, $secondExpression);
    }

    /**
     * GeneralCaseExpression ::= "CASE" WhenClause {WhenClause}* "ELSE" ScalarExpression "END"
     *
     * @return \Doctrine\ORM\Query\AST\GeneralExpression
     */
    public function GeneralCaseExpression()
    {
        $this->match(Lexer::T_CASE);

        // Process WhenClause (1..N)
        $whenClauses = array();

        do {
            $whenClauses[] = $this->WhenClause();
        } while ($this->_lexer->isNextToken(Lexer::T_WHEN));

        $this->match(Lexer::T_ELSE);
        $scalarExpression = $this->ScalarExpression();
        $this->match(Lexer::T_END);

        return new AST\GeneralCaseExpression($whenClauses, $scalarExpression);
    }

    /**
     * SimpleCaseExpression ::= "CASE" CaseOperand SimpleWhenClause {SimpleWhenClause}* "ELSE" ScalarExpression "END"
     * CaseOperand ::= StateFieldPathExpression | TypeDiscriminator
     */
    public function SimpleCaseExpression()
    {
        $this->match(Lexer::T_CASE);
        $caseOperand = $this->StateFieldPathExpression();

        // Process SimpleWhenClause (1..N)
        $simpleWhenClauses = array();

        do {
            $simpleWhenClauses[] = $this->SimpleWhenClause();
        } while ($this->_lexer->isNextToken(Lexer::T_WHEN));

        $this->match(Lexer::T_ELSE);
        $scalarExpression = $this->ScalarExpression();
        $this->match(Lexer::T_END);

        return new AST\SimpleCaseExpression($caseOperand, $simpleWhenClauses, $scalarExpression);
    }

    /**
     * WhenClause ::= "WHEN" ConditionalExpression "THEN" ScalarExpression
     *
     * @return \Doctrine\ORM\Query\AST\WhenExpression
     */
    public function WhenClause()
    {
        $this->match(Lexer::T_WHEN);
        $conditionalExpression = $this->ConditionalExpression();
        $this->match(Lexer::T_THEN);

        return new AST\WhenClause($conditionalExpression, $this->ScalarExpression());
    }

    /**
     * SimpleWhenClause ::= "WHEN" ScalarExpression "THEN" ScalarExpression
     *
     * @return \Doctrine\ORM\Query\AST\SimpleWhenExpression
     */
    public function SimpleWhenClause()
    {
        $this->match(Lexer::T_WHEN);
        $conditionalExpression = $this->ScalarExpression();
        $this->match(Lexer::T_THEN);

        return new AST\SimpleWhenClause($conditionalExpression, $this->ScalarExpression());
    }

    /**
     * SelectExpression ::= (
     *     IdentificationVariable | ScalarExpression | AggregateExpression | FunctionDeclaration |
     *     PartialObjectExpression | "(" Subselect ")" | CaseExpression
     * ) [["AS"] ["HIDDEN"] AliasResultVariable]
     *
     * @return \Doctrine\ORM\Query\AST\SelectExpression
     */
    public function SelectExpression()
    {
        $expression    = null;
        $identVariable = null;
        $peek          = $this->_lexer->glimpse();
        $lookaheadType = $this->_lexer->lookahead['type'];

        switch (true) {
            // ScalarExpression (u.name)
            case ($lookaheadType === Lexer::T_IDENTIFIER && $peek['type'] === Lexer::T_DOT):
                $expression = $this->ScalarExpression();
                break;

            // IdentificationVariable (u)
            case ($lookaheadType === Lexer::T_IDENTIFIER && $peek['type'] !== Lexer::T_OPEN_PARENTHESIS):
                $expression = $identVariable = $this->IdentificationVariable();
                break;

            // CaseExpression (CASE ... or NULLIF(...) or COALESCE(...))
            case ($lookaheadType === Lexer::T_CASE):
            case ($lookaheadType === Lexer::T_COALESCE):
            case ($lookaheadType === Lexer::T_NULLIF):
                $expression = $this->CaseExpression();
                break;

            // DQL Function (SUM(u.value) or SUM(u.value) + 1)
            case ($this->_isFunction()):
                $this->_lexer->peek(); // "("

                switch (true) {
                    case ($this->_isMathOperator($this->_peekBeyondClosingParenthesis())):
                        // SUM(u.id) + COUNT(u.id)
                        $expression = $this->ScalarExpression();
                        break;

                    case ($this->_isAggregateFunction($lookaheadType)):
                        // COUNT(u.id)
                        $expression = $this->AggregateExpression();
                        break;

                    default:
                        // IDENTITY(u)
                        $expression = $this->FunctionDeclaration();
                        break;
                }

                break;

            // PartialObjectExpression (PARTIAL u.{id, name})
            case ($lookaheadType === Lexer::T_PARTIAL):
                $expression    = $this->PartialObjectExpression();
                $identVariable = $expression->identificationVariable;
                break;

            // Subselect
            case ($lookaheadType === Lexer::T_OPEN_PARENTHESIS && $peek['type'] === Lexer::T_SELECT):
                $this->match(Lexer::T_OPEN_PARENTHESIS);
                $expression = $this->Subselect();
                $this->match(Lexer::T_CLOSE_PARENTHESIS);
                break;

            // Shortcut: ScalarExpression => SimpleArithmeticExpression
            case ($lookaheadType === Lexer::T_OPEN_PARENTHESIS):
            case ($lookaheadType === Lexer::T_INTEGER):
            case ($lookaheadType === Lexer::T_STRING):
            case ($lookaheadType === Lexer::T_FLOAT):
            // SimpleArithmeticExpression : (- u.value ) or ( + u.value )
            case ($lookaheadType === Lexer::T_MINUS):
            case ($lookaheadType === Lexer::T_PLUS):
                $expression = $this->SimpleArithmeticExpression();
                break;

            default:
                $this->syntaxError(
                    'IdentificationVariable | ScalarExpression | AggregateExpression | FunctionDeclaration | PartialObjectExpression | "(" Subselect ")" | CaseExpression',
                    $this->_lexer->lookahead
                );
        }

        // [["AS"] ["HIDDEN"] AliasResultVariable]

        if ($this->_lexer->isNextToken(Lexer::T_AS)) {
            $this->match(Lexer::T_AS);
        }

        $hiddenAliasResultVariable = false;

        if ($this->_lexer->isNextToken(Lexer::T_HIDDEN)) {
            $this->match(Lexer::T_HIDDEN);

            $hiddenAliasResultVariable = true;
        }

        $aliasResultVariable = null;

        if ($this->_lexer->isNextToken(Lexer::T_IDENTIFIER)) {
            $token = $this->_lexer->lookahead;
            $aliasResultVariable = $this->AliasResultVariable();

            // Include AliasResultVariable in query components.
            $this->_queryComponents[$aliasResultVariable] = array(
                'resultVariable' => $expression,
                'nestingLevel'   => $this->_nestingLevel,
                'token'          => $token,
            );
        }

        // AST

        $expr = new AST\SelectExpression($expression, $aliasResultVariable, $hiddenAliasResultVariable);

        if ($identVariable) {
            $this->_identVariableExpressions[$identVariable] = $expr;
        }

        return $expr;
    }

    /**
     * SimpleSelectExpression ::=
     *      StateFieldPathExpression | IdentificationVariable |
     *      ((AggregateExpression | "(" Subselect ")" | ScalarExpression) [["AS"] AliasResultVariable])
     *
     * @return \Doctrine\ORM\Query\AST\SimpleSelectExpression
     */
    public function SimpleSelectExpression()
    {
        $peek = $this->_lexer->glimpse();

        switch ($this->_lexer->lookahead['type']) {
            case Lexer::T_IDENTIFIER:
                switch (true) {
                    case ($peek['type'] === Lexer::T_DOT):
                        $expression = $this->StateFieldPathExpression();

                        return new AST\SimpleSelectExpression($expression);

                    case ($peek['type'] !== Lexer::T_OPEN_PARENTHESIS):
                        $expression = $this->IdentificationVariable();

                        return new AST\SimpleSelectExpression($expression);

                    default:
                        // Do nothing
                }
                break;

            case Lexer::T_OPEN_PARENTHESIS:
                if ($peek['type'] !== Lexer::T_SELECT) {
                    // Shortcut: ScalarExpression => SimpleArithmeticExpression
                    $expression = $this->SimpleArithmeticExpression();

                    return new AST\SimpleSelectExpression($expression);
                }

                // Subselect
                $this->match(Lexer::T_OPEN_PARENTHESIS);
                $expression = $this->Subselect();
                $this->match(Lexer::T_CLOSE_PARENTHESIS);

                return new AST\SimpleSelectExpression($expression);

            default:
                // Do nothing
        }

        $this->_lexer->peek();

        $expression = $this->ScalarExpression();
        $expr       = new AST\SimpleSelectExpression($expression);

        if ($this->_lexer->isNextToken(Lexer::T_AS)) {
            $this->match(Lexer::T_AS);
        }

        if ($this->_lexer->isNextToken(Lexer::T_IDENTIFIER)) {
            $token = $this->_lexer->lookahead;
            $resultVariable = $this->AliasResultVariable();
            $expr->fieldIdentificationVariable = $resultVariable;

            // Include AliasResultVariable in query components.
            $this->_queryComponents[$resultVariable] = array(
                'resultvariable' => $expr,
                'nestingLevel'   => $this->_nestingLevel,
                'token'          => $token,
            );
        }

        return $expr;
    }

    /**
     * ConditionalExpression ::= ConditionalTerm {"OR" ConditionalTerm}*
     *
     * @return \Doctrine\ORM\Query\AST\ConditionalExpression
     */
    public function ConditionalExpression()
    {
        $conditionalTerms = array();
        $conditionalTerms[] = $this->ConditionalTerm();

        while ($this->_lexer->isNextToken(Lexer::T_OR)) {
            $this->match(Lexer::T_OR);

            $conditionalTerms[] = $this->ConditionalTerm();
        }

        // Phase 1 AST optimization: Prevent AST\ConditionalExpression
        // if only one AST\ConditionalTerm is defined
        if (count($conditionalTerms) == 1) {
            return $conditionalTerms[0];
        }

        return new AST\ConditionalExpression($conditionalTerms);
    }

    /**
     * ConditionalTerm ::= ConditionalFactor {"AND" ConditionalFactor}*
     *
     * @return \Doctrine\ORM\Query\AST\ConditionalTerm
     */
    public function ConditionalTerm()
    {
        $conditionalFactors = array();
        $conditionalFactors[] = $this->ConditionalFactor();

        while ($this->_lexer->isNextToken(Lexer::T_AND)) {
            $this->match(Lexer::T_AND);

            $conditionalFactors[] = $this->ConditionalFactor();
        }

        // Phase 1 AST optimization: Prevent AST\ConditionalTerm
        // if only one AST\ConditionalFactor is defined
        if (count($conditionalFactors) == 1) {
            return $conditionalFactors[0];
        }

        return new AST\ConditionalTerm($conditionalFactors);
    }

    /**
     * ConditionalFactor ::= ["NOT"] ConditionalPrimary
     *
     * @return \Doctrine\ORM\Query\AST\ConditionalFactor
     */
    public function ConditionalFactor()
    {
        $not = false;

        if ($this->_lexer->isNextToken(Lexer::T_NOT)) {
            $this->match(Lexer::T_NOT);

            $not = true;
        }

        $conditionalPrimary = $this->ConditionalPrimary();

        // Phase 1 AST optimization: Prevent AST\ConditionalFactor
        // if only one AST\ConditionalPrimary is defined
        if ( ! $not) {
            return $conditionalPrimary;
        }

        $conditionalFactor = new AST\ConditionalFactor($conditionalPrimary);
        $conditionalFactor->not = $not;

        return $conditionalFactor;
    }

    /**
     * ConditionalPrimary ::= SimpleConditionalExpression | "(" ConditionalExpression ")"
     *
     * @return \Doctrine\ORM\Query\AST\ConditionalPrimary
     */
    public function ConditionalPrimary()
    {
        $condPrimary = new AST\ConditionalPrimary;

        if ( ! $this->_lexer->isNextToken(Lexer::T_OPEN_PARENTHESIS)) {
            $condPrimary->simpleConditionalExpression = $this->SimpleConditionalExpression();

            return $condPrimary;
        }

        // Peek beyond the matching closing paranthesis ')'
        $peek = $this->_peekBeyondClosingParenthesis();

        if (in_array($peek['value'], array("=",  "<", "<=", "<>", ">", ">=", "!=")) ||
            in_array($peek['type'], array(Lexer::T_NOT, Lexer::T_BETWEEN, Lexer::T_LIKE, Lexer::T_IN, Lexer::T_IS, Lexer::T_EXISTS)) ||
            $this->_isMathOperator($peek)) {
            $condPrimary->simpleConditionalExpression = $this->SimpleConditionalExpression();

            return $condPrimary;
        }

        $this->match(Lexer::T_OPEN_PARENTHESIS);
        $condPrimary->conditionalExpression = $this->ConditionalExpression();
        $this->match(Lexer::T_CLOSE_PARENTHESIS);

        return $condPrimary;
    }

    /**
     * SimpleConditionalExpression ::=
     *      ComparisonExpression | BetweenExpression | LikeExpression |
     *      InExpression | NullComparisonExpression | ExistsExpression |
     *      EmptyCollectionComparisonExpression | CollectionMemberExpression |
     *      InstanceOfExpression
     */
    public function SimpleConditionalExpression()
    {
        $token = $this->_lexer->lookahead;

        if ($this->_lexer->isNextToken(Lexer::T_NOT)) {
            $token = $this->_lexer->glimpse();
        }

        if ($token['type'] === Lexer::T_EXISTS) {
            return $this->ExistsExpression();
        }

        $peek = $this->_lexer->glimpse();

        if ($token['type'] === Lexer::T_IDENTIFIER || $token['type'] === Lexer::T_INPUT_PARAMETER) {
            if ($peek['value'] == '(') {
                // Peek beyond the matching closing paranthesis ')'
                $this->_lexer->peek();
                $token = $this->_peekBeyondClosingParenthesis();
            } else {
                // Peek beyond the PathExpression (or InputParameter)
                $peek = $this->_lexer->peek();

                while ($peek['value'] === '.') {
                    $this->_lexer->peek();
                    $peek = $this->_lexer->peek();
                }

                // Also peek beyond a NOT if there is one
                if ($peek['type'] === Lexer::T_NOT) {
                    $peek = $this->_lexer->peek();
                }

                $token = $peek;

                // We need to go even further in case of IS (differenciate between NULL and EMPTY)
                $lookahead = $this->_lexer->peek();

                // Also peek beyond a NOT if there is one
                if ($lookahead['type'] === Lexer::T_NOT) {
                    $lookahead = $this->_lexer->peek();
                }

                $this->_lexer->resetPeek();
            }
        }

        switch ($token['type']) {
            case Lexer::T_BETWEEN:
                return $this->BetweenExpression();
            case Lexer::T_LIKE:
                return $this->LikeExpression();
            case Lexer::T_IN:
                return $this->InExpression();
            case Lexer::T_INSTANCE:
                return $this->InstanceOfExpression();
            case Lexer::T_IS:
                if ($lookahead['type'] == Lexer::T_NULL) {
                    return $this->NullComparisonExpression();
                }
                return $this->EmptyCollectionComparisonExpression();
            case Lexer::T_MEMBER:
                return $this->CollectionMemberExpression();
            default:
                return $this->ComparisonExpression();
        }
    }

    /**
     * EmptyCollectionComparisonExpression ::= CollectionValuedPathExpression "IS" ["NOT"] "EMPTY"
     *
     * @return \Doctrine\ORM\Query\AST\EmptyCollectionComparisonExpression
     */
    public function EmptyCollectionComparisonExpression()
    {
        $emptyColletionCompExpr = new AST\EmptyCollectionComparisonExpression(
            $this->CollectionValuedPathExpression()
        );
        $this->match(Lexer::T_IS);

        if ($this->_lexer->isNextToken(Lexer::T_NOT)) {
            $this->match(Lexer::T_NOT);
            $emptyColletionCompExpr->not = true;
        }

        $this->match(Lexer::T_EMPTY);

        return $emptyColletionCompExpr;
    }

    /**
     * CollectionMemberExpression ::= EntityExpression ["NOT"] "MEMBER" ["OF"] CollectionValuedPathExpression
     *
     * EntityExpression ::= SingleValuedAssociationPathExpression | SimpleEntityExpression
     * SimpleEntityExpression ::= IdentificationVariable | InputParameter
     *
     * @return \Doctrine\ORM\Query\AST\CollectionMemberExpression
     */
    public function CollectionMemberExpression()
    {
        $not        = false;
        $entityExpr = $this->EntityExpression();

        if ($this->_lexer->isNextToken(Lexer::T_NOT)) {
            $this->match(Lexer::T_NOT);

            $not = true;
        }

        $this->match(Lexer::T_MEMBER);

        if ($this->_lexer->isNextToken(Lexer::T_OF)) {
            $this->match(Lexer::T_OF);
        }

        $collMemberExpr = new AST\CollectionMemberExpression(
            $entityExpr, $this->CollectionValuedPathExpression()
        );
        $collMemberExpr->not = $not;

        return $collMemberExpr;
    }

    /**
     * Literal ::= string | char | integer | float | boolean
     *
     * @return string
     */
    public function Literal()
    {
        switch ($this->_lexer->lookahead['type']) {
            case Lexer::T_STRING:
                $this->match(Lexer::T_STRING);
                return new AST\Literal(AST\Literal::STRING, $this->_lexer->token['value']);

            case Lexer::T_INTEGER:
            case Lexer::T_FLOAT:
                $this->match(
                    $this->_lexer->isNextToken(Lexer::T_INTEGER) ? Lexer::T_INTEGER : Lexer::T_FLOAT
                );
                return new AST\Literal(AST\Literal::NUMERIC, $this->_lexer->token['value']);

            case Lexer::T_TRUE:
            case Lexer::T_FALSE:
                $this->match(
                    $this->_lexer->isNextToken(Lexer::T_TRUE) ? Lexer::T_TRUE : Lexer::T_FALSE
                );
                return new AST\Literal(AST\Literal::BOOLEAN, $this->_lexer->token['value']);

            default:
                $this->syntaxError('Literal');
        }
    }

    /**
     * InParameter ::= Literal | InputParameter
     *
     * @return string | \Doctrine\ORM\Query\AST\InputParameter
     */
    public function InParameter()
    {
        if ($this->_lexer->lookahead['type'] == Lexer::T_INPUT_PARAMETER) {
            return $this->InputParameter();
        }

        return $this->Literal();
    }

    /**
     * InputParameter ::= PositionalParameter | NamedParameter
     *
     * @return \Doctrine\ORM\Query\AST\InputParameter
     */
    public function InputParameter()
    {
        $this->match(Lexer::T_INPUT_PARAMETER);

        return new AST\InputParameter($this->_lexer->token['value']);
    }

    /**
     * ArithmeticExpression ::= SimpleArithmeticExpression | "(" Subselect ")"
     *
     * @return \Doctrine\ORM\Query\AST\ArithmeticExpression
     */
    public function ArithmeticExpression()
    {
        $expr = new AST\ArithmeticExpression;

        if ($this->_lexer->isNextToken(Lexer::T_OPEN_PARENTHESIS)) {
            $peek = $this->_lexer->glimpse();

            if ($peek['type'] === Lexer::T_SELECT) {
                $this->match(Lexer::T_OPEN_PARENTHESIS);
                $expr->subselect = $this->Subselect();
                $this->match(Lexer::T_CLOSE_PARENTHESIS);

                return $expr;
            }
        }

        $expr->simpleArithmeticExpression = $this->SimpleArithmeticExpression();

        return $expr;
    }

    /**
     * SimpleArithmeticExpression ::= ArithmeticTerm {("+" | "-") ArithmeticTerm}*
     *
     * @return \Doctrine\ORM\Query\AST\SimpleArithmeticExpression
     */
    public function SimpleArithmeticExpression()
    {
        $terms = array();
        $terms[] = $this->ArithmeticTerm();

        while (($isPlus = $this->_lexer->isNextToken(Lexer::T_PLUS)) || $this->_lexer->isNextToken(Lexer::T_MINUS)) {
            $this->match(($isPlus) ? Lexer::T_PLUS : Lexer::T_MINUS);

            $terms[] = $this->_lexer->token['value'];
            $terms[] = $this->ArithmeticTerm();
        }

        // Phase 1 AST optimization: Prevent AST\SimpleArithmeticExpression
        // if only one AST\ArithmeticTerm is defined
        if (count($terms) == 1) {
            return $terms[0];
        }

        return new AST\SimpleArithmeticExpression($terms);
    }

    /**
     * ArithmeticTerm ::= ArithmeticFactor {("*" | "/") ArithmeticFactor}*
     *
     * @return \Doctrine\ORM\Query\AST\ArithmeticTerm
     */
    public function ArithmeticTerm()
    {
        $factors = array();
        $factors[] = $this->ArithmeticFactor();

        while (($isMult = $this->_lexer->isNextToken(Lexer::T_MULTIPLY)) || $this->_lexer->isNextToken(Lexer::T_DIVIDE)) {
            $this->match(($isMult) ? Lexer::T_MULTIPLY : Lexer::T_DIVIDE);

            $factors[] = $this->_lexer->token['value'];
            $factors[] = $this->ArithmeticFactor();
        }

        // Phase 1 AST optimization: Prevent AST\ArithmeticTerm
        // if only one AST\ArithmeticFactor is defined
        if (count($factors) == 1) {
            return $factors[0];
        }

        return new AST\ArithmeticTerm($factors);
    }

    /**
     * ArithmeticFactor ::= [("+" | "-")] ArithmeticPrimary
     *
     * @return \Doctrine\ORM\Query\AST\ArithmeticFactor
     */
    public function ArithmeticFactor()
    {
        $sign = null;

        if (($isPlus = $this->_lexer->isNextToken(Lexer::T_PLUS)) || $this->_lexer->isNextToken(Lexer::T_MINUS)) {
            $this->match(($isPlus) ? Lexer::T_PLUS : Lexer::T_MINUS);
            $sign = $isPlus;
        }

        $primary = $this->ArithmeticPrimary();

        // Phase 1 AST optimization: Prevent AST\ArithmeticFactor
        // if only one AST\ArithmeticPrimary is defined
        if ($sign === null) {
            return $primary;
        }

        return new AST\ArithmeticFactor($primary, $sign);
    }

    /**
     * ArithmeticPrimary ::= SingleValuedPathExpression | Literal | "(" SimpleArithmeticExpression ")"
     *          | FunctionsReturningNumerics | AggregateExpression | FunctionsReturningStrings
     *          | FunctionsReturningDatetime | IdentificationVariable | ResultVariable | CaseExpression
     */
    public function ArithmeticPrimary()
    {
        if ($this->_lexer->isNextToken(Lexer::T_OPEN_PARENTHESIS)) {
            $this->match(Lexer::T_OPEN_PARENTHESIS);
            $expr = $this->SimpleArithmeticExpression();

            $this->match(Lexer::T_CLOSE_PARENTHESIS);

            return $expr;
        }

        switch ($this->_lexer->lookahead['type']) {
            case Lexer::T_COALESCE:
            case Lexer::T_NULLIF:
            case Lexer::T_CASE:
                return $this->CaseExpression();

            case Lexer::T_IDENTIFIER:
                $peek = $this->_lexer->glimpse();

                if ($peek['value'] == '(') {
                    return $this->FunctionDeclaration();
                }

                if ($peek['value'] == '.') {
                    return $this->SingleValuedPathExpression();
                }

                if (isset($this->_queryComponents[$this->_lexer->lookahead['value']]['resultVariable'])) {
                    return $this->ResultVariable();
                }

                return $this->StateFieldPathExpression();

            case Lexer::T_INPUT_PARAMETER:
                return $this->InputParameter();

            default:
                $peek = $this->_lexer->glimpse();

                if ($peek['value'] == '(') {
                    if ($this->_isAggregateFunction($this->_lexer->lookahead['type'])) {
                        return $this->AggregateExpression();
                    }

                    return $this->FunctionDeclaration();
                }

                return $this->Literal();
        }
    }

    /**
     * StringExpression ::= StringPrimary | "(" Subselect ")"
     *
     * @return \Doctrine\ORM\Query\AST\StringPrimary |
     *         \Doctrine]ORM\Query\AST\Subselect
     */
    public function StringExpression()
    {
        if ($this->_lexer->isNextToken(Lexer::T_OPEN_PARENTHESIS)) {
            $peek = $this->_lexer->glimpse();

            if ($peek['type'] === Lexer::T_SELECT) {
                $this->match(Lexer::T_OPEN_PARENTHESIS);
                $expr = $this->Subselect();
                $this->match(Lexer::T_CLOSE_PARENTHESIS);

                return $expr;
            }
        }

        return $this->StringPrimary();
    }

    /**
     * StringPrimary ::= StateFieldPathExpression | string | InputParameter | FunctionsReturningStrings | AggregateExpression | CaseExpression
     */
    public function StringPrimary()
    {
        $lookaheadType = $this->_lexer->lookahead['type'];

        switch ($lookaheadType) {
            case Lexer::T_IDENTIFIER:
                $peek = $this->_lexer->glimpse();

                if ($peek['value'] == '.') {
                    return $this->StateFieldPathExpression();
                }

                if ($peek['value'] == '(') {
                    // do NOT directly go to FunctionsReturningString() because it doesnt check for custom functions.
                    return $this->FunctionDeclaration();
                }

                $this->syntaxError("'.' or '('");
                break;

            case Lexer::T_STRING:
                $this->match(Lexer::T_STRING);

                return $this->_lexer->token['value'];

            case Lexer::T_INPUT_PARAMETER:
                return $this->InputParameter();

            case Lexer::T_CASE:
            case Lexer::T_COALESCE:
            case Lexer::T_NULLIF:
                return $this->CaseExpression();

            default:
                if ($this->_isAggregateFunction($lookaheadType)) {
                    return $this->AggregateExpression();
                }
        }

        $this->syntaxError(
            'StateFieldPathExpression | string | InputParameter | FunctionsReturningStrings | AggregateExpression'
        );
    }

    /**
     * EntityExpression ::= SingleValuedAssociationPathExpression | SimpleEntityExpression
     *
     * @return \Doctrine\ORM\Query\AST\SingleValuedAssociationPathExpression |
     *         \Doctrine\ORM\Query\AST\SimpleEntityExpression
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
     *
     * @return string | \Doctrine\ORM\Query\AST\InputParameter
     */
    public function SimpleEntityExpression()
    {
        if ($this->_lexer->isNextToken(Lexer::T_INPUT_PARAMETER)) {
            return $this->InputParameter();
        }

        return $this->StateFieldPathExpression();
    }

    /**
     * AggregateExpression ::=
     *  ("AVG" | "MAX" | "MIN" | "SUM") "(" ["DISTINCT"] StateFieldPathExpression ")" |
     *  "COUNT" "(" ["DISTINCT"] (IdentificationVariable | SingleValuedPathExpression) ")"
     *
     * @return \Doctrine\ORM\Query\AST\AggregateExpression
     */
    public function AggregateExpression()
    {
        $lookaheadType = $this->_lexer->lookahead['type'];
        $isDistinct = false;

        if ( ! in_array($lookaheadType, array(Lexer::T_COUNT, Lexer::T_AVG, Lexer::T_MAX, Lexer::T_MIN, Lexer::T_SUM))) {
            $this->syntaxError('One of: MAX, MIN, AVG, SUM, COUNT');
        }

        $this->match($lookaheadType);
        $functionName = $this->_lexer->token['value'];
        $this->match(Lexer::T_OPEN_PARENTHESIS);

        if ($this->_lexer->isNextToken(Lexer::T_DISTINCT)) {
            $this->match(Lexer::T_DISTINCT);
            $isDistinct = true;
        }

        $pathExp = ($lookaheadType === Lexer::T_COUNT)
            ? $this->SingleValuedPathExpression()
            : $this->SimpleArithmeticExpression();

	    $this->match(Lexer::T_CLOSE_PARENTHESIS);

        return new AST\AggregateExpression($functionName, $pathExp, $isDistinct);
    }

    /**
     * QuantifiedExpression ::= ("ALL" | "ANY" | "SOME") "(" Subselect ")"
     *
     * @return \Doctrine\ORM\Query\AST\QuantifiedExpression
     */
    public function QuantifiedExpression()
    {
        $lookaheadType = $this->_lexer->lookahead['type'];
        $value = $this->_lexer->lookahead['value'];

        if ( ! in_array($lookaheadType, array(Lexer::T_ALL, Lexer::T_ANY, Lexer::T_SOME))) {
            $this->syntaxError('ALL, ANY or SOME');
        }

        $this->match($lookaheadType);
        $this->match(Lexer::T_OPEN_PARENTHESIS);

        $qExpr = new AST\QuantifiedExpression($this->Subselect());
        $qExpr->type = $value;

        $this->match(Lexer::T_CLOSE_PARENTHESIS);

        return $qExpr;
    }

    /**
     * BetweenExpression ::= ArithmeticExpression ["NOT"] "BETWEEN" ArithmeticExpression "AND" ArithmeticExpression
     *
     * @return \Doctrine\ORM\Query\AST\BetweenExpression
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
        $betweenExpr->not = $not;

        return $betweenExpr;
    }

    /**
     * ComparisonExpression ::= ArithmeticExpression ComparisonOperator ( QuantifiedExpression | ArithmeticExpression )
     *
     * @return \Doctrine\ORM\Query\AST\ComparisonExpression
     */
    public function ComparisonExpression()
    {
        $peek = $this->_lexer->glimpse();

        $leftExpr  = $this->ArithmeticExpression();
        $operator  = $this->ComparisonOperator();
        $rightExpr = ($this->_isNextAllAnySome())
            ? $this->QuantifiedExpression()
            : $this->ArithmeticExpression();

        return new AST\ComparisonExpression($leftExpr, $operator, $rightExpr);
    }

    /**
     * InExpression ::= SingleValuedPathExpression ["NOT"] "IN" "(" (InParameter {"," InParameter}* | Subselect) ")"
     *
     * @return \Doctrine\ORM\Query\AST\InExpression
     */
    public function InExpression()
    {
        $inExpression = new AST\InExpression($this->ArithmeticExpression());

        if ($this->_lexer->isNextToken(Lexer::T_NOT)) {
            $this->match(Lexer::T_NOT);
            $inExpression->not = true;
        }

        $this->match(Lexer::T_IN);
        $this->match(Lexer::T_OPEN_PARENTHESIS);

        if ($this->_lexer->isNextToken(Lexer::T_SELECT)) {
            $inExpression->subselect = $this->Subselect();
        } else {
            $literals = array();
            $literals[] = $this->InParameter();

            while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
                $this->match(Lexer::T_COMMA);
                $literals[] = $this->InParameter();
            }

            $inExpression->literals = $literals;
        }

        $this->match(Lexer::T_CLOSE_PARENTHESIS);

        return $inExpression;
    }

    /**
     * InstanceOfExpression ::= IdentificationVariable ["NOT"] "INSTANCE" ["OF"] (InstanceOfParameter | "(" InstanceOfParameter {"," InstanceOfParameter}* ")")
     *
     * @return \Doctrine\ORM\Query\AST\InstanceOfExpression
     */
    public function InstanceOfExpression()
    {
        $instanceOfExpression = new AST\InstanceOfExpression($this->IdentificationVariable());

        if ($this->_lexer->isNextToken(Lexer::T_NOT)) {
            $this->match(Lexer::T_NOT);
            $instanceOfExpression->not = true;
        }

        $this->match(Lexer::T_INSTANCE);
        $this->match(Lexer::T_OF);

        $exprValues = array();

        if ($this->_lexer->isNextToken(Lexer::T_OPEN_PARENTHESIS)) {
            $this->match(Lexer::T_OPEN_PARENTHESIS);

            $exprValues[] = $this->InstanceOfParameter();

            while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
                $this->match(Lexer::T_COMMA);

                $exprValues[] = $this->InstanceOfParameter();
            }

            $this->match(Lexer::T_CLOSE_PARENTHESIS);

            $instanceOfExpression->value = $exprValues;

            return $instanceOfExpression;
        }

        $exprValues[] = $this->InstanceOfParameter();

        $instanceOfExpression->value = $exprValues;

        return $instanceOfExpression;
    }

    /**
     * InstanceOfParameter ::= AbstractSchemaName | InputParameter
     *
     * @return mixed
     */
    public function InstanceOfParameter()
    {
        if ($this->_lexer->isNextToken(Lexer::T_INPUT_PARAMETER)) {
            $this->match(Lexer::T_INPUT_PARAMETER);

            return new AST\InputParameter($this->_lexer->token['value']);
        }

        return $this->AliasIdentificationVariable();
    }

    /**
     * LikeExpression ::= StringExpression ["NOT"] "LIKE" (string | input_parameter) ["ESCAPE" char]
     *
     * @return \Doctrine\ORM\Query\AST\LikeExpression
     */
    public function LikeExpression()
    {
        $stringExpr = $this->StringExpression();
        $not = false;

        if ($this->_lexer->isNextToken(Lexer::T_NOT)) {
            $this->match(Lexer::T_NOT);
            $not = true;
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

        $likeExpr = new AST\LikeExpression($stringExpr, $stringPattern, $escapeChar);
        $likeExpr->not = $not;

        return $likeExpr;
    }

    /**
     * NullComparisonExpression ::= (SingleValuedPathExpression | InputParameter) "IS" ["NOT"] "NULL"
     *
     * @return \Doctrine\ORM\Query\AST\NullComparisonExpression
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
            $nullCompExpr->not = true;
        }

        $this->match(Lexer::T_NULL);

        return $nullCompExpr;
    }

    /**
     * ExistsExpression ::= ["NOT"] "EXISTS" "(" Subselect ")"
     *
     * @return \Doctrine\ORM\Query\AST\ExistsExpression
     */
    public function ExistsExpression()
    {
        $not = false;

        if ($this->_lexer->isNextToken(Lexer::T_NOT)) {
            $this->match(Lexer::T_NOT);
            $not = true;
        }

        $this->match(Lexer::T_EXISTS);
        $this->match(Lexer::T_OPEN_PARENTHESIS);

        $existsExpression = new AST\ExistsExpression($this->Subselect());
        $existsExpression->not = $not;

        $this->match(Lexer::T_CLOSE_PARENTHESIS);

        return $existsExpression;
    }

    /**
     * ComparisonOperator ::= "=" | "<" | "<=" | "<>" | ">" | ">=" | "!="
     *
     * @return string
     */
    public function ComparisonOperator()
    {
        switch ($this->_lexer->lookahead['value']) {
            case '=':
                $this->match(Lexer::T_EQUALS);

                return '=';

            case '<':
                $this->match(Lexer::T_LOWER_THAN);
                $operator = '<';

                if ($this->_lexer->isNextToken(Lexer::T_EQUALS)) {
                    $this->match(Lexer::T_EQUALS);
                    $operator .= '=';
                } else if ($this->_lexer->isNextToken(Lexer::T_GREATER_THAN)) {
                    $this->match(Lexer::T_GREATER_THAN);
                    $operator .= '>';
                }

                return $operator;

            case '>':
                $this->match(Lexer::T_GREATER_THAN);
                $operator = '>';

                if ($this->_lexer->isNextToken(Lexer::T_EQUALS)) {
                    $this->match(Lexer::T_EQUALS);
                    $operator .= '=';
                }

                return $operator;

            case '!':
                $this->match(Lexer::T_NEGATE);
                $this->match(Lexer::T_EQUALS);

                return '<>';

            default:
                $this->syntaxError('=, <, <=, <>, >, >=, !=');
        }
    }

    /**
     * FunctionDeclaration ::= FunctionsReturningStrings | FunctionsReturningNumerics | FunctionsReturningDatetime
     */
    public function FunctionDeclaration()
    {
        $token = $this->_lexer->lookahead;
        $funcName = strtolower($token['value']);

        // Check for built-in functions first!
        switch (true) {
            case (isset(self::$_STRING_FUNCTIONS[$funcName])):
                return $this->FunctionsReturningStrings();

            case (isset(self::$_NUMERIC_FUNCTIONS[$funcName])):
                return $this->FunctionsReturningNumerics();

            case (isset(self::$_DATETIME_FUNCTIONS[$funcName])):
                return $this->FunctionsReturningDatetime();

            default:
                return $this->CustomFunctionDeclaration();
        }
    }

    /**
     * Helper function for FunctionDeclaration grammar rule
     */
    private function CustomFunctionDeclaration()
    {
        $token = $this->_lexer->lookahead;
        $funcName = strtolower($token['value']);

        // Check for custom functions afterwards
        $config = $this->_em->getConfiguration();

        switch (true) {
            case ($config->getCustomStringFunction($funcName) !== null):
                return $this->CustomFunctionsReturningStrings();

            case ($config->getCustomNumericFunction($funcName) !== null):
                return $this->CustomFunctionsReturningNumerics();

            case ($config->getCustomDatetimeFunction($funcName) !== null):
                return $this->CustomFunctionsReturningDatetime();

            default:
                $this->syntaxError('known function', $token);
        }
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
        $funcClass     = self::$_NUMERIC_FUNCTIONS[$funcNameLower];

        $function = new $funcClass($funcNameLower);
        $function->parse($this);

        return $function;
    }

    public function CustomFunctionsReturningNumerics()
    {
        // getCustomNumericFunction is case-insensitive
        $funcName  = strtolower($this->_lexer->lookahead['value']);
        $funcClass = $this->_em->getConfiguration()->getCustomNumericFunction($funcName);

        $function = new $funcClass($funcName);
        $function->parse($this);

        return $function;
    }

    /**
     * FunctionsReturningDateTime ::= "CURRENT_DATE" | "CURRENT_TIME" | "CURRENT_TIMESTAMP"
     */
    public function FunctionsReturningDatetime()
    {
        $funcNameLower = strtolower($this->_lexer->lookahead['value']);
        $funcClass     = self::$_DATETIME_FUNCTIONS[$funcNameLower];

        $function = new $funcClass($funcNameLower);
        $function->parse($this);

        return $function;
    }

    public function CustomFunctionsReturningDatetime()
    {
        // getCustomDatetimeFunction is case-insensitive
        $funcName  = $this->_lexer->lookahead['value'];
        $funcClass = $this->_em->getConfiguration()->getCustomDatetimeFunction($funcName);

        $function = new $funcClass($funcName);
        $function->parse($this);

        return $function;
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
        $funcClass     = self::$_STRING_FUNCTIONS[$funcNameLower];

        $function = new $funcClass($funcNameLower);
        $function->parse($this);

        return $function;
    }

    public function CustomFunctionsReturningStrings()
    {
        // getCustomStringFunction is case-insensitive
        $funcName  = $this->_lexer->lookahead['value'];
        $funcClass = $this->_em->getConfiguration()->getCustomStringFunction($funcName);

        $function = new $funcClass($funcName);
        $function->parse($this);

        return $function;
    }
}
