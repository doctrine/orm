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
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Internal\Hydration;

use \PDO;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Common\Collections\Collection;

/**
 * The ObjectHydrator constructs an object graph out of an SQL result set.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
class ObjectHydrator extends AbstractHydrator
{
    /*
     * These two properties maintain their values between hydration runs.
     */
    private $_classMetadatas = array();
    private $_discriminatorMap = array();

    /*
     * The following parts are reinitialized on every hydration run.
     */
    private $_isSimpleQuery = false;
    private $_allowPartialObjects = false;
    private $_identifierMap = array();
    private $_resultPointers = array();
    private $_idTemplate = array();
    private $_resultCounter = 0;
    private $_rootAliases = array();
    private $_fetchedAssociations = array();
    /* TODO: Consider unifying _collections and _initializedRelations */
    /** Collections initialized by the hydrator */
    private $_collections = array();
    /** Memory for initialized relations */
    private $_initializedRelations = array();

    /** @override */
    protected function _prepare()
    {
        $this->_isSimpleQuery = count($this->_rsm->aliasMap) <= 1;
        $this->_allowPartialObjects = $this->_em->getConfiguration()->getAllowPartialObjects();
        $this->_identifierMap = array();
        $this->_resultPointers = array();
        $this->_idTemplate = array();
        $this->_resultCounter = 0;
        $this->_fetchedAssociations = array();
        
        foreach ($this->_rsm->aliasMap as $dqlAlias => $class) {
            $this->_identifierMap[$dqlAlias] = array();
            $this->_resultPointers[$dqlAlias] = array();
            $this->_idTemplate[$dqlAlias] = '';

            if ( ! isset($this->_classMetadatas[$class->name])) {
                $this->_classMetadatas[$class->name] = $class;
                // Gather class descriptors and discriminator values of subclasses, if necessary
                if ($class->isInheritanceTypeSingleTable() || $class->isInheritanceTypeJoined()) {
                    $this->_discriminatorMap[$class->name][$class->discriminatorValue] = $class->name;
                    foreach (array_merge($class->parentClasses, $class->subClasses) as $className) {
                        $otherClass = $this->_em->getClassMetadata($className);
                        $value = $otherClass->discriminatorValue;
                        $this->_classMetadatas[$className] = $otherClass;
                        $this->_discriminatorMap[$class->name][$value] = $className;
                    }
                }
            }
            
            // Remember which classes are "fetch joined"
            if (isset($this->_rsm->relationMap[$dqlAlias])) {
                $assoc = $this->_rsm->relationMap[$dqlAlias];
                $this->_fetchedAssociations[$assoc->getSourceEntityName()][$assoc->sourceFieldName] = true;
                if ($mappedByField = $assoc->mappedByFieldName) {
                    $this->_fetchedAssociations[$assoc->targetEntityName][$mappedByField] = true;
                } else if ($inverseAssoc = $this->_em->getClassMetadata($assoc->targetEntityName)
                        ->inverseMappings[$assoc->sourceFieldName]) {
                    $this->_fetchedAssociations[$assoc->targetEntityName][
                        $inverseAssoc->sourceFieldName
                    ] = true;
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @override
     */
    protected function _hydrateAll()
    {
        $s = microtime(true);
        
        if ($this->_rsm->isMixed) {
            $result = array();
        } else {
            $result = new Collection;
        }

        $cache = array();
        while ($data = $this->_stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->_hydrateRow($data, $cache, $result);
        }

        // Take snapshots from all initialized collections
        foreach ($this->_collections as $coll) {
            $coll->takeSnapshot();
            $coll->setHydrationFlag(false);
        }
        
        // Clean up
        $this->_collections = array();
        $this->_initializedRelations = array();
        $this->_classMetadatas = array();

        $e = microtime(true);

        echo 'Hydration took: ' . ($e - $s) . PHP_EOL;

        return $result;
    }

    /**
     * Updates the result pointer for an entity. The result pointers point to the
     * last seen instance of each entity type. This is used for graph construction.
     *
     * @param array $resultPointers  The result pointers.
     * @param Collection $coll  The element.
     * @param boolean|integer $index  Index of the element in the collection.
     * @param string $dqlAlias
     */
    private function updateResultPointer(&$coll, $index, $dqlAlias)
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
        } else if ($coll instanceof Collection) {
            if (count($coll) > 0) {
                $this->_resultPointers[$dqlAlias] = $coll->last();
            }
        } else {
            $this->_resultPointers[$dqlAlias] = $coll;
        }
    }

    /**
     *
     * @param string $component
     * @return PersistentCollection
     * @todo Consider inlining this method.
     */
    private function getCollection($component)
    {
        $coll = new PersistentCollection($this->_em, $component);
        $this->_collections[] = $coll;
        return $coll;
    }

    /**
     *
     * @param object $entity
     * @param string $name
     * @todo Consider inlining this method.
     */
    private function initRelatedCollection($entity, $name)
    {
        $oid = spl_object_hash($entity);
        $classMetadata = $this->_classMetadatas[get_class($entity)];

        $relation = $classMetadata->getAssociationMapping($name);
        $relatedClass = $this->_em->getClassMetadata($relation->targetEntityName);
        $coll = $this->getCollection($relatedClass);
        $coll->setOwner($entity, $relation);

        $classMetadata->reflFields[$name]->setValue($entity, $coll);
        $this->_uow->setOriginalEntityProperty($oid, $name, $coll);
        $this->_initializedRelations[$oid][$name] = true;

        return $coll;
    }

    /**
     *
     * @param <type> $entity
     * @param <type> $assocField
     * @param <type> $indexField
     * @return <type>
     * @todo Consider inlining this method.
     */
    private function isIndexKeyInUse($entity, $assocField, $indexField)
    {
        return $this->_classMetadatas[get_class($entity)]
                ->reflFields[$assocField]
                ->getValue($entity)
                ->containsKey($indexField);
    }

    private function getLastKey($coll)
    {
        // Check needed because of mixed results.
        // is_object instead of is_array because is_array is slow on large arrays.
        if (is_object($coll)) {
            $coll->last();
            return $coll->key();
        } else {
            end($coll);
            return key($coll);
        }
    }

    private function getEntity(array $data, $className)
    {
        if (isset($this->_rsm->discriminatorColumns[$className])) {
            $discrColumn = $this->_rsm->discriminatorColumns[$className];
            $className = $this->_discriminatorMap[$className][$data[$discrColumn]];
            unset($data[$discrColumn]);
        }
        $entity = $this->_uow->createEntity($className, $data);

        // Properly initialize any unfetched associations, if partial objects are not allowed.
        if ( ! $this->_allowPartialObjects) {
            foreach ($this->_classMetadatas[$className]->associationMappings as $field => $assoc) {
                if ( ! isset($this->_fetchedAssociations[$className][$field])) {
                    if ($assoc->isOneToOne()) {
                        if ($assoc->isLazilyFetched()) {
                            // Inject proxy
                            $proxy = $this->_em->getProxyGenerator()->getAssociationProxy($entity, $assoc);
                            $this->_classMetadatas[$className]->reflFields[$field]->setValue($entity, $proxy);
                        } else {
                            //TODO: Schedule for eager fetching?
                        }
                    } else {
                        // Inject collection
                        $this->_classMetadatas[$className]->reflFields[$field]
                            ->setValue($entity, new PersistentCollection(
                                $this->_em,
                                $this->_em->getClassMetadata($assoc->targetEntityName)
                            ));
                    }
                }
            }
        }

        return $entity;
    }

    /**
     * Checks whether a field on an entity has a non-null value.
     *
     * @param object $entity
     * @param string $field
     * @return boolean
     */
    private function isFieldSet($entity, $field)
    {
        return $this->_classMetadatas[get_class($entity)]
                ->reflFields[$field]
                ->getValue($entity) !== null;
    }

    /**
     * Sets a related element.
     *
     * @param object $entity1
     * @param string $property
     * @param object $entity2
     */
    private function setRelatedElement($entity1, $property, $entity2)
    {
        $classMetadata1 = $this->_classMetadatas[get_class($entity1)];
        $classMetadata1->reflFields[$property]->setValue($entity1, $entity2);
        $this->_uow->setOriginalEntityProperty(spl_object_hash($entity1), $property, $entity2);
        $relation = $classMetadata1->getAssociationMapping($property);
        if ($relation->isOneToOne()) {
            $targetClass = $this->_classMetadatas[$relation->targetEntityName];
            if ($relation->isOwningSide()) {
                // If there is an inverse mapping on the target class its bidirectional
                if (isset($targetClass->inverseMappings[$property])) {
                    $sourceProp = $targetClass->inverseMappings[$fieldName]->sourceFieldName;
                    $targetClass->reflFields[$sourceProp]->setValue($entity2, $entity1);
                }
            } else {
                // For sure bidirectional, as there is no inverse side in unidirectional
                $targetClass->reflFields[$relation->mappedByFieldName]->setValue($entity2, $entity1);
            }
        }
    }

    /**
     * {@inheritdoc}
     * 
     * @override
     */
    protected function _hydrateRow(array &$data, array &$cache, &$result)
    {
        // 1) Initialize
        $id = $this->_idTemplate; // initialize the id-memory
        $nonemptyComponents = array();
        $rowData = $this->_gatherRowData($data, $cache, $id, $nonemptyComponents);

        // Extract scalar values. They're appended at the end.
        if (isset($rowData['scalars'])) {
            $scalars = $rowData['scalars'];
            unset($rowData['scalars']);
        }

        // Hydrate the entity data found in the current row.
        foreach ($rowData as $dqlAlias => $data) {
            $index = false;
            $entityName = $this->_rsm->aliasMap[$dqlAlias]->name;
            
            if (isset($this->_rsm->parentAliasMap[$dqlAlias])) {
                // It's a joined result
                
                $parent = $this->_rsm->parentAliasMap[$dqlAlias];
                $relation = $this->_rsm->relationMap[$dqlAlias];
                $relationAlias = $relation->sourceFieldName;

                // Get a reference to the right element in the result tree.
                // This element will get the associated element attached.
                if ($this->_rsm->isMixed && isset($this->_rootAliases[$parent])) {
                    $key = key(reset($this->_resultPointers));
                    // TODO: Exception if $key === null ?
                    $baseElement =& $this->_resultPointers[$parent][$key];
                } else if (isset($this->_resultPointers[$parent])) {
                    $baseElement =& $this->_resultPointers[$parent];
                } else {
                    unset($this->_resultPointers[$dqlAlias]); // Ticket #1228
                    continue;
                }

                $parentClass = get_class($baseElement);

                // Check the type of the relation (many or single-valued)
                if ( ! $relation->isOneToOne()) {
                    if (isset($nonemptyComponents[$dqlAlias])) {
                        if ( ! isset($this->_initializedRelations[spl_object_hash($baseElement)][$relationAlias])) {
                            $this->initRelatedCollection($baseElement, $relationAlias)->setHydrationFlag(true);
                        }
                        
                        $path = $parent . '.' . $dqlAlias;
                        $indexExists = isset($this->_identifierMap[$path][$id[$parent]][$id[$dqlAlias]]);
                        $index = $indexExists ? $this->_identifierMap[$path][$id[$parent]][$id[$dqlAlias]] : false;
                        $indexIsValid = $index !== false ? $this->isIndexKeyInUse($baseElement, $relationAlias, $index) : false;
                        if ( ! $indexExists || ! $indexIsValid) {
                            $element = $this->getEntity($data, $entityName);

                            // If it's a bi-directional many-to-many, also initialize the reverse collection.
                            if ($relation->isManyToMany()) {
                                if ($relation->isOwningSide()) {
                                    $reverseAssoc = $this->_classMetadatas[$entityName]->inverseMappings[$relationAlias];
                                    if ($reverseAssoc) {
                                        $this->initRelatedCollection($element, $reverseAssoc->sourceFieldName);
                                    }
                                } else if ($mappedByField = $relation->mappedByFieldName) {
                                    $this->initRelatedCollection($element, $mappedByField);
                                }
                            }

                            if ($field = $this->_getCustomIndexField($dqlAlias)) {
                                $indexValue = $this->_classMetadatas[$entityName]
                                    ->reflFields[$field]
                                    ->getValue($element);
                                $this->_classMetadatas[$parentClass]
                                    ->reflFields[$relationAlias]
                                    ->getValue($baseElement)
                                    ->set($indexValue, $element);
                            } else {
                                $this->_classMetadatas[$parentClass]
                                    ->reflFields[$relationAlias]
                                    ->getValue($baseElement)
                                    ->add($element);
                            }
                            $this->_identifierMap[$path][$id[$parent]][$id[$dqlAlias]] = $this->getLastKey(
                                    $this->_classMetadatas[$parentClass]
                                        ->reflFields[$relationAlias]
                                        ->getValue($baseElement)
                                    );
                        }
                    } else if ( ! $this->isFieldSet($baseElement, $relationAlias)) {
                        $coll = new PersistentCollection($this->_em, $this->_classMetadatas[$entityName]);
                        $this->_collections[] = $coll;
                        $this->setRelatedElement($baseElement, $relationAlias, $coll);
                    }
                } else {
                    if ( ! isset($nonemptyComponents[$dqlAlias]) && ! $this->isFieldSet($baseElement, $relationAlias)) {
                        $this->setRelatedElement($baseElement, $relationAlias, null);
                    } else if ( ! $this->isFieldSet($baseElement, $relationAlias)) {
                        $this->setRelatedElement($baseElement, $relationAlias, $this->getEntity($data, $entityName));
                    }
                }

                $coll = $this->_classMetadatas[$parentClass]
                        ->reflFields[$relationAlias]
                        ->getValue($baseElement);

                if ($coll !== null) {
                    $this->updateResultPointer($coll, $index, $dqlAlias);
                }
            } else {
                // Its a root result element

                $this->_rootAliases[$dqlAlias] = true; // Mark as root alias

                if ($this->_isSimpleQuery || ! isset($this->_identifierMap[$dqlAlias][$id[$dqlAlias]])) {
                    $element = $this->getEntity($rowData[$dqlAlias], $entityName);
                    if ($field = $this->_getCustomIndexField($dqlAlias)) {
                        if ($this->_rsm->isMixed) {
                            $result[] = array(
                                $this->_classMetadatas[$entityName]
                                        ->reflFields[$field]
                                        ->getValue($element) => $element
                            );
                            ++$this->_resultCounter;
                        } else {
                            $result->set($element, $this->_classMetadatas[$entityName]
                                    ->reflFields[$field]
                                    ->getValue($element));
                        }
                    } else {
                        if ($this->_rsm->isMixed) {
                            $result[] = array($element);
                            ++$this->_resultCounter;
                        } else {
                            $result->add($element);
                        }
                    }
                    $this->_identifierMap[$dqlAlias][$id[$dqlAlias]] = $this->getLastKey($result);
                } else {
                    $index = $this->_identifierMap[$dqlAlias][$id[$dqlAlias]];
                }
                $this->updateResultPointer($result, $index, $dqlAlias);
                //unset($rowData[$dqlAlias]);
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
        return new \Doctrine\Common\Collections\Collection;
    }
}