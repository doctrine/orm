<?php
/*
 *  $Id: UnitOfWork.php 4947 2008-09-12 13:16:05Z romanb $
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

namespace Doctrine\ORM;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\DoctrineException;
use Doctrine\Common\PropertyChangedListener;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Internal\CommitOrderCalculator;
use Doctrine\ORM\Internal\CommitOrderNode;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Persisters;
use Doctrine\ORM\EntityManager;

/**
 * The UnitOfWork is responsible for tracking changes to objects during an
 * "object-level" transaction and for writing out changes to the database
 * in the correct order.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 * @author      Roman Borschel <roman@code-factory.org>
 * @internal    This class contains performance-critical code. Work with care and
 *              regularly run the ORM performance tests.
 */
class UnitOfWork implements PropertyChangedListener
{
    /**
     * An entity is in managed state when it has a primary key/identifier (and
     * therefore persistent state) and is managed by an EntityManager
     * (registered in the identity map).
     * In MANAGED state the entity is associated with an EntityManager that manages
     * the persistent state of the Entity.
     */
    const STATE_MANAGED = 1;

    /**
     * An entity is new if it does not yet have an identifier/primary key
     * and is not (yet) managed by an EntityManager.
     */
    const STATE_NEW = 2;

    /**
     * A detached entity is an instance with a persistent identity that is not
     * (or no longer) associated with an EntityManager (and a UnitOfWork).
     * This means it is no longer in the identity map.
     */
    const STATE_DETACHED = 3;

    /**
     * A removed entity instance is an instance with a persistent identity,
     * associated with an EntityManager, whose persistent state has been
     * deleted (or is scheduled for deletion).
     */
    const STATE_DELETED = 4;

    /**
     * The identity map that holds references to all managed entities that have
     * an identity. The entities are grouped by their class name.
     * Since all classes in a hierarchy must share the same identifier set,
     * we always take the root class name of the hierarchy.
     *
     * @var array
     */
    private $_identityMap = array();

    /**
     * Map of all identifiers. Keys are object ids.
     *
     * @var array
     */
    private $_entityIdentifiers = array();

    /**
     * Map of the original entity data of entities fetched from the database.
     * Keys are object ids. This is used for calculating changesets at commit time.
     * Note that PHPs "copy-on-write" behavior helps a lot with memory usage.
     *
     * @var array
     */
    private $_originalEntityData = array();

    /**
     * Map of data changes. Keys are object ids.
     * Filled at the beginning of a commit of the UnitOfWork and cleaned at the end.
     *
     * @var array
     */
    private $_entityChangeSets = array();

    /**
     * The states of entities in this UnitOfWork.
     *
     * @var array
     */
    private $_entityStates = array();

    /**
     * Map of entities that are scheduled for dirty checking at commit time.
     * This is only used if automatic dirty checking is disabled.
     */
    private $_scheduledForDirtyCheck = array();

    /**
     * A list of all pending entity insertions.
     *
     * @var array
     */
    private $_entityInsertions = array();

    /**
     * A list of all pending entity updates.
     *
     * @var array
     */
    private $_entityUpdates = array();
    
    /**
     * Any extra updates that have been scheduled by persisters.
     * 
     * @var array
     */
    private $_extraUpdates = array();

    /**
     * A list of all pending entity deletions.
     *
     * @var array
     */
    private $_entityDeletions = array();

    /**
     * All pending collection deletions.
     *
     * @var array
     */
    private $_collectionDeletions = array();

    /**
     * All pending collection creations.
     *
     * @var array
     */
    private $_collectionCreations = array();

    /**
     * All collection updates.
     *
     * @var array
     */
    private $_collectionUpdates = array();

    /**
     * List of collections visited during a commit-phase of a UnitOfWork.
     * At the end of the UnitOfWork all these collections will make new snapshots
     * of their data.
     *
     * @var array
     */
    private $_visitedCollections = array();

    /**
     * The EntityManager that "owns" this UnitOfWork instance.
     *
     * @var Doctrine\ORM\EntityManager
     */
    private $_em;

    /**
     * The calculator used to calculate the order in which changes to
     * entities need to be written to the database.
     *
     * @var Doctrine\ORM\Internal\CommitOrderCalculator
     */
    private $_commitOrderCalculator;

    /**
     * The entity persister instances used to persist entity instances.
     *
     * @var array
     */
    private $_persisters = array();

    /**
     * The collection persister instances used to persist collections.
     *
     * @var array
     */
    private $_collectionPersisters = array();

    /**
     * Flag for whether or not to use the C extension for hydration
     *
     * @var boolean
     */
    private $_useCExtension = false;
    
    /**
     * The EventManager.
     * 
     * @var EventManager
     */
    private $_evm;

