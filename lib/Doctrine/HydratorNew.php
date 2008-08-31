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

#namespace Doctrine::ORM::Internal;

/**
 * The hydrator has the tedious to process result sets returned by the database
 * and turn them into useable structures.
 * 
 * Runtime complexity: The following gives the overall number of iterations
 * required to process a result set when using identity hydration
 * (HYDRATE_IDENTITY_OBJECT or HYDRATE_IDENTITY_ARRAY).
 * 
 * <code>numRowsInResult * numColumnsInResult + numRowsInResult * numClassesInQuery</code>
 * 
 * This comes down to:
 * 
 * <code>(numRowsInResult * (numColumnsInResult + numClassesInQuery))</code>
 * 
 * For scalar hydration (HYDRATE_SCALAR) it's:
 * 
 * <code>numRowsInResult * numColumnsInResult</code>
 * 
 * Note that this is only a crude definition as it also heavily
 * depends on the complexity of all the single operations that are performed in
 * each iteration.
 * 
 * As can be seen, the number of columns in the result has the most impact on
 * the overall performance (apart from the row count, of course), since numClassesInQuery
 * is usually pretty low.
 * That's why the performance of the _gatherRowData() methods which are responsible
 * for the "numRowsInResult * numColumnsInResult" part is crucial to fast hydration.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision: 3192 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class Doctrine_HydratorNew extends Doctrine_Hydrator_Abstract
{    
    /**
     * Parses the data returned by statement object.
     *
     * This is method defines the core of Doctrine's object population algorithm.
     *
     * @todo: Detailed documentation. Refactor (too long & nesting level).
     *
     * @param mixed $stmt
     * @param array $tableAliases  Array that maps table aliases (SQL alias => DQL alias)
     * @param array $aliasMap  Array that maps DQL aliases to their components
     *                         (DQL alias => array(
     *                              'metadata' => Table object,
     *                              'parent' => Parent DQL alias (if any),
     *                              'relation' => Relation object (if any),
     *                              'map' => Custom index to use as the key in the result (if any),
     *                              'agg' => List of aggregate value names (sql alias => dql alias)
     *                              )
     *                         )
     * @return mixed  The created object/array graph.
     * @throws Doctrine_Hydrator_Exception  If the hydration process failed.
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

        $s = microtime(true);
        
        // Used variables during hydration
        reset($this->_queryComponents);
        $rootAlias = key($this->_queryComponents);
        $rootEntityName = $this->_queryComponents[$rootAlias]['metadata']->getClassName();
        // if only one class is involved we can make our lives easier
        $isSimpleQuery = count($this->_queryComponents) <= 1;
        // Lookup map to quickly discover/lookup existing records in the result
        // It's the identifier "memory"
        $identifierMap = array();
        // Holds for each class a pointer to the last previously seen element in the result set
        $resultPointers = array();
        // holds the values of the identifier/primary key fields of components,
        // separated by a pipe '|' and grouped by component alias (r, u, i, ... whatever)
        // the $idTemplate is a prepared template. $id is set to a fresh template when
        // starting to process a row.
        $id = array();
        $idTemplate = array();
        
        // Holds the resulting hydrated data structure
        if ($parserResult->isMixedQuery() || $hydrationMode == Doctrine::HYDRATE_SCALAR) {
            $result = array();
        } else {
            $result = $driver->getElementCollection($rootEntityName);
        }  

        if ($stmt === false || $stmt === 0) {
            return $result;
        }

        // Initialize
        foreach ($this->_queryComponents as $dqlAlias => $component) {
            // disable lazy-loading of related elements during hydration
            $component['metadata']->setAttribute('loadReferences', false);
            $entityName = $component['metadata']->getClassName();
            $identifierMap[$dqlAlias] = array();
            $resultPointers[$dqlAlias] = array();
            $idTemplate[$dqlAlias] = '';
        }
        
        $cache = array();
        // Evaluate HYDRATE_SINGLE_SCALAR
        if ($hydrationMode == Doctrine::HYDRATE_SINGLE_SCALAR) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($result) > 1 || count($result[0]) > 1) {
                throw Doctrine_Hydrator_Exception::nonUniqueResult();
            }
            return array_shift($this->_gatherScalarRowData($result[0], $cache)); 
        }
        
        // Process result set
        while ($data = $stmt->fetch(Doctrine::FETCH_ASSOC)) {
            // Evaluate HYDRATE_SCALAR
            if ($hydrationMode == Doctrine::HYDRATE_SCALAR) {
                $result[] = $this->_gatherScalarRowData($data, $cache);
                continue;      
            }
            
            // From here on its all about graph construction            
            
            // 1) Initialize
            $id = $idTemplate; // initialize the id-memory
            $nonemptyComponents = array();
            $rowData = $this->_gatherRowData($data, $cache, $id, $nonemptyComponents);

            // 2) Hydrate the data of the root entity from the current row            
            // Check for an existing element
            $index = false;
            if ($isSimpleQuery || ! isset($identifierMap[$rootAlias][$id[$rootAlias]])) {
                $element = $driver->getElement($rowData[$rootAlias], $rootEntityName);
                if ($field = $this->_getCustomIndexField($rootAlias)) {
                    if ($parserResult->isMixedQuery()) {
                        $result[] = array(
                            $driver->getFieldValue($element, $field) => $element
                        );
                    } else {
                        $driver->addElementToIndexedCollection($result, $element, $field);
                    }
                } else {
                    if ($parserResult->isMixedQuery()) {
                        $result[] = array($element);
                    } else {
                        $driver->addElementToCollection($result, $element);
                    }
                }
                $identifierMap[$rootAlias][$id[$rootAlias]] = $driver->getLastKey($result);
            } else {
                $index = $identifierMap[$rootAlias][$id[$rootAlias]];
            }
            $this->_updateResultPointer($resultPointers, $result, $index, $rootAlias, false);
            unset($rowData[$rootAlias]);
            // end hydrate data of the root component for the current row
            
            // Extract scalar values. They're appended at the end.
            if (isset($rowData['scalars'])) {
                $scalars = $rowData['scalars'];
                unset($rowData['scalars']);
            }
            
            // 3) Now hydrate the rest of the data found in the current row, that
            // belongs to other (related) entities.
            foreach ($rowData as $dqlAlias => $data) {                
                $index = false;
                $map = $this->_queryComponents[$dqlAlias];
                $entityName = $map['metadata']->getClassName();
                $parent = $map['parent'];
                $relation = $map['relation'];
                $relationAlias = $relation->getSourceFieldName();//$relation->getAlias();
                $path = $parent . '.' . $dqlAlias;
                
                // pick the right element that will get the associated element attached
                if ($parserResult->isMixedQuery() && $parent == $rootAlias) {
                    $key = key(reset($resultPointers));
                    // TODO: Exception if $key === null ?
                    $baseElement =& $resultPointers[$parent][$key];
                } else if (isset($resultPointers[$parent])) {
                    $baseElement =& $resultPointers[$parent];
                } else {
                    unset($resultPointers[$dqlAlias]); // Ticket #1228
                    continue;
                }

                // check the type of the relation (many or single-valued)
                if ( ! $relation->isOneToOne()) {
                    // x-many relation
                    $oneToOne = false;
                    if (isset($nonemptyComponents[$dqlAlias])) {
                        $driver->initRelatedCollection($baseElement, $relationAlias);
                        $indexExists = isset($identifierMap[$path][$id[$parent]][$id[$dqlAlias]]);
                        $index = $indexExists ? $identifierMap[$path][$id[$parent]][$id[$dqlAlias]] : false;
                        $indexIsValid = $index !== false ? isset($baseElement[$relationAlias][$index]) : false;
                        if ( ! $indexExists || ! $indexIsValid) {
                            $element = $driver->getElement($data, $entityName);
                            if ($field = $this->_getCustomIndexField($dqlAlias)) {
                                $driver->addRelatedIndexedElement($baseElement, $relationAlias, $element, $field);
                            } else {
                                $driver->addRelatedElement($baseElement, $relationAlias, $element);
                            }
                            $identifierMap[$path][$id[$parent]][$id[$dqlAlias]] = $driver->getLastKey(
                                    $driver->getReferenceValue($baseElement, $relationAlias));
                        }
                    } else if ( ! isset($baseElement[$relationAlias])) {
                        $driver->setRelatedElement($baseElement, $relationAlias,
                                $driver->getElementCollection($entityName));
                    }
                } else {
                    // x-1 relation
                    $oneToOne = true;
                    if ( ! isset($nonemptyComponents[$dqlAlias]) &&
                            ! $driver->isFieldSet($baseElement, $relationAlias)) {
                        $driver->setRelatedElement($baseElement, $relationAlias,
                                $driver->getNullPointer());
                    } else if ( ! $driver->isFieldSet($baseElement, $relationAlias)) {
                        $driver->setRelatedElement($baseElement, $relationAlias,
                                $driver->getElement($data, $entityName));
                    }
                }
                if (($coll =& $driver->getReferenceValue($baseElement, $relationAlias)) !== null) {
                    $this->_updateResultPointer($resultPointers, $coll, $index, $dqlAlias, $oneToOne);   
                }
            }
            
            // append scalar values to mixed result sets
            if (isset($scalars)) {
                $rowNumber = count($result) - 1;
                foreach ($scalars as $name => $value) {
                    $result[$rowNumber][$name] = $value;
                }
            }
        }

        $stmt->closeCursor(); 
        $driver->flush();
        
        // re-enable lazy loading
        foreach ($this->_queryComponents as $dqlAlias => $data) {
            $data['metadata']->setAttribute('loadReferences', true);
        }
        
        $e = microtime(true);
        echo 'Hydration took: ' . ($e - $s) . ' for '.count($result).' records' . PHP_EOL;

        return $result;
    }

    /**
     * Updates the result pointer for an Entity. The result pointers point to the
     * last seen instance of each Entity type. This is used for graph construction.
     *
     * @param array $resultPointers  The result pointers.
     * @param array|Collection $coll  The element.
     * @param boolean|integer $index  Index of the element in the collection.
     * @param string $dqlAlias
     * @param boolean $oneToOne  Whether it is a single-valued association or not.
     */
    protected function _updateResultPointer(&$resultPointers, &$coll, $index, $dqlAlias, $oneToOne)
    {
        if ($coll === $this->_nullObject || $coll === null) {
            unset($resultPointers[$dqlAlias]); // Ticket #1228
            return;
        }
        
        if ($index !== false) {
            $resultPointers[$dqlAlias] =& $coll[$index];
            return;
        }
        
        if (is_array($coll) && $coll) {
            if ($oneToOne) {
                $resultPointers[$dqlAlias] =& $coll;
            } else {
                end($coll);
                $resultPointers[$dqlAlias] =& $coll[key($coll)];
            }
        } else if ($coll instanceof Doctrine_Entity) {
            $resultPointers[$dqlAlias] = $coll;
        } else if (count($coll) > 0) {
            $resultPointers[$dqlAlias] = $coll->getLast();
        }
    }
    
    /**
     * Processes a row of the result set.
     * Used for identity hydration (HYDRATE_IDENTITY_OBJECT and HYDRATE_IDENTITY_ARRAY).
     * Puts the elements of a result row into a new array, grouped by the class
     * they belong to. The column names in the result set are mapped to their 
     * field names during this procedure as well as any necessary conversions on
     * the values applied.
     * 
     * @return array  An array with all the fields (name => value) of the data row, 
     *                grouped by their component (alias).
     * @todo Significant code duplication with _gatherScalarRowData(). Good refactoring
     *       possible without sacrificing performance?
     */
    protected function _gatherRowData(&$data, &$cache, &$id, &$nonemptyComponents)
    {
        $rowData = array();
        
        foreach ($data as $key => $value) {
            // Parse each column name only once. Cache the results.
            if ( ! isset($cache[$key])) {
                if ($this->_isIgnoredName($key)) continue;
                
                // Cache general information like the column name <-> field name mapping
                $e = explode(Doctrine_Query_Production::SQLALIAS_SEPARATOR, $key);
                $columnName = array_pop($e);                
                $cache[$key]['dqlAlias'] = $this->_tableAliases[
                        implode(Doctrine_Query_Production::SQLALIAS_SEPARATOR, $e)
                        ];
                $classMetadata = $this->_queryComponents[$cache[$key]['dqlAlias']]['metadata'];
                // check whether it's an aggregate value or a regular field
                if (isset($this->_queryComponents[$cache[$key]['dqlAlias']]['agg'][$columnName])) {
                    $fieldName = $this->_queryComponents[$cache[$key]['dqlAlias']]['agg'][$columnName];
                    $cache[$key]['isScalar'] = true;
                } else {
                    $fieldName = $classMetadata->lookupFieldName($columnName);
                    $cache[$key]['isScalar'] = false;
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

            $class = $this->_queryComponents[$cache[$key]['dqlAlias']]['metadata'];
            $dqlAlias = $cache[$key]['dqlAlias'];
            $fieldName = $cache[$key]['fieldName'];

            if ($cache[$key]['isScalar']) {
                $rowData['scalars'][$fieldName] = $value;
                continue;
            }
            
            if ($cache[$key]['isIdentifier']) {
                $id[$dqlAlias] .= '|' . $value;
            }

            if ($cache[$key]['isSimpleType']) {
                $rowData[$dqlAlias][$fieldName] = $value;
            } else {
                $rowData[$dqlAlias][$fieldName] = $this->prepareValue(
                        $class, $fieldName, $value, $cache[$key]['type']);
            }
            //$rowData[$dqlAlias][$fieldName] = $cache[$key]['type']->convertToObjectValue($value);

            if ( ! isset($nonemptyComponents[$dqlAlias]) && $value !== null) {
                $nonemptyComponents[$dqlAlias] = true;
            }
        }
        
        return $rowData;
    }
    
    /**
     * Processes a row of the result set.
     * Used for HYDRATE_SCALAR. This is a variant of _gatherRowData() that
     * simply converts column names to field names and properly prepares the
     * values. The resulting row has the same number of elements as before.
     *
     * @param array $data
     * @param array $cache
     * @return array The processed row.
     * @todo Significant code duplication with _gatherRowData(). Good refactoring
     *       possible without sacrificing performance?
     */
    private function _gatherScalarRowData(&$data, &$cache)
    {
        $rowData = array();
        
        foreach ($data as $key => $value) {
            // Parse each column name only once. Cache the results.
            if ( ! isset($cache[$key])) {
                if ($this->_isIgnoredName($key)) continue;
                
                // cache general information like the column name <-> field name mapping
                $e = explode(Doctrine_Query_Production::SQLALIAS_SEPARATOR, $key);
                $columnName = array_pop($e);              
                $cache[$key]['dqlAlias'] = $this->_tableAliases[
                        implode(Doctrine_Query_Production::SQLALIAS_SEPARATOR, $e)
                        ];
                $classMetadata = $this->_queryComponents[$cache[$key]['dqlAlias']]['metadata'];
                // check whether it's an aggregate value or a regular field
                if (isset($this->_queryComponents[$cache[$key]['dqlAlias']]['agg'][$columnName])) {
                    $fieldName = $this->_queryComponents[$cache[$key]['dqlAlias']]['agg'][$columnName];
                    $cache[$key]['isScalar'] = true;
                } else {
                    $fieldName = $classMetadata->lookupFieldName($columnName);
                    $cache[$key]['isScalar'] = false;
                }
                
                $cache[$key]['fieldName'] = $fieldName;
                
                // cache type information
                $type = $classMetadata->getTypeOfColumn($columnName);
                if ($type == 'integer' || $type == 'string') {
                    $cache[$key]['isSimpleType'] = true;
                } else {
                    $cache[$key]['type'] = $type;
                    $cache[$key]['isSimpleType'] = false;
                }
            }

            $class = $this->_queryComponents[$cache[$key]['dqlAlias']]['metadata'];
            $dqlAlias = $cache[$key]['dqlAlias'];
            $fieldName = $cache[$key]['fieldName'];

            if ($cache[$key]['isSimpleType'] || $cache[$key]['isScalar']) {
                $rowData[$dqlAlias . '_' . $fieldName] = $value;
            } else {
                $rowData[$dqlAlias . '_' . $fieldName] = $this->prepareValue(
                        $class, $fieldName, $value, $cache[$key]['type']);
            }
            //$rowData[$dqlAlias . '_' . $fieldName] = $cache[$key]['type']->convertToObjectValue($value);
        }
        
        return $rowData;
    }
    
    /** 
     * Gets the custom field used for indexing for the specified component alias.
     * 
     * @return string  The field name of the field used for indexing or NULL
     *                 if the component does not use any custom field indices.
     */
    private function _getCustomIndexField($alias)
    {
        return isset($this->_queryComponents[$alias]['map']) ? $this->_queryComponents[$alias]['map'] : null;
    }
    
    /**
     * Checks whether a name is ignored. Used during result set parsing to skip
     * certain elements in the result set that do not have any meaning for the result.
     * (I.e. ORACLE limit/offset emulation adds doctrine_rownum to the result set).
     *
     * @param string $name
     * @return boolean
     */
    private function _isIgnoredName($name)
    {
        return $name == 'doctrine_rownum';
    }
    
    /**
     * prepareValue
     * this method performs special data preparation depending on
     * the type of the given column
     *
     * 1. It unserializes array and object typed columns
     * 2. Uncompresses gzip typed columns
     * 3. Gets the appropriate enum values for enum typed columns
     * 4. Initializes special null object pointer for null values (for fast column existence checking purposes)
     *
     * example:
     * <code type='php'>
     * $field = 'name';
     * $value = null;
     * $table->prepareValue($field, $value); // Doctrine_Null
     * </code>
     *
     * @param string $field     the name of the field
     * @param string $value     field value
     * @param string $typeHint  A hint on the type of the value. If provided, the type lookup
     *                          for the field can be skipped. Used i.e. during hydration to
     *                          improve performance on large and/or complex results.
     * @return mixed            prepared value
     */
    public function prepareValue(Doctrine_ClassMetadata $class, $fieldName, $value, $typeHint = null)
    {
        if ($value === $this->_nullObject) {
            return $this->_nullObject;
        } else if ($value === null) {
            return null;
        } else {
            $type = is_null($typeHint) ? $class->getTypeOf($fieldName) : $typeHint;
            switch ($type) {
                case 'integer':
                case 'string':
                case 'enum':
                case 'boolean':
                    // don't do any conversions on primitive types
                    break;
                case 'array':
                case 'object':
                    if (is_string($value)) {
                        $value = unserialize($value);
                        if ($value === false) {
                            throw new Doctrine_Hydrator_Exception('Unserialization of ' . $fieldName . ' failed.');
                        }
                        return $value;
                    }
                    break;
                case 'gzip':
                    $value = gzuncompress($value);
                    if ($value === false) {
                        throw new Doctrine_Hydrator_Exception('Uncompressing of ' . $fieldName . ' failed.');
                    }
                    return $value;
                    break;
            }
        }
        return $value;
    }
    
    
    
    /** Needed only temporarily until the new parser is ready */
    private $_isResultMixed = false;
    public function setResultMixed($bool)
    {
        $this->_isResultMixed = $bool;
    }
    
}
