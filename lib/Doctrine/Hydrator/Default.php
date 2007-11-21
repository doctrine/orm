<?php
/*
 *  $Id: Hydrate.php 3192 2007-11-19 17:55:23Z romanb $
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

/**
 * Doctrine_Hydrate is a base class for Doctrine_RawSql and Doctrine_Query.
 * Its purpose is to populate object graphs.
 *
 *
 * @package     Doctrine
 * @subpackage  Hydrate
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 3192 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Hydrator_Default extends Doctrine_Hydrator_Abstract
{    
    /**
     * hydrateResultSet
     * parses the data returned by statement object
     *
     * This is method defines the core of Doctrine object population algorithm
     * hence this method strives to be as fast as possible
     *
     * The key idea is the loop over the rowset only once doing all the needed operations
     * within this massive loop.
     *
     * @todo: Detailed documentation. Refactor (too long & nesting level).
     *
     * @param mixed $stmt
     * @param array $tableAliases  Array that maps table aliases (SQL alias => DQL alias)
     * @param array $aliasMap  Array that maps DQL aliases to their components
     *                         (DQL alias => array('table' => Table object,
     *                                             'parent' => Parent DQL alias (if any),
     *                                             'relation' => Relation object (if any),
     *                                             'map' => ??? (if any)
     *                                            )
     *                         )
     * @return array
     */
    public function hydrateResultSet($stmt, $aliasMap, $tableAliases, $hydrationMode = null)
    {
        //$s = microtime(true);
        
        $this->_aliasMap = $aliasMap;
        $this->_tableAliases = $tableAliases;
        /*echo "aliasmap:<br />";
        foreach ($this->_aliasMap as $map) {
            if ( ! empty($map['map'])) {
                Doctrine::dump($map['map']);
            }
        }*/
        //Doctrine::dump($this->_aliasMap);
        //echo "<br />";
        //echo "tableAliases:<br />";
        //Doctrine::dump($tableAliases);
        //echo "<br /><br />";
        
        if ($hydrationMode == Doctrine::HYDRATE_NONE) {
            return $stmt->fetchAll(PDO::FETCH_NUM);
        }
        
        if ($hydrationMode === null) {
            $hydrationMode = $this->_hydrationMode;
        }

        if ($hydrationMode === Doctrine::HYDRATE_ARRAY) {
            $driver = new Doctrine_Hydrator_Default_FetchModeDriver_Array();
        } else {
            $driver = new Doctrine_Hydrator_Default_FetchModeDriver_Record();
        }

        $event = new Doctrine_Event(null, Doctrine_Event::HYDRATE, null);


        // Used variables during hydration
        $rootMap = reset($this->_aliasMap);
        $rootAlias = key($this->_aliasMap);
        $componentName = $rootMap['table']->getComponentName();
        $isSimpleQuery = count($this->_aliasMap) <= 1;
        $result = array(); // Holds the resulting hydrated data structure
        $listeners = array(); // Holds hydration listeners that get called during hydration
        $identifierMap = array(); // Lookup map to quickly discover/lookup existing records in the result 
        $prev = array(); // Holds for each component the last previously seen element in the result set
        $id = array(); // holds the values of the identifier/primary key columns of components,
                       // separated by a pipe '|' and grouped by component alias (r, u, i, ... whatever)
        
        $result = $driver->getElementCollection($componentName);

        if ($stmt === false || $stmt === 0) {
            return $result;
        }

        // Initialize the variables
        foreach ($this->_aliasMap as $alias => $data) {
            $componentName = $data['table']->getComponentName();
            $listeners[$componentName] = $data['table']->getRecordListener();
            $identifierMap[$alias] = array();
            $prev[$alias] = array();
            $id[$alias] = '';
        }
        
        // Process result set
        $cache = array();
        while ($data = $stmt->fetch(Doctrine::FETCH_ASSOC)) {
            $identifiable = array();

            $rowData = $this->_gatherRowData($data, $cache, $id, $identifiable);
            
            //echo "rowData of row:<br />";
            //Doctrine::dump($rowData);
            //echo "<br /><br />";

            //
            // hydrate the data of the root component from the current row
            //
            $table = $this->_aliasMap[$rootAlias]['table'];
            $componentName = $table->getComponentName();
            $event->set('data', $rowData[$rootAlias]);
            $listeners[$componentName]->preHydrate($event);
            $element = $driver->getElement($rowData[$rootAlias], $componentName);
            $index = false;
            
            // Check for an existing element
            if ($isSimpleQuery || ! isset($identifierMap[$rootAlias][$id[$rootAlias]])) {
                $event->set('data', $element);
                $listeners[$componentName]->postHydrate($event);

                // do we need to index by a custom field?
                if ($field = $this->_getCustomIndexField($rootAlias)) {
                    if (isset($result[$field])) {
                        throw new Doctrine_Hydrate_Exception("Couldn't hydrate. Found non-unique key mapping.");
                    } else if ( ! isset($element[$field])) {
                        throw new Doctrine_Hydrate_Exception("Couldn't hydrate. Found a non-existent key.");
                    }
                    $result[$element[$field]] = $element;
                } else {
                    $result[] = $element;
                }

                $identifierMap[$rootAlias][$id[$rootAlias]] = $driver->getLastKey($result);
            } else {
                $index = $identifierMap[$rootAlias][$id[$rootAlias]];
            }

            $this->_setLastElement($prev, $result, $index, $rootAlias, false);
            unset($rowData[$rootAlias]);
            
            // end hydrate data of the root component for the current row
            //echo "\$result after root element hydration:<br />";
            //Doctrine::dump($result);
            //echo "<br /><br />";
            
            // now hydrate the rest of the data found in the current row, that belongs to other
            // (related) components
            $oneToOne = false;
            foreach ($rowData as $alias => $data) {
                $index = false;
                $map   = $this->_aliasMap[$alias];
                $table = $this->_aliasMap[$alias]['table'];
                $componentName = $table->getComponentName();
                $event->set('data', $data);
                $listeners[$componentName]->preHydrate($event);

                $element = $driver->getElement($data, $componentName);

                $parent   = $map['parent'];
                $relation = $map['relation'];
                $componentAlias = $map['relation']->getAlias();

                $path = $parent . '.' . $alias;

                if ( ! isset($prev[$parent])) {
                    break;
                }
                
                // check the type of the relation
                if ( ! $relation->isOneToOne() && $driver->initRelated($prev[$parent], $componentAlias)) {
                    // append element
                    if (isset($identifiable[$alias])) {
                        if ($isSimpleQuery || ! isset($identifierMap[$path][$id[$parent]][$id[$alias]])) {
                            //$index = false;
                            $event->set('data', $element);
                            $listeners[$componentName]->postHydrate($event);

                            if ($field = $this->_getCustomIndexField($alias)) {
                                if (isset($prev[$parent][$componentAlias][$field])) {
                                    throw new Doctrine_Hydrate_Exception("Couldn't hydrate. Found non-unique key mapping.");
                                } else if ( ! isset($element[$field])) {
                                    throw new Doctrine_Hydrate_Exception("Couldn't hydrate. Found a non-existent key.");
                                }
                                $prev[$parent][$componentAlias][$element[$field]] = $element;
                            } else {
                                $prev[$parent][$componentAlias][] = $element;
                            }

                            $identifierMap[$path][$id[$parent]][$id[$alias]] = $driver->getLastKey($prev[$parent][$componentAlias]);
                        } else {
                            $index = $identifierMap[$path][$id[$parent]][$id[$alias]];
                        }
                    }
                    // register collection for later snapshots
                    $driver->registerCollection($prev[$parent][$componentAlias]);
                } else {
                    if ( ! isset($identifiable[$alias])) {
                        $prev[$parent][$componentAlias] = $driver->getNullPointer();
                    } else {
                        $prev[$parent][$componentAlias] = $element;
                    }
                    $oneToOne = true; 
                }
                $coll =& $prev[$parent][$componentAlias];
                $this->_setLastElement($prev, $coll, $index, $alias, $oneToOne);
                $id[$alias] = '';
            }
            //echo "\$result after related element hydration:<br />";
            //Doctrine::dump($result);
            //echo "<br /><br />";
            $id[$rootAlias] = '';
        }
        
        $driver->flush();

        $stmt->closeCursor();
        
        $e = microtime(true);

        //echo 'Hydration took: ' . ($e - $s) . ' for '.count($result).' records<br />';
        
        return $result;
    }

    /**
     * _setLastElement
     *
     * sets the last element of given data array / collection
     * as previous element
     *
     * @param boolean|integer $index
     * @return void
     * @todo Detailed documentation
     */
    protected function _setLastElement(&$prev, &$coll, $index, $alias, $oneToOne)
    {
        if ($coll === self::$_null) {
            return false;
        }
        
        if ($index !== false) {
            // Set lement at $index as previous element for the component 
            // identified by the DQL alias $alias
            $prev[$alias] =& $coll[$index];
            return;
        }
        
        if (is_array($coll) && $coll) {
            if ($oneToOne) {
                $prev[$alias] =& $coll;
            } else {
                end($coll);
                $prev[$alias] =& $coll[key($coll)];
            }
        } else if (count($coll) > 0) {
            $prev[$alias] = $coll->getLast();
        } else if (isset($prev[$alias])) {
            unset($prev[$alias]);
        }
    }
    
    /**
     * Puts the fields of a data row into a new array, grouped by the component
     * they belong to. The column names in the result set are mapped to their 
     * field names during this procedure.
     * 
     * @return array  An array with all the fields (name => value) of the data row, 
     *                grouped by their component (alias).
     */
    protected function _gatherRowData(&$data, &$cache, &$id, &$identifiable)
    {
        $rowData = array();

        foreach ($data as $key => $value) {
            // Parse each column name only once. Cache the results.
            if ( ! isset($cache[$key])) {
                $e = explode('__', $key);
                $last = strtolower(array_pop($e));          
                $cache[$key]['alias'] = $this->_tableAliases[strtolower(implode('__', $e))];
                $fieldName = $this->_aliasMap[$cache[$key]['alias']]['table']->getFieldName($last);
                $cache[$key]['fieldName'] = $fieldName;
            }

            $map   = $this->_aliasMap[$cache[$key]['alias']];
            $table = $map['table'];
            $alias = $cache[$key]['alias'];
            $fieldName = $cache[$key]['fieldName'];

            if (isset($this->_aliasMap[$alias]['agg'][$fieldName])) {
                $fieldName = $this->_aliasMap[$alias]['agg'][$fieldName];
            }

            if ($table->isIdentifier($fieldName)) {
                $id[$alias] .= '|' . $value;
            }

            $rowData[$alias][$fieldName] = $table->prepareValue($fieldName, $value);

            if ($value !== null) {
                $identifiable[$alias] = true;
            }
        }
        
        return $rowData;
    }
    
    /** 
     * Gets the custom field used for indexing for the specified component alias.
     * 
     * @return string  The field name of the field used for indexing or NULL
     *                 if the component does not use any custom field indices.
     */
    protected function _getCustomIndexField($alias)
    {
        return isset($this->_aliasMap[$alias]['map']) ? $this->_aliasMap[$alias]['map'] : null;
    }
    
}
