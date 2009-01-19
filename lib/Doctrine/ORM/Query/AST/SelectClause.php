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
 * SelectClause = "SELECT" ["DISTINCT"] SelectExpression {"," SelectExpression}
 *
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class Doctrine_ORM_Query_AST_SelectClause extends Doctrine_ORM_Query_AST
{
    protected $_isDistinct;

    protected $_selectExpressions = array();

    public function __construct(array $selectExpressions, $isDistinct)
    {
        $this->_isDistinct = $isDistinct;
        $this->_selectExpressions = $selectExpressions;
    }
    
    /* Getters */
    public function isDistinct()
    {
        return $this->_isDistinct;
    }


    public function getSelectExpressions()
    {
        return $this->_selectExpressions;
    }
    
    
    /* REMOVE ME LATER. COPIED METHODS FROM SPLIT OF PRODUCTION INTO "AST" AND "PARSER" */
    
    public function buildSql()
    {
        return 'SELECT ' . (($this->_isDistinct) ? 'DISTINCT ' : '')
             . implode(', ', $this->_mapSelectExpressions());
    }


    protected function _mapSelectExpressions()
    {
        return array_map(array(&$this, '_mapSelectExpression'), $this->_selectExpressions);
    }


    protected function _mapSelectExpression($value)
    {
        return is_object($value) ? $value->buildSql() : $value;
    }
}
