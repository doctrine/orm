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
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Query\AST;

/**
 * Subselect ::= SimpleSelectClause SubselectFromClause [WhereClause] [GroupByClause] [HavingClause] [OrderByClause]
 *
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 */
class Subselect extends Node
{
    private $_simpleSelectClause;
    private $_subselectFromClause;
    private $_whereClause;
    private $_groupByClause;
    private $_havingClause;
    private $_orderByClause;

    public function __construct($simpleSelectClause, $subselectFromClause)
    {
        $this->_simpleSelectClause = $simpleSelectClause;
        $this->_subselectFromClause = $subselectFromClause;
    }    
    
    /* Getters */
    public function getSimpleSelectClause()
    {
        return $this->_simpleSelectClause;
    }

    public function getSubselectFromClause()
    {
        return $this->_subselectFromClause;
    }

    public function getWhereClause()
    {
        return $this->_whereClause;
    }

    public function setWhereClause($whereClause)
    {
        $this->_whereClause = $whereClause;
    }

    public function getGroupByClause()
    {
        return $this->_groupByClause;
    }

    public function setGroupByClause($groupByClause)
    {
        $this->_groupByClause = $groupByClause;
    }

    public function getHavingClause()
    {
        return $this->_havingClause;
    }

    public function setHavingClause($havingClause)
    {
        $this->_havingClause = $havingClause;
    }

    public function getOrderByClause()
    {
        return $this->_orderByClause;
    }
    
    public function setOrderByClause($orderByClause)
    {
        $this->_orderByClause = $orderByClause;
    }
}