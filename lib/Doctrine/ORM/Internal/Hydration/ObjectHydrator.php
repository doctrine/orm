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

use Doctrine\DBAL\Connection,
    Doctrine\ORM\PersistentCollection,
    Doctrine\ORM\Query,
    Doctrine\Common\Collections\ArrayCollection,
    Doctrine\Common\Collections\Collection;

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
    /* Local ClassMetadata cache to avoid going to the EntityManager all the time. */
    private $_ce = array();
    /*
     * The following parts are reinitialized on every hydration run.
     */
    private $_isSimpleQuery = false;
    private $_allowPartialObjects = false;
    private $_identifierMap = array();
    private $_resultPointers = array();
    private $_idTemplate = array();
    private $_resultCounter;
    private $_rootAliases = array();
    private $_fetchedAssociations;
    /** Memory for initialized collections. */
    private $_initializedCollections = array();

    /** @override */
    protected function _prepare()
    {
        $this->_isSimpleQuery = count($this->_rsm->aliasMap) <= 1;
        $this->_allowPartialObjects = $this->_em->getConfiguration()->getAllowPartialObjects()
                || isset($this->_hints[Query::HINT_FORCE_PARTIAL_LOAD]);
        
        $this->_identifierMap =
        $this->_resultPointers =
        $this->_idTemplate =
        $this->_fetchedAssociations = array();
        $this->_resultCounter = 0;
        
        foreach ($this->_rsm->aliasMap as $dqlAlias => $className) {
            $this->_identifierMap[$dqlAlias] = array();
            $this->_resultPointers[$dqlAlias] = array();
            $this->_idTemplate[$dqlAlias] = '';
            $class = $this->_em->getClassMetadata($className);

            if ( ! isset($this->_ce[$className])) {
                $this->_ce[$className] = $class;
            }
            
            // Remember which associations are "fetch joined"
            if (isset($this->_rsm->relationMap[$dqlAlias])) {
                $targetClassName = $this->_rsm->aliasMap[$this->_rsm->parentAliasMap[$dqlAlias]];
                $targetClass = $this->_em->getClassMetadata($targetClassName);
                $this->_ce[$targetClassName] = $targetClass;
                $assoc = $targetClass->associationMappings[$this->_rsm->relationMap[$dqlAlias]];
                $this->_fetchedAssociations[$assoc->sourceEntityName][$assoc->sourceFieldName] = true;
                if ($assoc->mappedByFieldName) {
                    $this->_fetchedAssociations[$assoc->targetEntityName][$assoc->mappedByFieldName] = true;
                } else {
                    if (isset($targetClass->inverseMappings[$className][$assoc->sourceFieldName])) {
                        $inverseAssoc = $targetClass->inverseMappings[$className][$assoc->sourceFieldName];
                        $this->_fetchedAssociations[$assoc->targetEntityName][$inverseAssoc->sourceFieldName] = true;
                    }
                }
            }
        }
    }
    
    protected function _cleanup()
    {
        parent::_cleanup();
        $this->_identifierMap =
        $this->_resultPointers = array();
    }

    /**
     * {@inheritdoc}
     *
     * @override
     */
    protected function _hydrateAll()
    {
        $result = array();
        $cache = array();
        while ($data = $this->_stmt->fetch(Connection::FETCH_ASSOC)) {
            $this->_hydrateRow($data, $cache, $result);
        }

        // Take snapshots from all initialized collections
        foreach ($this->_initializedCollections as $coll) {
            $coll->takeSnapshot();
        }
        $this->_initializedCollections = array();

        return $result;
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
        $class = $this->_ce[get_class($entity)];
        $relation = $class->associationMappings[$name];
        
        $pColl = new PersistentCollection($this->_em, $this->_ce[$relation->targetEntityName],
                $class->reflFields[$name]->getValue($entity) ?: new ArrayCollection);
        
        $pColl->setOwner($entity, $relation);
        $class->reflFields[$name]->setValue($entity, $pColl);
        $this->_uow->setOriginalEntityProperty($oid, $name, $pColl);
        $this->_initializedCollections[$oid . $name] = $pColl;
        
        return $pColl;
    }
    
    /**
     * Gets an entity instance.
     * 
     * @param $data The instance data.
     * @param $dqlAlias The DQL alias of the entity's class.
     * @return object The entity.
     */
    private function getEntity(array $data, $dqlAlias)
    {
    	$className = $this->_rsm->aliasMap[$dqlAlias];
        if (isset($this->_rsm->discriminatorColumns[$dqlAlias])) {
            $discrColumn = $this->_rsm->metaMappings[$this->_rsm->discriminatorColumns[$dqlAlias]];
            $className = $this->_ce[$className]->discriminatorMap[$data[$discrColumn]];
            unset($data[$discrColumn]);
        }
        
        $entity = $this->_uow->createEntity($className, $data, $this->_hints);

        // Properly initialize any unfetched associations, if partial objects are not allowed.
        if ( ! $this->_allowPartialObjects) {
            foreach ($this->_getClassMetadata($className)->associationMappings as $field => $assoc) {
                // Check if the association is not among the fetch-joined associatons already.
                if ( ! isset($this->_fetchedAssociations[$className][$field])) {
                    if ($assoc->isOneToOne()) {
                        $joinColumns = array();
                        foreach ($assoc->targetToSourceKeyColumns as $srcColumn) {
                            $joinColumns[$srcColumn] = $data[$assoc->joinColumnFieldNames[$srcColumn]];
                        }
                        if ($assoc->isLazilyFetched()) {
                            // Inject proxy
                            $this->_ce[$className]->reflFields[$field]->setValue($entity,
                                    $this->_em->getProxyFactory()->getAssociationProxy($entity, $assoc, $joinColumns)
                                    );
                        } else {
                            // Eager load
                            //TODO: Allow more efficient and configurable batching of these loads
                            $assoc->load($entity, new $assoc->targetEntityName, $this->_em, $joinColumns);
                        }
                    } else {
                        // Inject collection
                        $reflField = $this->_ce[$className]->reflFields[$field];
                        $pColl = new PersistentCollection($this->_em,
                                $this->_getClassMetadata($assoc->targetEntityName),
                                $reflField->getValue($entity) ?: new ArrayCollection
                                );
                        $pColl->setOwner($entity, $assoc);
                        $reflField->setValue($entity, $pColl);
                        if ($assoc->isLazilyFetched()) {
                            $pColl->setInitialized(false);
                        } else {
                            //TODO: Allow more efficient and configurable batching of these loads
                            $assoc->load($entity, $pColl, $this->_em);
                        }
                    }
                }
            }
        }

        return $entity;
    }
    
    /**
     * Gets a ClassMetadata instance from the local cache.
     * If the instance is not yet in the local cache, it is loaded into the
     * local cache.
     * 
     * @param string $className The name of the class.
     * @return ClassMetadata
     */
    private function _getClassMetadata($className)
    {
        if ( ! isset($this->_ce[$className])) {
            $this->_ce[$className] = $this->_em->getClassMetadata($className);
        }
        return $this->_ce[$className];
    }

    /**
     * {@inheritdoc}
     * 
     * @override
     */
    protected function _hydrateRow(array &$data, array &$cache, array &$result)
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
                $oid = spl_object_hash($baseElement);
                $relationField = $this->_rsm->relationMap[$dqlAlias];
                $relation = $this->_ce[$parentClass]->associationMappings[$relationField];
                $reflField = $this->_ce[$parentClass]->reflFields[$relationField];
                $reflFieldValue = $reflField->getValue($baseElement);
                
                // Check the type of the relation (many or single-valued)
                if ( ! $relation->isOneToOne()) {
                    // Collection-valued association
                    if (isset($nonemptyComponents[$dqlAlias])) {
                        if ( ! isset($this->_initializedCollections[$oid . $relationField])) {
                            $reflFieldValue = $this->initRelatedCollection($baseElement, $relationField);
                        }
                        
                        $path = $parent . '.' . $dqlAlias;
                        $indexExists = isset($this->_identifierMap[$path][$id[$parent]][$id[$dqlAlias]]);
                        $index = $indexExists ? $this->_identifierMap[$path][$id[$parent]][$id[$dqlAlias]] : false;
                        $indexIsValid = $index !== false ? $reflFieldValue->containsKey($index) : false;
                        
                        if ( ! $indexExists || ! $indexIsValid) {
                            $element = $this->getEntity($data, $dqlAlias);

                            // If it's a bi-directional many-to-many, also initialize the reverse collection.
                            if ($relation->isManyToMany()) {
                                if ($relation->isOwningSide && isset($this->_ce[$entityName]->inverseMappings[$relation->sourceEntityName][$relationField])) {
                                    $inverseFieldName = $this->_ce[$entityName]->inverseMappings[$relation->sourceEntityName][$relationField]->sourceFieldName;
                                    // Only initialize reverse collection if it is not yet initialized.
                                    if ( ! isset($this->_initializedCollections[spl_object_hash($element) . $inverseFieldName])) {
                                        $this->initRelatedCollection($element, $inverseFieldName);
                                    }
                                } else if ($relation->mappedByFieldName) {
                                    // Only initialize reverse collection if it is not yet initialized.
                                    if ( ! isset($this->_initializedCollections[spl_object_hash($element) . $relation->mappedByFieldName])) {
                                        $this->initRelatedCollection($element, $relation->mappedByFieldName);
                                    }
                                }
                            }

                            if (isset($this->_rsm->indexByMap[$dqlAlias])) {
                                $field = $this->_rsm->indexByMap[$dqlAlias];
                                $indexValue = $this->_ce[$entityName]
                                    ->reflFields[$field]
                                    ->getValue($element);
                                $reflFieldValue->hydrateSet($indexValue, $element);
                            } else {
                                $reflFieldValue->hydrateAdd($element);
                            }
                            
                            $reflFieldValue->last();
                            $this->_identifierMap[$path][$id[$parent]][$id[$dqlAlias]] = $reflFieldValue->key();
                        }
                    } else if ( ! $reflFieldValue) {
                        $coll = new PersistentCollection($this->_em, $this->_ce[$entityName], new ArrayCollection);
                        $reflField->setValue($baseElement, $coll);
                        $reflFieldValue = $coll;
                        $this->_uow->setOriginalEntityProperty($oid, $relationField, $coll);
                    }
                    
                    // Update result pointer
                    $this->_resultPointers[$dqlAlias] = $index === false ?
                            $reflFieldValue->last() : $reflFieldValue[$index];
                } else {
                    // Single-valued association
                    $reflFieldValue = $reflField->getValue($baseElement);
                    if ( ! $reflFieldValue) {
                        if (isset($nonemptyComponents[$dqlAlias])) {
                            $element = $this->getEntity($data, $dqlAlias);
                            $reflField->setValue($baseElement, $element);
                            $this->_uow->setOriginalEntityProperty($oid, $relationField, $element);
                            $targetClass = $this->_ce[$relation->targetEntityName];
                            if ($relation->isOwningSide) {
                                // If there is an inverse mapping on the target class its bidirectional
                                if (isset($targetClass->inverseMappings[$relation->sourceEntityName][$relationField])) {
                                    $sourceProp = $targetClass->inverseMappings[$relation->sourceEntityName][$relationField]->sourceFieldName;
                                    $targetClass->reflFields[$sourceProp]->setValue($element, $base);
                                } else if ($this->_ce[$parentClass] === $targetClass && $relation->mappedByFieldName) {
                                    // Special case: bi-directional self-referencing one-one on the same class
                                    $targetClass->reflFields[$relationField]->setValue($element, $baseElement);
                                }
                            } else {
                                // For sure bidirectional, as there is no inverse side in unidirectional mappings
                                $targetClass->reflFields[$relation->mappedByFieldName]->setValue($element, $baseElement);
                            }
                        }
                    }
                    
                    // Update result pointer
                    if ($reflFieldValue !== null) {
                        $this->_resultPointers[$dqlAlias] = $reflFieldValue;
                    }
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
                            $result[$this->_ce[$entityName]
                                    ->reflFields[$field]
                                    ->getValue($element)] = $element;
                        }
                    } else {
                        if ($this->_rsm->isMixed) {
                            $result[] = array($element);
                            ++$this->_resultCounter;
                        } else {
                            $result[] = $element;
                        }
                    }
                    
                    $last = end($result);
                    $this->_identifierMap[$dqlAlias][$id[$dqlAlias]] = key($result);
                    
                    // Update result pointer
                    $this->_resultPointers[$dqlAlias] = $last;
                } else {
                    // Update result pointer
                    $index = $this->_identifierMap[$dqlAlias][$id[$dqlAlias]];
                    $this->_resultPointers[$dqlAlias] = $result[$index];
                }
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
        return array();
    }
}
