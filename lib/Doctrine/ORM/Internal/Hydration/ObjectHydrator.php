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

use Doctrine\ORM\PersistentCollection,
    Doctrine\ORM\Query,
    Doctrine\Common\Collections\ArrayCollection,
    Doctrine\Common\Collections\Collection;

/**
 * The ObjectHydrator constructs an object graph out of an SQL result set.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 * @internal Highly performance-sensitive code.
 */
class ObjectHydrator extends AbstractHydrator
{
    /* Local ClassMetadata cache to avoid going to the EntityManager all the time.
     * This local cache is maintained between hydration runs and not cleared.
     */
    private $_ce = array();
    
    /* The following parts are reinitialized on every hydration run. */
    
    private $_allowPartialObjects = false;
    private $_identifierMap;
    private $_resultPointers;
    private $_idTemplate;
    private $_resultCounter;
    private $_fetchedAssociations;
    private $_rootAliases = array();
    private $_initializedCollections = array();
    private $_existingCollections = array();
    private $_proxyFactory;
    //private $_createdEntities;
    

    /** @override */
    protected function _prepare()
    {
        $this->_allowPartialObjects = isset($this->_hints[Query::HINT_FORCE_PARTIAL_LOAD]);
        
        $this->_proxyFactory = $this->_em->getProxyFactory();
        
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
            
            // Remember which associations are "fetch joined", so that we know where to inject
            // collection stubs or proxies and where not.
            if (isset($this->_rsm->relationMap[$dqlAlias])) {
                $targetClassName = $this->_rsm->aliasMap[$this->_rsm->parentAliasMap[$dqlAlias]];
                $targetClass = $this->_getClassMetadata($targetClassName);
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
    
    /**
     * {@inheritdoc}
     */
    protected function _cleanup()
    {
        parent::_cleanup();
        $this->_identifierMap =
        $this->_initializedCollections =
        $this->_existingCollections =
        $this->_resultPointers = array();
    }

    /**
     * {@inheritdoc}
     */
    protected function _hydrateAll()
    {
        $result = array();
        $cache = array();
        while ($data = $this->_stmt->fetch(\Doctrine\DBAL\Connection::FETCH_ASSOC)) {
            $this->_hydrateRow($data, $cache, $result);
        }

        // Take snapshots from all newly initialized collections
        foreach ($this->_initializedCollections as $coll) {
            $coll->takeSnapshot();
        }

        return $result;
    }

    /**
     * Initializes a related collection.
     *
     * @param object $entity The entity to which the collection belongs.
     * @param string $name The name of the field on the entity that holds the collection.
     */
    private function _initRelatedCollection($entity, $name)
    {
        $oid = spl_object_hash($entity);
        $class = $this->_ce[get_class($entity)];
        $relation = $class->associationMappings[$name];
        
        $value = $class->reflFields[$name]->getValue($entity);
        if ($value === null) {
            $value = new ArrayCollection;
        }
        
        if ($value instanceof ArrayCollection) {
            $value = new PersistentCollection(
                $this->_em,
                $this->_ce[$relation->targetEntityName],
                $value
            );
            $value->setOwner($entity, $relation);
            $class->reflFields[$name]->setValue($entity, $value);
            $this->_uow->setOriginalEntityProperty($oid, $name, $value);
            $this->_initializedCollections[$oid . $name] = $value;
        } else if (isset($this->_hints[Query::HINT_REFRESH])) {
            // Is already PersistentCollection, but REFRESH
            $value->clear();
            $value->setDirty(false);
            $value->setInitialized(true);
            $this->_initializedCollections[$oid . $name] = $value;
        } else {
            // Is already PersistentCollection, and DONT REFRESH
            $this->_existingCollections[$oid . $name] = $value;
        }
        
        return $value;
    }
    
    /**
     * Gets an entity instance.
     * 
     * @param $data The instance data.
     * @param $dqlAlias The DQL alias of the entity's class.
     * @return object The entity.
     */
    private function _getEntity(array $data, $dqlAlias)
    {
    	$className = $this->_rsm->aliasMap[$dqlAlias];
        if (isset($this->_rsm->discriminatorColumns[$dqlAlias])) {
            $discrColumn = $this->_rsm->metaMappings[$this->_rsm->discriminatorColumns[$dqlAlias]];
            $className = $this->_ce[$className]->discriminatorMap[$data[$discrColumn]];
            unset($data[$discrColumn]);
        }
        
        $entity = $this->_uow->createEntity($className, $data, $this->_hints);

        //FIXME: If $entity comes from the identity map there is no need to do this!
        // Properly initialize any unfetched associations, if partial objects are not allowed.
        if ( ! $this->_allowPartialObjects) {
            $oid = spl_object_hash($entity);
            foreach ($this->_getClassMetadata($className)->associationMappings as $field => $assoc) {
                // Check if the association is not among the fetch-joined associatons already.
                if ( ! isset($this->_fetchedAssociations[$className][$field])) {
                    if ($assoc->isOneToOne()) {
                        $joinColumns = array();
                        foreach ($assoc->targetToSourceKeyColumns as $srcColumn) {
                            $joinColumns[$srcColumn] = $data[$assoc->joinColumnFieldNames[$srcColumn]];
                        }
                        if ($assoc->isLazilyFetched() /*&& ! $assoc->isOptional*/) {
                            // Inject proxy
                            $proxy = $this->_proxyFactory->getAssociationProxy($entity, $assoc, $joinColumns);
                            $this->_uow->setOriginalEntityProperty($oid, $field, $proxy);
                            $this->_ce[$className]->reflFields[$field]->setValue($entity, $proxy);
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
                        $this->_uow->setOriginalEntityProperty($oid, $field, $pColl);
                    }
                }
            }
        }

        return $entity;
    }
    
    private function _getEntityFromIdentityMap($className, array $data)
    {
        $class = $this->_ce[$className];
        if ($class->isIdentifierComposite) {
            $idHash = '';
            foreach ($class->identifier as $fieldName) {
                $idHash .= $data[$fieldName] . ' ';
            }
            return $this->_uow->tryGetByIdHash(rtrim($idHash), $class->rootEntityName);
        } else {
            return $this->_uow->tryGetByIdHash($data[$class->identifier[0]], $class->rootEntityName);
        }
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
     */
    protected function _hydrateRow(array &$data, array &$cache, array &$result)
    {
        // Initialize
        $id = $this->_idTemplate; // initialize the id-memory
        $nonemptyComponents = array();
        $rowData = $this->_gatherRowData($data, $cache, $id, $nonemptyComponents);

        // Extract scalar values. They're appended at the end.
        if (isset($rowData['scalars'])) {
            $scalars = $rowData['scalars'];
            unset($rowData['scalars']);
        }

        // Hydrate the data found in the current row.
        foreach ($rowData as $dqlAlias => $data) {
            $index = false;
            $entityName = $this->_rsm->aliasMap[$dqlAlias];
            
            if (isset($this->_rsm->parentAliasMap[$dqlAlias])) {
                // It's a joined result
                
                $parentAlias = $this->_rsm->parentAliasMap[$dqlAlias];

                // Get a reference to the parent object to which the joined element belongs.
                if ($this->_rsm->isMixed && isset($this->_rootAliases[$parentAlias])) {
                	$first = reset($this->_resultPointers);
                    $parentObject = $this->_resultPointers[$parentAlias][key($first)];
                } else if (isset($this->_resultPointers[$parentAlias])) {
                    $parentObject = $this->_resultPointers[$parentAlias];
                } else {
                    // Parent object of relation not found, so skip it.
                    continue;
                }

                $parentClass = get_class($parentObject);
                $oid = spl_object_hash($parentObject);
                $relationField = $this->_rsm->relationMap[$dqlAlias];
                $relation = $this->_ce[$parentClass]->associationMappings[$relationField];
                $reflField = $this->_ce[$parentClass]->reflFields[$relationField];
                
                // Check the type of the relation (many or single-valued)
                if ( ! $relation->isOneToOne()) {
                    // Collection-valued association
                    if (isset($nonemptyComponents[$dqlAlias])) {
                        $collKey = $oid . $relationField;
                        if (isset($this->_initializedCollections[$collKey])) {
                            $reflFieldValue = $this->_initializedCollections[$collKey];
                        } else if ( ! isset($this->_existingCollections[$collKey])) {
                            $reflFieldValue = $this->_initRelatedCollection($parentObject, $relationField);
                        }
                        
                        $indexExists = isset($this->_identifierMap[$dqlAlias][$id[$dqlAlias]]);
                        $index = $indexExists ? $this->_identifierMap[$dqlAlias][$id[$dqlAlias]] : false;
                        $indexIsValid = $index !== false ? isset($reflFieldValue[$index]) : false;
                        
                        if ( ! $indexExists || ! $indexIsValid) {
                            if (isset($this->_existingCollections[$collKey])) {
                                // Collection exists, only look for $element in identity map.
                                if ($element = $this->_getEntityFromIdentityMap($entityName, $data)) {
                                    $this->_resultPointers[$dqlAlias] = $element;
                                } else {
                                    unset($this->_resultPointers[$dqlAlias]);
                                }
                            } else {
                                $element = $this->_getEntity($data, $dqlAlias);
                                
                                // If it's a bi-directional many-to-many, also initialize the reverse collection.
                                if ($relation->isManyToMany()) {
                                    if ($relation->isOwningSide && isset($this->_ce[$entityName]->inverseMappings[$relation->sourceEntityName][$relationField])) {
                                        $inverseFieldName = $this->_ce[$entityName]->inverseMappings[$relation->sourceEntityName][$relationField]->sourceFieldName;
                                        // Only initialize reverse collection if it is not yet initialized.
                                        if ( ! isset($this->_initializedCollections[spl_object_hash($element) . $inverseFieldName])) {
                                            $this->_initRelatedCollection($element, $inverseFieldName);
                                        }
                                    } else if ($relation->mappedByFieldName) {
                                        // Only initialize reverse collection if it is not yet initialized.
                                        if ( ! isset($this->_initializedCollections[spl_object_hash($element) . $relation->mappedByFieldName])) {
                                            $this->_initRelatedCollection($element, $relation->mappedByFieldName);
                                        }
                                    }
                                }
    
                                if (isset($this->_rsm->indexByMap[$dqlAlias])) {
                                    $field = $this->_rsm->indexByMap[$dqlAlias];
                                    $indexValue = $this->_ce[$entityName]->reflFields[$field]->getValue($element);
                                    $reflFieldValue->hydrateSet($indexValue, $element);
                                    $this->_identifierMap[$dqlAlias][$id[$dqlAlias]] = $indexValue;
                                } else {
                                    $reflFieldValue->hydrateAdd($element);
                                    $reflFieldValue->last();
                                    $this->_identifierMap[$dqlAlias][$id[$dqlAlias]] = $reflFieldValue->key();
                                }
                                // Update result pointer
                                $this->_resultPointers[$dqlAlias] = $element;
                            }
                        } else {
                            // Update result pointer
                            $this->_resultPointers[$dqlAlias] = $reflFieldValue[$index];
                        }
                    } else if ( ! $reflField->getValue($parentObject)) {
                        $coll = new PersistentCollection($this->_em, $this->_ce[$entityName], new ArrayCollection);
                        $reflField->setValue($parentObject, $coll);
                        $this->_uow->setOriginalEntityProperty($oid, $relationField, $coll);
                    }
                } else {
                    // Single-valued association
                    $reflFieldValue = $reflField->getValue($parentObject);
                    if ( ! $reflFieldValue || isset($this->_hints[Query::HINT_REFRESH])) {
                        if (isset($nonemptyComponents[$dqlAlias])) {
                            $element = $this->_getEntity($data, $dqlAlias);
                            $reflField->setValue($parentObject, $element);
                            $this->_uow->setOriginalEntityProperty($oid, $relationField, $element);
                            $targetClass = $this->_ce[$relation->targetEntityName];
                            if ($relation->isOwningSide) {
                                // If there is an inverse mapping on the target class its bidirectional
                                if (isset($targetClass->inverseMappings[$relation->sourceEntityName][$relationField])) {
                                    $inverseAssoc = $targetClass->inverseMappings[$relation->sourceEntityName][$relationField];
                                    if ($inverseAssoc->isOneToMany()) {
                                        // Only initialize reverse collection if it is not yet initialized.
                                        if ( ! isset($this->_initializedCollections[spl_object_hash($element) . $inverseAssoc->sourceFieldName])) {
                                            $this->_initRelatedCollection($element, $inverseAssoc->sourceFieldName);
                                        }
                                    } else {
                                        $targetClass->reflFields[$inverseAssoc->sourceFieldName]->setValue($element, $parentObject);
                                    }
                                } else if ($this->_ce[$parentClass] === $targetClass && $relation->mappedByFieldName) {
                                    // Special case: bi-directional self-referencing one-one on the same class
                                    $targetClass->reflFields[$relationField]->setValue($element, $parentObject);
                                }
                            } else {
                                // For sure bidirectional, as there is no inverse side in unidirectional mappings
                                $targetClass->reflFields[$relation->mappedByFieldName]->setValue($element, $parentObject);
                            }
                            // Update result pointer
                            $this->_resultPointers[$dqlAlias] = $element;
                        }
                        // else leave $reflFieldValue null for single-valued associations
                    } else {
                        // Update result pointer
                        $this->_resultPointers[$dqlAlias] = $reflFieldValue;
                    }
                }
            } else {
                // Its a root result element
                $this->_rootAliases[$dqlAlias] = true; // Mark as root alias

                if ( ! isset($this->_identifierMap[$dqlAlias][$id[$dqlAlias]])) {
                    $element = $this->_getEntity($rowData[$dqlAlias], $dqlAlias);
                    if (isset($this->_rsm->indexByMap[$dqlAlias])) {
                        $field = $this->_rsm->indexByMap[$dqlAlias];
                        $key = $this->_ce[$entityName]->reflFields[$field]->getValue($element);
                        if ($this->_rsm->isMixed) {
                            $element = array($key => $element);
                            $result[] = $element;
                            $this->_identifierMap[$dqlAlias][$id[$dqlAlias]] = $this->_resultCounter;
                            ++$this->_resultCounter;
                        } else {
                            $result[$key] = $element;
                            $this->_identifierMap[$dqlAlias][$id[$dqlAlias]] = $key;
                        }
                    } else {
                        if ($this->_rsm->isMixed) {
                            $element = array(0 => $element);
                        }
                        $result[] = $element;
                        $this->_identifierMap[$dqlAlias][$id[$dqlAlias]] = $this->_resultCounter;
                        ++$this->_resultCounter;
                    }
                    
                    // Update result pointer
                    $this->_resultPointers[$dqlAlias] = $element;
                    
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
}
