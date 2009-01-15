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
 * 	SelectExpression ::= IdentificationVariable ["." "*"] | StateFieldPathExpression |
 *                       (AggregateExpression | "(" Subselect ")") [["AS"] FieldAliasIdentificationVariable]
 *
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_ORM_Query_Parser_SelectExpression extends Doctrine_ORM_Query_ParserRule
{
    protected $_AST = null;
    
    protected $_expression = null;
    
    protected $_fieldIdentificationVariable = null;
    

    public function syntax()
    {
        // SelectExpression ::= IdentificationVariable ["." "*"] | StateFieldPathExpression | 
        //                      (AggregateExpression | "(" Subselect ")") [["AS"] FieldAliasIdentificationVariable]
        $this->_AST = $this->AST('SelectExpression');

        // First we recognize for an IdentificationVariable (Component alias)
        if ($this->_isIdentificationVariable()) {
            $this->_expression = $this->parse('IdentificationVariable');

            // Inspecting if we are in a ["." "*"]
            if ($this->_isNextToken('.')) {
                $this->_parser->match('.');
                $this->_parser->match('*');
            }
        } else if (($isFunction = $this->_isFunction()) !== false || $this->_isSubselect()) {
            $this->_expression = $this->parse($isFunction ? 'AggregateExpression' : 'Subselect');

            if ($this->_isNextToken(Doctrine_ORM_Query_Token::T_AS)) {
                $this->_parser->match(Doctrine_ORM_Query_Token::T_AS);

                $this->_fieldIdentificationVariable = $this->parse('FieldAliasIdentificationVariable');
            } elseif ($this->_isNextToken(Doctrine_ORM_Query_Token::T_IDENTIFIER)) {
                $this->_fieldIdentificationVariable = $this->parse('FieldAliasIdentificationVariable');
            }
        } else {
            $this->_expression = $this->parse('StateFieldPathExpression');
        }
    }


    public function semantical()
    {
        $this->_AST->setExpression($this->_expression->semantical());
        
        if ($this->_fieldIdentificationVariable !== null) {
            $this->_AST->setFieldIdentificationVariable($this->_fieldIdentificationVariable->semantical());
        }

        // Return AST node
        return $this->_AST;
    }
    
    
    protected function _isIdentificationVariable()
    {
        // Trying to recoginize this grammar: IdentificationVariable ["." "*"]
        $token = $this->_parser->lookahead;
        $this->_parser->getScanner()->resetPeek();

        // We have an identifier here
        if ($token['type'] === Doctrine_ORM_Query_Token::T_IDENTIFIER) {
            $token = $this->_parser->getScanner()->peek();

            // If we have a dot ".", then next char must be the "*"
            if ($token['type'] === Doctrine_ORM_Query_Token::T_DOT) {
                $token = $this->_parser->getScanner()->peek();

                return $token['value'] === '*';
            }
        }
        
        return false;
    }
}