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
 * QuantifiedExpression = ("ALL" | "ANY" | "SOME") "(" Subselect ")"
 *
 * @package     Doctrine
 * @subpackage  Query
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class Doctrine_Query_Production_QuantifiedExpression extends Doctrine_Query_Production
{
    protected $_type;

    protected $_subselect;


    public function syntax($paramHolder)
    {
        switch ($this->_parser->lookahead['type']) {
            case Doctrine_Query_Token::T_ALL:
                $this->_parser->match(Doctrine_Query_Token::T_ALL);
            break;

            case Doctrine_Query_Token::T_ANY:
                $this->_parser->match(Doctrine_Query_Token::T_ANY);
            break;

            case Doctrine_Query_Token::T_SOME:
                $this->_parser->match(Doctrine_Query_Token::T_SOME);
            break;

            default:
                $this->_parser->logError('ALL, ANY or SOME');
            break;
        }

        $this->_type = strtoupper($this->_parser->lookahead['value']);

        $this->_parser->match('(');
        $this->_subselect = $this->AST('Subselect', $paramHolder);
        $this->_parser->match(')');
    }


    public function buildSql()
    {
        return $this->_type . ' (' . $this->_subselect->buildSql() . ')';
    }
    
    /**
     * Visitor support
     *
     * @param object $visitor
     */
    public function accept($visitor)
    {
        $this->_subselect->accept($visitor);
        $visitor->visitQuantifiedExpression($this);
    }
    
    /* Getters */
    
    public function getType()
    {
        return $this->_type;
    }
    
    public function getSubselect()
    {
        return $this->_subselect;
    }
}
