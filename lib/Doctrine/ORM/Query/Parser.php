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
 * <http://www.phpdoctrine.org>.
 */

/**
 * An LL(k) parser for the context-free grammar of Doctrine Query Language.
 * Parses a DQL query, reports any errors in it, and generates the corresponding
 * SQL.
 *
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 */
class Doctrine_ORM_Query_Parser
{
    /**
     * The minimum number of tokens read after last detected error before
     * another error can be reported.
     *
     * @var int
     */
    const MIN_ERROR_DISTANCE = 2;

    /**
     * Path expressions that were encountered during parsing of SelectExpressions
     * and still need to be validated.
     *
     * @var array
     */
    private $_pendingPathExpressionsInSelect = array();
    
    /**
     * DQL string.
     *
     * @var string
     */
    protected $_input;

    /**
     * A scanner object.
     *
     * @var Doctrine_ORM_Query_Scanner
     */
    protected $_scanner;

    /**
     * The Parser Result object.
     *
     * @var Doctrine_ORM_Query_ParserResult
     */
    protected $_parserResult;

    /**
     * Keyword symbol table
     *
     * @var Doctrine_ORM_Query_Token
     */
    protected $_keywordTable;

    // Scanner Stuff

    /**
     * @var array The next token in the query string.
     */
    public $lookahead;

    /**
     * @var array The last matched token.
     */
    public $token;

    // End of Scanner Stuff


    // Error management stuff

    /**
     * Array containing errors detected in the query string during parsing process.
     *
     * @var array
     */
    protected $_errors;

    /**
     * @var int The number of tokens read since last error in the input string.
     */
    protected $_errorDistance;
    
    /**
     * The EntityManager.
     *
     * @var EnityManager
     */
    protected $_em;

    // End of Error management stuff


    /**
     * Creates a new query parser object.
     *
     * @param string $dql DQL to be parsed.
     * @param Doctrine_Connection $connection The connection to use
     */
    public function __construct(Doctrine_ORM_Query $query)
    {
        $this->_em = $query->getEntityManager();
        $this->_input = $query->getDql();
        $this->_scanner = new Doctrine_ORM_Query_Scanner($this->_input);
        $this->_keywordTable = new Doctrine_ORM_Query_Token();
        
        $defaultQueryComponent = Doctrine_ORM_Query_ParserRule::DEFAULT_QUERYCOMPONENT;

        $this->_parserResult = new Doctrine_ORM_Query_ParserResult(
            '',
            array( // queryComponent
                $defaultQueryComponent => array(
                    'metadata' => null,
                    'parent'   => null,
                    'relation' => null,
                    'map'      => null,
                    'scalar'   => null,
                ),
            ),
            array( // tableAliasMap
                $defaultQueryComponent => $defaultQueryComponent,
            )
        );
        
        $this->_parserResult->setEntityManager($this->_em);

        $this->free(true);
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
            $isMatch = ($this->lookahead['value'] === $token);
        } else {
            $isMatch = ($this->lookahead['type'] === $token);
        }

        if ( ! $isMatch) {
            // No definition for value checking.
            $this->syntaxError($this->_keywordTable->getLiteral($token));
        }

