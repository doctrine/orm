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
 * Join ::= ["LEFT" ["OUTER"] | "INNER"] "JOIN" JoinAssociationPathExpression
 *          ["AS"] AliasIdentificationVariable [("ON" | "WITH") ConditionalExpression]
 *
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class Doctrine_ORM_Query_Parser_Join extends Doctrine_ORM_Query_ParserRule
{
    protected $_AST = null;
    

    public function syntax()
    {
        //  Join ::= ["LEFT" ["OUTER"] | "INNER"] "JOIN" JoinAssociationPathExpression
        //           ["AS"] AliasIdentificationVariable [("ON" | "WITH") ConditionalExpression]
        $this->_AST = $this->AST('Join');

        // Check Join type
        if ($this->_isNextToken(Doctrine_ORM_Query_Token::T_LEFT)) {
            $this->_parser->match(Doctrine_ORM_Query_Token::T_LEFT);

            // Possible LEFT OUTER join
            if ($this->_isNextToken(Doctrine_ORM_Query_Token::T_OUTER)) {
                $this->_parser->match(Doctrine_ORM_Query_Token::T_OUTER);

                $this->_AST->setJoinType(Doctrine_ORM_Query_AST_Join::JOIN_TYPE_LEFTOUTER);
            } else {
                $this->_AST->setJoinType(Doctrine_ORM_Query_AST_Join::JOIN_TYPE_LEFT);
            }
        } else if ($this->_isNextToken(Doctrine_ORM_Query_Token::T_INNER)) {
            // Default Join type. Not need to setJoinType.
            $this->_parser->match(Doctrine_ORM_Query_Token::T_INNER);
        }

        $this->_parser->match(Doctrine_ORM_Query_Token::T_JOIN);
        
        $this->_AST->setJoinAssociationPathExpression($this->parse('JoinAssociationPathExpression'));
        
        if ($this->_isNextToken(Doctrine_ORM_Query_Token::T_AS)) {
            $this->_parser->match(Doctrine_ORM_Query_Token::T_AS);
        }
        
        $this->_AST->setAliasIdentificationVariable($this->parse('AliasIdentificationVariable'));
        
        // Check Join where type
        if (
            $this->_isNextToken(Doctrine_ORM_Query_Token::T_ON) || 
            $this->_isNextToken(Doctrine_ORM_Query_Token::T_WITH)
        ) {
            // Apply matches and adjusts
            if ($this->_isNextToken(Doctrine_ORM_Query_Token::T_ON)) {
                $this->_parser->match(Doctrine_ORM_Query_Token::T_ON);
                
                $this->_AST->setWhereType(Doctrine_ORM_Query_AST_Join::JOIN_WHERE_ON);
            } else {
                // Default Join where type. Not need to setWhereType.
                $this->_parser->match(Doctrine_ORM_Query_Token::T_WITH);
            }

            $this->_AST->setConditionalExpression($this->parse('ConditionalExpression'));
        }

        // Return AST node
        return $this->_AST;
    }
}