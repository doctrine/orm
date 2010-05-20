<?php
/*
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

use Exception,
    Doctrine\Common\Collections\ArrayCollection,
    Doctrine\Common\Collections\Collection,
    Doctrine\Common\NotifyPropertyChanged,
    Doctrine\Common\PropertyChangedListener,
    Doctrine\ORM\Event\LifecycleEventArgs,
    Doctrine\ORM\Proxy\Proxy;

/**
 * The UnitOfWork is responsible for tracking changes to objects during an
 * "object-level" transaction and for writing out changes to the database
 * in the correct order.
 *
 * @since       2.0
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Jonathan Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 * @internal    This class contains highly performance-sensitive code.
 */
class UnitOfWork implements PropertyChangedListener
{
    /**
     * An entity is in MANAGED state when its persistence is managed by an EntityManager.
     */
    const STATE_MANAGED = 1;

    /**
     * An entity is new if it has just been instantiated (i.e. using the "new" operator)
     * and is not (yet) managed by an EntityManager.
     */
    const STATE_NEW = 2;

    /**
     * A detached entity is an instance with a persistent identity that is not
     * (or no longer) associated with an EntityManager (and a UnitOfWork).
     */
    const STATE_DETACHED = 3;

    /**
     * A removed entity instance is an instance with a persistent identity,
     * associated with an EntityManager, whose persistent state has been
     * deleted (or is scheduled for deletion).
     */
    const STATE_REMOVED = 4;

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
     * Map of all identifiers of managed entities.
     * Keys are object ids (spl_object_hash).
     *
     * @var array
     */
    private $_entityIdentifiers = array();

    /**
     * Map of the original entity data of managed entities.
     * Keys are object ids (spl_object_hash). This is used for calculating changesets
     * at commit time.
     *
     * @var array
     * @internal Note that PHPs "copy-on-write" behavior helps a lot with memory usage.
     *           A value will only really be copied if the value in the entity is modified
     *           by the user.
     */
    private $_originalEntityData = array();

    /**
     * Map of entity changes. Keys are object ids (spl_object_hash).
     * Filled at the beginning of a commit of the UnitOfWork and cleaned at the end.
     *
     * @var array
     */
    private $_entityChangeSets = array();

    /**
     * The (cached) states of any known entities.
     * Keys are object ids (spl_object_hash).
     *
     * @var array
     */
    private $_entityStates = array();

    /**
     * Map of entities that are scheduled for dirty checking at commit time.
     * This is only used for entities with a change tracking policy of DEFERRED_EXPLICIT.
     * Keys are object ids (spl_object_hash).
     * 
     * @var array
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
     * Any pending extra updates that have been scheduled by persisters.
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
     * All pending collection updates.
     *
     * @var array
     */
    private $_collectionUpdates = array();

    /**
     * List of collections visited during changeset calculation on a commit-phase of a UnitOfWork.
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
     * EXPERIMENTAL:
     * Flag for whether or not to make use of the C extension.
     *
     * @var boolean
     */
    private $_useCExtension = false;
    
    /**
     * The EventManager used for dispatching events.
     * 
     * @var EventManager
     */
    private $_evm;
    
    /**
     * Orphaned entities that are scheduled for removal.
     * 
     * @var array
     */
    private $_orphanRemovals = array();
    
    //private $_readOnlyObjects = array();

    /**
     * Initializes a new UnitOfWork instance, bound to the given EntityManager.
     *
     * @param Doctrine\ORM\EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->_em = $em;
        $this->_evm = $em->getEventManager();
        $this->_useCExtension = $this->_em->getConfiguration()->getUseCExtension();
    }

    /**
     * Commits the UnitOfWork, executing all operations that have been postponed
     * up to this point. The state of all managed entities will be synchronized with
     * the database.
     * 
     * The operations are executed in the following order:
     * 
     * 1) All entity insertions
     * 2) All entity updates
     * 3) All collection deletions
     * 4) All collection updates
     * 5) All entity deletions
     * 
     */
    public function commit()
    {
        // Compute changes done since last commit.
        $this->computeChangeSets();

        if ( ! ($this->_entityInsertions ||
                $this->_entityDeletions ||
                $this->_entityUpdates ||
                $this->_collectionUpdates ||
                $this->_collectionDeletions ||
                $this->_orphanRemovals)) {
            return; // Nothing to do.
        }

        if ($this->_orphanRemovals) {
            foreach ($this->_orphanRemovals as $orphan) {
                $this->remove($orphan);
            }
        }
        
        // Raise onFlush
        if ($this->_evm->hasListeners(Events::onFlush)) {
            $this->_evm->dispatchEvent(Events::onFlush, new Event\OnFlushEventArgs($this->_em));
        }
        
        // Now we need a commit order to maintain referential integrity
        $commitOrder = $this->_getCommitOrder();

        $conn = $this->_em->getConnection();

        $conn->beginTransaction();
        try {
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

            // Entity deletions come last and need to be in reverse commit order
            if ($this->_entityDeletions) {
                for ($count = count($commitOrder), $i = $count - 1; $i >= 0; --$i) {
                    $this->_executeDeletions($commitOrder[$i]);
                }
            }

            $conn->commit();
        } catch (Exception $e) {
            $this->_em->close();
            $conn->rollback();
            throw $e;
        }

        // Take new snapshots from visited collections
        foreach ($this->_visitedCollections as $coll) {
            $coll->takeSnapshot();
        }

        // Clear up
        $this->_entityInsertions =
        $this->_entityUpdates =
        $this->_entityDeletions =
        $this->_extraUpdates =
        $this->_entityChangeSets =
        $this->_collectionUpdates =
        $this->_collectionDeletions =
        $this->_visitedCollections =
        $this->_scheduledForDirtyCheck =
        $this->_orphanRemovals = array();
    }
    
