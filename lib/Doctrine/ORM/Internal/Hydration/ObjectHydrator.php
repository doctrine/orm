<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ObjectHydrator
 *
 * @author robo
 */
class Doctrine_ORM_Internal_Hydration_ObjectHydrator extends Doctrine_ORM_Internal_Hydration_AbstractHydrator
{
    /** Collections initialized by the driver */
    private $_collections = array();
    /** Memory for initialized relations */
    private $_initializedRelations = array();
    private $_metadataMap = array();
    private $_rootAlias;
    private $_rootEntityName;
    private $_isSimpleQuery = false;
    private $_identifierMap = array();
    private $_resultPointers = array();
    private $_idTemplate = array();
    private $_resultCounter = 0;

    protected function _prepare($parserResult)
    {
        parent::_prepare($parserResult);
        reset($this->_queryComponents);
        $this->_rootAlias = key($this->_queryComponents);
        $this->_rootEntityName = $this->_queryComponents[$this->_rootAlias]['metadata']->getClassName();
        $this->_isSimpleQuery = count($this->_queryComponents) <= 1;
        $this->_identifierMap = array();
        $this->_resultPointers = array();
        $this->_idTemplate = array();
        $this->_resultCounter = 0;
        foreach ($this->_queryComponents as $dqlAlias => $component) {
            $this->_identifierMap[$dqlAlias] = array();
            $this->_resultPointers[$dqlAlias] = array();
            $this->_idTemplate[$dqlAlias] = '';
        }
    }

    /** @override */
    protected function _hydrateAll($parserResult)
    {
        $s = microtime(true);
        
        if ($this->_parserResult->isMixedQuery()) {
            $result = array();
        } else {
            $result = new Doctrine_ORM_Collection($this->_em, $this->_rootEntityName);
        }

        $cache = array();
        // Process result set
        while ($data = $this->_stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->_hydrateRow($data, $cache, $result);
        }

        // Take snapshots from all initialized collections
        foreach ($this->_collections as $coll) {
            $coll->_takeSnapshot();
            $coll->_setHydrationFlag(false);
            $this->_uow->addManagedCollection($coll);
        }
        
        // Clean up
        $this->_collections = array();
        $this->_initializedRelations = array();
        $this->_metadataMap = array();

        $e = microtime(true);
        echo 'Hydration took: ' . ($e - $s) . PHP_EOL;

        return $result;
    }

    /**
     * Updates the result pointer for an Entity. The result pointers point to the
     * last seen instance of each Entity type. This is used for graph construction.
     *
     * @param array $resultPointers  The result pointers.
     * @param Collection $coll  The element.
     * @param boolean|integer $index  Index of the element in the collection.
     * @param string $dqlAlias
     * @param boolean $oneToOne  Whether it is a single-valued association or not.
     */
    private function updateResultPointer(&$coll, $index, $dqlAlias, $oneToOne)
    {
        if ($coll === null) {
            unset($this->_resultPointers[$dqlAlias]); // Ticket #1228
            return;
        }

        if ($index !== false) {
            $this->_resultPointers[$dqlAlias] = $coll[$index];
            return;
        }

        if ( ! is_object($coll)) {
            end($coll);
            $this->_resultPointers[$dqlAlias] =& $coll[key($coll)];
        } else if ($coll instanceof Doctrine_ORM_Collection) {
            if (count($coll) > 0) {
                $this->_resultPointers[$dqlAlias] = $coll->last();
            }
        } else {
            $this->_resultPointers[$dqlAlias] = $coll;
        }
    }

    private function getElementCollection($component)
    {
        $coll = new Doctrine_ORM_Collection($this->_em, $component);
        $this->_collections[] = $coll;
        return $coll;
    }

    private function initRelatedCollection($entity, $name)
    {
        $oid = spl_object_hash($entity);
        $classMetadata = $this->_metadataMap[$oid];
        if ( ! isset($this->_initializedRelations[$oid][$name])) {
            $relation = $classMetadata->getAssociationMapping($name);
            $relatedClass = $this->_em->getClassMetadata($relation->getTargetEntityName());
            $coll = $this->getElementCollection($relatedClass->getClassName());
            $coll->_setOwner($entity, $relation);
            $coll->_setHydrationFlag(true);
            $classMetadata->getReflectionProperty($name)->setValue($entity, $coll);
            $this->_initializedRelations[$oid][$name] = true;
            $this->_uow->setOriginalEntityProperty($oid, $name, $coll);
        }
    }

