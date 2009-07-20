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

use Doctrine\DBAL\Connection;
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
    /* Class entries */
    private $_ce = array();
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
        
        foreach ($this->_rsm->aliasMap as $dqlAlias => $className) {
            $this->_identifierMap[$dqlAlias] = array();
            $this->_resultPointers[$dqlAlias] = array();
            $this->_idTemplate[$dqlAlias] = '';
            $class = $this->_em->getClassMetadata($className);

            if ( ! isset($this->_ce[$class->name])) {
                $this->_ce[$class->name] = $class;
                // Gather class descriptors and discriminator values of subclasses, if necessary
                if ($class->isInheritanceTypeSingleTable() || $class->isInheritanceTypeJoined()) {
                    $this->_discriminatorMap[$class->name][$class->discriminatorValue] = $class->name;
                    foreach (array_merge($class->parentClasses, $class->subClasses) as $className) {
                        $otherClass = $this->_em->getClassMetadata($className);
                        $value = $otherClass->discriminatorValue;
                        $this->_ce[$className] = $otherClass;
                        $this->_discriminatorMap[$class->name][$value] = $className;
                        $this->_discriminatorMap[$className][$value] = $className;
                    }
                }
            }
            
            // Remember which associations are "fetch joined"
            if (isset($this->_rsm->relationMap[$dqlAlias])) {
                $assoc = $this->_rsm->relationMap[$dqlAlias];
                $this->_fetchedAssociations[$assoc->sourceEntityName][$assoc->sourceFieldName] = true;
                if ($assoc->mappedByFieldName) {
                    $this->_fetchedAssociations[$assoc->targetEntityName][$assoc->mappedByFieldName] = true;
                } else {
                    $targetClass = $this->_em->getClassMetadata($assoc->targetEntityName);
                    if (isset($targetClass->inverseMappings[$assoc->sourceFieldName])) {
                        $inverseAssoc = $targetClass->inverseMappings[$assoc->sourceFieldName];
                        $this->_fetchedAssociations[$assoc->targetEntityName][$inverseAssoc->sourceFieldName] = true;
                    }
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
        $result = $this->_rsm->isMixed ? array() : new Collection;

        $cache = array();
        while ($data = $this->_stmt->fetch(Connection::FETCH_ASSOC)) {
            $this->_hydrateRow($data, $cache, $result);
        }

        // Take snapshots from all initialized collections
        foreach ($this->_collections as $coll) {
            $coll->takeSnapshot();
        }
        
        // Clean up
        $this->_collections = array();
        $this->_initializedRelations = array();

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
     * @todo May be worth to try to inline this method (through first reducing the
     *       calls of this method to 1).
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
     * Initializes a related collection.
     *
     * @param object $entity The entity to which the collection belongs.
     * @param string $name The name of the field on the entity that holds the collection.
     */
    private function initRelatedCollection($entity, $name)
    {
        $oid = spl_object_hash($entity);
        $classMetadata = $this->_ce[get_class($entity)];

        $relation = $classMetadata->associationMappings[$name];
        if ( ! isset($this->_ce[$relation->targetEntityName])) {
            $this->_ce[$relation->targetEntityName] = $this->_em->getClassMetadata($relation->targetEntityName);
        }
        $coll = new PersistentCollection($this->_em, $this->_ce[$relation->targetEntityName]);
        $this->_collections[] = $coll;
        $coll->setOwner($entity, $relation);

        $classMetadata->reflFields[$name]->setValue($entity, $coll);
        $this->_uow->setOriginalEntityProperty($oid, $name, $coll);
        $this->_initializedRelations[$oid][$name] = true;
    }

    /**
     *
     * @param <type> $entity
     * @param <type> $assocField
     * @param <type> $indexField
     * @return <type>
     * @todo Inline this method.
     */
    private function isIndexKeyInUse($entity, $assocField, $indexField)
    {
        return $this->_ce[get_class($entity)]
                ->reflFields[$assocField]
                ->getValue($entity)
                ->containsKey($indexField);
    }

    /**
     *
     * @param <type> $coll
     * @return <type>
     * @todo Consider inlining this method, introducing $coll->lastKey().
     */
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

    private function getEntity(array $data, $dqlAlias)
    {
    	$className = $this->_rsm->aliasMap[$dqlAlias];
        if (isset($this->_rsm->discriminatorColumns[$dqlAlias])) {
            $discrColumn = $this->_rsm->discriminatorColumns[$dqlAlias];
            $className = $this->_discriminatorMap[$className][$data[$discrColumn]];
            unset($data[$discrColumn]);
        }
        $entity = $this->_uow->createEntity($className, $data);

        $joinColumnsValues = array();
        foreach ($this->_ce[$className]->joinColumnNames as $name) {
            if (isset($data[$name])) {
                $joinColumnsValues[$name] = $data[$name];
            }
        }

        // Properly initialize any unfetched associations, if partial objects are not allowed.
        if ( ! $this->_allowPartialObjects) {
            foreach ($this->_ce[$className]->associationMappings as $field => $assoc) {
                if ( ! isset($this->_fetchedAssociations[$className][$field])) {
                    if ($assoc->isOneToOne()) {
                        if ($assoc->isLazilyFetched()) {
                            // Inject proxy
                            $proxy = $this->_em->getProxyFactory()->getAssociationProxy($entity, $assoc, $joinColumnsValues);
                            $this->_ce[$className]->reflFields[$field]->setValue($entity, $proxy);
                        } else {
                            //TODO: Schedule for eager fetching
                        }
                    } else {
                        // Inject collection
                        $this->_ce[$className]->reflFields[$field]
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
     * Sets a related element.
     *
     * @param object $entity1
     * @param string $property
     * @param object $entity2
     */
    private function setRelatedElement($entity1, $property, $entity2)
    {
        $class = $this->_ce[get_class($entity1)];
        $class->reflFields[$property]->setValue($entity1, $entity2);
        $this->_uow->setOriginalEntityProperty(spl_object_hash($entity1), $property, $entity2);
        $relation = $class->associationMappings[$property];
        if ($relation->isOneToOne()) {
            $targetClass = $this->_ce[$relation->targetEntityName];
            if ($relation->isOwningSide) {
                // If there is an inverse mapping on the target class its bidirectional
                if (isset($targetClass->inverseMappings[$property])) {
                    $sourceProp = $targetClass->inverseMappings[$property]->sourceFieldName;
                    $targetClass->reflFields[$sourceProp]->setValue($entity2, $entity1);
                } else if ($class === $targetClass && $relation->mappedByFieldName) {
                	// Special case: bi-directional self-referencing one-one on the same class
                	$targetClass->reflFields[$property]->setValue($entity2, $entity1);
                }
            } else {
                // For sure bidirectional, as there is no inverse side in unidirectional mappings
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
            $entityName = $this->_rsm->aliasMap[$dqlAlias];
            
            if (isset($this->_rsm->parentAliasMap[$dqlAlias])) {
                // It's a joined result
                
                $parent = $this->_rsm->parentAliasMap[$dqlAlias];
                $relation = $this->_rsm->relationMap[$dqlAlias];
                $relationAlias = $relation->sourceFieldName;

                // Get a reference to the right element in the result tree.
                // This element will get the associated element attached.
                if ($this->_rsm->isMixed && isset($this->_rootAliases[$parent])) {
                	$first = reset($this->_resultPointers);
                    // TODO: Exception if key($first) === null ?
                    $baseElement = $this->_resultPointers[$parent][key($first)];
                } else if (isset($this->_resultPointers[$parent])) {
                    $baseElement = $this->_resultPointers[$parent];
                } else {
                    unset($this->_resultPointers[$dqlAlias]); // Ticket #1228
                    continue;
                }

                $parentClass = get_class($baseElement);

                // Check the type of the relation (many or single-valued)
                if ( ! $relation->isOneToOne()) {
                    if (isset($nonemptyComponents[$dqlAlias])) {
                        if ( ! isset($this->_initializedRelations[spl_object_hash($baseElement)][$relationAlias])) {
                            $this->initRelatedCollection($baseElement, $relationAlias);
                        }
                        
                        $path = $parent . '.' . $dqlAlias;
                        $indexExists = isset($this->_identifierMap[$path][$id[$parent]][$id[$dqlAlias]]);
                        $index = $indexExists ? $this->_identifierMap[$path][$id[$parent]][$id[$dqlAlias]] : false;
                        $indexIsValid = $index !== false ? $this->isIndexKeyInUse($baseElement, $relationAlias, $index) : false;
                        
                        if ( ! $indexExists || ! $indexIsValid) {
                            $element = $this->getEntity($data, $dqlAlias);

                            // If it's a bi-directional many-to-many, also initialize the reverse collection.
                            if ($relation->isManyToMany()) {
                                if ($relation->isOwningSide && isset($this->_ce[$entityName]->inverseMappings[$relationAlias])) {
                                    $inverseFieldName = $this->_ce[$entityName]->inverseMappings[$relationAlias]->sourceFieldName;
                                    // Only initialize reverse collection if it is not yet initialized.
                                    if ( ! isset($this->_initializedRelations[spl_object_hash($element)][$inverseFieldName])) {
                                        $this->initRelatedCollection($element, $this->_ce[$entityName]
                                                ->inverseMappings[$relationAlias]->sourceFieldName);
                                    }
                                } else if ($relation->mappedByFieldName) {
                                    // Only initialize reverse collection if it is not yet initialized.
                                    if ( ! isset($this->_initializedRelations[spl_object_hash($element)][$relation->mappedByFieldName])) {
                                        $this->initRelatedCollection($element, $relation->mappedByFieldName);
                                    }
                                }
                            }

                            if (isset($this->_rsm->indexByMap[$dqlAlias])) {
                                $field = $this->_rsm->indexByMap[$dqlAlias];
                                $indexValue = $this->_ce[$entityName]
                                    ->reflFields[$field]
                                    ->getValue($element);
                                $this->_ce[$parentClass]
                                    ->reflFields[$relationAlias]
                                    ->getValue($baseElement)
                                    ->hydrateSet($indexValue, $element);
                            } else {
                                $this->_ce[$parentClass]
                                    ->reflFields[$relationAlias]
                                    ->getValue($baseElement)
                                    ->hydrateAdd($element);
                            }
                            
                            $this->_identifierMap[$path][$id[$parent]][$id[$dqlAlias]] = $this->getLastKey(
                                    $this->_ce[$parentClass]
                                        ->reflFields[$relationAlias]
                                        ->getValue($baseElement)
                                    );
                        }
                    } else if ( ! $this->_ce[$parentClass]->reflFields[$relationAlias]->getValue($baseElement)) {
                        $coll = new PersistentCollection($this->_em, $this->_ce[$entityName]);
                        $this->_collections[] = $coll;
                        $this->setRelatedElement($baseElement, $relationAlias, $coll);
                    }
                } else {
                    if ( ! $this->_ce[$parentClass]->reflFields[$relationAlias]->getValue($baseElement)) {
                        if (isset($nonemptyComponents[$dqlAlias])) {
                            $this->setRelatedElement($baseElement, $relationAlias, $this->getEntity($data, $dqlAlias));
                        }
                    }
                }

                $coll = $this->_ce[$parentClass]->reflFields[$relationAlias]->getValue($baseElement);

                if ($coll !== null) {
                    $this->updateResultPointer($coll, $index, $dqlAlias);
                }
            } else {
                // Its a root result element

                $this->_rootAliases[$dqlAlias] = true; // Mark as root alias

                if ($this->_isSimpleQuery || ! isset($this->_identifierMap[$dqlAlias][$id[$dqlAlias]])) {
                    $element = $this->getEntity($rowData[$dqlAlias], $dqlAlias);
                    if (isset($this->_rsm->indexByMap[$dqlAlias])) {
                        $field = $this->_rsm->indexByMap[$dqlAlias];
                        if ($this->_rsm->isMixed) {
                            $result[] = array(
                                $this->_ce[$entityName]
                                        ->reflFields[$field]
                                        ->getValue($element) => $element
                            );
                            ++$this->_resultCounter;
                        } else {
                            $result->set($element, $this->_ce[$entityName]
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
