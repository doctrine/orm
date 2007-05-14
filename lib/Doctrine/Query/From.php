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
 * <http://www.phpdoctrine.com>.
 */
Doctrine::autoload("Doctrine_Query_Part");
/**
 * Doctrine_Query_From
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Query_From extends Doctrine_Query_Part
{

    /**
     * DQL FROM PARSER
     * parses the from part of the query string

     * @param string $str
     * @return void
     */
    final public function parse($str)
    {

        $str = trim($str);
        $parts = Doctrine_Query::bracketExplode($str, 'JOIN');

        $operator = false;

        switch (trim($parts[0])) {
            case 'INNER':
                $operator = ':';
            case 'LEFT':
                array_shift($parts);
        }

        $last = '';

        foreach ($parts as $k => $part) {
            $part = trim($part);

            if (empty($part)) {
                continue;
            }

            $e    = explode(' ', $part);

            if (end($e) == 'INNER' || end($e) == 'LEFT') {
                $last = array_pop($e);
            }
            $part = implode(' ', $e);

            foreach (Doctrine_Query::bracketExplode($part, ',') as $reference) {
                $reference = trim($reference);
                $e         = explode('.', $reference);

                if ($operator) {
                    $reference = array_shift($e) . $operator . implode('.', $e);
                }

                $table = $this->query->load($reference);
            }

            $operator = ($last == 'INNER') ? ':' : '.';
        }
    }
    public function load($path, $loadFields = true) 
    {
        // parse custom join conditions
        $e = explode(' ON ', $path);
        
        $joinCondition = '';

        if (count($e) > 1) {
            $joinCondition = ' AND ' . $e[1];
            $path = $e[0];
        }

        $tmp            = explode(' ', $path);
        $componentAlias = (count($tmp) > 1) ? end($tmp) : false;

        $e = preg_split("/[.:]/", $tmp[0], -1);

        $fullPath = $tmp[0];
        $prevPath = '';
        $fullLength = strlen($fullPath);

        if (isset($this->_aliasMap[$e[0]])) {
            $table = $this->_aliasMap[$e[0]]['table'];

            $prevPath = $parent = array_shift($e);
        }

        foreach ($e as $key => $name) {
            // get length of the previous path
            $length = strlen($prevPath);

            // build the current component path
            $prevPath = ($prevPath) ? $prevPath . '.' . $name : $name;

            $delimeter = substr($fullPath, $length, 1);

            // if an alias is not given use the current path as an alias identifier
            if (strlen($prevPath) !== $fullLength || ! $componentAlias) {
                $componentAlias = $prevPath;
            }

            if ( ! isset($table)) {
                // process the root of the path
                $table = $this->loadRoot($name, $componentAlias);
            } else {

    
                $join = ($delimeter == ':') ? 'INNER JOIN ' : 'LEFT JOIN ';

                $relation = $table->getRelation($name);
                $this->_aliasMap[$componentAlias] = array('table'    => $relation->getTable(),
                                                          'parent'   => $parent,
                                                          'relation' => $relation);
                if( ! $relation->isOneToOne()) {
                   $this->needsSubquery = true;
                }
  
                $localAlias   = $this->getShortAlias($parent, $table->getTableName());
                $foreignAlias = $this->getShortAlias($componentAlias, $relation->getTable()->getTableName());
                $localSql     = $this->conn->quoteIdentifier($table->getTableName()) . ' ' . $localAlias;
                $foreignSql   = $this->conn->quoteIdentifier($relation->getTable()->getTableName()) . ' ' . $foreignAlias;

                $map = $relation->getTable()->inheritanceMap;
  
                if ( ! $loadFields || ! empty($map) || $joinCondition) {
                    $this->subqueryAliases[] = $foreignAlias;
                }

                if ($relation instanceof Doctrine_Relation_Association) {
                    $asf = $relation->getAssociationFactory();
  
                    $assocTableName = $asf->getTableName();
  
                    if( ! $loadFields || ! empty($map) || $joinCondition) {
                        $this->subqueryAliases[] = $assocTableName;
                    }

                    $assocPath = $prevPath . '.' . $asf->getComponentName();
  
                    $assocAlias = $this->getShortAlias($assocPath, $asf->getTableName());

                    $queryPart = $join . $assocTableName . ' ' . $assocAlias . ' ON ' . $localAlias  . '.'
                                                                  . $table->getIdentifier() . ' = '
                                                                  . $assocAlias . '.' . $relation->getLocal();

                    if ($relation instanceof Doctrine_Relation_Association_Self) {
                        $queryPart .= ' OR ' . $localAlias  . '.' . $table->getIdentifier() . ' = '
                                                                  . $assocAlias . '.' . $relation->getForeign();
                    }

                    $this->parts['from'][] = $queryPart;

                    $queryPart = $join . $foreignSql . ' ON ' . $foreignAlias . '.'
                                               . $relation->getTable()->getIdentifier() . ' = '
                                               . $assocAlias . '.' . $relation->getForeign()
                                               . $joinCondition;

                    if ($relation instanceof Doctrine_Relation_Association_Self) {
                        $queryPart .= ' OR ' . $foreignTable  . '.' . $table->getIdentifier() . ' = '
                                             . $assocAlias . '.' . $relation->getLocal();
                    }

                } else {
                    $queryPart = $join . $foreignSql
                                       . ' ON ' . $localAlias .  '.'
                                       . $relation->getLocal() . ' = ' . $foreignAlias . '.' . $relation->getForeign()
                                       . $joinCondition;
                }
                $this->parts['from'][] = $queryPart;
            }
            if ($loadFields) {
                             	
                $restoreState = false;
                // load fields if necessary
                if ($loadFields && empty($this->pendingFields)) {
                    $this->pendingFields[$componentAlias] = array('*');

                    $restoreState = true;
                }

                if(isset($this->pendingFields[$componentAlias])) {
                    $this->processPendingFields($componentAlias);
                }

                if ($restoreState) {
                    $this->pendingFields = array();
                }


                if(isset($this->pendingAggregates[$componentAlias]) || isset($this->pendingAggregates[0])) {
                    $this->processPendingAggregates($componentAlias);
                }
            }
        }
    }
    /**
     * loadRoot
     *
     * @param string $name
     * @param string $componentAlias
     */
    public function loadRoot($name, $componentAlias)
    {
    	// get the connection for the component
        $this->conn = Doctrine_Manager::getInstance()
                      ->getConnectionForComponent($name);

        $table = $this->conn->getTable($name);
        $tableName = $table->getTableName();

        // get the short alias for this table
        $tableAlias = $this->aliasHandler->getShortAlias($componentAlias, $tableName);
        // quote table name
        $queryPart = $this->conn->quoteIdentifier($tableName);

        if ($this->type === self::SELECT) {
            $queryPart .= ' ' . $tableAlias;
        }

        $this->parts['from'][] = $queryPart;
        $this->tableAliases[$tableAlias]  = $componentAlias;
        $this->_aliasMap[$componentAlias] = array('table' => $table);
        
        return $table;
    }
}
