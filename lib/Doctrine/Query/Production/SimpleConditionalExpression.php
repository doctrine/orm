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
 * SimpleConditionalExpression =
 *     ExistsExpression | Expression (ComparisonExpression | BetweenExpression |
 *     LikeExpression | InExpression | NullComparisonExpression | QuantifiedExpression)
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
class Doctrine_Query_Production_SimpleConditionalExpression extends Doctrine_Query_Production
{
    protected $_leftExpression;

    protected $_rightExpression;


    public function syntax($paramHolder)
    {
        // SimpleConditionalExpression =
        //     ExistsExpression | Expression (ComparisonExpression | BetweenExpression |
        //     LikeExpression | InExpression | NullComparisonExpression | QuantifiedExpression)
        if ($this->_getExpressionType() === Doctrine_Query_Token::T_EXISTS) {
            return $this->AST('ExistsExpression', $paramHolder);
        }

        $this->_leftExpression = $this->AST('Expression', $paramHolder);

        switch ($this->_getExpressionType()) {
            case Doctrine_Query_Token::T_BETWEEN:
                $this->_rightExpression = $this->AST('BetweenExpression', $paramHolder);
            break;

            case Doctrine_Query_Token::T_LIKE:
                $this->_rightExpression = $this->AST('LikeExpression', $paramHolder);
            break;

            case Doctrine_Query_Token::T_IN:
                $this->_rightExpression = $this->AST('InExpression', $paramHolder);
            break;

            case Doctrine_Query_Token::T_IS:
                $this->_rightExpression = $this->AST('NullComparisonExpression', $paramHolder);
            break;

            case Doctrine_Query_Token::T_ALL:
            case Doctrine_Query_Token::T_ANY:
            case Doctrine_Query_Token::T_SOME:
                $this->_rightExpression = $this->AST('QuantifiedExpression', $paramHolder);
            break;

            case Doctrine_Query_Token::T_NONE:
                // [TODO] Check out ticket #935 to understand what will be done with enumParams
                $this->_rightExpression = $this->AST('ComparisonExpression', $paramHolder);
            break;

            default:
                $message = "BETWEEN, LIKE, IN, IS, quantified (ALL, ANY or SOME) "
                         . "or comparison (=, <, <=, <>, >, >=, !=)";
                $this->_parser->syntaxError($message);
            break;
        }
    }


    public function buildSql()
    {
        return $this->_leftExpression->buildSql() . ' ' . $this->_rightExpression->buildSql();
    }


    protected function _getExpressionType() {
        if ($this->_isNextToken(Doctrine_Query_Token::T_NOT)) {
            $scanner = $this->_parser->getScanner();

            $token = $scanner->peek();
            $scanner->resetPeek();
        } else {
            $token = $this->_parser->lookahead;
        }

        return $token['type'];
    }


    /* Getters */
    public function getLeftExpression()
    {
        return $this->_leftExpression;
    }


    public function getRightExpression()
    {
        return $this->_rightExpression;
    }
}