    private function isIndexKeyInUse($entity, $assocField, $indexField)
    {
        return $this->_metadataMap[spl_object_hash($entity)]->getReflectionProperty($assocField)
                ->getValue($entity)->containsKey($indexField);
    }

    private function getLastKey($coll)
    {
        // check needed because of mixed results.
        // is_object instead of is_array because is_array is slow on large arrays.
        if (is_object($coll)) {
            $coll->last();
            return $coll->key();
        } else {
            end($coll);
            return key($coll);
        }
    }

    private function getElement(array $data, $className)
    {
        $entity = $this->_em->getUnitOfWork()->createEntity($className, $data);
        $oid = spl_object_hash($entity);
        $this->_metadataMap[$oid] = $this->_em->getClassMetadata($className);
        return $entity;
    }

    /**
     * Adds an element to an indexed collection-valued property.
     *
     * @param <type> $entity1
     * @param <type> $property
     * @param <type> $entity2
     * @param <type> $indexField
     */
    private function addRelatedIndexedElement($entity1, $property, $entity2, $indexField)
    {
        $classMetadata1 = $this->_metadataMap[spl_object_hash($entity1)];
        $classMetadata2 = $this->_metadataMap[spl_object_hash($entity2)];
        $indexValue = $classMetadata2->getReflectionProperty($indexField)->getValue($entity2);
        $classMetadata1->getReflectionProperty($property)->getValue($entity1)->set($indexValue, $entity2);
    }

    /**
     * Adds an element to a collection-valued property.
     *
     * @param <type> $entity1
     * @param <type> $property
     * @param <type> $entity2
     */
    private function addRelatedElement($entity1, $property, $entity2)
    {
        $classMetadata1 = $this->_metadataMap[spl_object_hash($entity1)];
        $classMetadata1->getReflectionProperty($property)->getValue($entity1)->add($entity2);
    }

    private function isFieldSet($entity, $field)
    {
        return $this->_metadataMap[spl_object_hash($entity)]->getReflectionProperty($field)
                ->getValue($entity) !== null;
    }

    /**
     * Sets a related element.
     *
     * @param <type> $entity1
     * @param <type> $property
     * @param <type> $entity2
     */
    private function setRelatedElement($entity1, $property, $entity2)
    {
        $oid = spl_object_hash($entity1);
        $classMetadata1 = $this->_metadataMap[$oid];
        $classMetadata1->getReflectionProperty($property)->setValue($entity1, $entity2);
        $this->_uow->setOriginalEntityProperty($oid, $property, $entity2);
        $relation = $classMetadata1->getAssociationMapping($property);
        if ($relation->isOneToOne()) {
            $targetClass = $this->_em->getClassMetadata($relation->getTargetEntityName());
            if ($relation->isOwningSide()) {
                // If there is an inverse mapping on the target class its bidirectional
                if ($targetClass->hasInverseAssociationMapping($property)) {
                    $oid2 = spl_object_hash($entity2);
                    $sourceProp = $targetClass->getInverseAssociationMapping($fieldName)->getSourceFieldName();
                    $targetClass->getReflectionProperty($sourceProp)->setValue($entity2, $entity1);
                }
            } else {
                // for sure bidirectional, as there is no inverse side in unidirectional
                $mappedByProp = $relation->getMappedByFieldName();
                $targetClass->getReflectionProperty($mappedByProp)->setValue($entity2, $entity1);
            }
        }
    }

