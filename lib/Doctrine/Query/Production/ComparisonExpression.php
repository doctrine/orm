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
 * ComparisonExpression = ComparisonOperator ( QuantifiedExpression | Expression | "(" Subselect ")" )
 *
 * @package     Doctrine
 * @subpackage  Query
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_Production_ComparisonExpression extends Doctrine_Query_Production
{
    protected $_operator;

    protected $_expression;

    protected $_isSubselect;


    public function syntax($paramHolder)
    {
        // ComparisonExpression = ComparisonOperator ( QuantifiedExpression | Expression | "(" Subselect ")" )
        $this->_operator = $this->AST('ComparisonOperator', $paramHolder);

        if (($this->_isSubselect = $this->_isSubselect()) === true) {
            $this->_parser->match('(');
            $this->_expression = $this->AST('Subselect', $paramHolder);
            $this->_parser->match(')');

            $this->_isSubselect = true;
        } else {
            switch ($this->_parser->lookahead['type']) {
                case Doctrine_Query_Token::T_ALL:
                case Doctrine_Query_Token::T_ANY:
                case Doctrine_Query_Token::T_SOME:
                    $this->_expression = $this->AST('QuantifiedExpression', $paramHolder);
                break;

                default:
                    $this->_expression = $this->AST('Expression', $paramHolder);
                break;
            }
        }
    }


    public function buildSql()
    {
        return $this->_operator . ' ' . (($this->_isSubselect) ?
            '(' . $this->_expression->buildSql() . ')' : $this->_expression->buildSql()
        );
    }
}
