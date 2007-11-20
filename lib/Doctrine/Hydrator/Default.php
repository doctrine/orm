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
     * @return array
     */
    public function hydrateResultSet($stmt, $aliasMap, $tableAliases, $hydrationMode = null)
    {
        $this->_aliasMap = $aliasMap;
        //echo "aliasmap:<br />";
        /*foreach ($this->_aliasMap as $map) {
            Doctrine::dump($map['map']);
        }*/
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
        $array = array(); // Holds the resulting hydrated data structure
        $cache = array(); // Temporarily holds results of some operations to improve performance
        $listeners = array(); // Holds hydration listeners that get called during hydration
        $identifierMap = array(); // ???
        $prev = array(); // ???
        $id = array(); // ???
        $currData = array(); // ???
        $identifiable = array(); // ???
        
        $array = $driver->getElementCollection($componentName);

        if ($stmt === false || $stmt === 0) {
            return $array;
        }

        // Initialize the variables
        foreach ($this->_aliasMap as $alias => $data) {
            $componentName = $data['table']->getComponentName();
            $listeners[$componentName] = $data['table']->getRecordListener();
            $identifierMap[$alias] = array();
            $currData[$alias] = array();
            $prev[$alias] = array();
            $id[$alias] = '';
        }
        
        // Hydrate
        while ($data = $stmt->fetch(Doctrine::FETCH_ASSOC)) {
            $currData = array();
            $identifiable = array();

            foreach ($data as $key => $value) {

                if ( ! isset($cache[$key])) {
                    $e = explode('__', $key);
                    $last = strtolower(array_pop($e));          
                    $cache[$key]['alias'] = $tableAliases[strtolower(implode('__', $e))];
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

                $currData[$alias][$fieldName] = $table->prepareValue($fieldName, $value);

                if ($value !== null) {
                    $identifiable[$alias] = true;
                }
            }
            
            //echo "currdata of row:<br />";
            //Doctrine::dump($currData);
            //echo "<br /><br />";

            // dealing with root component
            $table = $this->_aliasMap[$rootAlias]['table'];
            $componentName = $table->getComponentName();
            $event->set('data', $currData[$rootAlias]);
            $listeners[$componentName]->preHydrate($event);
            $element = $driver->getElement($currData[$rootAlias], $componentName);

            $oneToOne = false;

            if ($isSimpleQuery) {
                $index = false;
            } else {
                $index = isset($identifierMap[$rootAlias][$id[$rootAlias]]) ?
                         $identifierMap[$rootAlias][$id[$rootAlias]] : false;
            }

            if ($index === false) {
                $event->set('data', $element);
                $listeners[$componentName]->postHydrate($event);

                if (isset($this->_aliasMap[$rootAlias]['map'])) {
                    $key = $this->_aliasMap[$rootAlias]['map'];

                    if (isset($array[$key])) {
                        throw new Doctrine_Hydrate_Exception("Couldn't hydrate. Found non-unique key mapping.");
                    }

                    if ( ! isset($element[$key])) {
                        throw new Doctrine_Hydrate_Exception("Couldn't hydrate. Found a non-existent key.");
                    }

                    $array[$element[$key]] = $element;
                } else {
                    $array[] = $element;
                }

                $identifierMap[$rootAlias][$id[$rootAlias]] = $driver->getLastKey($array);
            }

            $this->_setLastElement($prev, $array, $index, $rootAlias, $oneToOne);
            unset($currData[$rootAlias]);

            foreach ($currData as $alias => $data) {
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
                if ( ! $relation->isOneToOne()) {
                    // initialize the collection

                    if ($driver->initRelated($prev[$parent], $componentAlias)) {

                        // append element
                        if (isset($identifiable[$alias])) {
                            if ($isSimpleQuery) {
                                $index = false;
                            } else {
                                $index = isset($identifierMap[$path][$id[$parent]][$id[$alias]]) ?
                                         $identifierMap[$path][$id[$parent]][$id[$alias]] : false;
                            }

                            if ($index === false) {
                                $event->set('data', $element);
                                $listeners[$componentName]->postHydrate($event);

                                if (isset($map['map'])) {
                                    $key = $map['map'];
                                    if (isset($prev[$parent][$componentAlias][$key])) {
                                        throw new Doctrine_Hydrate_Exception("Couldn't hydrate. Found non-unique key mapping.");
                                    }
                                    if ( ! isset($element[$key])) {
                                        throw new Doctrine_Hydrate_Exception("Couldn't hydrate. Found a non-existent key.");
                                    }
                                    $prev[$parent][$componentAlias][$element[$key]] = $element;
                                } else {
                                    $prev[$parent][$componentAlias][] = $element;
                                }

                                $identifierMap[$path][$id[$parent]][$id[$alias]] = $driver->getLastKey($prev[$parent][$componentAlias]);
                            }
                        }
                        // register collection for later snapshots
                        $driver->registerCollection($prev[$parent][$componentAlias]);
                    }
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
            $id[$rootAlias] = '';
        }
        
        $driver->flush();

        $stmt->closeCursor();
        
        return $array;
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
            $prev[$alias] =& $coll[$index];
            return;
        }
        // first check the count (we do not want to get the last element
        // of an empty collection/array)
        if (count($coll) > 0) {
            if (is_array($coll)) {
                if ($oneToOne) {
                    $prev[$alias] =& $coll;
                } else {
                    end($coll);
                    $prev[$alias] =& $coll[key($coll)];
                }
            } else {
                $prev[$alias] = $coll->getLast();
            }
        } else {
            if (isset($prev[$alias])) {
                unset($prev[$alias]);
            }
        }
    }
    
}
