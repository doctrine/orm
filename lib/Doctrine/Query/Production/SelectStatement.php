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
 * SelectStatement = SelectClause FromClause [WhereClause] [GroupByClause] [HavingClause] [OrderByClause]
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
class Doctrine_Query_Production_SelectStatement extends Doctrine_Query_Production
{
    protected $_selectClause;

    protected $_fromClause;

    protected $_whereClause;

    protected $_groupByClause;

    protected $_havingClause;

    protected $_orderByClause;


    public function syntax($paramHolder)
    {
        // SelectStatement = SelectClause FromClause [WhereClause] [GroupByClause] [HavingClause] [OrderByClause]

        // Disable the semantical check for SelectClause now. This is needed
        // since we dont know the query components yet (will be known only
        // when the FROM clause be processed).
        $paramHolder->set('semanticalCheck', false);
        $this->_selectClause = $this->AST('SelectClause', $paramHolder);
        $paramHolder->remove('semanticalCheck');

        $this->_fromClause = $this->AST('FromClause', $paramHolder);

        if ($this->_isNextToken(Doctrine_Query_Token::T_WHERE)) {
            $this->_whereClause = $this->AST('WhereClause', $paramHolder);
        }

        if ($this->_isNextToken(Doctrine_Query_Token::T_GROUP)) {
            $this->_groupByClause = $this->AST('GroupByClause', $paramHolder);
        }

        if ($this->_isNextToken(Doctrine_Query_Token::T_HAVING)) {
            $this->_havingClause = $this->AST('HavingClause', $paramHolder);
        }

        if ($this->_isNextToken(Doctrine_Query_Token::T_ORDER)) {
            $this->_orderByClause = $this->AST('OrderByClause', $paramHolder);
        }
    }


    public function semantical($paramHolder)
    {
        // We need to invoke the semantical check of SelectClause here, since
        // it was not yet checked.
        $this->_selectClause->semantical($paramHolder);
    }


    public function buildSql()
    {
        return $this->_selectClause->buildSql() . ' ' . $this->_fromClause->buildSql()
             . (($this->_whereClause !== null) ? ' ' . $this->_whereClause->buildSql() : ' WHERE 1 = 1')
             . (($this->_groupByClause !== null) ? ' ' . $this->_groupByClause->buildSql() : '')
             . (($this->_havingClause !== null) ? ' ' . $this->_havingClause->buildSql() : '')
             . (($this->_orderByClause !== null) ? ' ' . $this->_orderByClause->buildSql() : '');
    }
    
    /**
     * Visitor support
     *
     * @param object $visitor
     */
    public function accept($visitor)
    {
        $this->_selectClause->accept($visitor);
        $this->_fromClause->accept($visitor);
        if ($this->_whereClause) {
            $this->_whereClause->accept($visitor);
        }
        if ($this->_groupByClause) {
            $this->_groupByClause->accept($visitor);
        }
        if ($this->_havingClause) {
            $this->_havingClause->accept($visitor);
        }
        if ($this->_orderByClause) {
            $this->_orderByClause->accept($visitor);
        }
        $visitor->visitSelectStatement($this);
    }
    
    /* Getters */
    
    public function getSelectClause()
    {
        return $this->_selectClause;
    }
    
    public function getFromClause()
    {
        return $this->_fromClause;
    }
    
    public function getWhereClause()
    {
        return $this->_whereClause;
    }
    
    public function getGroupByClause()
    {
        return $this->_groupByClause;
    }
    
    public function getHavingClause()
    {
        return $this->_havingClause;
    }
    
    public function getOrderByClause()
    {
        return $this->_orderByClause;
    }
}
