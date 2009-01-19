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
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class Doctrine_ORM_Query_AST_SelectStatement extends Doctrine_ORM_Query_AST
{
    protected $_selectClause;
    protected $_fromClause;
    protected $_whereClause;
    protected $_groupByClause;
    protected $_havingClause;
    protected $_orderByClause;

    public function __construct($selectClause, $fromClause, $whereClause, $groupByClause,
            $havingClause, $orderByClause) {
        $this->_selectClause = $selectClause;
        $this->_fromClause = $fromClause;
        $this->_whereClause = $whereClause;
        $this->_groupByClause = $groupByClause;
        $this->_havingClause = $havingClause;
        $this->_orderByClause = $orderByClause;
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
    
    
    /* REMOVE ME LATER. COPIED METHODS FROM SPLIT OF PRODUCTION INTO "AST" AND "PARSER" */
    
    public function buildSql()
    {
        return $this->_selectClause->buildSql() . ' ' . $this->_fromClause->buildSql()
             . (($this->_whereClause !== null) ? ' ' . $this->_whereClause->buildSql() : '')
             . (($this->_groupByClause !== null) ? ' ' . $this->_groupByClause->buildSql() : '')
             . (($this->_havingClause !== null) ? ' ' . $this->_havingClause->buildSql() : '')
             . (($this->_orderByClause !== null) ? ' ' . $this->_orderByClause->buildSql() : '');
    }
}