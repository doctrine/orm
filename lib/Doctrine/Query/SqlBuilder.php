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
 * Base class of each Sql Builder object
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
abstract class Doctrine_Query_SqlBuilder
{
    /**
     * The Connection object.
     *
     * @var Doctrine_Connection
     */
    protected $_connection;


    public static function fromConnection(Doctrine_EntityManager $entityManager)
    {
        $connection = $entityManager->getConnection();

        $className = "Doctrine_Query_SqlBuilder_" . $connection->getDriverName();
        $sqlBuilder = new $className();
        $sqlBuilder->_connection = $connection;

        return $sqlBuilder;
    }


    /**
     * Retrieves the assocated Doctrine_Connection to this object.
     *
     * @return Doctrine_Connection
     */
    public function getConnection()
    {
        return $this->_connection;
    }


    /**
     * @nodoc
     */
    public function quoteIdentifier($identifier)
    {
        return $this->_connection->quoteIdentifier($identifier);
    }



    // Start Common SQL generations
    // Here we follow the SQL-99 specifications available at:
    // http://savage.net.au/SQL/sql-99.bnf

    

    // End of Common SQL generations
 
    
      
    /** The following is just test/draft code for now. */
    
    /*private $_sql;
    private $_conditionalTerms = array();
    private $_conditionalFactors = array();
    private $_conditionalPrimaries = array();
    private $_variableDeclaration = array();
    private $_expressions = array();
    private $_deleteClause;
    private $_whereClause;
    
    public function visitVariableDeclaration($variableDeclaration)
    {
        echo " VariableDeclaration ";
        // Basic handy variables
        $parserResult = $variableDeclaration->getParser()->getParserResult();
        $queryComponent = $parserResult->getQueryComponent($variableDeclaration->getComponentAlias());

        // Retrieving connection
        $manager = Doctrine_EntityManagerFactory::getManager();
        $conn = $manager->getConnection();

        $this->_variableDeclaration[] = $conn->quoteIdentifier($queryComponent['metadata']->getTableName()) . ' '
             . $conn->quoteIdentifier($parserResult->getTableAliasFromComponentAlias(
                    $variableDeclaration->getComponentAlias()));
    }
    
    public function visitDeleteClause($deleteClause)
    {
        echo " DeleteClause ";
        $this->_deleteClause = 'DELETE FROM ' . array_pop($this->_variableDeclaration);
    }
    
    public function visitDeleteStatement($deleteStatement)
    {
        echo " DeleteStatement ";
        $this->_sql = $this->_deleteClause;
        if ($this->_whereClause) {
            $this->_sql .= $this->_whereClause;
        } else {
            $this->_sql .= " WHERE 1 = 1";
        }
    }
    
    public function visitWhereClause($whereClause)
    {
        echo " WhereClause ";
        if ($this->_expressions) {
            $this->_whereClause = ' WHERE ' . array_pop($this->_expressions);
        }
    }
    
    public function visitConditionalExpression($conditionalExpression)
    {
        echo " ConditionalExpression ";
        $count = count($conditionalExpression->getConditionalTerms());
        $terms = array();
        for ($i=0; $i<$count; $i++) {
            $terms[] = array_pop($this->_conditionalTerms);
        }
        
        $this->_expressions[] = implode(' OR ', $terms);
    }
    
    public function visitSimpleConditionalExpression($simpleConditionalExpression)
    {
        //var_dump($this->_expressions);
        echo " SimpleConditionalExpression ";
        $rightExpr = array_pop($this->_expressions);
        $leftExpr = array_pop($this->_expressions); 
        $this->_expressions[] = $leftExpr . ' ' . $rightExpr;
    }
    
    public function visitConditionalPrimary($conditionalPrimary)
    {
        echo " ConditionalPrimary ";
        if ($this->_expressions) {
            $this->_conditionalPrimaries[] = '(' . array_pop($this->_expressions) . ')';
        }
    }
    
    public function visitConditionalTerm($conditionalTerm)
    {
        echo " ConditionalTerm ";
        $count = count($conditionalTerm->getConditionalFactors());
        $factors = array();
        for ($i=0; $i<$count; $i++) {
            $factors[] = array_pop($this->_conditionalFactors);
        }
        
        $this->_conditionalTerms[] = implode(' AND ', $factors);
    }
    
    public function visitConditionalFactor($conditionalFactor)
    {
        echo " ConditionalFactor ";
        if ($this->_conditionalPrimaries) {
            $this->_conditionalFactors[] = 'NOT ' . array_pop($this->_conditionalPrimaries);
        }
    }
    
    public function visitBetweenExpression($betweenExpression)
    {
        $this->_expressions[] = (($betweenExpression->getNot()) ? 'NOT ' : '') . 'BETWEEN '
             . array_pop($this->_expressions) . ' AND ' . array_pop($this->_expressions);
    }
    
    public function visitLikeExpression($likeExpression)
    {
        $this->_expressions[] = (($likeExpression->getNot()) ? 'NOT ' : '') . 'LIKE ' .
                array_pop($this->_expressions)
             . (($likeExpression->getEscapeString() !== null) ? ' ESCAPE ' . $likeExpression->getEscapeString() : '');
    }
    
    public function visitInExpression($inExpression)
    {
        $count = count($inExpression->getAtoms());
        $atoms = array();
        for ($i=0; $i<$count; $i++) {
            $atoms[] = array_pop($this->_expressions);
        }
        
        $this->_expressions[] = (($inExpression->getNot()) ? 'NOT ' : '') . 'IN ('
             . (($inExpression->getSubselect() !== null) ? array_pop($this->_expressions) :
                    implode(', ', $atoms))
             . ')';
    }
    
    public function visitNullComparisonExpression($nullComparisonExpression)
    {
        $this->_expressions[] = 'IS ' . (($nullComparisonExpression->getNot()) ? 'NOT ' : '') . 'NULL';
    }
    
    public function visitAtom($atom)
    {
        $conn = $atom->getParser()->getSqlBuilder()->getConnection();
        switch ($atom->getType()) {
            case 'param':
                $this->_expressions[] = $atom->getValue();
            break;
            case 'integer':
            case 'float':
                $this->_expressions[] = $conn->quote($atom->getValue(), $atom->getType());
            break;
            default:
                $stringQuoting = $this->_conn->getProperty('string_quoting');
                $this->_expressions[] = $stringQuoting['start']
                     . $conn->quote($this->_value, $this->_type)
                     . $stringQuoting['end'];
            break;
        }
    }
    
    public function visitPathExpression($pathExpression)
    {
        echo " PathExpression ";
                // Basic handy variables
        $parserResult = $pathExpression->getParser()->getParserResult();

        // Retrieving connection
        $manager = Doctrine_EntityManagerFactory::getManager(); 
        $conn = $manager->getConnection();

        // Looking for queryComponent to fetch
        $queryComponent = $parserResult->getQueryComponent($pathExpression->getComponentAlias());

        // Generating the SQL piece
        $str = $parserResult->getTableAliasFromComponentAlias($pathExpression->getComponentAlias()) . '.'
             . $queryComponent['metadata']->getColumnName($pathExpression->getFieldName());

        $this->_expressions[] = $conn->quoteIdentifier($str);
    }
    
    public function visitComparisonExpression($comparisonExpression)
    {
        echo " ComparisonExpression ";
        
        $expr = $comparisonExpression->getOperator() . ' ';
        if ($comparisonExpression->getIsSubselect()) {
            $expr .= '(' . array_pop($this->_expressions) . ')';
        } else {
            $expr .= array_pop($this->_expressions);
        }
        $this->_expressions[] = $expr;
    }
    
    
    
    public function getSql()
    {
        return $this->_sql;
    }*/
}
