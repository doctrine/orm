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

namespace Doctrine\ORM\Query\Production;

use \Doctrine\ORM\Query\Token;

/**
 * AggregateExpression = ("AVG" | "MAX" | "MIN" | "SUM" | "COUNT") "(" ["DISTINCT"] Expression ")"
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
class AggregateExpression extends Doctrine_Query_Production
{
    protected $_functionName;
    protected $_isDistinct;
    protected $_expression;

    public function syntax($paramHolder)
    {
        // AggregateExpression = ("AVG" | "MAX" | "MIN" | "SUM" | "COUNT") "(" ["DISTINCT"] Expression ")"
        $this->_isDistinct = false;
        $token = $this->_parser->lookahead;

        switch ($token['type']) {
            case Token::T_AVG:
            case Token::T_MAX:
            case Token::T_MIN:
            case Token::T_SUM:
            case Token::T_COUNT:
                $this->_parser->match($token['type']);
                $this->_functionName = strtoupper($token['value']);
            break;

            default:
                $this->_parser->logError('AVG, MAX, MIN, SUM or COUNT');
            break;
        }

        $this->_parser->match('(');

        if ($this->_isNextToken(Token::T_DISTINCT)) {
            $this->_parser->match(Token::T_DISTINCT);
            $this->_isDistinct = true;
        }

        $this->_expression = $this->AST('Expression', $paramHolder);

        $this->_parser->match(')');
    }


    public function semantical($paramHolder)
    {
        $this->_expression->semantical($paramHolder);
    }


    public function buildSql()
    {
        return $this->_functionName
             . '(' . (($this->_isDistinct) ? 'DISTINCT ' : '')
             . $this->_expression->buildSql()
             . ')';
    }
    
    /**
     * Visitor support.
     *
     * @param object $visitor
     */
    public function accept($visitor)
    {
        $this->_expression->accept($visitor);
        $visitor->visitAggregateExpression($this);
    }
    
    /* Getters */
    
    public function getExpression()
    {
        return $this->_expression;
    }
    
    public function getFunctionName()
    {
        return $this->_functionName;
    }
    
    public function isDistinct()
    {
        return $this->_isDistinct;
    }
}