    /**
     * Hydrates a single row.
     *
     * @param <type> $data The row data.
     * @param <type> $cache The cache to use.
     * @param <type> $result The result to append to.
     * @override
     */
    protected function _hydrateRow(array &$data, array &$cache, &$result)
    {
        // 1) Initialize
        $id = $this->_idTemplate; // initialize the id-memory
        $nonemptyComponents = array();
        $rowData = parent::_gatherRowData($data, $cache, $id, $nonemptyComponents);
        $rootAlias = $this->_rootAlias;

        // 2) Hydrate the data of the root entity from the current row
        // Check for an existing element
        $index = false;
        if ($this->_isSimpleQuery || ! isset($this->_identifierMap[$rootAlias][$id[$rootAlias]])) {
            $element = $this->_uow->createEntity($this->_rootEntityName, $rowData[$rootAlias]);
            $oid = spl_object_hash($element);
            $this->_metadataMap[$oid] = $this->_em->getClassMetadata($this->_rootEntityName);
            if ($field = $this->_getCustomIndexField($rootAlias)) {
                if ($this->_parserResult->isMixedQuery()) {
                    $result[] = array(
                        $this->_metadataMap[$oid]->getReflectionProperty($field)
                                ->getValue($element) => $element
                    );
                    ++$this->_resultCounter;
                } else {
                    $result->set($element, $this->_metadataMap[$oid]
                            ->getReflectionProperty($field)
                            ->getValue($element));
                }
            } else {
                if ($this->_parserResult->isMixedQuery()) {
                    $result[] = array($element);
                    ++$this->_resultCounter;
                } else {
                    $result->add($element);
                }
            }
            $this->_identifierMap[$rootAlias][$id[$rootAlias]] = $this->getLastKey($result);
        } else {
            $index = $this->_identifierMap[$rootAlias][$id[$rootAlias]];
        }
        $this->updateResultPointer($result, $index, $rootAlias, false);
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
            $relationAlias = $map['relation']->getSourceFieldName();
            $path = $parent . '.' . $dqlAlias;

            // Get a reference to the right element in the result tree.
            // This element will get the associated element attached.
            if ($this->_parserResult->isMixedQuery() && $parent == $rootAlias) {
                $key = key(reset($this->_resultPointers));
                // TODO: Exception if $key === null ?
                $baseElement =& $this->_resultPointers[$parent][$key];
            } else if (isset($this->_resultPointers[$parent])) {
                $baseElement =& $this->_resultPointers[$parent];
            } else {
                unset($this->_resultPointers[$dqlAlias]); // Ticket #1228
                continue;
            }

            $oid = spl_object_hash($baseElement);

            // Check the type of the relation (many or single-valued)
            if ( ! $map['relation']->isOneToOne()) {
                $oneToOne = false;
                if (isset($nonemptyComponents[$dqlAlias])) {
                    $this->initRelatedCollection($baseElement, $relationAlias);
                    $indexExists = isset($this->_identifierMap[$path][$id[$parent]][$id[$dqlAlias]]);
                    $index = $indexExists ? $this->_identifierMap[$path][$id[$parent]][$id[$dqlAlias]] : false;
                    $indexIsValid = $index !== false ? $this->isIndexKeyInUse($baseElement, $relationAlias, $index) : false;
                    if ( ! $indexExists || ! $indexIsValid) {
                        $element = $this->getElement($data, $entityName);
                        if ($field = $this->_getCustomIndexField($dqlAlias)) {
                            $this->addRelatedIndexedElement($baseElement, $relationAlias, $element, $field);
                        } else {
                            $this->addRelatedElement($baseElement, $relationAlias, $element);
                        }
                        $this->_identifierMap[$path][$id[$parent]][$id[$dqlAlias]] = $this->getLastKey(
                            $this->_metadataMap[$oid]
                                    ->getReflectionProperty($relationAlias)
                                    ->getValue($baseElement));
                    }
                } else if ( ! $this->isFieldSet($baseElement, $relationAlias)) {
                    $coll = new Doctrine_ORM_Collection($this->_em, $entityName);
                    $this->_collections[] = $coll;
                    $this->setRelatedElement($baseElement, $relationAlias, $coll);
                }
            } else {
                $oneToOne = true;
                if ( ! isset($nonemptyComponents[$dqlAlias]) &&
                        ! $this->isFieldSet($baseElement, $relationAlias)) {
                    $this->setRelatedElement($baseElement, $relationAlias, null);
                } else if ( ! $this->isFieldSet($baseElement, $relationAlias)) {
                    $this->setRelatedElement($baseElement, $relationAlias,
                            $this->getElement($data, $entityName));
                }
            }

            $coll = $this->_metadataMap[$oid]
                    ->getReflectionProperty($relationAlias)
                    ->getValue($baseElement);

            if ($coll !== null) {
                $this->updateResultPointer($coll, $index, $dqlAlias, $oneToOne);
            }
        }

        // Append scalar values to mixed result sets
        if (isset($scalars)) {
            foreach ($scalars as $name => $value) {
                $result[$this->_resultCounter - 1][$name] = $value;
            }
        }
    }

    /** {@inheritdoc} */
    protected function _getRowContainer()
    {
        return new Doctrine_Common_Collections_Collection;
    }
}