        $this->next();
        return true;
    }


    /**
     * Moves the parser scanner to next token
     *
     * @return void
     */
    public function next()
    {
        $this->token = $this->lookahead;
        $this->lookahead = $this->_scanner->next();
        $this->_errorDistance++;
    }


    public function isA($value, $token)
    {
        return $this->_scanner->isA($value, $token);
    }


    /**
     * Free this parser enabling it to be reused 
     * 
     * @param boolean $deep     Whether to clean peek and reset errors 
     * @param integer $position Position to reset 
     * @return void
     */
    public function free($deep = false, $position = 0)
    {
        // WARNING! Use this method with care. It resets the scanner!
        $this->_scanner->resetPosition($position);

        // Deep = true cleans peek and also any previously defined errors
        if ($deep) {
            $this->_scanner->resetPeek();
            $this->_errors = array();
        }

        $this->token = null;
        $this->lookahead = null;

        $this->_errorDistance = self::MIN_ERROR_DISTANCE;
    }


    /**
     * Parses a query string.
     */
    public function parse()
    {
        // Parse & build AST
        $AST = $this->_QueryLanguage();

        // Check for end of string
        if ($this->lookahead !== null) {
            $this->syntaxError('end of string');
        }

        // Check for semantical errors
        if (count($this->_errors) > 0) {
            throw new Doctrine_ORM_Query_Exception(implode("\r\n", $this->_errors));
        }

        // Create SqlWalker who creates the SQL from the AST
        $sqlWalker = new Doctrine_ORM_Query_SqlWalker($this->_em, $this->_parserResult);

        // Assign the executor in parser result
        $this->_parserResult->setSqlExecutor(Doctrine_ORM_Query_SqlExecutor_Abstract::create($AST, $sqlWalker));

        return $this->_parserResult;
    }

    /**
     * Returns the scanner object associated with this object.
     *
     * @return Doctrine_ORM_Query_Scanner
     */
    public function getScanner()
    {
        return $this->_scanner;
    }

    /**
     * Returns the parser result associated with this object.
     *
     * @return Doctrine_ORM_Query_ParserResult
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
            $token = $this->lookahead;
        }

        // Formatting message
        $message = 'line 0, col ' . (isset($token['position']) ? $token['position'] : '-1') . ': Error: ';

        if ($expected !== '') {
            $message .= "Expected '$expected', got ";
        } else {
            $message .= 'Unexpected ';
        }

        if ($this->lookahead === null) {
            $message .= 'end of string.';
        } else {
            $message .= "'{$this->lookahead['value']}'";
        }

        throw new Doctrine_ORM_Query_Exception($message);
    }

    /**
     * Generates a new semantical error.
     *
     * @param string $message Optional message.
     * @param array $token Optional token.
     */
    public function semanticalError($message = '', $token = null)
    {
        $this->_semanticalErrorCount++;

        if ($token === null) {
            $token = $this->token;
        }

        $this->_logError('Warning: ' . $message, $token);
    }

    /**
     * Logs new error entry.
     *
     * @param string $message Message to log.
     * @param array $token Token that it was processing.
     */
    protected function _logError($message = '', $token)
    {
        if ($this->_errorDistance >= self::MIN_ERROR_DISTANCE) {
            $message = 'line 0, col ' . $token['position'] . ': ' . $message;
            $this->_errors[] = $message;
        }

        $this->_errorDistance = 0;
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
     * Retrieve the piece of DQL string given the token position
     *
     * @param array $token Token that it was processing.
     * @return string Piece of DQL string.
     */
    /*public function getQueryPiece($token, $previousChars = 10, $nextChars = 10)
    {
        $start = max(0, $token['position'] - $previousChars);
        $end = max($token['position'] + $nextChars, strlen($this->_input));
        
        return substr($this->_input, $start, $end);
    }*/

    private function _isNextToken($token)
    {
        $la = $this->lookahead;
        return ($la['type'] === $token || $la['value'] === $token);
    }

    /**
     * Checks if the next-next (after lookahead) token start a function.
     *
     * @return boolean
     */
    private function _isFunction()
    {
        $next = $this->_scanner->peek();
        $this->_scanner->resetPeek();
        return ($next['value'] === '(');
    }

    /**
     * Checks whether the next 2 tokens start a subselect.
     *
     * @return boolean TRUE if the next 2 tokens start a subselect, FALSE otherwise.
     */
    private function _isSubselect()
    {
        $la = $this->lookahead;
        $next = $this->_scanner->peek();
        $this->_scanner->resetPeek();
        return ($la['value'] === '(' && $next['type'] === Doctrine_ORM_Query_Token::T_SELECT);
    }

    /* Parse methods */

    /**
     * QueryLanguage ::= SelectStatement | UpdateStatement | DeleteStatement
     *
     * @return <type>
     */
    private function _QueryLanguage()
    {
        $this->lookahead = $this->_scanner->next();
        switch ($this->lookahead['type']) {
            case Doctrine_ORM_Query_Token::T_SELECT:
                return $this->_SelectStatement();
                break;

            case Doctrine_ORM_Query_Token::T_UPDATE:
                return $this->_UpdateStatement();
                break;

            case Doctrine_ORM_Query_Token::T_DELETE:
                return $this->_DeleteStatement();
                break;

            default:
                $this->syntaxError('SELECT, UPDATE or DELETE');
                break;
        }
    }

    /**
     * SelectStatement ::= SelectClause FromClause [WhereClause] [GroupByClause] [HavingClause] [OrderByClause]
     *
     * @return <type> 
     */
    private function _SelectStatement()
    {
        $selectClause = $this->_SelectClause();
        $fromClause = $this->_FromClause();
        $this->_processPendingPathExpressionsInSelect();

        $whereClause = $this->_isNextToken(Doctrine_ORM_Query_Token::T_WHERE) ?
                $this->_WhereClause() : null;

        $groupByClause = $this->_isNextToken(Doctrine_ORM_Query_Token::T_GROUP) ?
                $this->_GroupByClause() : null;

        $havingClause = $this->_isNextToken(Doctrine_ORM_Query_Token::T_HAVING) ?
                $this->_HavingClause() : null;

        $orderByClause = $this->_isNextToken(Doctrine_ORM_Query_Token::T_ORDER) ?
                $this->_OrderByClause() : null;

        return new Doctrine_ORM_Query_AST_SelectStatement(
            $selectClause, $fromClause, $whereClause, $groupByClause, $havingClause, $orderByClause
        );
    }

    /**
     * Processes pending path expressions that were encountered while parsing
     * select expressions. These will be validated to make sure they are indeed
     * valid <tt>StateFieldPathExpression</tt>s and additional information
     * is attached to their AST nodes.
     */
    private function _processPendingPathExpressionsInSelect()
    {
        $qComps = $this->_parserResult->getQueryComponents();
        foreach ($this->_pendingPathExpressionsInSelect as $expr) {
            $parts = $expr->getParts();
            $numParts = count($parts);
            $dqlAlias = $parts[0];
            if (count($parts) == 2) {
                $expr->setIsSimpleStateFieldPathExpression(true);
                if ( ! $qComps[$dqlAlias]['metadata']->hasField($parts[1])) {
                    $this->syntaxError();
                }
            } else {
                $embeddedClassFieldSeen = false;
                $assocSeen = false;
                for ($i = 1; $i < $numParts - 1; ++$i) {
                    if ($qComps[$dqlAlias]['metadata']->hasAssociation($parts[$i])) {
                        if ($embeddedClassFieldSeen) {
                            $this->semanticalError('Invalid navigation path.');
                        }
                        // Indirect join
                        $assoc = $qComps[$dqlAlias]['metadata']->getAssociationMapping($parts[$i]);
                        if ( ! $assoc->isOneToOne()) {
                            $this->semanticalError('Single-valued association expected.');
                        }
                        $expr->setIsSingleValuedAssociationPart($parts[$i]);
                        //TODO...
                        $assocSeen = true;
                    } else if ($qComps[$dqlAlias]['metadata']->hasEmbeddedClassField($parts[$i])) {
                        //TODO...
                        $expr->setIsEmbeddedClassPart($parts[$i]);
                        $this->syntaxError();
                    } else {
                        $this->syntaxError();
                    }
                }
                if ( ! $assocSeen) {
                    $expr->setIsSimpleStateFieldPathExpression(true);
                } else {
                    $expr->setIsSimpleStateFieldAssociationPathExpression(true);
                }
                // Last part MUST be a simple state field
                if ( ! $qComps[$dqlAlias]['metadata']->hasField($parts[$numParts-1])) {
                    $this->syntaxError();
                }
            }
        }
    }

    private function _UpdateStatement()
    {
        //TODO
    }

    private function _DeleteStatement()
    {
        //TODO
    }

    /**
     * SelectClause ::= "SELECT" ["DISTINCT"] SelectExpression {"," SelectExpression}
     */
    private function _SelectClause()
    {
        $isDistinct = false;
        $this->match(Doctrine_ORM_Query_Token::T_SELECT);

        // Inspecting if we are in a DISTINCT query
        if ($this->_isNextToken(Doctrine_ORM_Query_Token::T_DISTINCT)) {
            $this->match(Doctrine_ORM_Query_Token::T_DISTINCT);
            $isDistinct = true;
        }

        // Process SelectExpressions (1..N)
        $selectExpressions = array();
        $selectExpressions[] = $this->_SelectExpression();
        while ($this->_isNextToken(',')) {
            $this->match(',');
            $selectExpressions[] = $this->_SelectExpression();
        }

        return new Doctrine_ORM_Query_AST_SelectClause($selectExpressions, $isDistinct);
    }

    /**
     * FromClause ::= "FROM" IdentificationVariableDeclaration {"," IdentificationVariableDeclaration}
     */
    private function _FromClause()
    {
        $this->match(Doctrine_ORM_Query_Token::T_FROM);
        $identificationVariableDeclarations = array();
        $identificationVariableDeclarations[] = $this->_IdentificationVariableDeclaration();
        while ($this->_isNextToken(',')) {
            $this->match(',');
            $identificationVariableDeclarations[] = $this->_IdentificationVariableDeclaration();
        }

        return new Doctrine_ORM_Query_AST_FromClause($identificationVariableDeclarations);
    }

    /**
     * SelectExpression ::=
     *      IdentificationVariable | StateFieldPathExpression |
     *      (AggregateExpression | "(" Subselect ")") [["AS"] FieldAliasIdentificationVariable]
     */
    private function _SelectExpression()
    {
        $expression = null;
        $fieldIdentificationVariable = null;
        $peek = $this->_scanner->peek();
        $this->_scanner->resetPeek();
        // First we recognize for an IdentificationVariable (DQL class alias)
        if ($peek['value'] != '.' && $this->lookahead['type'] === Doctrine_ORM_Query_Token::T_IDENTIFIER) {
            $expression = $this->_IdentificationVariable();
        } else if (($isFunction = $this->_isFunction()) !== false || $this->_isSubselect()) {
            $expression = $isFunction ? $this->_AggregateExpression() : $this->_Subselect();
            if ($this->_isNextToken(Doctrine_ORM_Query_Token::T_AS)) {
                $this->match(Doctrine_ORM_Query_Token::T_AS);
                $fieldIdentificationVariable = $this->_FieldAliasIdentificationVariable();
            } elseif ($this->_isNextToken(Doctrine_ORM_Query_Token::T_IDENTIFIER)) {
                $fieldIdentificationVariable = $this->_FieldAliasIdentificationVariable();
            }
        } else {
            $expression = $this->_PathExpressionInSelect();
        }

        return new Doctrine_ORM_Query_AST_SelectExpression($expression, $fieldIdentificationVariable);
    }

    /**
     * IdentificationVariable ::= identifier
     */
    private function _IdentificationVariable()
    {
        $this->match(Doctrine_ORM_Query_Token::T_IDENTIFIER);
        return $this->token['value'];
    }

    /**
     * IdentificationVariableDeclaration ::= RangeVariableDeclaration [IndexBy] {JoinVariableDeclaration}*
     */
    private function _IdentificationVariableDeclaration()
    {
        $rangeVariableDeclaration = $this->_RangeVariableDeclaration();
        $indexBy = $this->_isNextToken(Doctrine_ORM_Query_Token::T_INDEX) ?
                $this->_IndexBy() : null;
        $joinVariableDeclarations = array();
        while (
            $this->_isNextToken(Doctrine_ORM_Query_Token::T_LEFT) ||
            $this->_isNextToken(Doctrine_ORM_Query_Token::T_INNER) ||
            $this->_isNextToken(Doctrine_ORM_Query_Token::T_JOIN)
        ) {
            $joinVariableDeclarations[] = $this->_JoinVariableDeclaration();
        }

        return new Doctrine_ORM_Query_AST_IdentificationVariableDeclaration(
            $rangeVariableDeclaration, $indexBy, $joinVariableDeclarations
        );
    }

    /**
     * RangeVariableDeclaration ::= AbstractSchemaName ["AS"] AliasIdentificationVariable
     */
    private function _RangeVariableDeclaration()
    {
        $abstractSchemaName = $this->_AbstractSchemaName();

        if ($this->_isNextToken(Doctrine_ORM_Query_Token::T_AS)) {
            $this->match(Doctrine_ORM_Query_Token::T_AS);
        }
        $aliasIdentificationVariable = $this->_AliasIdentificationVariable();
        $classMetadata = $this->_em->getClassMetadata($abstractSchemaName);

        // Building queryComponent
        $queryComponent = array(
            'metadata' => $classMetadata,
            'parent'   => null,
            'relation' => null,
            'map'      => null,
            'scalar'   => null,
        );
        $this->_parserResult->setQueryComponent($aliasIdentificationVariable, $queryComponent);

        return new Doctrine_ORM_Query_AST_RangeVariableDeclaration(
            $classMetadata, $aliasIdentificationVariable
        );
    }

    /**
     * AbstractSchemaName ::= identifier
     */
    private function _AbstractSchemaName()
    {
        $this->match(Doctrine_ORM_Query_Token::T_IDENTIFIER);
        return $this->token['value'];
    }

    /**
     * AliasIdentificationVariable = identifier
     */
    private function _AliasIdentificationVariable()
    {
        $this->match(Doctrine_ORM_Query_Token::T_IDENTIFIER);
        return $this->token['value'];
    }

    /**
     * Special rule that acceps all kinds of path expressions.
     */
    private function _PathExpression()
    {
        $this->match(Doctrine_ORM_Query_Token::T_IDENTIFIER);
        $parts = array($this->token['value']);
        while ($this->_isNextToken('.')) {
            $this->match('.');
            $this->match(Doctrine_ORM_Query_Token::T_IDENTIFIER);
            $parts[] = $this->token['value'];
        }
        $pathExpression = new Doctrine_ORM_Query_AST_PathExpression($parts);

        return $pathExpression;
    }

    /**
     * Special rule that acceps all kinds of path expressions. and defers their
     * semantical checking until the FROM part has been parsed completely (joins inclusive).
     * Mainly used for path expressions in the SelectExpressions.
     */
    private function _PathExpressionInSelect()
    {
        $expr = $this->_PathExpression();
        $this->_pendingPathExpressionsInSelect[] = $expr;
        return $expr;
    }

    /**
     * JoinVariableDeclaration ::= Join [IndexBy]
     */
    private function _JoinVariableDeclaration()
    {
        $join = $this->_Join();
        $indexBy = $this->_isNextToken(Doctrine_ORM_Query_Token::T_INDEX) ?
                $this->_IndexBy() : null;
        return new Doctrine_ORM_Query_AST_JoinVariableDeclaration($join, $indexBy);
    }

    /**
     * Join ::= ["LEFT" ["OUTER"] | "INNER"] "JOIN" JoinAssociationPathExpression
     *          ["AS"] AliasIdentificationVariable [("ON" | "WITH") ConditionalExpression]
     */
    private function _Join()
    {
        // Check Join type
        $joinType = Doctrine_ORM_Query_AST_Join::JOIN_TYPE_INNER;
        if ($this->_isNextToken(Doctrine_ORM_Query_Token::T_LEFT)) {
            $this->match(Doctrine_ORM_Query_Token::T_LEFT);
            // Possible LEFT OUTER join
            if ($this->_isNextToken(Doctrine_ORM_Query_Token::T_OUTER)) {
                $this->match(Doctrine_ORM_Query_Token::T_OUTER);
                $joinType = Doctrine_ORM_Query_AST_Join::JOIN_TYPE_LEFTOUTER;
            } else {
                $joinType = Doctrine_ORM_Query_AST_Join::JOIN_TYPE_LEFT;
            }
        } else if ($this->_isNextToken(Doctrine_ORM_Query_Token::T_INNER)) {
            $this->match(Doctrine_ORM_Query_Token::T_INNER);
        }

        $this->match(Doctrine_ORM_Query_Token::T_JOIN);

        $joinPathExpression = $this->_JoinPathExpression();
        if ($this->_isNextToken(Doctrine_ORM_Query_Token::T_AS)) {
            $this->match(Doctrine_ORM_Query_Token::T_AS);
        }

        $aliasIdentificationVariable = $this->_AliasIdentificationVariable();

        // Verify that the association exists, if yes update the ParserResult
        // with the new component.
        $parentComp = $this->_parserResult->getQueryComponent($joinPathExpression->getIdentificationVariable());
        $parentClass = $parentComp['metadata'];
        $assocField = $joinPathExpression->getAssociationField();
        if ( ! $parentClass->hasAssociation($assocField)) {
            $this->semanticalError("Class " . $parentClass->getClassName() .
                    " has no association named '$assocField'.");
        }
        $targetClassName = $parentClass->getAssociationMapping($assocField)->getTargetEntityName();

        // Building queryComponent
        $joinQueryComponent = array(
            'metadata' => $this->_em->getClassMetadata($targetClassName),
            'parent'   => $joinPathExpression->getIdentificationVariable(),
            'relation' => $parentClass->getAssociationMapping($assocField),
            'map'      => null,
            'scalar'   => null,
        );
        $this->_parserResult->setQueryComponent($aliasIdentificationVariable, $joinQueryComponent);

        // Create AST node
        $join = new Doctrine_ORM_Query_AST_Join($joinType, $joinPathExpression, $aliasIdentificationVariable);

        // Check Join where type
        if (
            $this->_isNextToken(Doctrine_ORM_Query_Token::T_ON) ||
            $this->_isNextToken(Doctrine_ORM_Query_Token::T_WITH)
        ) {
            if ($this->_isNextToken(Doctrine_ORM_Query_Token::T_ON)) {
                $this->match(Doctrine_ORM_Query_Token::T_ON);
                $join->setWhereType(Doctrine_ORM_Query_AST_Join::JOIN_WHERE_ON);
            } else {
                $this->match(Doctrine_ORM_Query_Token::T_WITH);
            }
            $join->setConditionalExpression($this->_ConditionalExpression());
        }

        return $join;
    }

    /*private function _JoinAssociationPathExpression() {
        if ($this->_isSingleValuedPathExpression()) {
            return $this->_JoinSingleValuedAssociationPathExpression();
        } else {
            return $this->_JoinCollectionValuedPathExpression();
        }
    }*/

    /*private function _isSingleValuedPathExpression()
    {
        $parserResult = $this->_parserResult;

        // Trying to recoginize this grammar:
        // IdentificationVariable "." (CollectionValuedAssociationField | SingleValuedAssociationField)
        $token = $this->lookahead;
        $this->_scanner->resetPeek();
        if ($parserResult->hasQueryComponent($token['value'])) {
            $queryComponent = $parserResult->getQueryComponent($token['value']);
            $peek = $this->_scanner->peek();
            if ($peek['value'] === '.') {
                $peek2 = $this->_scanner->peek();
                if ($queryComponent['metadata']->hasAssociation($peek2['value']) &&
                        $queryComponent['metadata']->getAssociationMapping($peek2['value'])->isOneToOne()) {
	                return true;
	            }
            }
        }
        return false;
    }*/

    /**
     * JoinPathExpression ::= IdentificationVariable "." (CollectionValuedAssociationField | SingleValuedAssociationField)
     */
    private function _JoinPathExpression()
    {
        $identificationVariable = $this->_IdentificationVariable();
        $this->match('.');
        $this->match(Doctrine_ORM_Query_Token::T_IDENTIFIER);
        $assocField = $this->token['value'];
        return new Doctrine_ORM_Query_AST_JoinPathExpression(
            $identificationVariable, $assocField
        );
    }

    /**
     * IndexBy ::= "INDEX" "BY" SimpleStateFieldPathExpression
     */
    private function _IndexBy()
    {
        $this->match(Doctrine_ORM_Query_Token::T_INDEX);
        $this->match(Doctrine_ORM_Query_Token::T_BY);
        $pathExp = $this->_SimpleStateFieldPathExpression();
        // Add the INDEX BY info to the query component
        $qComp = $this->_parserResult->getQueryComponent($pathExp->getIdentificationVariable());
        $qComp['map'] = $pathExp->getSimpleStateField();
        $this->_parserResult->setQueryComponent($pathExp->getIdentificationVariable(), $qComp);
        return $pathExp;
    }

    /**
     * SimpleStateFieldPathExpression ::= IdentificationVariable "." StateField
     */
    private function _SimpleStateFieldPathExpression()
    {
        $identificationVariable = $this->_IdentificationVariable();
        $this->match('.');
        $this->match(Doctrine_ORM_Query_Token::T_IDENTIFIER);
        $simpleStateField = $this->token['value'];
        return new Doctrine_ORM_Query_AST_SimpleStateFieldPathExpression($identificationVariable, $simpleStateField);
    }

    /**
     * StateFieldPathExpression ::= SimpleStateFieldPathExpression | SimpleStateFieldAssociationPathExpression
     */
    private function _StateFieldPathExpression()
    {
        $parts = array();
        $stateFieldSeen = false;
        $assocSeen = false;

        $identificationVariable = $this->_IdentificationVariable();
        if ( ! $this->_parserResult->hasQueryComponent($identificationVariable)) {
            $this->syntaxError("Identification variable.");
        }
        $qComp = $this->_parserResult->getQueryComponent($identificationVariable);
        $parts[] = $identificationVariable;

        $class = $qComp['metadata'];

        if ( ! $this->_isNextToken('.')) {
            $this->syntaxError();
        }
        
        while ($this->_isNextToken('.')) {
            if ($stateFieldSeen) $this->syntaxError();
            $this->match('.');
            $part = $this->_IdentificationVariable();
            if ($class->hasField($part)) {
                $stateFieldSeen = true;
            } else if ($class->hasAssociation($part)) {
                $assoc = $class->getAssociationMapping($part);
                $class = $this->_em->getClassMetadata($assoc->getTargetEntityName());
                $assocSeen = true;
            } else {
                $this->syntaxError();
            }
            $parts[] = $part;
        }

        $pathExpr = new Doctrine_ORM_Query_AST_PathExpression($parts);

        if ($assocSeen) {
            $pathExpr->setIsSimpleStateFieldAssociationPathExpression(true);
        } else {
            $pathExpr->setIsSimpleStateFieldPathExpression(true);
        }

        return $pathExpr;
    }

    /**
     * AggregateExpression ::=
     *  ("AVG" | "MAX" | "MIN" | "SUM") "(" ["DISTINCT"] StateFieldPathExpression ")" |
     *  "COUNT" "(" ["DISTINCT"] (IdentificationVariable | SingleValuedAssociationPathExpression | StateFieldPathExpression) ")"
     */
    private function _AggregateExpression()
    {
        $isDistinct = false;
        $functionName = '';
        if ($this->_isNextToken(Doctrine_ORM_Query_Token::T_COUNT)) {
            $this->match(Doctrine_ORM_Query_Token::T_COUNT);
            $functionName = $this->token['value'];
            $this->match('(');
            if ($this->_isNextToken(Doctrine_ORM_Query_Token::T_DISTINCT)) {
                $this->match(Doctrine_ORM_Query_Token::T_DISTINCT);
                $isDistinct = true;
            }
            // For now we only support a PathExpression here...
            $pathExp = $this->_PathExpression();
            $this->match(')');
        } else if ($this->_isNextToken(Doctrine_ORM_Query_Token::T_AVG)) {
            $this->match(Doctrine_ORM_Query_Token::T_AVG);
            $functionName = $this->token['value'];
            $this->match('(');
            //...
        } else {
            $this->syntaxError('One of: MAX, MIN, AVG, SUM, COUNT');
        }
        return new Doctrine_ORM_Query_AST_AggregateExpression($functionName, $pathExp, $isDistinct);
    }

    /**
     * GroupByClause ::= "GROUP" "BY" GroupByItem {"," GroupByItem}*
     * GroupByItem ::= SingleValuedPathExpression
     */
    private function _GroupByClause()
    {
        $this->match(Doctrine_ORM_Query_Token::T_GROUP);
        $this->match(Doctrine_ORM_Query_Token::T_BY);
        $groupByItems = array();
        $groupByItems[] = $this->_PathExpression();
        while ($this->_isNextToken(',')) {
            $this->match(',');
            $groupByItems[] = $this->_PathExpression();
        }
        return new Doctrine_ORM_Query_AST_GroupByClause($groupByItems);
    }

    /**
     * WhereClause ::= "WHERE" ConditionalExpression
     */
    private function _WhereClause()
    {
        $this->match(Doctrine_ORM_Query_Token::T_WHERE);
        return new Doctrine_ORM_Query_AST_WhereClause($this->_ConditionalExpression());
    }

    /**
     * ConditionalExpression ::= ConditionalTerm {"OR" ConditionalTerm}*
     */
    private function _ConditionalExpression()
    {
        $conditionalTerms = array();
        $conditionalTerms[] = $this->_ConditionalTerm();
        while ($this->_isNextToken(Doctrine_ORM_Query_Token::T_OR)) {
            $this->match(Doctrine_ORM_Query_Token::T_OR);
            $conditionalTerms[] = $this->_ConditionalTerm();
        }
        return new Doctrine_ORM_Query_AST_ConditionalExpression($conditionalTerms);
    }

    /**
     * ConditionalTerm ::= ConditionalFactor {"AND" ConditionalFactor}*
     */
    private function _ConditionalTerm()
    {
        $conditionalFactors = array();
        $conditionalFactors[] = $this->_ConditionalFactor();
        while ($this->_isNextToken(Doctrine_ORM_Query_Token::T_AND)) {
            $this->match(Doctrine_ORM_Query_Token::T_AND);
            $conditionalFactors[] = $this->_ConditionalFactor();
        }
        return new Doctrine_ORM_Query_AST_ConditionalTerm($conditionalFactors);
    }

    /**
     * ConditionalFactor ::= ["NOT"] ConditionalPrimary
     */
    private function _ConditionalFactor()
    {
        $not = false;
        if ($this->_isNextToken(Doctrine_ORM_Query_Token::T_NOT)) {
            $this->match(Doctrine_ORM_Query_Token::T_NOT);
            $not = true;
        }
        return new Doctrine_ORM_Query_AST_ConditionalFactor($this->_ConditionalPrimary(), $not);
    }

    /**
     * ConditionalPrimary ::= SimpleConditionalExpression | "(" ConditionalExpression ")"
     */
    private function _ConditionalPrimary()
    {
        $condPrimary = new Doctrine_ORM_Query_AST_ConditionalPrimary;
        if ($this->_isNextToken('(')) {
            $this->match('(');
            $conditionalExpression = $this->_ConditionalExpression();
            $this->match(')');
            $condPrimary->setConditionalExpression($conditionalExpression);
        } else {
            $condPrimary->setSimpleConditionalExpression($this->_SimpleConditionalExpression());
        }
        return $condPrimary;
    }

    /**
     * SimpleConditionalExpression ::=
     *      ComparisonExpression | BetweenExpression | LikeExpression |
     *      InExpression | NullComparisonExpression | ExistsExpression |
     *      EmptyCollectionComparisonExpression | CollectionMemberExpression
     */
    private function _SimpleConditionalExpression()
    {
        if ($this->_isNextToken(Doctrine_ORM_Query_Token::T_NOT)) {
            $token = $this->_scanner->peek();
            $this->_scanner->resetPeek();
        } else {
            $token = $this->lookahead;
        }
        if ($token['type'] === Doctrine_ORM_Query_Token::T_EXISTS) {
            return $this->_ExistsExpression();
        }

        $stateFieldPathExpr = false;
        if ($token['type'] === Doctrine_ORM_Query_Token::T_IDENTIFIER) {
            // Peek beyond the PathExpression
            $stateFieldPathExpr = true;
            $peek = $this->_scanner->peek();
            while ($peek['value'] === '.') {
                $this->_scanner->peek();
                $peek = $this->_scanner->peek();
            }
            $this->_scanner->resetPeek();
            $token = $peek;
        }

        if ($stateFieldPathExpr) {
            switch ($token['type']) {
                case Doctrine_ORM_Query_Token::T_BETWEEN:
                    return $this->_BetweenExpression();
                case Doctrine_ORM_Query_Token::T_LIKE:
                    return $this->_LikeExpression();
                case Doctrine_ORM_Query_Token::T_IN:
                    return $this->_InExpression();
                case Doctrine_ORM_Query_Token::T_IS:
                    return $this->_NullComparisonExpression();
                case Doctrine_ORM_Query_Token::T_NONE:
                    return $this->_ComparisonExpression();
                default:
                    $this->syntaxError();
            }
        } else {
            switch ($token['type']) {
                case Doctrine_ORM_Query_Token::T_INTEGER:
                    // IF it turns out its a ComparisonExpression, then it MUST be ArithmeticExpression
                    break;
                case Doctrine_ORM_Query_Token::T_STRING:
                    // IF it turns out its a ComparisonExpression, then it MUST be StringExpression
                    break;
                default:
                    $this->syntaxError();
            }
        }
    }

    /**
     * SIMPLIFIED FROM BNF FOR NOW
     * ComparisonExpression ::= ArithmeticExpression ComparisonOperator ( QuantifiedExpression | ArithmeticExpression )
     */
    private function _ComparisonExpression()
    {
        $leftExpr = $this->_ArithmeticExpression();
        $operator = $this->_ComparisonOperator();
        if ($this->lookahead['type'] === Doctrine_ORM_Query_Token::T_ALL ||
                $this->lookahead['type'] === Doctrine_ORM_Query_Token::T_ANY ||
                $this->lookahead['type'] === Doctrine_ORM_Query_Token::T_SOME) {
            $rightExpr = $this->_QuantifiedExpression();
        } else {
            $rightExpr = $this->_ArithmeticExpression();
        }
        return new Doctrine_ORM_Query_AST_ComparisonExpression($leftExpr, $operator, $rightExpr);
    }

    /**
     * ArithmeticExpression ::= SimpleArithmeticExpression | "(" Subselect ")"
     */
    private function _ArithmeticExpression()
    {
        $expr = new Doctrine_ORM_Query_AST_ArithmeticExpression;
        if ($this->lookahead['value'] === '(') {
            $peek = $this->_scanner->peek();
            $this->_scanner->resetPeek();
            if ($peek['type'] === Doctrine_ORM_Query_Token::T_SELECT) {
                $expr->setSubselect($this->_Subselect());
                return $expr;
            }
        }
        $expr->setSimpleArithmeticExpression($this->_SimpleArithmeticExpression());
        return $expr;
    }

    /**
     * SimpleArithmeticExpression ::= ArithmeticTerm {("+" | "-") ArithmeticTerm}*
     */
    private function _SimpleArithmeticExpression()
    {
        $terms = array();
        $terms[] = $this->_ArithmeticTerm();
        while ($this->lookahead['value'] == '+' || $this->lookahead['value'] == '-') {
            $terms[] = $this->_ArithmeticTerm();
        }
        return new Doctrine_ORM_Query_AST_SimpleArithmeticExpression($terms);
    }

    /**
     * ArithmeticTerm ::= ArithmeticFactor {("*" | "/") ArithmeticFactor}*
     */
    private function _ArithmeticTerm()
    {
        $factors = array();
        $factors[] = $this->_ArithmeticFactor();
        while ($this->lookahead['value'] == '*' || $this->lookahead['value'] == '/') {
            $factors[] = $this->_ArithmeticFactor();
        }
        return new Doctrine_ORM_Query_AST_ArithmeticTerm($factors);
    }

    /**
     * ArithmeticFactor ::= [("+" | "-")] ArithmeticPrimary
     */
    private function _ArithmeticFactor()
    {
        $pSign = $nSign = false;
        if ($this->lookahead['value'] == '+') {
            $this->match('+');
            $pSign = true;
        } else if ($this->lookahead['value'] == '-') {
            $this->match('-');
            $nSign = true;
        }
        return new Doctrine_ORM_Query_AST_ArithmeticFactor($this->_ArithmeticPrimary(), $pSign, $nSign);
    }

    /**
     * ArithmeticPrimary ::= StateFieldPathExpression | Literal | "(" SimpleArithmeticExpression ")" | Function | AggregateExpression
     */
    private function _ArithmeticPrimary()
    {
        if ($this->lookahead['type'] === Doctrine_ORM_Query_Token::T_IDENTIFIER) {
            return $this->_StateFieldPathExpression();
        }
        if ($this->lookahead['value'] === '(') {
            return $this->_SimpleArithmeticExpression();
        }
        if ($this->lookahead['type'] === Doctrine_ORM_Query_Token::T_INPUT_PARAMETER) {
            $this->match($this->lookahead['value']);
            return new Doctrine_ORM_Query_AST_InputParameter($this->token['value']);
        }
        //TODO...
    }

    /**
     * ComparisonOperator ::= "=" | "<" | "<=" | "<>" | ">" | ">=" | "!="
     */
    private function _ComparisonOperator()
    {
        switch ($this->lookahead['value']) {
            case '=':
                $this->match('=');
                return '=';
            case '<':
                $this->match('<');
                $operator = '<';
                if ($this->_isNextToken('=')) {
                    $this->match('=');
                    $operator .= '=';
                } else if ($this->_isNextToken('>')) {
                    $this->match('>');
                    $operator .= '>';
                }
                return $operator;
            case '>':
                $this->match('>');
                $operator = '>';
                if ($this->_isNextToken('=')) {
                    $this->match('=');
                    $operator .= '=';
                }
                return $operator;
            case '!':
                $this->match('!');
                $this->match('=');
                return '<>';
            default:
                $this->_parser->syntaxError('=, <, <=, <>, >, >=, !=');
                break;
        }
    }
}
