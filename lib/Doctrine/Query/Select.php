<?php
/*
 *  $Id: From.php 1080 2007-02-10 18:17:08Z romanb $
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
 * <http://www.phpdoctrine.com>.
 */
Doctrine::autoload("Doctrine_Query_Part");
/**
 * Doctrine_Query_Select
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1080 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Query_Select extends Doctrine_Query_Part
{
    /**
     * processPendingFields
     * the fields in SELECT clause cannot be parsed until the components
     * in FROM clause are parsed, hence this method is called everytime a 
     * specific component is being parsed.
     *
     * @throws Doctrine_Query_Exception     if unknown component alias has been given
     * @param string $componentAlias        the alias of the component
     * @return void
     */
    public function processPendingFields($componentAlias)
    {
        $tableAlias = $this->getTableAlias($componentAlias);
        $table      = $this->_aliasMap[$componentAlias]['table'];

        if (isset($this->pendingFields[$componentAlias])) {
            $fields = $this->pendingFields[$componentAlias];

            // check for wildcards
            if (in_array('*', $fields)) {
                $fields = $table->getColumnNames();
            } else {
                // only auto-add the primary key fields if this query object is not 
                // a subquery of another query object
                if ( ! $this->isSubquery) {
                    $fields = array_unique(array_merge($table->getPrimaryKeys(), $fields));
                }
            }
        }
        foreach ($fields as $name) {
            $name = $table->getColumnName($name);

            $this->parts['select'][] = $tableAlias . '.' .$name . ' AS ' . $tableAlias . '__' . $name;
        }
        
        $this->neededTables[] = $tableAlias;

    }
    /**
     * parseSelect
     * parses the query select part and
     * adds selected fields to pendingFields array
     *
     * @param string $dql
     */
    public function parseSelect($dql)
    {
        $refs = Doctrine_Query::bracketExplode($dql, ',');

        foreach ($refs as $reference) {
            if (strpos($reference, '(') !== false) {
                if (substr($reference, 0, 1) === '(') {
                    // subselect found in SELECT part
                    $this->parseSubselect($reference);
                } else {
                    $this->parseAggregateFunction2($reference);
                }
            } else {

                $e = explode('.', $reference);
                if (count($e) > 2) {
                    $this->pendingFields[] = $reference;
                } else {
                    $this->pendingFields[$e[0]][] = $e[1];
                }
            }
        }
    }
    /** 
     * parseSubselect
     *
     * parses the subquery found in DQL SELECT part and adds the
     * parsed form into $pendingSubqueries stack
     *
     * @param string $reference
     * @return void
     */
    public function parseSubselect($reference) 
    {
        $e     = Doctrine_Query::bracketExplode($reference, ' ');
        $alias = $e[1];

        if (count($e) > 2) {
            if (strtoupper($e[1]) !== 'AS') {
                throw new Doctrine_Query_Exception('Syntax error near: ' . $reference);
            }
            $alias = $e[2];
        }
        
        $subquery = substr($e[0], 1, -1);
        
        $this->pendingSubqueries[] = array($subquery, $alias);
    }
    public function parseAggregateFunction2($func)
    {
        $e    = Doctrine_Query::bracketExplode($func, ' ');
        $func = $e[0];

        $pos  = strpos($func, '(');
        $name = substr($func, 0, $pos);

        try {
            $argStr = substr($func, ($pos + 1), -1);
            $args   = explode(',', $argStr);
    
            $func   = call_user_func_array(array($this->conn->expression, $name), $args);
    
            if(substr($func, 0, 1) !== '(') {
                $pos  = strpos($func, '(');
                $name = substr($func, 0, $pos);
            } else {
                $name = $func;
            }
    
            $e2     = explode(' ', $args[0]);
    
            $distinct = '';
            if(count($e2) > 1) {
                if(strtoupper($e2[0]) == 'DISTINCT')
                    $distinct  = 'DISTINCT ';
    
                $args[0] = $e2[1];
            }
    
    
    
            $parts = explode('.', $args[0]);
            $owner = $parts[0];
            $alias = (isset($e[1])) ? $e[1] : $name;
    
            $e3    = explode('.', $alias);
    
            if(count($e3) > 1) {
                $alias = $e3[1];
                $owner = $e3[0];
            }
    
            // a function without parameters eg. RANDOM()
            if ($owner === '') {
                $owner = 0;
            }
    
            $this->pendingAggregates[$owner][] = array($name, $args, $distinct, $alias);
        } catch(Doctrine_Expression_Exception $e) {
            throw new Doctrine_Query_Exception('Unknown function ' . $func . '.');
        }
    }
    public function processPendingSubqueries() 
    {
    	if ($this->subqueriesProcessed === true) {
            return false;
    	}

    	foreach ($this->pendingSubqueries as $value) {
            list($dql, $alias) = $value;

            $sql = $this->createSubquery()->parseQuery($dql, false)->getQuery();
            
            reset($this->tableAliases);
            
            $tableAlias = current($this->tableAliases);

            reset($this->compAliases);
            
            $componentAlias = key($this->compAliases);

            $sqlAlias = $tableAlias . '__' . count($this->aggregateMap);
    
            $this->parts['select'][] = '(' . $sql . ') AS ' . $sqlAlias;
    
            $this->aggregateMap[$alias] = $sqlAlias;
            $this->subqueryAggregates[$componentAlias][] = $alias;
        }
        $this->subqueriesProcessed = true;
        
        return true;
    }
    public function processPendingAggregates($componentAlias)
    {
        $tableAlias = $this->getTableAlias($componentAlias);

        if ( ! isset($this->tables[$tableAlias])) {
            throw new Doctrine_Query_Exception('Unknown component path ' . $componentAlias);
        }
        
        $root       = current($this->tables);
        $table      = $this->tables[$tableAlias];
        $aggregates = array();

        if(isset($this->pendingAggregates[$componentAlias])) {
            $aggregates = $this->pendingAggregates[$componentAlias];
        }
        
        if ($root === $table) {
            if (isset($this->pendingAggregates[0])) {
                $aggregates += $this->pendingAggregates[0];
            }
        }

        foreach($aggregates as $parts) {
            list($name, $args, $distinct, $alias) = $parts;

            $arglist = array();
            foreach($args as $arg) {
                $e = explode('.', $arg);


                if (is_numeric($arg)) {
                    $arglist[]  = $arg;
                } elseif (count($e) > 1) {
                    //$tableAlias = $this->getTableAlias($e[0]);
                    $table      = $this->tables[$tableAlias];

                    $e[1]       = $table->getColumnName($e[1]);

                    if( ! $table->hasColumn($e[1])) {
                        throw new Doctrine_Query_Exception('Unknown column ' . $e[1]);
                    }

                    $arglist[]  = $tableAlias . '.' . $e[1];
                } else {
                    $arglist[]  = $e[0];
                }
            }

            $sqlAlias = $tableAlias . '__' . count($this->aggregateMap);

            if(substr($name, 0, 1) !== '(') {
                $this->parts['select'][] = $name . '(' . $distinct . implode(', ', $arglist) . ') AS ' . $sqlAlias;
            } else {
                $this->parts['select'][] = $name . ' AS ' . $sqlAlias;
            }
            $this->aggregateMap[$alias] = $sqlAlias;
            $this->neededTables[] = $tableAlias;
        }
    }
}
