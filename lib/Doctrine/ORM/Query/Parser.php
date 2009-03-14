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

use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\Exec;

/**
 * An LL(*) parser for the context-free grammar of Doctrine Query Language.
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
    private $_pendingPathExpressionsInSelect = array();

    /**
     * A scanner object.
     *
     * @var Doctrine_ORM_Query_Scanner
     */
    protected $_lexer;

    /**
     * The Parser Result object.
     *
     * @var Doctrine_ORM_Query_ParserResult
     */
    protected $_parserResult;
    
    /**
     * The EntityManager.
     *
     * @var EnityManager
     */
    protected $_em;

    /**
     * Creates a new query parser object.
     *
     * @param string $dql DQL to be parsed.
     * @param Doctrine_Connection $connection The connection to use
     */
    public function __construct(\Doctrine\ORM\Query $query)
    {
        $this->_em = $query->getEntityManager();
        $this->_lexer = new Lexer($query->getDql());
        
        $defaultQueryComponent = ParserRule::DEFAULT_QUERYCOMPONENT;

        $this->_parserResult = new ParserResult(
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
            $isMatch = ($this->_lexer->lookahead['value'] === $token);
        } else {
            $isMatch = ($this->_lexer->lookahead['type'] === $token);
        }

        if ( ! $isMatch) {
            // No definition for value checking.
            $this->syntaxError($this->_lexer->getLiteral($token));
        }

        $this->_lexer->next();
        return true;
    }

    public function isA($value, $token)
    {
        return $this->_lexer->isA($value, $token);
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
     */
    public function parse()
    {
        // Parse & build AST
        $AST = $this->_QueryLanguage();
        
        // Check for end of string
        if ($this->_lexer->lookahead !== null) {
            var_dump($this->_lexer->lookahead);
            $this->syntaxError('end of string');
        }

        // Create SqlWalker who creates the SQL from the AST
        $sqlWalker = new SqlWalker($this->_em, $this->_parserResult);

        // Assign the executor in parser result
        $this->_parserResult->setSqlExecutor(Exec\AbstractExecutor::create($AST, $sqlWalker));

        return $this->_parserResult;
    }

    /**
     * Returns the scanner object associated with this object.
     *
     * @return Doctrine_ORM_Query_Lexer
     */
    public function getLexer()
    {
        return $this->_lexer;
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
            $token = $this->_lexer->lookahead;
        }

        // Formatting message
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

        throw \Doctrine\Common\DoctrineException::updateMe($message);
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
            $token = $this->_lexer->token;
        }
        //TODO: Include $token in $message
        throw \Doctrine\Common\DoctrineException::updateMe($message);
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

    /**
     * Checks if the next-next (after lookahead) token start a function.
     *
     * @return boolean
     */
    private function _isFunction()
    {
        $next = $this->_lexer->glimpse();
        return ($next['value'] === '(');
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

    /* Parse methods */

    /**
     * QueryLanguage ::= SelectStatement | UpdateStatement | DeleteStatement
     *
     * @return <type>
     */
    private function _QueryLanguage()
    {
        $this->_lexer->next();
        switch ($this->_lexer->lookahead['type']) {
            case Lexer::T_SELECT:
                return $this->_SelectStatement();
                break;

            case Lexer::T_UPDATE:
                return $this->_UpdateStatement();
                break;

            case Lexer::T_DELETE:
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

        $whereClause = $this->_lexer->isNextToken(Lexer::T_WHERE) ?
                $this->_WhereClause() : null;

        $groupByClause = $this->_lexer->isNextToken(Lexer::T_GROUP) ?
                $this->_GroupByClause() : null;

        $havingClause = $this->_lexer->isNextToken(Lexer::T_HAVING) ?
                $this->_HavingClause() : null;

        $orderByClause = $this->_lexer->isNextToken(Lexer::T_ORDER) ?
                $this->_OrderByClause() : null;

        return new AST\SelectStatement(
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
        $this->match(Lexer::T_SELECT);

        // Inspecting if we are in a DISTINCT query
        if ($this->_lexer->isNextToken(Lexer::T_DISTINCT)) {
            $this->match(Lexer::T_DISTINCT);
            $isDistinct = true;
        }

        // Process SelectExpressions (1..N)
        $selectExpressions = array();
        $selectExpressions[] = $this->_SelectExpression();
        while ($this->_lexer->isNextToken(',')) {
            $this->match(',');
            $selectExpressions[] = $this->_SelectExpression();
        }

        return new AST\SelectClause($selectExpressions, $isDistinct);
    }

    /**
     * FromClause ::= "FROM" IdentificationVariableDeclaration {"," IdentificationVariableDeclaration}
     */
    private function _FromClause()
    {
        $this->match(Lexer::T_FROM);
        $identificationVariableDeclarations = array();
        $identificationVariableDeclarations[] = $this->_IdentificationVariableDeclaration();
        while ($this->_lexer->isNextToken(',')) {
            $this->match(',');
            $identificationVariableDeclarations[] = $this->_IdentificationVariableDeclaration();
        }

        return new AST\FromClause($identificationVariableDeclarations);
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
        $peek = $this->_lexer->glimpse();
        // First we recognize for an IdentificationVariable (DQL class alias)
        if ($peek['value'] != '.' && $this->_lexer->lookahead['type'] === Lexer::T_IDENTIFIER) {
            $expression = $this->_IdentificationVariable();
        } else if (($isFunction = $this->_isFunction()) !== false || $this->_isSubselect()) {
            $expression = $isFunction ? $this->_AggregateExpression() : $this->_Subselect();
            if ($this->_lexer->isNextToken(Lexer::T_AS)) {
                $this->match(Lexer::T_AS);
                $fieldIdentificationVariable = $this->_FieldAliasIdentificationVariable();
            } elseif ($this->_lexer->isNextToken(Lexer::T_IDENTIFIER)) {
                $fieldIdentificationVariable = $this->_FieldAliasIdentificationVariable();
            }
        } else {
            $expression = $this->_PathExpressionInSelect();
        }

        return new AST\SelectExpression($expression, $fieldIdentificationVariable);
    }

    /**
     * IdentificationVariable ::= identifier
     */
    private function _IdentificationVariable()
    {
        $this->match(Lexer::T_IDENTIFIER);
        return $this->_lexer->token['value'];
    }

    /**
     * IdentificationVariableDeclaration ::= RangeVariableDeclaration [IndexBy] {JoinVariableDeclaration}*
     */
    private function _IdentificationVariableDeclaration()
    {
        $rangeVariableDeclaration = $this->_RangeVariableDeclaration();
        $indexBy = $this->_lexer->isNextToken(Lexer::T_INDEX) ?
                $this->_IndexBy() : null;
        $joinVariableDeclarations = array();
        while (
            $this->_lexer->isNextToken(Lexer::T_LEFT) ||
            $this->_lexer->isNextToken(Lexer::T_INNER) ||
            $this->_lexer->isNextToken(Lexer::T_JOIN)
        ) {
            $joinVariableDeclarations[] = $this->_JoinVariableDeclaration();
        }

        return new AST\IdentificationVariableDeclaration(
            $rangeVariableDeclaration, $indexBy, $joinVariableDeclarations
        );
    }

    /**
     * RangeVariableDeclaration ::= AbstractSchemaName ["AS"] AliasIdentificationVariable
     */
    private function _RangeVariableDeclaration()
    {
        $abstractSchemaName = $this->_AbstractSchemaName();

        if ($this->_lexer->isNextToken(Lexer::T_AS)) {
            $this->match(Lexer::T_AS);
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

        return new AST\RangeVariableDeclaration(
            $classMetadata, $aliasIdentificationVariable
        );
    }

    /**
     * AbstractSchemaName ::= identifier
     */
    private function _AbstractSchemaName()
    {
        $this->match(Lexer::T_IDENTIFIER);
        return $this->_lexer->token['value'];
    }

    /**
     * AliasIdentificationVariable = identifier
     */
    private function _AliasIdentificationVariable()
    {
        $this->match(Lexer::T_IDENTIFIER);
        return $this->_lexer->token['value'];
    }

    /**
     * Special rule that acceps all kinds of path expressions.
     */
    private function _PathExpression()
    {
        $this->match(Lexer::T_IDENTIFIER);
        $parts = array($this->_lexer->token['value']);
        while ($this->_lexer->isNextToken('.')) {
            $this->match('.');
            $this->match(Lexer::T_IDENTIFIER);
            $parts[] = $this->_lexer->token['value'];
        }
        $pathExpression = new AST\PathExpression($parts);
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
        $indexBy = $this->_lexer->isNextToken(Lexer::T_INDEX) ?
                $this->_IndexBy() : null;
        return new AST\JoinVariableDeclaration($join, $indexBy);
    }

    /**
     * Join ::= ["LEFT" ["OUTER"] | "INNER"] "JOIN" JoinAssociationPathExpression
     *          ["AS"] AliasIdentificationVariable [("ON" | "WITH") ConditionalExpression]
     */
    private function _Join()
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

        $joinPathExpression = $this->_JoinPathExpression();
        if ($this->_lexer->isNextToken(Lexer::T_AS)) {
            $this->match(Lexer::T_AS);
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
        $join = new AST\Join($joinType, $joinPathExpression, $aliasIdentificationVariable);

        // Check Join where type
        if (
            $this->_lexer->isNextToken(Lexer::T_ON) ||
            $this->_lexer->isNextToken(Lexer::T_WITH)
        ) {
            if ($this->_lexer->isNextToken(Lexer::T_ON)) {
                $this->match(Lexer::T_ON);
                $join->setWhereType(AST\Join::JOIN_WHERE_ON);
            } else {
                $this->match(Lexer::T_WITH);
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
        $this->match(Lexer::T_IDENTIFIER);
        $assocField = $this->_lexer->token['value'];
        return new AST\JoinPathExpression(
            $identificationVariable, $assocField
        );
    }

    /**
     * IndexBy ::= "INDEX" "BY" SimpleStateFieldPathExpression
     */
    private function _IndexBy()
    {
        $this->match(Lexer::T_INDEX);
        $this->match(Lexer::T_BY);
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
        $this->match(Lexer::T_IDENTIFIER);
        $simpleStateField = $this->_lexer->token['value'];
        return new AST\SimpleStateFieldPathExpression($identificationVariable, $simpleStateField);
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

        if ( ! $this->_lexer->isNextToken('.')) {
            $this->syntaxError();
        }
        
        while ($this->_lexer->isNextToken('.')) {
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

        $pathExpr = new AST\PathExpression($parts);

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
        if ($this->_lexer->isNextToken(Lexer::T_COUNT)) {
            $this->match(Lexer::T_COUNT);
            $functionName = $this->_lexer->token['value'];
            $this->match('(');
            if ($this->_lexer->isNextToken(Lexer::T_DISTINCT)) {
                $this->match(Lexer::T_DISTINCT);
                $isDistinct = true;
            }
            // For now we only support a PathExpression here...
            $pathExp = $this->_PathExpression();
            $this->match(')');
        } else if ($this->_lexer->isNextToken(Lexer::T_AVG)) {
            $this->match(Lexer::T_AVG);
            $functionName = $this->_lexer->token['value'];
            $this->match('(');
            //...
        } else {
            $this->syntaxError('One of: MAX, MIN, AVG, SUM, COUNT');
        }
        return new AST\AggregateExpression($functionName, $pathExp, $isDistinct);
    }

    /**
     * GroupByClause ::= "GROUP" "BY" GroupByItem {"," GroupByItem}*
     * GroupByItem ::= SingleValuedPathExpression
     */
    private function _GroupByClause()
    {
        $this->match(Lexer::T_GROUP);
        $this->match(Lexer::T_BY);
        $groupByItems = array();
        $groupByItems[] = $this->_PathExpression();
        while ($this->_lexer->isNextToken(',')) {
            $this->match(',');
            $groupByItems[] = $this->_PathExpression();
        }
        return new AST\GroupByClause($groupByItems);
    }

    /**
     * WhereClause ::= "WHERE" ConditionalExpression
     */
    private function _WhereClause()
    {
        $this->match(Lexer::T_WHERE);
        return new AST\WhereClause($this->_ConditionalExpression());
    }

    /**
     * ConditionalExpression ::= ConditionalTerm {"OR" ConditionalTerm}*
     */
    private function _ConditionalExpression()
    {
        $conditionalTerms = array();
        $conditionalTerms[] = $this->_ConditionalTerm();
        while ($this->_lexer->isNextToken(Lexer::T_OR)) {
            $this->match(Lexer::T_OR);
            $conditionalTerms[] = $this->_ConditionalTerm();
        }
        return new AST\ConditionalExpression($conditionalTerms);
    }

    /**
     * ConditionalTerm ::= ConditionalFactor {"AND" ConditionalFactor}*
     */
    private function _ConditionalTerm()
    {
        $conditionalFactors = array();
        $conditionalFactors[] = $this->_ConditionalFactor();
        while ($this->_lexer->isNextToken(Lexer::T_AND)) {
            $this->match(Lexer::T_AND);
            $conditionalFactors[] = $this->_ConditionalFactor();
        }
        return new AST\ConditionalTerm($conditionalFactors);
    }

    /**
     * ConditionalFactor ::= ["NOT"] ConditionalPrimary
     */
    private function _ConditionalFactor()
    {
        $not = false;
        if ($this->_lexer->isNextToken(Lexer::T_NOT)) {
            $this->match(Lexer::T_NOT);
            $not = true;
        }
        return new AST\ConditionalFactor($this->_ConditionalPrimary(), $not);
    }

    /**
     * ConditionalPrimary ::= SimpleConditionalExpression | "(" ConditionalExpression ")"
     */
    private function _ConditionalPrimary()
    {
        $condPrimary = new AST\ConditionalPrimary;
        if ($this->_lexer->isNextToken('(')) {
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
                $condPrimary->setSimpleConditionalExpression($this->_SimpleConditionalExpression());
            } else {
                $this->match('(');
                $conditionalExpression = $this->_ConditionalExpression();
                $this->match(')');
                $condPrimary->setConditionalExpression($conditionalExpression);
            }
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
        if ($this->_lexer->isNextToken(Lexer::T_NOT)) {
            $token = $this->_lexer->glimpse();
        } else {
            $token = $this->_lexer->lookahead;
        }
        if ($token['type'] === Lexer::T_EXISTS) {
            return $this->_ExistsExpression();
        }

        $stateFieldPathExpr = false;
        if ($token['type'] === Lexer::T_IDENTIFIER) {
            // Peek beyond the PathExpression
            $stateFieldPathExpr = true;
            $peek = $this->_lexer->peek();
            while ($peek['value'] === '.') {
                $this->_lexer->peek();
                $peek = $this->_lexer->peek();
            }
            $this->_lexer->resetPeek();
            $token = $peek;
        }

        if ($stateFieldPathExpr) {
            switch ($token['type']) {
                case Lexer::T_BETWEEN:
                    return $this->_BetweenExpression();
                case Lexer::T_LIKE:
                    return $this->_LikeExpression();
                case Lexer::T_IN:
                    return $this->_InExpression();
                case Lexer::T_IS:
                    return $this->_NullComparisonExpression();
                case Lexer::T_NONE:
                    return $this->_ComparisonExpression();
                default:
                    $this->syntaxError();
            }
        } else if ($token['value'] == '(') {
            return $this->_ComparisonExpression();
        } else {
            switch ($token['type']) {
                case Lexer::T_INTEGER:
                    // IF it turns out its a ComparisonExpression, then it MUST be ArithmeticExpression
                    break;
                case Lexer::T_STRING:
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
        if ($this->_lexer->lookahead['type'] === Lexer::T_ALL ||
                $this->_lexer->lookahead['type'] === Lexer::T_ANY ||
                $this->_lexer->lookahead['type'] === Lexer::T_SOME) {
            $rightExpr = $this->_QuantifiedExpression();
        } else {
            $rightExpr = $this->_ArithmeticExpression();
        }
        return new AST\ComparisonExpression($leftExpr, $operator, $rightExpr);
    }

    /**
     * ArithmeticExpression ::= SimpleArithmeticExpression | "(" Subselect ")"
     */
    private function _ArithmeticExpression()
    {
        $expr = new AST\ArithmeticExpression;
        if ($this->_lexer->lookahead['value'] === '(') {
            $peek = $this->_lexer->glimpse();
            if ($peek['type'] === Lexer::T_SELECT) {
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
        while ($this->_lexer->lookahead['value'] == '+' || $this->_lexer->lookahead['value'] == '-') {
            if ($this->_lexer->lookahead['value'] == '+') {
                $this->match('+');
            } else {
                $this->match('-');
            }
            $terms[] = $this->_lexer->token['value'];
            $terms[] = $this->_ArithmeticTerm();
        }
        return new AST\SimpleArithmeticExpression($terms);
    }

    /**
     * ArithmeticTerm ::= ArithmeticFactor {("*" | "/") ArithmeticFactor}*
     */
    private function _ArithmeticTerm()
    {
        $factors = array();
        $factors[] = $this->_ArithmeticFactor();
        while ($this->_lexer->lookahead['value'] == '*' || $this->_lexer->lookahead['value'] == '/') {
            if ($this->_lexer->lookahead['value'] == '*') {
                $this->match('*');
            } else {
                $this->match('/');
            }
            $factors[] = $this->_lexer->token['value'];
            $factors[] = $this->_ArithmeticFactor();
        }
        return new AST\ArithmeticTerm($factors);
    }

    /**
     * ArithmeticFactor ::= [("+" | "-")] ArithmeticPrimary
     */
    private function _ArithmeticFactor()
    {
        $pSign = $nSign = false;
        if ($this->_lexer->lookahead['value'] == '+') {
            $this->match('+');
            $pSign = true;
        } else if ($this->_lexer->lookahead['value'] == '-') {
            $this->match('-');
            $nSign = true;
        }
        return new AST\ArithmeticFactor($this->_ArithmeticPrimary(), $pSign, $nSign);
    }

    /**
     * ArithmeticPrimary ::= StateFieldPathExpression | Literal | "(" SimpleArithmeticExpression ")" | Function | AggregateExpression
     */
    private function _ArithmeticPrimary()
    {
        if ($this->_lexer->lookahead['value'] === '(') {
            $this->match('(');
            $expr = $this->_SimpleArithmeticExpression();
            $this->match(')');
            return $expr;
        }
        switch ($this->_lexer->lookahead['type']) {
            case Lexer::T_IDENTIFIER:
                $peek = $this->_lexer->glimpse();
                if ($peek['value'] == '(') {
                    if ($this->_isAggregateFunction($peek['type'])) {
                        return $this->_AggregateExpression();
                    }
                    return $this->_FunctionsReturningStrings();
                }
                return $this->_StateFieldPathExpression();
            case Lexer::T_INPUT_PARAMETER:
                $this->match($this->_lexer->lookahead['value']);
                return new AST\InputParameter($this->_lexer->token['value']);
            case Lexer::T_STRING:
            case Lexer::T_INTEGER:
            case Lexer::T_FLOAT:
                $this->match($this->_lexer->lookahead['value']);
                return $this->_lexer->token['value'];
            default:
                $this->syntaxError();
        }
        throw \Doctrine\Common\DoctrineException::updateMe("Not yet implemented.");
        //TODO...
    }

    /**
     * PortableFunctionsReturningStrings ::=
     *   "CONCAT" "(" StringPrimary "," StringPrimary ")" |
     *   "SUBSTRING" "(" StringPrimary "," SimpleArithmeticExpression "," SimpleArithmeticExpression ")" |
     *   "TRIM" "(" [["LEADING" | "TRAILING" | "BOTH"] [char] "FROM"] StringPrimary ")" |
     *   "LOWER" "(" StringPrimary ")" |
     *   "UPPER" "(" StringPrimary ")"
     */
    private function _FunctionsReturningStrings()
    {
        switch (strtoupper($this->_lexer->lookahead['value'])) {
            case 'CONCAT':
                
                break;
            case 'SUBSTRING':

                break;
            case 'TRIM':
                $this->match($this->_lexer->lookahead['value']);
                $this->match('(');
                //TODO: This is not complete! See BNF
                $this->_StringPrimary();
                break;
            case 'LOWER':

                break;
            case 'UPPER':

            default:
                $this->syntaxError('CONCAT, SUBSTRING, TRIM or UPPER');
        }
    }

    private function _isAggregateFunction($tokenType)
    {
        switch ($tokenType) {
            case Lexer::T_AVG:
            case Lexer::T_MIN:
            case Lexer::T_MAX:
            case Lexer::T_SUM:
            case Lexer::T_COUNT:
                return true;
            default:
                return false;
        }
    }

    /**
     * ComparisonOperator ::= "=" | "<" | "<=" | "<>" | ">" | ">=" | "!="
     */
    private function _ComparisonOperator()
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
                break;
        }
    }

    /**
     * LikeExpression ::= StringExpression ["NOT"] "LIKE" string ["ESCAPE" char]
     */
    private function _LikeExpression()
    {
        $stringExpr = $this->_StringExpression();
        $isNot = false;
        if ($this->_lexer->lookahead['type'] === Lexer::T_NOT) {
            $this->match(Lexer::T_NOT);
            $isNot = true;
        }
        $this->match(Lexer::T_LIKE);
        $this->match(Lexer::T_STRING);
        $stringPattern = $this->_lexer->token['value'];
        $escapeChar = null;
        if ($this->_lexer->lookahead['type'] === Lexer::T_ESCAPE) {
            $this->match(Lexer::T_ESCAPE);
            var_dump($this->_lexer->lookahead);
            //$this->match(Lexer::T_)
            //$escapeChar =
        }
        return new AST\LikeExpression($stringExpr, $stringPattern, $isNot, $escapeChar);
    }

    /**
     * StringExpression ::= StringPrimary | "(" Subselect ")"
     */
    private function _StringExpression()
    {
        if ($this->_lexer->lookahead['value'] === '(') {
            $peek = $this->_lexer->glimpse();
            if ($peek['type'] === Lexer::T_SELECT) {
                return $this->_Subselect();
            }
        }
        return $this->_StringPrimary();
    }

    /**
     * StringPrimary ::= StateFieldPathExpression | string | InputParameter | FunctionsReturningStrings | AggregateExpression
     */
    private function _StringPrimary()
    {
        if ($this->_lexer->lookahead['type'] === Lexer::T_IDENTIFIER) {
            $peek = $this->_lexer->glimpse();
            if ($peek['value'] == '.') {
                return $this->_StateFieldPathExpression();
            } else if ($peek['value'] == '(') {
                //TODO... FunctionsReturningStrings or AggregateExpression
            } else {
                $this->syntaxError("'.' or '('");
            }
        } else if ($this->_lexer->lookahead['type'] === Lexer::T_STRING) {
            //TODO...
        } else if ($this->_lexer->lookahead['type'] === Lexer::T_INPUT_PARAMETER) {
            //TODO...
        } else {
            $this->syntaxError('StateFieldPathExpression | string | InputParameter | FunctionsReturningStrings | AggregateExpression');
        }
    }
}