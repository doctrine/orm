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

#namespace Doctrine\ORM\Internal\Hydration;

/**
 * The hydrator has the tedious task to process result sets returned by the database
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
 * @link        www.doctrine-project.org
 * @since       1.0
 * @version     $Revision: 3192 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class Doctrine_ORM_Internal_Hydration_StandardHydrator extends Doctrine_ORM_Internal_Hydration_AbstractHydrator
{    
    /**
     * Parses the data returned by statement object.
     *
     * This is method defines the core of Doctrine's object population algorithm.
     *
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
        
        if ($hydrationMode == Doctrine_ORM_Query::HYDRATE_NONE) {
            return $stmt->fetchAll(PDO::FETCH_NUM);
        }
        
        $this->_tableAliases = $parserResult->getTableToClassAliasMap();
        $this->_queryComponents = $parserResult->getQueryComponents();

        if ($hydrationMode == Doctrine_ORM_Query::HYDRATE_ARRAY) {
            $driver = new Doctrine_ORM_Internal_Hydration_ArrayDriver();
        } else {
            $driver = new Doctrine_ORM_Internal_Hydration_ObjectDriver($this->_em);
        }

        $s = microtime(true);
        
        reset($this->_queryComponents);
        $rootAlias = key($this->_queryComponents);
        $rootEntityName = $this->_queryComponents[$rootAlias]['metadata']->getClassName();
        // if only one class is involved we can make our lives easier
        $isSimpleQuery = count($this->_queryComponents) <= 1;
        // Lookup map to quickly discover/lookup existing entities in the result
        // It's the identifier "memory"
        $identifierMap = array();
        // Holds for each class a pointer to the last previously seen element in the result set
        $resultPointers = array();
        // Holds the values of the identifier/primary key fields of entities,
        // separated by a pipe '|' and grouped by DQL class alias (r, u, i, ... whatever)
        // The $idTemplate is a prepared template. $id is set to a fresh template when
        // starting to process a row.
        $id = array();
        $idTemplate = array();
        
        if ($parserResult->isMixedQuery() || $hydrationMode == Doctrine_ORM_Query::HYDRATE_SCALAR) {
            $result = array();
        } else {
            $result = $driver->getElementCollection($rootEntityName);
        }  

        if ($stmt === false || $stmt === 0) {
            return $result;
        }

        // Initialize
        foreach ($this->_queryComponents as $dqlAlias => $component) {
            $identifierMap[$dqlAlias] = array();
            $resultPointers[$dqlAlias] = array();
            $idTemplate[$dqlAlias] = '';
        }
        
        $cache = array();
        // Evaluate HYDRATE_SINGLE_SCALAR
        if ($hydrationMode == Doctrine_ORM_Query::HYDRATE_SINGLE_SCALAR) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            //TODO: Let this exception be raised by Query as QueryException
            if (count($result) > 1 || count($result[0]) > 1) {
                throw Doctrine_ORM_Exceptions_HydrationException::nonUniqueResult();
            }
            $result = $this->_gatherScalarRowData($result[0], $cache);
            return array_shift($result);
        }

        $resultCounter = 0;
        // Process result set
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Evaluate HYDRATE_SCALAR
            if ($hydrationMode == Doctrine_ORM_Query::HYDRATE_SCALAR) {
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
                        ++$resultCounter;
                    } else {
                        $driver->addElementToIndexedCollection($result, $element, $field);
                    }
                } else {
                    if ($parserResult->isMixedQuery()) {
                        $result[] = array($element);
                        ++$resultCounter;
                    } else {
                        $driver->addElementToCollection($result, $element);
                    }
                }
                $identifierMap[$rootAlias][$id[$rootAlias]] = $driver->getLastKey($result);
            } else {
                $index = $identifierMap[$rootAlias][$id[$rootAlias]];
            }
            $driver->updateResultPointer($resultPointers, $result, $index, $rootAlias, false);
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
                $relationAlias = $relation->getSourceFieldName();
                $path = $parent . '.' . $dqlAlias;
                
                // Get a reference to the right element in the result tree.
                // This element will get the associated element attached.
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

                // Check the type of the relation (many or single-valued)
                if ( ! $relation->isOneToOne()) {
                    // x-to-many relation
                    $oneToOne = false;
                    if (isset($nonemptyComponents[$dqlAlias])) {
                        $driver->initRelatedCollection($baseElement, $relationAlias);
                        $indexExists = isset($identifierMap[$path][$id[$parent]][$id[$dqlAlias]]);
                        $index = $indexExists ? $identifierMap[$path][$id[$parent]][$id[$dqlAlias]] : false;
                        $indexIsValid = $index !== false ? $driver->isIndexKeyInUse($baseElement, $relationAlias, $index) : false;
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
                    } else if ( ! $driver->isFieldSet($baseElement, $relationAlias)) {
                        if ($hydrationMode == Doctrine_ORM_Query::HYDRATE_ARRAY) {
                            $baseElement[$relationAlias] = array();
                        } else {
                            $driver->setRelatedElement($baseElement, $relationAlias,
                                    $driver->getElementCollection($entityName));
                        }
                    }
                } else {
                    // x-to-one relation
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
                
                if ($hydrationMode == Doctrine_ORM_Query::HYDRATE_ARRAY) {
                    $coll =& $baseElement[$relationAlias];
                } else {
                    $coll = $driver->getReferenceValue($baseElement, $relationAlias);
                }
                
                if ($coll !== null) {
                    $driver->updateResultPointer($resultPointers, $coll, $index, $dqlAlias, $oneToOne); 
                }
            }
            
            // Append scalar values to mixed result sets
            if (isset($scalars)) {
                foreach ($scalars as $name => $value) {
                    $result[$resultCounter - 1][$name] = $value;
                }
            }
        }

        $stmt->closeCursor(); 
        $driver->flush();
        
        $e = microtime(true);
        echo 'Hydration took: ' . ($e - $s) . PHP_EOL;

        return $result;
    }
    
}