    /**
     * Initializes a new UnitOfWork instance, bound to the given EntityManager.
     *
     * @param Doctrine\ORM\EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->_em = $em;
        $this->_evm = $em->getEventManager();
        $this->_commitOrderCalculator = new CommitOrderCalculator();
        $this->_useCExtension = $this->_em->getConfiguration()->getUseCExtension();
    }

    /**
     * Commits the UnitOfWork, executing all operations that have been postponed
     * up to this point.
     */
    public function commit()
    {
        // Compute changes done since last commit.
        // This populates _entityUpdates and _collectionUpdates.
        $this->computeChangeSets();

        if (empty($this->_entityInsertions) &&
                empty($this->_entityDeletions) &&
                empty($this->_entityUpdates) &&
                empty($this->_collectionUpdates) &&
                empty($this->_collectionDeletions)) {
            return; // Nothing to do.
        }

        // Now we need a commit order to maintain referential integrity
        $commitOrder = $this->_getCommitOrder();

        $conn = $this->_em->getConnection();
        try {
            $conn->beginTransaction();
            
            if ($this->_entityInsertions) {
                foreach ($commitOrder as $class) {
                    $this->_executeInserts($class);
                }
            }
            
            if ($this->_entityUpdates) {
                foreach ($commitOrder as $class) {
                    $this->_executeUpdates($class);
                }
            }

            // Extra updates that were requested by persisters.
            if ($this->_extraUpdates) {
                $this->_executeExtraUpdates();
            }

            // Collection deletions (deletions of complete collections)
            foreach ($this->_collectionDeletions as $collectionToDelete) {
                $this->getCollectionPersister($collectionToDelete->getMapping())
                        ->delete($collectionToDelete);
            }
            // Collection updates (deleteRows, updateRows, insertRows)
            foreach ($this->_collectionUpdates as $collectionToUpdate) {
                $this->getCollectionPersister($collectionToUpdate->getMapping())
                        ->update($collectionToUpdate);
            }
            //TODO: collection recreations (insertions of complete collections)

            // Entity deletions come last and need to be in reverse commit order
            if ($this->_entityDeletions) {
                for ($count = count($commitOrder), $i = $count - 1; $i >= 0; --$i) {
                    $this->_executeDeletions($commitOrder[$i]);
                }
            }

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        // Take new snapshots from visited collections
        foreach ($this->_visitedCollections as $coll) {
            $coll->takeSnapshot();
        }

        // Clear up
        $this->_entityInsertions = array();
        $this->_entityUpdates = array();
        $this->_entityDeletions = array();
        $this->_extraUpdates = array();
        $this->_entityChangeSets = array();
        $this->_collectionUpdates = array();
        $this->_collectionDeletions = array();
        $this->_visitedCollections = array();
    }

    protected function _executeExtraUpdates()
    {
        foreach ($this->_extraUpdates as $oid => $update) {
            list ($entity, $changeset) = $update;
            $this->_entityChangeSets[$oid] = $changeset;
            $this->getEntityPersister(get_class($entity))->update($entity);
        }
    }

    /**
     * Gets the changeset for an entity.
     *
     * @return array
     */
    public function getEntityChangeSet($entity)
    {
        $oid = spl_object_hash($entity);
        if (isset($this->_entityChangeSets[$oid])) {
            return $this->_entityChangeSets[$oid];
        }
        return array();
    }

    /**
     * Computes all the changes that have been done to entities and collections
     * since the last commit and stores these changes in the _entityChangeSet map
     * temporarily for access by the persisters, until the UoW commit is finished.
     *
     * @param array $entities The entities for which to compute the changesets. If this
     *          parameter is not specified, the changesets of all entities in the identity
     *          map are computed if automatic dirty checking is enabled (the default).
     *          If automatic dirty checking is disabled, only those changesets will be
     *          computed that have been scheduled through scheduleForDirtyCheck().
     */
    public function computeChangeSets(array $entities = null)
    {
        $entitySet = array();
        $newEntities = array();
        if ($entities !== null) {
            foreach ($entities as $entity) {
                $entitySet[get_class($entity)][] = $entity;
            }
            $newEntities = $entities;
        } else {
            $entitySet = $this->_identityMap;
            $newEntities = $this->_entityInsertions;
        }

        // Compute changes for NEW entities first. This must always happen.
        foreach ($newEntities as $entity) {
            $this->_computeEntityChanges($this->_em->getClassMetadata(get_class($entity)), $entity);
        }

        // Compute changes for MANAGED entities. Change tracking policies take effect here.
        foreach ($entitySet as $className => $entities) {
            $class = $this->_em->getClassMetadata($className);

            // Skip class if change tracking happens through notification
            if ($class->isChangeTrackingNotify()) {
                continue;
            }

            // If change tracking is explicit, then only compute changes on explicitly saved entities
            $entitiesToProcess = $class->isChangeTrackingDeferredExplicit() ?
                    $this->_scheduledForDirtyCheck[$className] : $entities;

            foreach ($entitiesToProcess as $entity) {
                // Only MANAGED entities are processed here.
                if ($this->getEntityState($entity) == self::STATE_MANAGED) {
                    $this->_computeEntityChanges($class, $entity);
                    // Look for changes in associations of the entity
                    foreach ($class->associationMappings as $assoc) {
                        $val = $class->reflFields[$assoc->sourceFieldName]->getValue($entity);
                        if ($val !== null) {
                            $this->_computeAssociationChanges($assoc, $val);
                        }
                    }
                }
            }
        }
    }

    /**
     * Computes the changes done to a single entity.
     *
     * Modifies/populates the following properties:
     *
     * {@link _originalEntityData}
     * If the entity is NEW or MANAGED but not yet fully persisted (only has an id)
     * then it was not fetched from the database and therefore we have no original
     * entity data yet. All of the current entity data is stored as the original entity data.
     *
     * {@link _entityChangeSets}
     * The changes detected on all properties of the entity are stored there.
     * A change is a tuple array where the first entry is the old value and the second
     * entry is the new value of the property. Changesets are used by persisters
     * to INSERT/UPDATE the persistent entity state.
     *
     * {@link _entityUpdates}
     * If the entity is already fully MANAGED (has been fetched from the database before)
     * and any changes to its properties are detected, then a reference to the entity is stored
     * there to mark it for an update.
     *
     * {@link _collectionDeletions}
     * If a PersistentCollection has been de-referenced in a fully MANAGED entity,
     * then this collection is marked for deletion.
     *
     * @param ClassMetadata $class The class descriptor of the entity.
     * @param object $entity The entity for which to compute the changes.
     */
    private function _computeEntityChanges($class, $entity)
    {
        $oid = spl_object_hash($entity);

        if ( ! $class->isInheritanceTypeNone()) {
            $class = $this->_em->getClassMetadata(get_class($entity));
        }

        $actualData = array();
        foreach ($class->reflFields as $name => $refProp) {
            if ( ! $class->isIdentifier($name) || ! $class->isIdGeneratorIdentity()) {
                $actualData[$name] = $refProp->getValue($entity);
            }

            if ($class->isCollectionValuedAssociation($name) && $actualData[$name] !== null
                    && ! ($actualData[$name] instanceof PersistentCollection)) {
                // If $actualData[$name] is Collection then unwrap the array
                if ($actualData[$name] instanceof Collection) {
                    $actualData[$name] = $actualData[$name]->unwrap();
                }
                $assoc = $class->associationMappings[$name];
                // Inject PersistentCollection
                $coll = new PersistentCollection($this->_em, $this->_em->getClassMetadata($assoc->targetEntityName),
                $actualData[$name] ? $actualData[$name] : array());
                $coll->setOwner($entity, $assoc);
                $coll->setDirty( ! $coll->isEmpty());
                $class->reflFields[$name]->setValue($entity, $coll);
                $actualData[$name] = $coll;
            }
        }

        if ( ! isset($this->_originalEntityData[$oid])) {
            // Entity is either NEW or MANAGED but not yet fully persisted (only has an id).
            // These result in an INSERT.
            $this->_originalEntityData[$oid] = $actualData;
            $this->_entityChangeSets[$oid] = array_map(
                function($e) { return array(null, $e); }, $actualData
            );
        } else {
            // Entity is "fully" MANAGED: it was already fully persisted before
            // and we have a copy of the original data
            $originalData = $this->_originalEntityData[$oid];
            $changeSet = array();
            $entityIsDirty = false;

            foreach ($actualData as $propName => $actualValue) {
                $orgValue = isset($originalData[$propName]) ? $originalData[$propName] : null;
                if (is_object($orgValue) && $orgValue !== $actualValue) {
                    $changeSet[$propName] = array($orgValue, $actualValue);
                } else if ($orgValue != $actualValue || ($orgValue === null ^ $actualValue === null)) {
                    $changeSet[$propName] = array($orgValue, $actualValue);
                }

                if (isset($changeSet[$propName])) {
                    if (isset($class->associationMappings[$propName])) {
                        $assoc = $class->associationMappings[$propName];
                        if ($assoc->isOneToOne() && $assoc->isOwningSide) {
                            $entityIsDirty = true;
                        } else if ($orgValue instanceof PersistentCollection) {
                            // A PersistentCollection was de-referenced, so delete it.
                            if  ( ! in_array($orgValue, $this->_collectionDeletions, true)) {
                                $this->_collectionDeletions[] = $orgValue;
                            }
                        }
                    } else {
                        $entityIsDirty = true;
                    }
                }
            }
            if ($changeSet) {
                if ($entityIsDirty) {
                    $this->_entityUpdates[$oid] = $entity;
                }
                $this->_entityChangeSets[$oid] = $changeSet;
                $this->_originalEntityData[$oid] = $actualData;
            }
        }
    }

    /**
     * Computes the changes of an association.
     *
     * @param AssociationMapping $assoc
     * @param mixed $value The value of the association.
     */
    private function _computeAssociationChanges($assoc, $value)
    {
        if ($value instanceof PersistentCollection && $value->isDirty()) {
            if ($assoc->isOwningSide) {
                $this->_collectionUpdates[] = $value;
            }
            $this->_visitedCollections[] = $value;
        }

        if ( ! $assoc->isCascadeSave) {
            return; // "Persistence by reachability" only if save cascade specified
        }

        // Look through the entities, and in any of their associations, for transient
        // enities, recursively. ("Persistence by reachability")
        if ($assoc->isOneToOne()) {
            $value = array($value);
        }
        $targetClass = $this->_em->getClassMetadata($assoc->targetEntityName);
        foreach ($value as $entry) {
            $state = $this->getEntityState($entry);
            $oid = spl_object_hash($entry);
            if ($state == self::STATE_NEW) {
                // Get identifier, if possible (not post-insert)
                $idGen = $targetClass->idGenerator;
                if ( ! $idGen->isPostInsertGenerator()) {
                    $idValue = $idGen->generate($this->_em, $entry);
                    $this->_entityStates[$oid] = self::STATE_MANAGED;
                    if ( ! $idGen instanceof \Doctrine\ORM\Id\Assigned) {
                        $this->_entityIdentifiers[$oid] = array($idValue);
                        $targetClass->getSingleIdReflectionProperty()->setValue($entry, $idValue);
                    } else {
                        $this->_entityIdentifiers[$oid] = $idValue;
                    }
                    $this->addToIdentityMap($entry);
                }

                // Collect the original data and changeset, recursing into associations.
                $data = array();
                $changeSet = array();
                foreach ($targetClass->reflFields as $name => $refProp) {
                    $data[$name] = $refProp->getValue($entry);
                    $changeSet[$name] = array(null, $data[$name]);
                    if (isset($targetClass->associationMappings[$name])) {
                        //TODO: Prevent infinite recursion
                        $this->_computeAssociationChanges($targetClass->associationMappings[$name], $data[$name]);
                    }
                }

                // NEW entities are INSERTed within the current unit of work.
                $this->_entityInsertions[$oid] = $entry;
                $this->_entityChangeSets[$oid] = $changeSet;
                $this->_originalEntityData[$oid] = $data;
            } else if ($state == self::STATE_DELETED) {
                throw DoctrineException::updateMe("Deleted entity in collection detected during flush."
                        . " Make sure you properly remove deleted entities from collections.");
            }
            // MANAGED associated entities are already taken into account
            // during changeset calculation anyway, since they are in the identity map.
        }
    }
    
    /**
     * EXPERIMENTAL:
     * Computes the changeset of an individual entity, independently of the
     * computeChangeSets() routine that is used at the beginning of a UnitOfWork#commit().
     * 
     * @param $class
     * @param $entity
     */
    public function computeSingleEntityChangeSet($class, $entity)
    {
        $oid = spl_object_hash($entity);

        if ( ! $class->isInheritanceTypeNone()) {
            $class = $this->_em->getClassMetadata(get_class($entity));
        }

        $actualData = array();
        foreach ($class->reflFields as $name => $refProp) {
            if ( ! $class->isIdentifier($name) || ! $class->isIdGeneratorIdentity()) {
                $actualData[$name] = $refProp->getValue($entity);
            }
        }
        
        if ( ! isset($this->_originalEntityData[$oid])) {
            $this->_originalEntityData[$oid] = $actualData;
            $this->_entityChangeSets[$oid] = array_map(
                function($e) { return array(null, $e); }, $actualData
            );
        } else {
            $originalData = $this->_originalEntityData[$oid];
            $changeSet = array();

            foreach ($actualData as $propName => $actualValue) {
                $orgValue = isset($originalData[$propName]) ? $originalData[$propName] : null;
                if (is_object($orgValue) && $orgValue !== $actualValue) {
                    $changeSet[$propName] = array($orgValue, $actualValue);
                } else if ($orgValue != $actualValue || ($orgValue === null ^ $actualValue === null)) {
                    $changeSet[$propName] = array($orgValue, $actualValue);
                }
            }
            
            if ($changeSet) {
                $this->_entityChangeSets[$oid] = $changeSet;
                $this->_originalEntityData[$oid] = $actualData;
            }
        }
    }

    /**
     * Executes all entity insertions for entities of the specified type.
     *
     * @param Doctrine\ORM\Mapping\ClassMetadata $class
     */
    private function _executeInserts($class)
    {
        $className = $class->name;
        $persister = $this->getEntityPersister($className);
        
        $hasLifecycleCallbacks = isset($class->lifecycleCallbacks[Events::postSave]);
        $hasListeners = $this->_evm->hasListeners(Events::postSave);
        if ($hasLifecycleCallbacks || $hasListeners) {
            $entities = array();
        }
        
        foreach ($this->_entityInsertions as $oid => $entity) {
            if (get_class($entity) == $className) {
                $persister->addInsert($entity);
                unset($this->_entityInsertions[$oid]);
                if ($hasLifecycleCallbacks || $hasListeners) {
                    $entities[] = $entity;
                }
            }
        }
        
        $postInsertIds = $persister->executeInserts();
        
        if ($postInsertIds) {
            // Persister returned a post-insert IDs
            foreach ($postInsertIds as $id => $entity) {
                $oid = spl_object_hash($entity);
                $idField = $class->identifier[0];
                $class->reflFields[$idField]->setValue($entity, $id);
                $this->_entityIdentifiers[$oid] = array($id);
                $this->_entityStates[$oid] = self::STATE_MANAGED;
                $this->_originalEntityData[$oid][$idField] = $id;
                $this->addToIdentityMap($entity);
            }
        }
        
        if ($hasLifecycleCallbacks || $hasListeners) {
            foreach ($entities as $entity) {
                if ($hasLifecycleCallbacks) {
                    $class->invokeLifecycleCallbacks(Events::postSave, $entity);
                }
                if ($hasListeners) {
                    $this->_evm->dispatchEvent(Events::postSave, new LifecycleEventArgs($entity));
                }
            }
        }
    }

    /**
     * Executes all entity updates for entities of the specified type.
     *
     * @param Doctrine\ORM\Mapping\ClassMetadata $class
     */
    private function _executeUpdates($class)
    {
        $className = $class->name;
        $persister = $this->getEntityPersister($className);
        
        $hasPreUpdateLifecycleCallbacks = isset($class->lifecycleCallbacks[Events::preUpdate]);
        $hasPreUpdateListeners = $this->_evm->hasListeners(Events::preUpdate);
        $hasPostUpdateLifecycleCallbacks = isset($class->lifecycleCallbacks[Events::postUpdate]);
        $hasPostUpdateListeners = $this->_evm->hasListeners(Events::postUpdate);
        
        foreach ($this->_entityUpdates as $oid => $entity) {
            if (get_class($entity) == $className) {
                if ($hasPreUpdateLifecycleCallbacks) {
                    $class->invokeLifecycleCallbacks(Events::preUpdate, $entity);
                    if ( ! $hasPreUpdateListeners) {
                        // Need to recompute entity changeset to detect changes made in the callback.
                        $this->computeSingleEntityChangeSet($class, $entity);
                    }
                }
                if ($hasPreUpdateListeners) {
                    $this->_evm->dispatchEvent(Events::preUpdate, new LifecycleEventArgs($entity));
                    // Need to recompute entity changeset to detect changes made in the listener.
                    $this->computeSingleEntityChangeSet($class, $entity);
                }
                
                $persister->update($entity);
                unset($this->_entityUpdates[$oid]);
                
                if ($hasPostUpdateLifecycleCallbacks) {
                    $class->invokeLifecycleCallbacks(Events::postUpdate, $entity);
                }
                if ($hasPostUpdateListeners) {
                    $this->_evm->dispatchEvent(Events::postUpdate, new LifecycleEventArgs($entity));
                }
            }
        }
    }

    /**
     * Executes all entity deletions for entities of the specified type.
     *
     * @param Doctrine\ORM\Mapping\ClassMetadata $class
     */
    private function _executeDeletions($class)
    {
        $className = $class->name;
        $persister = $this->getEntityPersister($className);
                
        $hasLifecycleCallbacks = isset($class->lifecycleCallbacks[Events::postDelete]);
        $hasListeners = $this->_evm->hasListeners(Events::postDelete);
        
        foreach ($this->_entityDeletions as $oid => $entity) {
            if (get_class($entity) == $className) {
                $persister->delete($entity);
                unset($this->_entityDeletions[$oid]);
                
                if ($hasLifecycleCallbacks) {
                    $class->invokeLifecycleCallbacks(Events::postDelete, $entity);
                }
                if ($hasListeners) {
                    $this->_evm->dispatchEvent(Events::postDelete, new LifecycleEventArgs($entity));
                }
            }
        }
    }

    /**
     * Gets the commit order.
     *
     * @return array
     */
    private function _getCommitOrder(array $entityChangeSet = null)
    {
        if ($entityChangeSet === null) {
            $entityChangeSet = array_merge(
                    $this->_entityInsertions,
                    $this->_entityUpdates,
                    $this->_entityDeletions
                    );
        }

        // TODO: We can cache computed commit orders in the metadata cache!
        // Check cache at this point here!

        // See if there are any new classes in the changeset, that are not in the
        // commit order graph yet (dont have a node).
        $newNodes = array();
        foreach ($entityChangeSet as $entity) {
            $className = get_class($entity);
            if ( ! $this->_commitOrderCalculator->hasNodeWithKey($className)) {
                $this->_commitOrderCalculator->addNodeWithItem(
                    $className, // index/key
                    $this->_em->getClassMetadata($className) // item
                );
                $newNodes[] = $this->_commitOrderCalculator->getNodeForKey($className);
            }
        }

        // Calculate dependencies for new nodes
        foreach ($newNodes as $node) {
            $class = $node->getClass();
            foreach ($class->associationMappings as $assocMapping) {
                //TODO: should skip target classes that are not in the changeset.
                if ($assocMapping->isOwningSide) {
                    $targetClass = $this->_em->getClassMetadata($assocMapping->targetEntityName);
                    $targetClassName = $targetClass->name;
                    // If the target class does not yet have a node, create it
                    if ( ! $this->_commitOrderCalculator->hasNodeWithKey($targetClassName)) {
                        $this->_commitOrderCalculator->addNodeWithItem(
                            $targetClassName, // index/key
                            $targetClass // item
                        );
                    }
                    // add dependency
                    $otherNode = $this->_commitOrderCalculator->getNodeForKey($targetClassName);
                    $otherNode->before($node);
                }
            }
        }

        return $this->_commitOrderCalculator->getCommitOrder();
    }

    /**
     * Registers a new entity. The entity will be scheduled for insertion.
     * If the entity already has an identifier, it will be added to the identity map.
     *
     * @param object $entity
     * @todo Rename to scheduleForInsert().
     */
    public function registerNew($entity)
    {
        $oid = spl_object_hash($entity);

        if (isset($this->_entityUpdates[$oid])) {
            throw DoctrineException::updateMe("Dirty object can't be registered as new.");
        }
        if (isset($this->_entityDeletions[$oid])) {
            throw DoctrineException::updateMe("Removed object can't be registered as new.");
        }
        if (isset($this->_entityInsertions[$oid])) {
            throw DoctrineException::updateMe("Object already registered as new. Can't register twice.");
        }

        $this->_entityInsertions[$oid] = $entity;
        if (isset($this->_entityIdentifiers[$oid])) {
            $this->addToIdentityMap($entity);
        }
    }

    /**
     * Checks whether an entity is registered as new on this unit of work.
     *
     * @param object $entity
     * @return boolean
     * @todo Rename to isScheduledForInsert().
     */
    public function isRegisteredNew($entity)
    {
        return isset($this->_entityInsertions[spl_object_hash($entity)]);
    }

    /**
     * Registers a dirty entity.
     *
     * @param object $entity
     * @todo Rename to scheduleForUpdate().
     */
    public function registerDirty($entity)
    {
        $oid = spl_object_hash($entity);
        if ( ! isset($this->_entityIdentifiers[$oid])) {
            throw DoctrineException::updateMe("Entity without identity "
                    . "can't be registered as dirty.");
        }
        if (isset($this->_entityDeletions[$oid])) {
            throw DoctrineException::updateMe("Removed object can't be registered as dirty.");
        }

        if ( ! isset($this->_entityUpdates[$oid]) && ! isset($this->_entityInsertions[$oid])) {
            $this->_entityUpdates[$oid] = $entity;
        }
    }
    
    /**
     * Schedules an extra update that will be executed immediately after the
     * regular entity updates.
     * 
     * @param $entity
     * @param $changeset
     */
    public function scheduleExtraUpdate($entity, array $changeset)
    {
        $this->_extraUpdates[spl_object_hash($entity)] = array($entity, $changeset);
    }

    /**
     * Checks whether an entity is registered as dirty in the unit of work.
     * Note: Is not very useful currently as dirty entities are only registered
     * at commit time.
     *
     * @param object $entity
     * @return boolean
     * @todo Rename to isScheduledForUpdate().
     */
    public function isRegisteredDirty($entity)
    {
        return isset($this->_entityUpdates[spl_object_hash($entity)]);
    }

    /**
     * Registers a deleted entity.
     *
     * @todo Rename to scheduleForDelete().
     */
    public function registerDeleted($entity)
    {
        $oid = spl_object_hash($entity);
        if ( ! $this->isInIdentityMap($entity)) {
            return;
        }

        $this->removeFromIdentityMap($entity);
        $className = get_class($entity);

        if (isset($this->_entityInsertions[$oid])) {
            unset($this->_entityInsertions[$oid]);
            return; // entity has not been persisted yet, so nothing more to do.
        }

        if (isset($this->_entityUpdates[$oid])) {
            unset($this->_entityUpdates[$oid]);
        }
        if ( ! isset($this->_entityDeletions[$oid])) {
            $this->_entityDeletions[$oid] = $entity;
        }
    }

    /**
     * Checks whether an entity is registered as removed/deleted with the unit
     * of work.
     *
     * @param object $entity
     * @return boolean
     * @todo Rename to isScheduledForDelete().
     */
    public function isRegisteredRemoved($entity)
    {
        return isset($this->_entityDeletions[spl_object_hash($entity)]);
    }

    /**
     * Detaches an entity from the persistence management. It's persistence will
     * no longer be managed by Doctrine.
     *
     * @param object $entity The entity to detach.
     */
    public function detach($entity)
    {
        $oid = spl_object_hash($entity);
        $this->removeFromIdentityMap($entity);
        unset($this->_entityInsertions[$oid], $this->_entityUpdates[$oid],
        $this->_entityDeletions[$oid], $this->_entityIdentifiers[$oid],
        $this->_entityStates[$oid]);
    }

    /**
     * 
     * 
     * @param $entity
     * @return unknown_type
     */
    public function isEntityRegistered($entity)
    {
        $oid = spl_object_hash($entity);
        return isset($this->_entityInsertions[$oid]) ||
                isset($this->_entityUpdates[$oid]) ||
                isset($this->_entityDeletions[$oid]);
    }

    /**
     * Registers an entity in the identity map.
     * Note that entities in a hierarchy are registered with the class name of
     * the root entity.
     *
     * @param object $entity  The entity to register.
     * @return boolean  TRUE if the registration was successful, FALSE if the identity of
     *                  the entity in question is already managed.
     */
    public function addToIdentityMap($entity)
    {
        $classMetadata = $this->_em->getClassMetadata(get_class($entity));
        $idHash = implode(' ', $this->_entityIdentifiers[spl_object_hash($entity)]);
        if ($idHash === '') {
            throw DoctrineException::updateMe("Entity with oid '" . spl_object_hash($entity)
                    . "' has no identity and therefore can't be added to the identity map.");
        }
        $className = $classMetadata->rootEntityName;
        if (isset($this->_identityMap[$className][$idHash])) {
            return false;
        }
        $this->_identityMap[$className][$idHash] = $entity;
        if ($entity instanceof \Doctrine\Common\NotifyPropertyChanged) {
            $entity->addPropertyChangedListener($this);
        }
        return true;
    }

    /**
     * Gets the state of an entity within the current unit of work.
     *
     * @param object $entity
     * @return int The entity state.
     */
    public function getEntityState($entity)
    {
        $oid = spl_object_hash($entity);
        if ( ! isset($this->_entityStates[$oid])) {
            /*if (isset($this->_entityInsertions[$oid])) {
             $this->_entityStates[$oid] = self::STATE_NEW;
             } else if ( ! isset($this->_entityIdentifiers[$oid])) {
             // Either NEW (if no ID) or DETACHED (if ID)
             } else {
             $this->_entityStates[$oid] = self::STATE_DETACHED;
             }*/
            if (isset($this->_entityIdentifiers[$oid]) && ! isset($this->_entityInsertions[$oid])) {
                $this->_entityStates[$oid] = self::STATE_DETACHED;
            } else {
                $this->_entityStates[$oid] = self::STATE_NEW;
            }
        }
        return $this->_entityStates[$oid];
    }

    /**
     * Removes an entity from the identity map. This effectively detaches the
     * entity from the persistence management of Doctrine.
     *
     * @param object $entity
     * @return boolean
     */
    public function removeFromIdentityMap($entity)
    {
        $oid = spl_object_hash($entity);
        $classMetadata = $this->_em->getClassMetadata(get_class($entity));
        $idHash = implode(' ', $this->_entityIdentifiers[$oid]);
        if ($idHash === '') {
            throw DoctrineException::updateMe("Entity with oid '" . spl_object_hash($entity)
                    . "' has no identity and therefore can't be removed from the identity map.");
        }
        $className = $classMetadata->rootEntityName;
        if (isset($this->_identityMap[$className][$idHash])) {
            unset($this->_identityMap[$className][$idHash]);
            $this->_entityStates[$oid] = self::STATE_DETACHED;
            return true;
        }

        return false;
    }

    /**
     * Gets an entity in the identity map by its identifier hash.
     *
     * @param string $idHash
     * @param string $rootClassName
     * @return object
     */
    public function getByIdHash($idHash, $rootClassName)
    {
        return $this->_identityMap[$rootClassName][$idHash];
    }

    /**
     * Tries to get an entity by its identifier hash. If no entity is found for
     * the given hash, FALSE is returned.
     *
     * @param string $idHash
     * @param string $rootClassName
     * @return mixed The found entity or FALSE.
     */
    public function tryGetByIdHash($idHash, $rootClassName)
    {
        if ($this->containsIdHash($idHash, $rootClassName)) {
            return $this->getByIdHash($idHash, $rootClassName);
        }
        return false;
    }

    /**
     * Checks whether an entity is registered in the identity map of this UnitOfWork.
     *
     * @param object $entity
     * @return boolean
     */
    public function isInIdentityMap($entity)
    {
        $oid = spl_object_hash($entity);
        if ( ! isset($this->_entityIdentifiers[$oid])) {
            return false;
        }
        $classMetadata = $this->_em->getClassMetadata(get_class($entity));
        $idHash = implode(' ', $this->_entityIdentifiers[$oid]);
        if ($idHash === '') {
            return false;
        }

        return isset($this->_identityMap[$classMetadata->rootEntityName][$idHash]);
    }

    /**
     * Checks whether an identifier hash exists in the identity map.
     *
     * @param string $idHash
     * @param string $rootClassName
     * @return boolean
     */
    public function containsIdHash($idHash, $rootClassName)
    {
        return isset($this->_identityMap[$rootClassName][$idHash]);
    }

    /**
     * Saves an entity as part of the current unit of work.
     *
     * @param object $entity The entity to save.
     */
    public function save($entity)
    {
        $insertNow = array();
        $visited = array();
        $this->_doSave($entity, $visited, $insertNow);
        if ( ! empty($insertNow)) {
            // We have no choice. This means that there are new entities
            // with a post-insert ID generation strategy.
            $this->computeChangeSets($insertNow);
            $commitOrder = $this->_getCommitOrder($insertNow);
            foreach ($commitOrder as $class) {
                $this->_executeInserts($class);
            }
            // Extra updates that were requested by persisters.
            if ($this->_extraUpdates) {
                $this->_executeExtraUpdates();
                $this->_extraUpdates = array();
            }
            // Remove them from _entityInsertions and _entityChangeSets
            $this->_entityInsertions = array_diff_key($this->_entityInsertions, $insertNow);
            $this->_entityChangeSets = array_diff_key($this->_entityChangeSets, $insertNow);
        }
    }

    /**
     * Saves an entity as part of the current unit of work.
     * This method is internally called during save() cascades as it tracks
     * the already visited entities to prevent infinite recursions.
     *
     * @param object $entity The entity to save.
     * @param array $visited The already visited entities.
     * @param array $insertNow The entities that must be immediately inserted because of
     *                         post-insert ID generation.
     */
    private function _doSave($entity, array &$visited, array &$insertNow)
    {
        $oid = spl_object_hash($entity);
        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $entity; // Mark visited

        $class = $this->_em->getClassMetadata(get_class($entity));
        switch ($this->getEntityState($entity)) {
            case self::STATE_MANAGED:
                // Nothing to do, except if policy is "deferred explicit"
                if ($class->isChangeTrackingDeferredExplicit()) {
                    $this->scheduleForDirtyCheck($entity);
                }
                break;
            case self::STATE_NEW:
                if (isset($class->lifecycleCallbacks[Events::preSave])) {
                    $class->invokeLifecycleCallbacks(Events::preSave, $entity);
                }
                if ($this->_evm->hasListeners(Events::preSave)) {
                    $this->_evm->dispatchEvent(Events::preSave, new LifecycleEventArgs($entity));
                }
                
                $idGen = $class->idGenerator;
                if ($idGen->isPostInsertGenerator()) {
                    $insertNow[$oid] = $entity;
                } else {
                    $idValue = $idGen->generate($this->_em, $entity);
                    $this->_entityStates[$oid] = self::STATE_MANAGED;
                    if ( ! $idGen instanceof \Doctrine\ORM\Id\Assigned) {
                        $this->_entityIdentifiers[$oid] = array($idValue);
                        $class->setIdentifierValues($entity, $idValue);
                    } else {
                        $this->_entityIdentifiers[$oid] = $idValue;
                    }
                }
                $this->registerNew($entity);
                break;
            case self::STATE_DETACHED:
                throw DoctrineException::updateMe("Behavior of save() for a detached entity "
                        . "is not yet defined.");
            case self::STATE_DELETED:
                // Entity becomes managed again
                if ($this->isRegisteredRemoved($entity)) {
                    unset($this->_entityDeletions[$oid]);
                } else {
                    //FIXME: There's more to think of here...
                    $this->registerNew($entity);
                }
                break;
            default:
                throw DoctrineException::updateMe("Encountered invalid entity state.");
        }
        $this->_cascadeSave($entity, $visited, $insertNow);
    }

    /**
     * Deletes an entity as part of the current unit of work.
     *
     * @param object $entity
     */
    public function delete($entity)
    {
        $visited = array();
        $this->_doDelete($entity, $visited);
    }

    /**
     * Deletes an entity as part of the current unit of work.
     *
     * This method is internally called during delete() cascades as it tracks
     * the already visited entities to prevent infinite recursions.
     *
     * @param object $entity The entity to delete.
     * @param array $visited The map of the already visited entities.
     */
    private function _doDelete($entity, array &$visited)
    {
        $oid = spl_object_hash($entity);
        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $entity; // mark visited

        $class = $this->_em->getClassMetadata(get_class($entity));
        switch ($this->getEntityState($entity)) {
            case self::STATE_NEW:
            case self::STATE_DELETED:
                // nothing to do
                break;
            case self::STATE_MANAGED:
                if (isset($class->lifecycleCallbacks[Events::preDelete])) {
                    $class->invokeLifecycleCallbacks(Events::preDelete, $entity);
                }
                if ($this->_evm->hasListeners(Events::preDelete)) {
                    $this->_evm->dispatchEvent(Events::preDelete, new LifecycleEventArgs($entity));
                }
                $this->registerDeleted($entity);
                break;
            case self::STATE_DETACHED:
                throw DoctrineException::updateMe("A detached entity can't be deleted.");
            default:
                throw DoctrineException::updateMe("Encountered invalid entity state.");
        }
        $this->_cascadeDelete($entity, $visited);
    }

    /**
     * Merges the state of the given detached entity into this UnitOfWork.
     *
     * @param object $entity
     * @return object The managed copy of the entity.
     */
    public function merge($entity)
    {
        $visited = array();
        return $this->_doMerge($entity, $visited);
    }

    /**
     * Executes a merge operation on an entity.
     *
     * @param object $entity
     * @param array $visited
     * @return object The managed copy of the entity.
     */
    private function _doMerge($entity, array &$visited, $prevManagedCopy = null, $assoc = null)
    {
        $class = $this->_em->getClassMetadata(get_class($entity));
        $id = $class->getIdentifierValues($entity);

        if ( ! $id) {
            throw new \InvalidArgumentException('New entity passed to merge().');
        }

        $managedCopy = $this->tryGetById($id, $class->rootEntityName);
        if ($managedCopy) {
            if ($this->getEntityState($managedCopy) == self::STATE_DELETED) {
                throw new InvalidArgumentException('Can not merge with a deleted entity.');
            }
        } else {
            $managedCopy = $this->_em->find($class->name, $id);
        }
        
        if ($class->isVersioned) {
            $managedCopyVersion = $class->reflFields[$class->versionField]->getValue($managedCopy);
            $entityVersion = $class->reflFields[$class->versionField]->getValue($entity);
            // Throw exception if versions dont match.
            if ($managedCopyVersion != $entity) {
                throw OptimisticLockException::versionMismatch();
            }
        }

        // Merge state of $entity into existing (managed) entity
        foreach ($class->reflFields as $name => $prop) {
            if ( ! isset($class->associationMappings[$name])) {
                $prop->setValue($managedCopy, $prop->getValue($entity));
            }
            if ($class->isChangeTrackingNotify()) {
                //TODO
            }
        }
        if ($class->isChangeTrackingDeferredExplicit()) {
            //TODO
        }

        if ($prevManagedCopy !== null) {
            $assocField = $assoc->sourceFieldName;
            $prevClass = $this->_em->getClassMetadata(get_class($prevManagedCopy));
            if ($assoc->isOneToOne()) {
                $prevClass->reflFields[$assocField]->setValue($prevManagedCopy, $managedCopy);
            } else {
                $prevClass->reflFields[$assocField]->getValue($prevManagedCopy)->add($managedCopy);
            }
        }

        $this->_cascadeMerge($entity, $managedCopy, $visited);

        return $managedCopy;
    }

    /**
     * Cascades a merge operation to associated entities.
     *
     * @param object $entity
     * @param object $managedCopy
     * @param array $visited
     */
    private function _cascadeMerge($entity, $managedCopy, array &$visited)
    {
        $class = $this->_em->getClassMetadata(get_class($entity));
        foreach ($class->associationMappings as $assocMapping) {
            if ( ! $assocMapping->isCascadeMerge) {
                continue;
            }
            $relatedEntities = $class->reflFields[$assocMapping->getSourceFieldName()]
                    ->getValue($entity);
            if ($relatedEntities instanceof Collection) {
                foreach ($relatedEntities as $relatedEntity) {
                    $this->_doMerge($relatedEntity, $visited, $managedCopy, $assocMapping);
                }
            } else if ($relatedEntities !== null) {
                $this->_doMerge($relatedEntities, $visited, $managedCopy, $assocMapping);
            }
        }
    }

    /**
     * Cascades the save operation to associated entities.
     *
     * @param object $entity
     * @param array $visited
     * @param array $insertNow
     */
    private function _cascadeSave($entity, array &$visited, array &$insertNow)
    {
        $class = $this->_em->getClassMetadata(get_class($entity));
        foreach ($class->associationMappings as $assocMapping) {
            if ( ! $assocMapping->isCascadeSave) {
                continue;
            }
            $relatedEntities = $class->reflFields[$assocMapping->sourceFieldName]->getValue($entity);
            if (($relatedEntities instanceof Collection || is_array($relatedEntities))) {
                foreach ($relatedEntities as $relatedEntity) {
                    $this->_doSave($relatedEntity, $visited, $insertNow);
                }
            } else if ($relatedEntities !== null) {
                $this->_doSave($relatedEntities, $visited, $insertNow);
            }
        }
    }

    /**
     * Cascades the delete operation to associated entities.
     *
     * @param object $entity
     * @param array $visited
     */
    private function _cascadeDelete($entity, array &$visited)
    {
        $class = $this->_em->getClassMetadata(get_class($entity));
        foreach ($class->associationMappings as $assocMapping) {
            if ( ! $assocMapping->isCascadeDelete) {
                continue;
            }
            $relatedEntities = $class->reflFields[$assocMapping->sourceFieldName]
                    ->getValue($entity);
            if ($relatedEntities instanceof Collection || is_array($relatedEntities)) {
                foreach ($relatedEntities as $relatedEntity) {
                    $this->_doDelete($relatedEntity, $visited);
                }
            } else if ($relatedEntities !== null) {
                $this->_doDelete($relatedEntities, $visited);
            }
        }
    }

    /**
     * Gets the CommitOrderCalculator used by the UnitOfWork to order commits.
     *
     * @return Doctrine\ORM\Internal\CommitOrderCalculator
     */
    public function getCommitOrderCalculator()
    {
        return $this->_commitOrderCalculator;
    }

    /**
     * Clears the UnitOfWork.
     */
    public function clear()
    {
        $this->_identityMap = array();
        $this->_entityIdentifiers = array();
        $this->_originalEntityData = array();
        $this->_entityChangeSets = array();
        $this->_entityStates = array();
        $this->_scheduledForDirtyCheck = array();
        $this->_entityInsertions = array();
        $this->_entityUpdates = array();
        $this->_entityDeletions = array();
        $this->_collectionDeletions = array();
        $this->_collectionCreations = array();
        $this->_collectionUpdates = array();
        $this->_commitOrderCalculator->clear();
    }

    public function scheduleCollectionUpdate(PersistentCollection $coll)
    {
        $this->_collectionUpdates[] = $coll;
    }

    public function isCollectionScheduledForUpdate(PersistentCollection $coll)
    {
        //...
    }

    public function scheduleCollectionDeletion(PersistentCollection $coll)
    {
        //TODO: if $coll is already scheduled for recreation ... what to do?
        // Just remove $coll from the scheduled recreations?
        $this->_collectionDeletions[] = $coll;
    }

    public function isCollectionScheduledForDeletion(PersistentCollection $coll)
    {
        //...
    }

    public function scheduleCollectionRecreation(PersistentCollection $coll)
    {
        $this->_collectionRecreations[] = $coll;
    }

    public function isCollectionScheduledForRecreation(PersistentCollection $coll)
    {
        //...
    }

    /**
     * INTERNAL:
     * Creates an entity. Used for reconstitution of entities during hydration.
     *
     * @param string $className  The name of the entity class.
     * @param array $data  The data for the entity.
     * @return object
     * @internal Performance-sensitive method. Run the performance test suites when
     *           making modifications.
     */
    public function createEntity($className, array $data, $hints = array())
    {
        $class = $this->_em->getClassMetadata($className);

        if ($class->isIdentifierComposite) {
            $id = array();
            foreach ($class->identifier as $fieldName) {
                $id[] = $data[$fieldName];
            }
            $idHash = implode(' ', $id);
        } else {
            $id = array($data[$class->identifier[0]]);
            $idHash = $id[0];
        }

        if (isset($this->_identityMap[$class->rootEntityName][$idHash])) {
            $entity = $this->_identityMap[$class->rootEntityName][$idHash];
            $oid = spl_object_hash($entity);
            $overrideLocalChanges = false;
            //$overrideLocalChanges = isset($hints['doctrine.refresh']) && $hints['doctrine.refresh'] === true;
        } else {
            $entity = new $className;
            $oid = spl_object_hash($entity);
            $this->_entityIdentifiers[$oid] = $id;
            $this->_entityStates[$oid] = self::STATE_MANAGED;
            $this->_originalEntityData[$oid] = $data;
            $this->_identityMap[$class->rootEntityName][$idHash] = $entity;
            if ($entity instanceof \Doctrine\Common\NotifyPropertyChanged) {
                $entity->addPropertyChangedListener($this);
            }
            $overrideLocalChanges = true;
        }

        if ($overrideLocalChanges) {
            if ($this->_useCExtension) {
                doctrine_populate_data($entity, $data);
            } else {
                foreach ($data as $field => $value) {
                    if (isset($class->reflFields[$field])) {
                        $class->reflFields[$field]->setValue($entity, $value);
                    }
                }
            }
        } else {
            foreach ($data as $field => $value) {
                if (isset($class->reflFields[$field])) {
                    $currentValue = $class->reflFields[$field]->getValue($entity);
                    // Only override the current value if:
                    // a) There was no original value yet (nothing in _originalEntityData)
                    // or
                    // b) The original value is the same as the current value (it was not changed).
                    if ( ! isset($this->_originalEntityData[$oid][$field]) ||
                            $currentValue == $this->_originalEntityData[$oid][$field]) {
                        $class->reflFields[$field]->setValue($entity, $value);
                    }
                }
            }
        }
        
        if (isset($class->lifecycleCallbacks[Events::postLoad])) {
            $class->invokeLifecycleCallbacks(Events::postLoad, $entity);
        }
        if ($this->_evm->hasListeners(Events::postLoad)) {
            $this->_evm->dispatchEvent(Events::postLoad, new LifecycleEventArgs($entity));
        }
        

        return $entity;
    }

    /**
     * Gets the identity map of the UnitOfWork.
     *
     * @return array
     */
    public function getIdentityMap()
    {
        return $this->_identityMap;
    }

    /**
     * Gets the original data of an entity. The original data is the data that was
     * present at the time the entity was reconstituted from the database.
     *
     * @param object $entity
     * @return array
     */
    public function getOriginalEntityData($entity)
    {
        $oid = spl_object_hash($entity);
        if (isset($this->_originalEntityData[$oid])) {
            return $this->_originalEntityData[$oid];
        }
        return array();
    }

    /**
     * INTERNAL:
     * For internal purposes only.
     *
     * Sets a property value of the original data array of an entity.
     *
     * @param string $oid
     * @param string $property
     * @param mixed $value
     */
    public function setOriginalEntityProperty($oid, $property, $value)
    {
        $this->_originalEntityData[$oid][$property] = $value;
    }

    /**
     * Gets the identifier of an entity.
     * The returned value is always an array of identifier values. If the entity
     * has a composite identifier then the identifier values are in the same
     * order as the identifier field names as returned by ClassMetadata#getIdentifierFieldNames().
     *
     * @param object $entity
     * @return array The identifier values.
     */
    public function getEntityIdentifier($entity)
    {
        return $this->_entityIdentifiers[spl_object_hash($entity)];
    }

    /**
     * Tries to find an entity with the given identifier in the identity map of
     * this UnitOfWork.
     *
     * @param mixed $id The entity identifier to look for.
     * @param string $rootClassName The name of the root class of the mapped entity hierarchy.
     * @return mixed Returns the entity with the specified identifier if it exists in
     *               this UnitOfWork, FALSE otherwise.
     */
    public function tryGetById($id, $rootClassName)
    {
        $idHash = implode(' ', (array)$id);
        if (isset($this->_identityMap[$rootClassName][$idHash])) {
            return $this->_identityMap[$rootClassName][$idHash];
        }
        return false;
    }

    /**
     * Schedules an entity for dirty-checking at commit-time.
     *
     * @param object $entity The entity to schedule for dirty-checking.
     */
    public function scheduleForDirtyCheck($entity)
    {
        $rootClassName = $this->_em->getClassMetadata(get_class($entity))->rootEntityName;
        $this->_scheduledForDirtyCheck[$rootClassName] = $entity;
    }

    /**
     * Checks whether the UnitOfWork has any pending insertions.
     *
     * @return boolean TRUE if this UnitOfWork has pending insertions, FALSE otherwise.
     */
    public function hasPendingInsertions()
    {
        return ! empty($this->_entityInsertions);
    }

    /**
     * Calculates the size of the UnitOfWork. The size of the UnitOfWork is the
     * number of entities in the identity map.
     *
     * @return integer
     */
    public function size()
    {
        $count = 0;
        foreach ($this->_identityMap as $entitySet) {
            $count += count($entitySet);
        }
        return $count;
    }

    /**
     * Gets the EntityPersister for an Entity.
     *
     * @param string $entityName  The name of the Entity.
     * @return Doctrine\ORM\Persister\AbstractEntityPersister
     */
    public function getEntityPersister($entityName)
    {
        if ( ! isset($this->_persisters[$entityName])) {
            $class = $this->_em->getClassMetadata($entityName);
            if ($class->isInheritanceTypeNone()) {
                $persister = new Persisters\StandardEntityPersister($this->_em, $class);
            } else if ($class->isInheritanceTypeSingleTable()) {
                $persister = new Persisters\SingleTablePersister($this->_em, $class);
            } else if ($class->isInheritanceTypeJoined()) {
                $persister = new Persisters\JoinedSubclassPersister($this->_em, $class);
            } else {
                $persister = new Persisters\UnionSubclassPersister($this->_em, $class);
            }
            $this->_persisters[$entityName] = $persister;
        }
        return $this->_persisters[$entityName];
    }

    /**
     * Gets a collection persister for a collection-valued association.
     *
     * @param AssociationMapping $association
     * @return AbstractCollectionPersister
     */
    public function getCollectionPersister($association)
    {
        $type = get_class($association);
        if ( ! isset($this->_collectionPersisters[$type])) {
            if ($association instanceof Mapping\OneToManyMapping) {
                $persister = new Persisters\OneToManyPersister($this->_em);
            } else if ($association instanceof Mapping\ManyToManyMapping) {
                $persister = new Persisters\ManyToManyPersister($this->_em);
            }
            $this->_collectionPersisters[$type] = $persister;
        }
        return $this->_collectionPersisters[$type];
    }

    /**
     * INTERNAL:
     * Registers an entity as managed.
     *
     * @param object $entity The entity.
     * @param array $id The identifier values.
     * @param array $data The original entity data.
     */
    public function registerManaged($entity, $id, $data)
    {
        $oid = spl_object_hash($entity);
        $this->_entityIdentifiers[$oid] = $id;
        $this->_entityStates[$oid] = self::STATE_MANAGED;
        $this->_originalEntityData[$oid] = $data;
        $this->addToIdentityMap($entity);
    }

    /**
     * INTERNAL:
     * Clears the property changeset of the entity with the given OID.
     *
     * @param string $oid The entity's OID.
     */
    public function clearEntityChangeSet($oid)
    {
        unset($this->_entityChangeSets[$oid]);
    }

    /* PropertyChangedListener implementation */

    /**
     * Notifies this UnitOfWork of a property change in an entity.
     *
     * @param object $entity The entity that owns the property.
     * @param string $propertyName The name of the property that changed.
     * @param mixed $oldValue The old value of the property.
     * @param mixed $newValue The new value of the property.
     */
    public function propertyChanged($entity, $propertyName, $oldValue, $newValue)
    {
        //if ($this->getEntityState($entity) == self::STATE_MANAGED) {
            $oid = spl_object_hash($entity);
            $class = $this->_em->getClassMetadata(get_class($entity));

            $this->_entityChangeSets[$oid][$propertyName] = array($oldValue, $newValue);

            if (isset($class->associationMappings[$propertyName])) {
                $assoc = $class->associationMappings[$propertyName];
                if ($assoc->isOneToOne() && $assoc->isOwningSide) {
                    $this->_entityUpdates[$oid] = $entity;
                } else if ($oldValue instanceof PersistentCollection) {
                    // A PersistentCollection was de-referenced, so delete it.
                    if  ( ! in_array($oldValue, $this->_collectionDeletions, true)) {
                        $this->_collectionDeletions[] = $oldValue;
                    }
                }
            } else {
                $this->_entityUpdates[$oid] = $entity;
            }
        //}
    }
}