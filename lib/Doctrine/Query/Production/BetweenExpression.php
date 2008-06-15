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
 * BetweenExpression = ["NOT"] "BETWEEN" Expression "AND" Expression
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
class Doctrine_Query_Production_BetweenExpression extends Doctrine_Query_Production
{
    protected $_not;

    protected $_fromExpression;

    protected $_toExpression;


    public function syntax($paramHolder)
    {
        // BetweenExpression = ["NOT"] "BETWEEN" Expression "AND" Expression
        $this->_not = false;

        if ($this->_isNextToken(Doctrine_Query_Token::T_NOT)) {
            $this->_parser->match(Doctrine_Query_Token::T_NOT);
            $this->_not = true;
        }

        $this->_parser->match(Doctrine_Query_Token::T_BETWEEN);

        $this->_fromExpression = $this->AST('Expression', $paramHolder);

        $this->_parser->match(Doctrine_Query_Token::T_AND);

        $this->_toExpression = $this->AST('Expression', $paramHolder);
    }


    public function buildSql()
    {
        return (($this->_not) ? 'NOT ' : '') . 'BETWEEN '
             . $this->_fromExpression->buildSql() . ' AND ' . $this->_toExpression->buildSql();
    }
    
    /**
     * Visitor support.
     *
     * @param object $visitor
     */
    public function accept($visitor)
    {
        $this->_fromExpression->accept($visitor);
        $this->_toExpression->accept($visitor);
        $visitor->visitBetweenExpression($this);
    }
    
    /* Getters */
    
    public function isNot()
    {
        return $this->_not;
    }
    
    public function getFromExpression()
    {
        return $this->_fromExpression;
    }
    
    public function getToExpression()
    {
        return $this->_toExpression;
    }
}
