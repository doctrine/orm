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
 * Join = ["LEFT" | "INNER"] "JOIN" RangeVariableDeclaration [("ON" | "WITH") ConditionalExpression]
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
class Doctrine_Query_Production_Join extends Doctrine_Query_Production
{
    protected $_joinType;

    protected $_rangeVariableDeclaration;

    protected $_whereType;

    protected $_conditionalExpression;


    public function syntax($paramHolder)
    {
        $this->_joinType = 'INNER';
        $this->_whereType = 'WITH';

        if ($this->_isNextToken(Doctrine_Query_Token::T_LEFT)) {
            $this->_parser->match(Doctrine_Query_Token::T_LEFT);

            $this->_joinType = 'LEFT';
        } else if ($this->_isNextToken(Doctrine_Query_Token::T_INNER)) {
            $this->_parser->match(Doctrine_Query_Token::T_INNER);
        }

        $this->_parser->match(Doctrine_Query_Token::T_JOIN);

        $this->_rangeVariableDeclaration = $this->AST('RangeVariableDeclaration', $paramHolder);

        if ($this->_isNextToken(Doctrine_Query_Token::T_ON)) {
            $this->_parser->match(Doctrine_Query_Token::T_ON);

            $this->_whereType = 'ON';

            $this->_conditionalExpression = $this->AST('ConditionalExpression', $paramHolder);
        } else if ($this->_isNextToken(Doctrine_Query_Token::T_WITH)) {
            $this->_parser->match(Doctrine_Query_Token::T_WITH);

            $this->_conditionalExpression = $this->AST('ConditionalExpression', $paramHolder);
        }
    }


    public function buildSql()
    {
        $parserResult = $this->_parser->getParserResult();

        // Get the connection for the component
        $conn = $this->_em->getConnection();

        $sql = $this->_joinType . ' JOIN ' . $this->_rangeVariableDeclaration->buildSql();
        $conditionExpression = isset($this->_conditionExpression)
            ? $this->_conditionExpression->buildSql() : '';

        if ($this->_whereType == 'ON') {
            return $sql . ' ON ' . $conditionExpression;
        }

        // We need to build the relationship conditions. Retrieving AssociationMapping
       $queryComponent = $this->_rangeVariableDeclaration->getQueryComponent();
       $relationColumns = $queryComponent['relation']->getSourceToTargetKeyColumns();
       $relationConditionExpression = '';

       // We have an array('localColumn' => 'foreignColumn', ...) here
       foreach ($relationColumns as $localColumn => $foreignColumn) {
           // leftExpression = rightExpression

           // Defining leftExpression
           $leftExpression = $conn->quoteIdentifier(
               $parserResult->getTableAliasFromComponentAlias($queryComponent['parent']) . '.' . $localColumn
           );

           // Defining rightExpression
           $rightExpression = $conn->quoteIdentifier(
               $parserResult->getTableAliasFromComponentAlias(
                   $this->_rangeVariableDeclaration->getIdentificationVariable()
               ) . '.' . $foreignColumn
           );

           // Building the relation
           $relationConditionExpression .= (($relationConditionExpression != '') ? ' AND ' : '')
               . $leftExpression . ' = ' . $rightExpression;
       }

       return $sql . ' ON ' . $relationConditionExpression . ' AND (' . $conditionExpression . ')';
    }


    /* Getters */
    public function getJoinType()
    {
        return $this->_joinType;
    }


    public function getRangeVariableDeclaration()
    {
        return $this->_rangeVariableDeclaration;
    }


    public function getWhereType()
    {
        return $this->_whereType;
    }


    public function getConditionalExpression()
    {
        return $this->_conditionalExpression;
    }
}
