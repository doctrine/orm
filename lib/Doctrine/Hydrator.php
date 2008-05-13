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
 * The hydrator has the tedious task to construct object or array graphs out of
 * a database result set.
 *
 * @package     Doctrine
 * @subpackage  Hydrator
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision: 3192 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class Doctrine_Hydrator extends Doctrine_Hydrator_Abstract
{    
    /**
     * hydrateResultSet
     * parses the data returned by statement object
     *
     * This is method defines the core of Doctrine's object population algorithm.
     *
     * The key idea is the loop over the rowset only once doing all the needed operations
     * within this massive loop.
     *
     * @todo: Detailed documentation. Refactor (too long & nesting level).
     *
     * @param mixed $stmt
     * @param array $tableAliases  Array that maps table aliases (SQL alias => DQL alias)
     * @param array $aliasMap  Array that maps DQL aliases to their components
     *                         (DQL alias => array(
     *                              'table' => Table object,
     *                              'parent' => Parent DQL alias (if any),
     *                              'relation' => Relation object (if any),
     *                              'map' => Custom index to use as the key in the result (if any),
     *                              'agg' => List of aggregate values (sql alias => dql alias)
     *                              )
     *                         )
     * @return mixed  The created object/array graph.
     */
    public function hydrateResultSet($parserResult)
    {        
        if ($parserResult->getHydrationMode() === null) {
            $hydrationMode = $this->_hydrationMode;
        } else {
            $hydrationMode = $parserResult->getHydrationMode();
        }
        
        $stmt = $parserResult->getDatabaseStatement();
        
        if ($hydrationMode == Doctrine::HYDRATE_NONE) {
            return $stmt->fetchAll(PDO::FETCH_NUM);
        }
        
        $this->_tableAliases = $parserResult->getTableToClassAliasMap();
        $this->_queryComponents = $parserResult->getQueryComponents();

        if ($hydrationMode == Doctrine::HYDRATE_ARRAY) {
            $driver = new Doctrine_Hydrator_ArrayDriver();
        } else {
            $driver = new Doctrine_Hydrator_RecordDriver($this->_em);
        }

        $event = new Doctrine_Event(null, Doctrine_Event::HYDRATE, null);

        //$s = microtime(true);
        
        // Used variables during hydration
        reset($this->_queryComponents);
        $rootAlias = key($this->_queryComponents);
        $rootComponentName = $this->_queryComponents[$rootAlias]['table']->getComponentName();
        // if only one component is involved we can make our lives easier
        $isSimpleQuery = count($this->_queryComponents) <= 1;
        // Holds hydration listeners that get called during hydration
        $listeners = array();
        // Lookup map to quickly discover/lookup existing records in the result
        // It's the identifier "memory"
        $identifierMap = array();
        // Holds for each component the last previously seen element in the result set
        $prev = array();
        // holds the values of the identifier/primary key fields of components,
        // separated by a pipe '|' and grouped by component alias (r, u, i, ... whatever)
        // the $idTemplate is a prepared template. $id is set to a fresh template when
        // starting to process a row.
        $id = array();
        $idTemplate = array();
        
        // Holds the resulting hydrated data structure
        $result = $driver->getElementCollection($rootComponentName);

        if ($stmt === false || $stmt === 0) {
            return $result;
        }

        // Initialize
        foreach ($this->_queryComponents as $dqlAlias => $component) {
            // disable lazy-loading of related elements during hydration
            $component['table']->setAttribute(Doctrine::ATTR_LOAD_REFERENCES, false);
            $componentName = $component['table']->getClassName();
            $listeners[$componentName] = $component['table']->getRecordListener();
            $identifierMap[$dqlAlias] = array();
            $prev[$dqlAlias] = array();
            $idTemplate[$dqlAlias] = '';
        }
        
        // Process result set
        $cache = array();
        while ($data = $stmt->fetch(Doctrine::FETCH_ASSOC)) {
            $id = $idTemplate; // initialize the id-memory
            $nonemptyComponents = array();
            $rowData = $this->_gatherRowData($data, $cache, $id, $nonemptyComponents);

            //
            // hydrate the data of the root entity from the current row
            //
            $class = $this->_queryComponents[$rootAlias]['table'];
            $componentName = $class->getComponentName();
            
            // just event stuff
            $event->set('data', $rowData[$rootAlias]);
            $listeners[$componentName]->preHydrate($event);
            //--
            
            // Check for an existing element
            $index = false;
            if ($isSimpleQuery || ! isset($identifierMap[$rootAlias][$id[$rootAlias]])) {
                $element = $driver->getElement($rowData[$rootAlias], $componentName);
                
                // just event stuff
                $event->set('data', $element);
                $listeners[$componentName]->postHydrate($event);
                //--

                // do we need to index by a custom field?
                if ($field = $this->_getCustomIndexField($rootAlias)) {
                    if (isset($result[$field])) {
                        throw new Doctrine_Hydrator_Exception("Couldn't hydrate. Found non-unique key mapping.");
                    } else if ( ! isset($element[$field])) {
                        throw new Doctrine_Hydrator_Exception("Couldn't hydrate. Found a non-existent key.");
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
            
            
            // $prev[$rootAlias] now points to the last element in $result.
            // now hydrate the rest of the data found in the current row, that belongs to other
            // (related) components.
            foreach ($rowData as $dqlAlias => $data) {
                $index = false;
                $map = $this->_queryComponents[$dqlAlias];
                $componentName = $map['table']->getComponentName();
                
                // just event stuff
                $event->set('data', $data);
                $listeners[$componentName]->preHydrate($event);
                //--

                $parent = $map['parent'];
                $relation = $map['relation'];
                $relationAlias = $relation->getAlias();

                $path = $parent . '.' . $dqlAlias;

                if ( ! isset($prev[$parent])) {
                    continue;
                }
                
                // check the type of the relation
                if ( ! $relation->isOneToOne()) {
                    $oneToOne = false;
                    // append element
                    if (isset($nonemptyComponents[$dqlAlias])) {
                        $driver->initRelatedCollection($prev[$parent], $relationAlias);
                        if ( ! isset($identifierMap[$path][$id[$parent]][$id[$dqlAlias]])) {
                            $element = $driver->getElement($data, $componentName);
                            
                            // just event stuff
                            $event->set('data', $element);
                            $listeners[$componentName]->postHydrate($event);
                            //--
                            
                            if ($field = $this->_getCustomIndexField($dqlAlias)) {
                                // TODO: we should check this earlier. Fields used in INDEXBY
                                //       must be unique. Then this can be removed here.
                                if (isset($prev[$parent][$relationAlias][$field])) {
                                    throw Doctrine_Hydrator_Exception::nonUniqueKeyMapping();
                                } else if ( ! isset($element[$field])) {
                                    throw Doctrine_Hydrator_Exception::nonExistantFieldUsedAsIndex($field);
                                }
                                $prev[$parent][$relationAlias][$element[$field]] = $element;
                            } else {
                                $prev[$parent][$relationAlias][] = $element;
                            }

                            $identifierMap[$path][$id[$parent]][$id[$dqlAlias]] = $driver->getLastKey($prev[$parent][$relationAlias]);
                        } else {
                            $index = $identifierMap[$path][$id[$parent]][$id[$dqlAlias]];
                        }
                        // register collection for later snapshots
                        //$driver->registerCollection($prev[$parent][$relationAlias]);
                    } else if ( ! isset($prev[$parent][$relationAlias])) {
                        $prev[$parent][$relationAlias] = $driver->getNullPointer();
                    }
                } else {
                    // 1-1 relation
                    $oneToOne = true; 
                    if ( ! isset($nonemptyComponents[$dqlAlias])) {
                        $prev[$parent][$relationAlias] = $driver->getNullPointer();
                    } else if ( ! isset($prev[$parent][$relationAlias])) {
                        $element = $driver->getElement($data, $componentName);
                        $prev[$parent][$relationAlias] = $element;
                    }
                }
                if ($prev[$parent][$relationAlias] !== null) {
                    $coll =& $prev[$parent][$relationAlias];
                    $this->_setLastElement($prev, $coll, $index, $dqlAlias, $oneToOne);
                }
            }
        }

        $stmt->closeCursor();
        
        $driver->flush();
        
        // re-enable lazy loading
        foreach ($this->_queryComponents as $dqlAlias => $data) {
            $data['table']->setAttribute(Doctrine::ATTR_LOAD_REFERENCES, true);
        }
        
        //$e = microtime(true);
        //echo 'Hydration took: ' . ($e - $s) . ' for '.count($result).' records<br />';

        return $result;
    }

    /**
     * _setLastElement
     *
     * sets the last element of given data array / collection
     * as previous element
     *
     * @param array $prev  The array that contains the pointers to the latest element of each class.
     * @param array|Collection  The object collection.
     * @param boolean|integer $index  Index of the element in the collection.
     * @param string $dqlAlias
     * @param boolean $oneToOne  Whether it is a single-valued association or not.
     * @return void
     * @todo Detailed documentation
     */
    protected function _setLastElement(&$prev, &$coll, $index, $dqlAlias, $oneToOne)
    {
        if ($coll === $this->_nullObject) {
            return false;
        }
        
        if ($index !== false) {
            // Link element at $index to previous element for the component 
            // identified by the DQL alias $alias
            $prev[$dqlAlias] =& $coll[$index];
            return;
        }
        
        if (is_array($coll) && $coll) {
            if ($oneToOne) {
                $prev[$dqlAlias] =& $coll;
            } else {
                end($coll);
                $prev[$dqlAlias] =& $coll[key($coll)];
            }
        } else if ($coll instanceof Doctrine_Entity) {
            $prev[$dqlAlias] = $coll;
        } else if (count($coll) > 0) {
            $prev[$dqlAlias] = $coll->getLast();
        } else if (isset($prev[$dqlAlias])) {
            unset($prev[$dqlAlias]);
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
    protected function _gatherRowData(&$data, &$cache, &$id, &$nonemptyComponents)
    {
        $rowData = array();
        
        foreach ($data as $key => $value) {
            // Parse each column name only once. Cache the results.
            if ( ! isset($cache[$key])) {
                // cache general information like the column name <-> field name mapping
                $e = explode('__', $key);
                $columnName = strtolower(array_pop($e));                
                $cache[$key]['dqlAlias'] = $this->_tableAliases[strtolower(implode('__', $e))];
                $mapper = $this->_queryComponents[$cache[$key]['dqlAlias']]['mapper'];
                $classMetadata = $mapper->getClassMetadata();
                // check whether it's an aggregate value or a regular field
                if (isset($this->_queryComponents[$cache[$key]['dqlAlias']]['agg'][$columnName])) {
                    $fieldName = $this->_queryComponents[$cache[$key]['dqlAlias']]['agg'][$columnName];
                } else {
                    $fieldName = $mapper->getFieldName($columnName);
                }
                
                $cache[$key]['fieldName'] = $fieldName;
                
                // cache identifier information
                if ($classMetadata->isIdentifier($fieldName)) {
                    $cache[$key]['isIdentifier'] = true;
                } else {
                    $cache[$key]['isIdentifier'] = false;
                }
                
                // cache type information
                $type = $classMetadata->getTypeOfColumn($columnName);
                if ($type == 'integer' || $type == 'string') {
                    $cache[$key]['isSimpleType'] = true;
                } else {
                    $cache[$key]['type'] = $type;
                    $cache[$key]['isSimpleType'] = false;
                }
            }

            $mapper = $this->_queryComponents[$cache[$key]['dqlAlias']]['mapper'];
            $dqlAlias = $cache[$key]['dqlAlias'];
            $fieldName = $cache[$key]['fieldName'];

            if ($cache[$key]['isIdentifier']) {
                $id[$dqlAlias] .= '|' . $value;
            }

            if ($cache[$key]['isSimpleType']) {
                $rowData[$dqlAlias][$fieldName] = $value;
            } else {
                $rowData[$dqlAlias][$fieldName] = $mapper->prepareValue(
                        $fieldName, $value, $cache[$key]['type']);
            }

            if ( ! isset($nonemptyComponents[$dqlAlias]) && $value !== null) {
                $nonemptyComponents[$dqlAlias] = true;
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
        return isset($this->_queryComponents[$alias]['map']) ? $this->_queryComponents[$alias]['map'] : null;
    }
    
}
