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
 * Primary = PathExpression | Atom | "(" Expression ")" | Function | AggregateExpression
 *
 * @package     Doctrine
 * @subpackage  Query
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class Doctrine_Query_Production_Primary extends Doctrine_Query_Production
{
    protected $_expression;


    public function syntax($paramHolder)
    {
        // Primary = PathExpression | Atom | "(" Expression ")" | Function | AggregateExpression
        switch ($this->_parser->lookahead['type']) {
            case Doctrine_Query_Token::T_IDENTIFIER:
                if ($this->_isFunction()) {
                    return $this->AST('Function', $paramHolder);
                } else {
                    return $this->AST('PathExpression', $paramHolder);
                }
            break;

            case Doctrine_Query_Token::T_STRING:
            case Doctrine_Query_Token::T_INTEGER:
            case Doctrine_Query_Token::T_FLOAT:
            case Doctrine_Query_Token::T_INPUT_PARAMETER:
                return $this->AST('Atom', $paramHolder);
            break;

            case Doctrine_Query_Token::T_AVG:
            case Doctrine_Query_Token::T_COUNT:
            case Doctrine_Query_Token::T_MAX:
            case Doctrine_Query_Token::T_MIN:
            case Doctrine_Query_Token::T_SUM:
                return $this->AST('AggregateExpression', $paramHolder);
            break;

            case Doctrine_Query_Token::T_NONE:
                if ($this->_isNextToken('(')) {
                    $this->_parser->match('(');
                    $this->_expression = $this->AST('Expression', $paramHolder);
                    $this->_parser->match(')');
                }
            break;

            default:
                $this->_parser->syntaxError('Could not process primary type');
            break;
        }
    }


    public function semantical($paramHolder)
    {
        $this->_expression->semantical($paramHolder);
    }


    public function buildSql()
    {
        return '(' . $this->_expression->buildSql() . ')';
    }
    
    /**
     * Visitor support
     *
     * @param object $visitor
     */
    public function accept($visitor)
    {
        $this->_expression->accept($visitor);
        $visitor->visitPrimary($this);
    }
    
    /* Getters */
    
    public function getExpression()
    {
        return $this->_expression;
    }
    
}
