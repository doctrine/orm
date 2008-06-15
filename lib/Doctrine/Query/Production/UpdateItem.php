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
 * UpdateItem = PathExpression "=" (Expression | "NULL")
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
class Doctrine_Query_Production_UpdateItem extends Doctrine_Query_Production
{
    protected $_pathExpression;

    protected $_expression;


    public function syntax($paramHolder)
    {
        // UpdateItem = PathExpression "=" (Expression | "NULL")
        $this->_pathExpression = $this->AST('PathExpression', $paramHolder);

        $this->_parser->match('=');

        if ($this->_isNextToken(Doctrine_Query_Token::T_NULL)) {
            $this->_parser->match(Doctrine_Query_Token::T_NULL);
            $this->_expression = null;
        } else {
            $this->_expression = $this->AST('Expression', $paramHolder);
        }
    }


    public function buildSql()
    {
        return $this->_pathExpression->buildSql() . ' = ' 
             . ($this->_expression === null ? 'NULL' : $this->_expression->buildSql());
    }
    
    /**
     * Visitor support.
     */
    public function accept($visitor)
    {
        $this->_pathExpression->accept($visitor);
        if ($this->_expression) {
            $this->_expression->accept($visitor);
        }
        $visitor->visitUpdateItem($this);
    }
    
    /* Getters */
    
    public function getPathExpression()
    {
        return $this->_pathExpression;
    }
    
    public function getExpression()
    {
        return $this->_expression;
    }
}
