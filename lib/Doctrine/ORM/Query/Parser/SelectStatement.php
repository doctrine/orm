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
 * SelectStatement ::= SelectClause FromClause [WhereClause] [GroupByClause] [HavingClause] [OrderByClause]
 *
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class Doctrine_ORM_Query_Parser_SelectStatement extends Doctrine_ORM_Query_ParserRule
{
    protected $_AST = null;
    
    protected $_selectClause = null;


    public function syntax()
    {
        // SelectStatement ::= SelectClause FromClause [WhereClause] [GroupByClause] [HavingClause] [OrderByClause]
        $this->_AST = $this->AST('SelectStatement');

        // Disable the semantical check for SelectClause now. This is needed
        // since we dont know the query components yet (will be known only
        // when the FROM and WHERE clause are processed).
        //$this->_dataHolder->set('semanticalCheck', false);
        $this->_selectClause = $this->parse('SelectClause');
        //$this->_dataHolder->remove('semanticalCheck');
        
        $this->_AST->setFromClause($this->parse('FromClause'));

        if ($this->_isNextToken(Doctrine_ORM_Query_Token::T_WHERE)) {
            $this->_AST->setWhereClause($this->parse('WhereClause'));
        }

        if ($this->_isNextToken(Doctrine_ORM_Query_Token::T_GROUP)) {
            $this->_AST->setGroupByClause($this->parse('GroupByClause'));
        }

        if ($this->_isNextToken(Doctrine_ORM_Query_Token::T_HAVING)) {
            $this->_AST->setHavingClause($this->parse('HavingClause'));
        }

        if ($this->_isNextToken(Doctrine_ORM_Query_Token::T_ORDER)) {
            $this->_AST->setOrderByClause($this->parse('OrderByClause'));
        }
    }


    public function semantical()
    {
        // We need to invoke the semantical check of SelectClause here, since
        // it was not yet checked.
        // The semantical checks will be forwarded to all SelectClause dependant grammar rules
        $this->_AST->setSelectClause($this->_selectClause->semantical());
        
        // Return AST node
        return $this->_AST;
    }
}