    /**
     * Executes any extra updates that have been scheduled.
     */
    private function _executeExtraUpdates()
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
     * Computes the changes that happened to a single entity.
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
    public function computeChangeSet(Mapping\ClassMetadata $class, $entity)
    {
        if ( ! $class->isInheritanceTypeNone()) {
            $class = $this->_em->getClassMetadata(get_class($entity));
        }
        
        $oid = spl_object_hash($entity);

        $actualData = array();
        foreach ($class->reflFields as $name => $refProp) {
            if ( ! $class->isIdentifier($name) || ! $class->isIdGeneratorIdentity()) {
                $actualData[$name] = $refProp->getValue($entity);
            }

            if ($class->isCollectionValuedAssociation($name) && $actualData[$name] !== null
                    && ! ($actualData[$name] instanceof PersistentCollection)) {
                // If $actualData[$name] is not a Collection then use an ArrayCollection.
                if ( ! $actualData[$name] instanceof Collection) {
                    $actualData[$name] = new ArrayCollection($actualData[$name]);
                }
                
                $assoc = $class->associationMappings[$name];
                
                // Inject PersistentCollection
                $coll = new PersistentCollection(
                    $this->_em, 
                    $this->_em->getClassMetadata($assoc->targetEntityName), 
                    $actualData[$name]
                );
                
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
                        if ($assoc->isOneToOne()) {
                            if ($assoc->isOwningSide) {
                                $entityIsDirty = true;
                            }
                            if ($actualValue === null && $assoc->orphanRemoval) {
                                $this->scheduleOrphanRemoval($orgValue);
                            }
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
                $this->_entityChangeSets[$oid] = $changeSet;
                $this->_originalEntityData[$oid] = $actualData;

                if ($entityIsDirty) {
                    $this->_entityUpdates[$oid] = $entity;
                }
            }
        }

        // Look for changes in associations of the entity
        foreach ($class->associationMappings as $assoc) {
            $val = $class->reflFields[$assoc->sourceFieldName]->getValue($entity);
            if ($val !== null) {
                $this->_computeAssociationChanges($assoc, $val);
            }
        }
    }

    /**
     * Computes all the changes that have been done to entities and collections
     * since the last commit and stores these changes in the _entityChangeSet map
     * temporarily for access by the persisters, until the UoW commit is finished.
     */
    public function computeChangeSets()
    {
        // Compute changes for INSERTed entities first. This must always happen.
        foreach ($this->_entityInsertions as $entity) {
            $class = $this->_em->getClassMetadata(get_class($entity));
            $this->computeChangeSet($class, $entity);
        }

        // Compute changes for other MANAGED entities. Change tracking policies take effect here.
        foreach ($this->_identityMap as $className => $entities) {
            $class = $this->_em->getClassMetadata($className);

            // Skip class if change tracking happens through notification
            if ($class->isChangeTrackingNotify() /* || $class->isReadOnly*/) {
                continue;
            }

            // If change tracking is explicit, then only compute changes on explicitly persisted entities
            $entitiesToProcess = $class->isChangeTrackingDeferredExplicit() ?
                    (isset($this->_scheduledForDirtyCheck[$className]) ?
                        $this->_scheduledForDirtyCheck[$className] : array())
                    : $entities;

            foreach ($entitiesToProcess as $entity) {
                // Ignore uninitialized proxy objects
                if (/* $entity is readOnly || */ $entity instanceof Proxy && ! $entity->__isInitialized__) {
                    continue;
                }
                // Only MANAGED entities that are NOT SCHEDULED FOR INSERTION are processed here.
                $oid = spl_object_hash($entity);
                if ( ! isset($this->_entityInsertions[$oid]) && isset($this->_entityStates[$oid])) {
                    $this->computeChangeSet($class, $entity);
                }
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

        if ( ! $assoc->isCascadePersist) {
            return; // "Persistence by reachability" only if persist cascade specified
        }
        
        // Look through the entities, and in any of their associations, for transient
        // enities, recursively. ("Persistence by reachability")
        if ($assoc->isOneToOne()) {
            if ($value instanceof Proxy && ! $value->__isInitialized__) {
                return; // Ignore uninitialized proxy objects
            }
            $value = array($value);
        } else if ($value instanceof PersistentCollection) {
            $value = $value->unwrap();
        }
        
        $targetClass = $this->_em->getClassMetadata($assoc->targetEntityName);
        foreach ($value as $entry) {
            $state = $this->getEntityState($entry, self::STATE_NEW);
            $oid = spl_object_hash($entry);
            if ($state == self::STATE_NEW) {
                if (isset($targetClass->lifecycleCallbacks[Events::prePersist])) {
                    $targetClass->invokeLifecycleCallbacks(Events::prePersist, $entry);
                }
                if ($this->_evm->hasListeners(Events::prePersist)) {
                    $this->_evm->dispatchEvent(Events::prePersist, new LifecycleEventArgs($entry, $this->_em));
                }
                
                // Get identifier, if possible (not post-insert)
                $idGen = $targetClass->idGenerator;
                if ( ! $idGen->isPostInsertGenerator()) {
                    $idValue = $idGen->generate($this->_em, $entry);
                    if ( ! $idGen instanceof \Doctrine\ORM\Id\AssignedGenerator) {
                        $this->_entityIdentifiers[$oid] = array($targetClass->identifier[0] => $idValue);
                        $targetClass->getSingleIdReflectionProperty()->setValue($entry, $idValue);
                    } else {
                        $this->_entityIdentifiers[$oid] = $idValue;
                    }
                    $this->addToIdentityMap($entry);
                }
                $this->_entityStates[$oid] = self::STATE_MANAGED;

                // NEW entities are INSERTed within the current unit of work.
                $this->_entityInsertions[$oid] = $entry;

                $this->computeChangeSet($targetClass, $entry);
                
            } else if ($state == self::STATE_REMOVED) {
                throw ORMException::removedEntityInCollectionDetected($entity, $assoc);
            }
            // MANAGED associated entities are already taken into account
            // during changeset calculation anyway, since they are in the identity map.
        }
    }
    
    /**
     * INTERNAL:
     * Computes the changeset of an individual entity, independently of the
     * computeChangeSets() routine that is used at the beginning of a UnitOfWork#commit().
     * 
     * The passed entity must be a managed entity. If the entity already has a change set
     * because this method is invoked during a commit cycle then the change sets are added.
     * whereby changes detected in this method prevail.
     * 
     * @ignore
     * @param ClassMetadata $class The class descriptor of the entity.
     * @param object $entity The entity for which to (re)calculate the change set.
     * @throws InvalidArgumentException If the passed entity is not MANAGED.
     */
    public function recomputeSingleEntityChangeSet($class, $entity)
    {
        $oid = spl_object_hash($entity);
        
        if ( ! isset($this->_entityStates[$oid]) || $this->_entityStates[$oid] != self::STATE_MANAGED) {
            throw new \InvalidArgumentException('Entity must be managed.');
        }
        
        /* TODO: Just return if changetracking policy is NOTIFY?
        if ($class->isChangeTrackingNotify()) {
            return;
        }*/

        if ( ! $class->isInheritanceTypeNone()) {
            $class = $this->_em->getClassMetadata(get_class($entity));
        }

        $actualData = array();
        foreach ($class->reflFields as $name => $refProp) {
            if ( ! $class->isIdentifier($name) || ! $class->isIdGeneratorIdentity()) {
                $actualData[$name] = $refProp->getValue($entity);
            }
        }

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
            if (isset($this->_entityChangeSets[$oid])) {
                $this->_entityChangeSets[$oid] = $changeSet + $this->_entityChangeSets[$oid];
            }
            $this->_originalEntityData[$oid] = $actualData;
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
        
        $hasLifecycleCallbacks = isset($class->lifecycleCallbacks[Events::postPersist]);
        $hasListeners = $this->_evm->hasListeners(Events::postPersist);
        if ($hasLifecycleCallbacks || $hasListeners) {
            $entities = array();
        }
        
        foreach ($this->_entityInsertions as $oid => $entity) {
            if (get_class($entity) === $className) {
                $persister->addInsert($entity);
                unset($this->_entityInsertions[$oid]);
                if ($hasLifecycleCallbacks || $hasListeners) {
                    $entities[] = $entity;
                }
            }
        }

        $postInsertIds = $persister->executeInserts();

        if ($postInsertIds) {
            // Persister returned post-insert IDs
            foreach ($postInsertIds as $id => $entity) {
                $oid = spl_object_hash($entity);
                $idField = $class->identifier[0];
                $class->reflFields[$idField]->setValue($entity, $id);
                $this->_entityIdentifiers[$oid] = array($idField => $id);
                $this->_entityStates[$oid] = self::STATE_MANAGED;
                $this->_originalEntityData[$oid][$idField] = $id;
                $this->addToIdentityMap($entity);
            }
        }
        
        if ($hasLifecycleCallbacks || $hasListeners) {
            foreach ($entities as $entity) {
                if ($hasLifecycleCallbacks) {
                    $class->invokeLifecycleCallbacks(Events::postPersist, $entity);
                }
                if ($hasListeners) {
                    $this->_evm->dispatchEvent(Events::postPersist, new LifecycleEventArgs($entity, $this->_em));
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
            if (get_class($entity) == $className || $entity instanceof Proxy && $entity instanceof $className) {
                
                if ($hasPreUpdateLifecycleCallbacks) {
                    $class->invokeLifecycleCallbacks(Events::preUpdate, $entity);
                    $this->recomputeSingleEntityChangeSet($class, $entity);
                }
                
                if ($hasPreUpdateListeners) {
                    $this->_evm->dispatchEvent(Events::preUpdate, new Event\PreUpdateEventArgs(
                        $entity, $this->_em, $this->_entityChangeSets[$oid])
                    );
                }

                $persister->update($entity);
                unset($this->_entityUpdates[$oid]);
                
                if ($hasPostUpdateLifecycleCallbacks) {
                    $class->invokeLifecycleCallbacks(Events::postUpdate, $entity);
                }
                if ($hasPostUpdateListeners) {
                    $this->_evm->dispatchEvent(Events::postUpdate, new LifecycleEventArgs($entity, $this->_em));
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
                
        $hasLifecycleCallbacks = isset($class->lifecycleCallbacks[Events::postRemove]);
        $hasListeners = $this->_evm->hasListeners(Events::postRemove);
        
        foreach ($this->_entityDeletions as $oid => $entity) {
            if (get_class($entity) == $className || $entity instanceof Proxy && $entity instanceof $className) {
                $persister->delete($entity);
                unset(
                    $this->_entityDeletions[$oid],
                    $this->_entityIdentifiers[$oid],
                    $this->_originalEntityData[$oid]
                    );
                // Entity with this $oid after deletion treated as NEW, even if the $oid
                // is obtained by a new entity because the old one went out of scope.
                $this->_entityStates[$oid] = self::STATE_NEW;
                
                if ($hasLifecycleCallbacks) {
                    $class->invokeLifecycleCallbacks(Events::postRemove, $entity);
                }
                if ($hasListeners) {
                    $this->_evm->dispatchEvent(Events::postRemove, new LifecycleEventArgs($entity, $this->_em));
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
        
        $calc = $this->getCommitOrderCalculator();
        
        // See if there are any new classes in the changeset, that are not in the
        // commit order graph yet (dont have a node).
        $newNodes = array();
        foreach ($entityChangeSet as $oid => $entity) {
            $className = get_class($entity);         
            if ( ! $calc->hasClass($className)) {
                $class = $this->_em->getClassMetadata($className);
                $calc->addClass($class);
                $newNodes[] = $class;
            }
        }

        // Calculate dependencies for new nodes
        foreach ($newNodes as $class) {
            foreach ($class->associationMappings as $assoc) {
                if ($assoc->isOwningSide && $assoc->isOneToOne()) {
                    $targetClass = $this->_em->getClassMetadata($assoc->targetEntityName);
                    if ( ! $calc->hasClass($targetClass->name)) {
                        $calc->addClass($targetClass);
                    }
                    $calc->addDependency($targetClass, $class);
                }
            }
        }

        return $calc->getCommitOrder();
    }

    /**
     * Schedules an entity for insertion into the database.
     * If the entity already has an identifier, it will be added to the identity map.
     *
     * @param object $entity The entity to schedule for insertion.
     */
    public function scheduleForInsert($entity)
    {
        $oid = spl_object_hash($entity);

        if (isset($this->_entityUpdates[$oid])) {
            throw new \InvalidArgumentException("Dirty entity can not be scheduled for insertion.");
        }
        if (isset($this->_entityDeletions[$oid])) {
            throw new \InvalidArgumentException("Removed entity can not be scheduled for insertion.");
        }
        if (isset($this->_entityInsertions[$oid])) {
            throw new \InvalidArgumentException("Entity can not be scheduled for insertion twice.");
        }

        $this->_entityInsertions[$oid] = $entity;

        if (isset($this->_entityIdentifiers[$oid])) {
            $this->addToIdentityMap($entity);
        }
    }

    /**
     * Checks whether an entity is scheduled for insertion.
     *
     * @param object $entity
     * @return boolean
     */
    public function isScheduledForInsert($entity)
    {
        return isset($this->_entityInsertions[spl_object_hash($entity)]);
    }

    /**
     * Schedules an entity for being updated.
     *
     * @param object $entity The entity to schedule for being updated.
     */
    public function scheduleForUpdate($entity)
    {
        $oid = spl_object_hash($entity);
        if ( ! isset($this->_entityIdentifiers[$oid])) {
            throw new \InvalidArgumentException("Entity has no identity.");
        }
        if (isset($this->_entityDeletions[$oid])) {
            throw new \InvalidArgumentException("Entity is removed.");
        }

        if ( ! isset($this->_entityUpdates[$oid]) && ! isset($this->_entityInsertions[$oid])) {
            $this->_entityUpdates[$oid] = $entity;
        }
    }
    
    /**
     * INTERNAL:
     * Schedules an extra update that will be executed immediately after the
     * regular entity updates within the currently running commit cycle.
     * 
     * Extra updates for entities are stored as (entity, changeset) tuples.
     * 
     * @ignore
     * @param object $entity The entity for which to schedule an extra update.
     * @param array $changeset The changeset of the entity (what to update).
     */
    public function scheduleExtraUpdate($entity, array $changeset)
    {
        $oid = spl_object_hash($entity);
        if (isset($this->_extraUpdates[$oid])) {
            list($ignored, $changeset2) = $this->_extraUpdates[$oid];
            $this->_extraUpdates[$oid] = array($entity, $changeset + $changeset2);
        } else {
            $this->_extraUpdates[$oid] = array($entity, $changeset);
        }
    }

    /**
     * Checks whether an entity is registered as dirty in the unit of work.
     * Note: Is not very useful currently as dirty entities are only registered
     * at commit time.
     *
     * @param object $entity
     * @return boolean
     */
    public function isScheduledForUpdate($entity)
    {
        return isset($this->_entityUpdates[spl_object_hash($entity)]);
    }

    /**
     * INTERNAL:
     * Schedules an entity for deletion.
     * 
     * @param object $entity
     */
    public function scheduleForDelete($entity)
    {
        $oid = spl_object_hash($entity);
        
        if (isset($this->_entityInsertions[$oid])) {
            if ($this->isInIdentityMap($entity)) {
                $this->removeFromIdentityMap($entity);
            }
            unset($this->_entityInsertions[$oid]);
            return; // entity has not been persisted yet, so nothing more to do.
        }

        if ( ! $this->isInIdentityMap($entity)) {
            return; // ignore
        }

        $this->removeFromIdentityMap($entity);

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
     */
    public function isScheduledForDelete($entity)
    {
        return isset($this->_entityDeletions[spl_object_hash($entity)]);
    }

    /**
     * Checks whether an entity is scheduled for insertion, update or deletion.
     * 
     * @param $entity
     * @return boolean
     */
    public function isEntityScheduled($entity)
    {
        $oid = spl_object_hash($entity);
        return isset($this->_entityInsertions[$oid]) ||
                isset($this->_entityUpdates[$oid]) ||
                isset($this->_entityDeletions[$oid]);
    }

    /**
     * INTERNAL:
     * Registers an entity in the identity map.
     * Note that entities in a hierarchy are registered with the class name of
     * the root entity.
     *
     * @ignore
     * @param object $entity  The entity to register.
     * @return boolean  TRUE if the registration was successful, FALSE if the identity of
     *                  the entity in question is already managed.
     */
    public function addToIdentityMap($entity)
    {
        $classMetadata = $this->_em->getClassMetadata(get_class($entity));
        $idHash = implode(' ', $this->_entityIdentifiers[spl_object_hash($entity)]);
        if ($idHash === '') {
            throw new \InvalidArgumentException("The given entity has no identity.");
        }
        $className = $classMetadata->rootEntityName;
        if (isset($this->_identityMap[$className][$idHash])) {
            return false;
        }
        $this->_identityMap[$className][$idHash] = $entity;
        if ($entity instanceof NotifyPropertyChanged) {
            $entity->addPropertyChangedListener($this);
        }
        return true;
    }

    /**
     * Gets the state of an entity within the current unit of work.
     * 
     * NOTE: This method sees entities that are not MANAGED or REMOVED and have a
     *       populated identifier, whether it is generated or manually assigned, as
     *       DETACHED. This can be incorrect for manually assigned identifiers.
     *
     * @param object $entity
     * @param integer $assume The state to assume if the state is not yet known. This is usually
     *                        used to avoid costly state lookups, in the worst case with a database
     *                        lookup.
     * @return int The entity state.
     */
    public function getEntityState($entity, $assume = null)
    {
        $oid = spl_object_hash($entity);
        if ( ! isset($this->_entityStates[$oid])) {
            // State can only be NEW or DETACHED, because MANAGED/REMOVED states are immediately
            // set by the UnitOfWork directly. We treat all entities that have a populated
            // identifier as DETACHED and all others as NEW. This is not really correct for
            // manually assigned identifiers but in that case we would need to hit the database
            // and we would like to avoid that.
            if ($assume === null) {
                if ($this->_em->getClassMetadata(get_class($entity))->getIdentifierValues($entity)) {
                    $this->_entityStates[$oid] = self::STATE_DETACHED;
                } else {
                    $this->_entityStates[$oid] = self::STATE_NEW;
                }
            } else {
                $this->_entityStates[$oid] = $assume;
            }
        }
        return $this->_entityStates[$oid];
    }

    /**
     * INTERNAL:
     * Removes an entity from the identity map. This effectively detaches the
     * entity from the persistence management of Doctrine.
     *
     * @ignore
     * @param object $entity
     * @return boolean
     */
    public function removeFromIdentityMap($entity)
    {
        $oid = spl_object_hash($entity);
        $classMetadata = $this->_em->getClassMetadata(get_class($entity));
        $idHash = implode(' ', $this->_entityIdentifiers[$oid]);
        if ($idHash === '') {
            throw new \InvalidArgumentException("The given entity has no identity.");
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
     * INTERNAL:
     * Gets an entity in the identity map by its identifier hash.
     *
     * @ignore
     * @param string $idHash
     * @param string $rootClassName
     * @return object
     */
    public function getByIdHash($idHash, $rootClassName)
    {
        return $this->_identityMap[$rootClassName][$idHash];
    }

    /**
     * INTERNAL:
     * Tries to get an entity by its identifier hash. If no entity is found for
     * the given hash, FALSE is returned.
     *
     * @ignore
     * @param string $idHash
     * @param string $rootClassName
     * @return mixed The found entity or FALSE.
     */
    public function tryGetByIdHash($idHash, $rootClassName)
    {
        return isset($this->_identityMap[$rootClassName][$idHash]) ?
                $this->_identityMap[$rootClassName][$idHash] : false;
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
     * INTERNAL:
     * Checks whether an identifier hash exists in the identity map.
     *
     * @ignore
     * @param string $idHash
     * @param string $rootClassName
     * @return boolean
     */
    public function containsIdHash($idHash, $rootClassName)
    {
        return isset($this->_identityMap[$rootClassName][$idHash]);
    }

    /**
     * Persists an entity as part of the current unit of work.
     *
     * @param object $entity The entity to persist.
     */
    public function persist($entity)
    {
        $visited = array();
        $this->_doPersist($entity, $visited);
    }

    /**
     * Saves an entity as part of the current unit of work.
     * This method is internally called during save() cascades as it tracks
     * the already visited entities to prevent infinite recursions.
     * 
     * NOTE: This method always considers entities that are not yet known to
     * this UnitOfWork as NEW.
     *
     * @param object $entity The entity to persist.
     * @param array $visited The already visited entities.
     */
    private function _doPersist($entity, array &$visited)
    {
        $oid = spl_object_hash($entity);
        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $entity; // Mark visited

        $class = $this->_em->getClassMetadata(get_class($entity));
        $entityState = $this->getEntityState($entity, self::STATE_NEW);
        
        switch ($entityState) {
            case self::STATE_MANAGED:
                // Nothing to do, except if policy is "deferred explicit"
                if ($class->isChangeTrackingDeferredExplicit()) {
                    $this->scheduleForDirtyCheck($entity);
                }
                break;
            case self::STATE_NEW:
                if (isset($class->lifecycleCallbacks[Events::prePersist])) {
                    $class->invokeLifecycleCallbacks(Events::prePersist, $entity);
                }
                if ($this->_evm->hasListeners(Events::prePersist)) {
                    $this->_evm->dispatchEvent(Events::prePersist, new LifecycleEventArgs($entity, $this->_em));
                }
                
                $idGen = $class->idGenerator;
                if ( ! $idGen->isPostInsertGenerator()) {
                    $idValue = $idGen->generate($this->_em, $entity);
                    if ( ! $idGen instanceof \Doctrine\ORM\Id\AssignedGenerator) {
                        $this->_entityIdentifiers[$oid] = array($class->identifier[0] => $idValue);
                        $class->setIdentifierValues($entity, $idValue);
                    } else {
                        $this->_entityIdentifiers[$oid] = $idValue;
                    }
                }
                $this->_entityStates[$oid] = self::STATE_MANAGED;
                
                $this->scheduleForInsert($entity);
                break;
            case self::STATE_DETACHED:
                throw new \InvalidArgumentException(
                        "Behavior of persist() for a detached entity is not yet defined.");
            case self::STATE_REMOVED:
                // Entity becomes managed again
                if ($this->isScheduledForDelete($entity)) {
                    unset($this->_entityDeletions[$oid]);
                } else {
                    //FIXME: There's more to think of here...
                    $this->scheduleForInsert($entity);
                }
                break;
            default:
                throw ORMException::invalidEntityState($entityState);
        }
        
        $this->_cascadePersist($entity, $visited);
    }

    /**
     * Deletes an entity as part of the current unit of work.
     *
     * @param object $entity The entity to remove.
     */
    public function remove($entity)
    {
        $visited = array();
        $this->_doRemove($entity, $visited);
    }

    /**
     * Deletes an entity as part of the current unit of work.
     *
     * This method is internally called during delete() cascades as it tracks
     * the already visited entities to prevent infinite recursions.
     *
     * @param object $entity The entity to delete.
     * @param array $visited The map of the already visited entities.
     * @throws InvalidArgumentException If the instance is a detached entity.
     */
    private function _doRemove($entity, array &$visited)
    {
        $oid = spl_object_hash($entity);
        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $entity; // mark visited

        $class = $this->_em->getClassMetadata(get_class($entity));
        $entityState = $this->getEntityState($entity);
        switch ($entityState) {
            case self::STATE_NEW:
            case self::STATE_REMOVED:
                // nothing to do
                break;
            case self::STATE_MANAGED:
                if (isset($class->lifecycleCallbacks[Events::preRemove])) {
                    $class->invokeLifecycleCallbacks(Events::preRemove, $entity);
                }
                if ($this->_evm->hasListeners(Events::preRemove)) {
                    $this->_evm->dispatchEvent(Events::preRemove, new LifecycleEventArgs($entity, $this->_em));
                }
                $this->scheduleForDelete($entity);
                break;
            case self::STATE_DETACHED:
                throw ORMException::detachedEntityCannotBeRemoved();
            default:
                throw ORMException::invalidEntityState($entityState);
        }

        $this->_cascadeRemove($entity, $visited);
    }

    /**
     * Merges the state of the given detached entity into this UnitOfWork.
     *
     * @param object $entity
     * @return object The managed copy of the entity.
     * @throws OptimisticLockException If the entity uses optimistic locking through a version
     *         attribute and the version check against the managed copy fails.
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
     * @throws OptimisticLockException If the entity uses optimistic locking through a version
     *         attribute and the version check against the managed copy fails.
     * @throws InvalidArgumentException If the entity instance is NEW.
     */
    private function _doMerge($entity, array &$visited, $prevManagedCopy = null, $assoc = null)
    {
        $class = $this->_em->getClassMetadata(get_class($entity));
        $id = $class->getIdentifierValues($entity);

        if ( ! $id) {
            throw new \InvalidArgumentException('New entity detected during merge.'
                    . ' Persist the new entity before merging.');
        }

        // MANAGED entities are ignored by the merge operation
        if ($this->getEntityState($entity, self::STATE_DETACHED) == self::STATE_MANAGED) {
            $managedCopy = $entity;
        } else {
            // Try to look the entity up in the identity map.
            $managedCopy = $this->tryGetById($id, $class->rootEntityName);
            if ($managedCopy) {
                // We have the entity in-memory already, just make sure its not removed.
                if ($this->getEntityState($managedCopy) == self::STATE_REMOVED) {
                    throw new \InvalidArgumentException('Removed entity detected during merge.'
                            . ' Can not merge with a removed entity.');
                }
            } else {
                // We need to fetch the managed copy in order to merge.
                $managedCopy = $this->_em->find($class->name, $id);
            }

            if ($managedCopy === null) {
                throw new \InvalidArgumentException('New entity detected during merge.'
                        . ' Persist the new entity before merging.');
            }

            if ($class->isVersioned) {
                $managedCopyVersion = $class->reflFields[$class->versionField]->getValue($managedCopy);
                $entityVersion = $class->reflFields[$class->versionField]->getValue($entity);
                // Throw exception if versions dont match.
                if ($managedCopyVersion != $entityVersion) {
                    throw OptimisticLockException::lockFailed($entity);
                }
            }

            // Merge state of $entity into existing (managed) entity
            foreach ($class->reflFields as $name => $prop) {
                if ( ! isset($class->associationMappings[$name])) {
                    $prop->setValue($managedCopy, $prop->getValue($entity));
                } else {
                    $assoc2 = $class->associationMappings[$name];
                    if ($assoc2->isOneToOne()) {
                        if ( ! $assoc2->isCascadeMerge) {
                            $other = $class->reflFields[$name]->getValue($entity); //TODO: Just $prop->getValue($entity)?
                            if ($other !== null) {
                                $targetClass = $this->_em->getClassMetadata($assoc2->targetEntityName);
                                $id = $targetClass->getIdentifierValues($other);
                                $proxy = $this->_em->getProxyFactory()->getProxy($assoc2->targetEntityName, $id);
                                $prop->setValue($managedCopy, $proxy);
                                $this->registerManaged($proxy, $id, array());
                            }
                        }
                    } else {
                        $coll = new PersistentCollection($this->_em,
                                $this->_em->getClassMetadata($assoc2->targetEntityName),
                                new ArrayCollection
                                );
                        $coll->setOwner($managedCopy, $assoc2);
                        $coll->setInitialized($assoc2->isCascadeMerge);
                        $prop->setValue($managedCopy, $coll);
                    }
                }
                if ($class->isChangeTrackingNotify()) {
                    //TODO: put changed fields in changeset...?
                }
            }
            if ($class->isChangeTrackingDeferredExplicit()) {
                //TODO: Mark $managedCopy for dirty check...? ($this->_scheduledForDirtyCheck)
            }
        }

        if ($prevManagedCopy !== null) {
            $assocField = $assoc->sourceFieldName;
            $prevClass = $this->_em->getClassMetadata(get_class($prevManagedCopy));
            if ($assoc->isOneToOne()) {
                $prevClass->reflFields[$assocField]->setValue($prevManagedCopy, $managedCopy);
                //TODO: What about back-reference if bidirectional?
            } else {
                $prevClass->reflFields[$assocField]->getValue($prevManagedCopy)->unwrap()->add($managedCopy);
                if ($assoc->isOneToMany()) {
                    $class->reflFields[$assoc->mappedBy]->setValue($managedCopy, $prevManagedCopy);
                }
            }
        }

        $this->_cascadeMerge($entity, $managedCopy, $visited);

        return $managedCopy;
    }
    
    /**
     * Detaches an entity from the persistence management. It's persistence will
     * no longer be managed by Doctrine.
     *
     * @param object $entity The entity to detach.
     */
    public function detach($entity)
    {
        $visited = array();
        $this->_doDetach($entity, $visited);
    }
    
    /**
     * Executes a detach operation on the given entity.
     * 
     * @param object $entity
     * @param array $visited
     * @internal This method always considers entities with an assigned identifier as DETACHED.
     */
    private function _doDetach($entity, array &$visited)
    {
        $oid = spl_object_hash($entity);
        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $entity; // mark visited
        
        switch ($this->getEntityState($entity, self::STATE_DETACHED)) {
            case self::STATE_MANAGED:
                $this->removeFromIdentityMap($entity);
                unset($this->_entityInsertions[$oid], $this->_entityUpdates[$oid],
                        $this->_entityDeletions[$oid], $this->_entityIdentifiers[$oid],
                        $this->_entityStates[$oid], $this->_originalEntityData[$oid]);
                break;
            case self::STATE_NEW:
            case self::STATE_DETACHED:
                return;
        }
        
        $this->_cascadeDetach($entity, $visited);
    }
    
    /**
     * Refreshes the state of the given entity from the database, overwriting
     * any local, unpersisted changes.
     * 
     * @param object $entity The entity to refresh.
     * @throws InvalidArgumentException If the entity is not MANAGED.
     */
    public function refresh($entity)
    {
        $visited = array();
        $this->_doRefresh($entity, $visited);
    }
    
    /**
     * Executes a refresh operation on an entity.
     * 
     * @param object $entity The entity to refresh.
     * @param array $visited The already visited entities during cascades.
     * @throws InvalidArgumentException If the entity is not MANAGED.
     */
    private function _doRefresh($entity, array &$visited)
    {
        $oid = spl_object_hash($entity);
        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $entity; // mark visited

        $class = $this->_em->getClassMetadata(get_class($entity));
        if ($this->getEntityState($entity) == self::STATE_MANAGED) {
            $this->getEntityPersister($class->name)->refresh(
                array_combine($class->getIdentifierColumnNames(), $this->_entityIdentifiers[$oid]),
                $entity
            );
        } else {
            throw new \InvalidArgumentException("Entity is not MANAGED.");
        }
        
        $this->_cascadeRefresh($entity, $visited);
    }
    
    /**
     * Cascades a refresh operation to associated entities.
     *
     * @param object $entity
     * @param array $visited
     */
    private function _cascadeRefresh($entity, array &$visited)
    {
        $class = $this->_em->getClassMetadata(get_class($entity));
        foreach ($class->associationMappings as $assoc) {
            if ( ! $assoc->isCascadeRefresh) {
                continue;
            }
            $relatedEntities = $class->reflFields[$assoc->sourceFieldName]->getValue($entity);
            if ($relatedEntities instanceof Collection) {
                if ($relatedEntities instanceof PersistentCollection) {
                    // Unwrap so that foreach() does not initialize
                    $relatedEntities = $relatedEntities->unwrap();
                }
                foreach ($relatedEntities as $relatedEntity) {
                    $this->_doRefresh($relatedEntity, $visited);
                }
            } else if ($relatedEntities !== null) {
                $this->_doRefresh($relatedEntities, $visited);
            }
        }
    }
    
    /**
     * Cascades a detach operation to associated entities.
     *
     * @param object $entity
     * @param array $visited
     */
    private function _cascadeDetach($entity, array &$visited)
    {
        $class = $this->_em->getClassMetadata(get_class($entity));
        foreach ($class->associationMappings as $assoc) {
            if ( ! $assoc->isCascadeDetach) {
                continue;
            }
            $relatedEntities = $class->reflFields[$assoc->sourceFieldName]->getValue($entity);
            if ($relatedEntities instanceof Collection) {
                if ($relatedEntities instanceof PersistentCollection) {
                    // Unwrap so that foreach() does not initialize
                    $relatedEntities = $relatedEntities->unwrap();
                }
                foreach ($relatedEntities as $relatedEntity) {
                    $this->_doDetach($relatedEntity, $visited);
                }
            } else if ($relatedEntities !== null) {
                $this->_doDetach($relatedEntities, $visited);
            }
        }
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
        foreach ($class->associationMappings as $assoc) {
            if ( ! $assoc->isCascadeMerge) {
                continue;
            }
            $relatedEntities = $class->reflFields[$assoc->sourceFieldName]->getValue($entity);
            if ($relatedEntities instanceof Collection) {
                if ($relatedEntities instanceof PersistentCollection) {
                    // Unwrap so that foreach() does not initialize
                    $relatedEntities = $relatedEntities->unwrap();
                }
                foreach ($relatedEntities as $relatedEntity) {
                    $this->_doMerge($relatedEntity, $visited, $managedCopy, $assoc);
                }
            } else if ($relatedEntities !== null) {
                $this->_doMerge($relatedEntities, $visited, $managedCopy, $assoc);
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
    private function _cascadePersist($entity, array &$visited)
    {
        $class = $this->_em->getClassMetadata(get_class($entity));
        foreach ($class->associationMappings as $assoc) {
            if ( ! $assoc->isCascadePersist) {
                continue;
            }
            $relatedEntities = $class->reflFields[$assoc->sourceFieldName]->getValue($entity);
            if (($relatedEntities instanceof Collection || is_array($relatedEntities))) {
                if ($relatedEntities instanceof PersistentCollection) {
                    // Unwrap so that foreach() does not initialize
                    $relatedEntities = $relatedEntities->unwrap();
                }
                foreach ($relatedEntities as $relatedEntity) {
                    $this->_doPersist($relatedEntity, $visited);
                }
            } else if ($relatedEntities !== null) {
                $this->_doPersist($relatedEntities, $visited);
            }
        }
    }

    /**
     * Cascades the delete operation to associated entities.
     *
     * @param object $entity
     * @param array $visited
     */
    private function _cascadeRemove($entity, array &$visited)
    {
        $class = $this->_em->getClassMetadata(get_class($entity));
        foreach ($class->associationMappings as $assoc) {
            if ( ! $assoc->isCascadeRemove) {
                continue;
            }
            //TODO: If $entity instanceof Proxy => Initialize ?
            $relatedEntities = $class->reflFields[$assoc->sourceFieldName]->getValue($entity);
            if ($relatedEntities instanceof Collection || is_array($relatedEntities)) {
                // If its a PersistentCollection initialization is intended! No unwrap!
                foreach ($relatedEntities as $relatedEntity) {
                    $this->_doRemove($relatedEntity, $visited);
                }
            } else if ($relatedEntities !== null) {
                $this->_doRemove($relatedEntities, $visited);
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
        if ($this->_commitOrderCalculator === null) {
            $this->_commitOrderCalculator = new Internal\CommitOrderCalculator;
        }
        return $this->_commitOrderCalculator;
    }

    /**
     * Clears the UnitOfWork.
     */
    public function clear()
    {
        $this->_identityMap =
        $this->_entityIdentifiers =
        $this->_originalEntityData =
        $this->_entityChangeSets =
        $this->_entityStates =
        $this->_scheduledForDirtyCheck =
        $this->_entityInsertions =
        $this->_entityUpdates =
        $this->_entityDeletions =
        $this->_collectionDeletions =
        $this->_collectionUpdates =
        $this->_extraUpdates =
        $this->_orphanRemovals = array();
        if ($this->_commitOrderCalculator !== null) {
            $this->_commitOrderCalculator->clear();
        }
    }
    
    /**
     * INTERNAL:
     * Schedules an orphaned entity for removal. The remove() operation will be
     * invoked on that entity at the beginning of the next commit of this
     * UnitOfWork.
     * 
     * @ignore
     * @param object $entity
     */
    public function scheduleOrphanRemoval($entity)
    {
        $this->_orphanRemovals[spl_object_hash($entity)] = $entity;
    }
    
    /**
     * INTERNAL:
     * Schedules a complete collection for removal when this UnitOfWork commits.
     *
     * @param PersistentCollection $coll
     */
    public function scheduleCollectionDeletion(PersistentCollection $coll)
    {
        //TODO: if $coll is already scheduled for recreation ... what to do?
        // Just remove $coll from the scheduled recreations?
        $this->_collectionDeletions[] = $coll;
    }

    public function isCollectionScheduledForDeletion(PersistentCollection $coll)
    {
        return in_array($coll, $this->_collectionsDeletions, true);
    }

    /**
     * INTERNAL:
     * Creates an entity. Used for reconstitution of entities during hydration.
     *
     * @ignore
     * @param string $className The name of the entity class.
     * @param array $data The data for the entity.
     * @param array $hints Any hints to account for during reconstitution/lookup of the entity.
     * @return object The entity instance.
     * @internal Highly performance-sensitive method.
     * 
     * @todo Rename: getOrCreateEntity
     */
    public function createEntity($className, array $data, &$hints = array())
    {
        $class = $this->_em->getClassMetadata($className);
        //$isReadOnly = isset($hints[Query::HINT_READ_ONLY]);

        if ($class->isIdentifierComposite) {
            $id = array();
            foreach ($class->identifier as $fieldName) {
                $id[$fieldName] = $data[$fieldName];
            }
            $idHash = implode(' ', $id);
        } else {
            $idHash = $data[$class->identifier[0]];
            $id = array($class->identifier[0] => $idHash);
        }

        if (isset($this->_identityMap[$class->rootEntityName][$idHash])) {
            $entity = $this->_identityMap[$class->rootEntityName][$idHash];
            $oid = spl_object_hash($entity);
            if ($entity instanceof Proxy && ! $entity->__isInitialized__) {
                $entity->__isInitialized__ = true;
                $overrideLocalValues = true;
            } else {
                $overrideLocalValues = isset($hints[Query::HINT_REFRESH]);
            }
        } else {
            $entity = $class->newInstance();
            $oid = spl_object_hash($entity);
            $this->_entityIdentifiers[$oid] = $id;
            $this->_entityStates[$oid] = self::STATE_MANAGED;
            $this->_originalEntityData[$oid] = $data;
            $this->_identityMap[$class->rootEntityName][$idHash] = $entity;
            if ($entity instanceof NotifyPropertyChanged) {
                $entity->addPropertyChangedListener($this);
            }
            $overrideLocalValues = true;
        }

        if ($overrideLocalValues) {
            if ($this->_useCExtension) {
                doctrine_populate_data($entity, $data);
            } else {
                foreach ($data as $field => $value) {
                    if (isset($class->reflFields[$field])) {
                        $class->reflFields[$field]->setValue($entity, $value);
                    }
                }
            }
            
            // Properly initialize any unfetched associations, if partial objects are not allowed.
            if ( ! isset($hints[Query::HINT_FORCE_PARTIAL_LOAD])) {
                foreach ($class->associationMappings as $field => $assoc) {
                    // Check if the association is not among the fetch-joined associations already.
                    if (isset($hints['fetched'][$className][$field])) {
                        continue;
                    }

                    $targetClass = $this->_em->getClassMetadata($assoc->targetEntityName);

                    if ($assoc->isOneToOne()) {
                        if ($assoc->isOwningSide) {
                            $associatedId = array();
                            foreach ($assoc->targetToSourceKeyColumns as $targetColumn => $srcColumn) {
                                $joinColumnValue = $data[$srcColumn];
                                if ($joinColumnValue !== null) {
                                    $associatedId[$targetClass->fieldNames[$targetColumn]] = $joinColumnValue;
                                }
                            }
                            if ( ! $associatedId) {
                                // Foreign key is NULL
                                $class->reflFields[$field]->setValue($entity, null);
                                $this->_originalEntityData[$oid][$field] = null;
                            } else {
                                // Foreign key is set
                                // Check identity map first
                                // FIXME: Can break easily with composite keys if join column values are in
                                //        wrong order. The correct order is the one in ClassMetadata#identifier.
                                $relatedIdHash = implode(' ', $associatedId);
                                if (isset($this->_identityMap[$targetClass->rootEntityName][$relatedIdHash])) {
                                    $newValue = $this->_identityMap[$targetClass->rootEntityName][$relatedIdHash];
                                } else {
                                    if ($targetClass->subClasses) {
                                        // If it might be a subtype, it can not be lazy
                                        $newValue = $assoc->load($entity, null, $this->_em, $associatedId);
                                    } else {
                                        $newValue = $this->_em->getProxyFactory()->getProxy($assoc->targetEntityName, $associatedId);
                                        // PERF: Inlined & optimized code from UnitOfWork#registerManaged()
                                        $newValueOid = spl_object_hash($newValue);
                                        $this->_entityIdentifiers[$newValueOid] = $associatedId;
                                        $this->_identityMap[$targetClass->rootEntityName][$relatedIdHash] = $newValue;
                                        $this->_entityStates[$newValueOid] = self::STATE_MANAGED;
                                    }
                                }
                                $this->_originalEntityData[$oid][$field] = $newValue;
                                $class->reflFields[$field]->setValue($entity, $newValue);
                            }
                        } else {
                            // Inverse side of x-to-one can never be lazy
                            $class->reflFields[$field]->setValue($entity, $assoc->load($entity, null, $this->_em));
                        }
                    } else {
                        // Inject collection
                        $reflField = $class->reflFields[$field];
                        $pColl = new PersistentCollection(
                            $this->_em, $targetClass,
                            //TODO: getValue might be superfluous once DDC-79 is implemented. 
                            $reflField->getValue($entity) ?: new ArrayCollection
                        );
                        $pColl->setOwner($entity, $assoc);
                        $reflField->setValue($entity, $pColl);
                        if ($assoc->isLazilyFetched()) {
                            $pColl->setInitialized(false);
                        } else {
                            $assoc->load($entity, $pColl, $this->_em);
                        }
                        $this->_originalEntityData[$oid][$field] = $pColl;
                    }
                }
            }
        }
        
        //TODO: These should be invoked later, after hydration, because associations may not yet be loaded here.
        if (isset($class->lifecycleCallbacks[Events::postLoad])) {
            $class->invokeLifecycleCallbacks(Events::postLoad, $entity);
        }
        if ($this->_evm->hasListeners(Events::postLoad)) {
            $this->_evm->dispatchEvent(Events::postLoad, new LifecycleEventArgs($entity, $this->_em));
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
     * @ignore
     */
    public function setOriginalEntityData($entity, array $data)
    {
        $this->_originalEntityData[spl_object_hash($entity)] = $data;
    }

    /**
     * INTERNAL:
     * Sets a property value of the original data array of an entity.
     *
     * @ignore
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
        $idHash = implode(' ', (array) $id);
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
        $this->_scheduledForDirtyCheck[$rootClassName][] = $entity;
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
                $persister = new Persisters\BasicEntityPersister($this->_em, $class);
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
    public function registerManaged($entity, array $id, array $data)
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
        $oid = spl_object_hash($entity);
        $class = $this->_em->getClassMetadata(get_class($entity));

        $isAssocField = isset($class->associationMappings[$propertyName]);

        if ( ! $isAssocField && ! isset($class->fieldMappings[$propertyName])) {
            return; // ignore non-persistent fields
        }

        $this->_entityChangeSets[$oid][$propertyName] = array($oldValue, $newValue);

        if ($isAssocField) {
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
    }
    
    /**
     * Gets the currently scheduled entity insertions in this UnitOfWork.
     * 
     * @return array
     */
    public function getScheduledEntityInsertions()
    {
        return $this->_entityInsertions;
    }
    
    /**
     * Gets the currently scheduled entity updates in this UnitOfWork.
     * 
     * @return array
     */
    public function getScheduledEntityUpdates()
    {
        return $this->_entityUpdates;
    }
    
    /**
     * Gets the currently scheduled entity deletions in this UnitOfWork.
     * 
     * @return array
     */
    public function getScheduledEntityDeletions()
    {
        return $this->_entityDeletions;
    }

    /**
     * Get the currently scheduled complete collection deletions
     *
     * @return array
     */
    public function getScheduledCollectionDeletions()
    {
        return $this->_collectionDeletions;
    }

    /**
     * Gets the currently scheduled collection inserts, updates and deletes.
     *
     * @return array
     */
    public function getScheduledCollectionUpdates()
    {
        return $this->_collectionUpdates;
    }
}
