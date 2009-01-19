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
 * Join ::= ["LEFT" ["OUTER"] | "INNER"] "JOIN" JoinAssociationPathExpression
 *          ["AS"] AliasIdentificationVariable [("ON" | "WITH") ConditionalExpression]
 *
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class Doctrine_ORM_Query_AST_Join extends Doctrine_ORM_Query_AST
{
    const JOIN_TYPE_LEFT = 1;

    const JOIN_TYPE_LEFTOUTER = 2;

    const JOIN_TYPE_INNER = 3;


    const JOIN_WHERE_ON = 1;

    const JOIN_WHERE_WITH = 2;


    protected $_joinType = self::JOIN_TYPE_INNER;
    
    protected $_joinAssociationPathExpression = null;
    
    protected $_aliasIdentificationVariable = null;

    protected $_whereType = self::JOIN_WHERE_WITH;
    
    protected $_conditionalExpression = null;

    public function __construct($joinType, $joinAssocPathExpr, $aliasIdentVar)
    {
        $this->_joinType = $joinType;
        $this->_joinAssociationPathExpression = $joinAssocPathExpr;
        $this->_aliasIdentificationVariable = $aliasIdentVar;
    }
    
    /* Setters */
    
    public function setWhereType($whereType)
    {
        $this->_whereType = $whereType;
    }
    
    public function setConditionalExpression($conditionalExpression)
    {
        $this->_conditionalExpression = $conditionalExpression;
    }
    
    
    /* Getters */
    public function getJoinType()
    {
        return $this->_joinType;
    }
    
    public function getJoinAssociationPathExpression()
    {
        return $this->_joinAssociationPathExpression;
    }
    
    public function getAliasIdentificationVariable()
    {
        return $this->_aliasIdentificationVariable;
    }
    
    public function getWhereType()
    {
        return $this->_whereType;
    }
    
    public function getConditionalExpression()
    {
        return $this->_conditionalExpression;
    }
    
    
    /* REMOVE ME LATER. COPIED METHODS FROM SPLIT OF PRODUCTION INTO "AST" AND "PARSER" */
    
    public function buildSql()
    {
        $join = '';

        switch ($this->_joinType) {
            case self::JOIN_TYPE_LEFT:
                $join .= 'LEFT';
                break;
                
            case self::JOIN_TYPE_LEFTOUTER:
                $join .= 'LEFT OUTER';
                break;
                
            case self::JOIN_TYPE_INNER:
            default:
                $join .= 'INNER';
                break;
        }
        
        $join .= ' JOIN ' . $this->_joinAssociationPathExpression->buildSql();
        $condition = isset($this->_conditionalExpression)
            ? $this->_conditionalExpression->buildSql() : '';
        
        switch ($this->whereType) {
            case self::JOIN_WHERE_ON:
                // Nothing to do here... =)
                break;
                
            case self::JOIN_WHERE_WITH:
            default:
            
                /*

                TODO: Refactor to support split!!!

                $parserResult = $this->_parser->getParserResult();
        
                // Get the connection for the component
                $conn = $this->_em->getConnection();

                // We need to build the join conditions. Retrieving AssociationMapping
                $queryComponent = $this->_rangeVariableDeclaration->getQueryComponent();
                $association = $queryComponent['relation'];
                $joinColumns = array();
                
                if ($association->isOneToMany() || $association->isOneToOne()) {
                    if ($association->isInverseSide()) {
                        // joinColumns are found on the other (owning) side
                        $targetClass = $this->_em->getClassMetadata($association->getTargetEntityName());
                        $joinColumns = $targetClass->getAssociationMapping($association->getMappedByFieldName())
                                ->getTargetToSourceKeyColumns();
                    } else {
                        $joinColumns = $association->getSourceToTargetKeyColumns();
                    }
                } else {
                    //TODO: many-many
                }
               
                $relationConditionExpression = '';
        
                // We have an array('localColumn' => 'foreignColumn', ...) here
                foreach ($joinColumns as $localColumn => $foreignColumn) {
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
                
                $sql .= ' ON ' . $relationConditionExpression;
                $sql .= empty($conditionExpression) ? '' : ' AND (' . $conditionExpression . ')';
                */

                break;
        }
        
        return $join . ' ON ' . $condition;
    }
}