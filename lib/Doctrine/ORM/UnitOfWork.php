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

use Exception, InvalidArgumentException, UnexpectedValueException,
    Doctrine\Common\Collections\ArrayCollection,
    Doctrine\Common\Collections\Collection,
    Doctrine\Common\NotifyPropertyChanged,
    Doctrine\Common\PropertyChangedListener,
    Doctrine\Common\Persistence\ObjectManagerAware,
    Doctrine\ORM\Event\LifecycleEventArgs,
    Doctrine\ORM\Mapping\ClassMetadata,
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
     * A detached entity is an instance with persistent state and identity that is not
     * (or no longer) associated with an EntityManager (and a UnitOfWork).
     */
    const STATE_DETACHED = 3;

    /**
     * A removed entity instance is an instance with a persistent identity,
     * associated with an EntityManager, whose persistent state will be deleted
     * on commit.
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
    private $identityMap = array();

    /**
     * Map of all identifiers of managed entities.
     * Keys are object ids (spl_object_hash).
     *
     * @var array
     */
    private $entityIdentifiers = array();

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
    private $originalEntityData = array();

    /**
     * Map of entity changes. Keys are object ids (spl_object_hash).
     * Filled at the beginning of a commit of the UnitOfWork and cleaned at the end.
     *
     * @var array
     */
    private $entityChangeSets = array();

    /**
     * The (cached) states of any known entities.
     * Keys are object ids (spl_object_hash).
     *
     * @var array
     */
    private $entityStates = array();

    /**
     * Map of entities that are scheduled for dirty checking at commit time.
     * This is only used for entities with a change tracking policy of DEFERRED_EXPLICIT.
     * Keys are object ids (spl_object_hash).
     *
     * @var array
     * @todo rename: scheduledForSynchronization
     */
    private $scheduledForDirtyCheck = array();

    /**
     * A list of all pending entity insertions.
     *
     * @var array
     */
    private $entityInsertions = array();

    /**
     * A list of all pending entity updates.
     *
     * @var array
     */
    private $entityUpdates = array();

    /**
     * Any pending extra updates that have been scheduled by persisters.
     *
     * @var array
     */
    private $extraUpdates = array();

    /**
     * A list of all pending entity deletions.
     *
     * @var array
     */
    private $entityDeletions = array();

    /**
     * All pending collection deletions.
     *
     * @var array
     */
    private $collectionDeletions = array();

    /**
     * All pending collection updates.
     *
     * @var array
     */
    private $collectionUpdates = array();

    /**
     * List of collections visited during changeset calculation on a commit-phase of a UnitOfWork.
     * At the end of the UnitOfWork all these collections will make new snapshots
     * of their data.
     *
     * @var array
     */
    private $visitedCollections = array();

    /**
     * The EntityManager that "owns" this UnitOfWork instance.
     *
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * The calculator used to calculate the order in which changes to
     * entities need to be written to the database.
     *
     * @var \Doctrine\ORM\Internal\CommitOrderCalculator
     */
    private $commitOrderCalculator;

    /**
     * The entity persister instances used to persist entity instances.
     *
     * @var array
     */
    private $persisters = array();

    /**
     * The collection persister instances used to persist collections.
     *
     * @var array
     */
    private $collectionPersisters = array();

    /**
     * The EventManager used for dispatching events.
     *
     * @var EventManager
     */
    private $evm;

    /**
     * Orphaned entities that are scheduled for removal.
     *
     * @var array
     */
    private $orphanRemovals = array();

    /**
     * Read-Only objects are never evaluated
     *
     * @var array
     */
    private $readOnlyObjects = array();

    /**
     * Map of Entity Class-Names and corresponding IDs that should eager loaded when requested.
     *
     * @var array
     */
    private $eagerLoadingEntities = array();

    /**
     * Initializes a new UnitOfWork instance, bound to the given EntityManager.
     *
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->evm = $em->getEventManager();
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
     * @param object $entity
     * @return void
     */
    public function commit($entity = null)
    {
        // Raise preFlush
        if ($this->evm->hasListeners(Events::preFlush)) {
            $this->evm->dispatchEvent(Events::preFlush, new Event\PreFlushEventArgs($this->em));
        }

        // Compute changes done since last commit.
        if ($entity === null) {
            $this->computeChangeSets();
        } else {
            $this->computeSingleEntityChangeSet($entity);
        }

        if ( ! ($this->entityInsertions ||
                $this->entityDeletions ||
                $this->entityUpdates ||
                $this->collectionUpdates ||
                $this->collectionDeletions ||
                $this->orphanRemovals)) {
            return; // Nothing to do.
        }

        if ($this->orphanRemovals) {
            foreach ($this->orphanRemovals as $orphan) {
                $this->remove($orphan);
            }
        }

        // Raise onFlush
        if ($this->evm->hasListeners(Events::onFlush)) {
            $this->evm->dispatchEvent(Events::onFlush, new Event\OnFlushEventArgs($this->em));
        }

        // Now we need a commit order to maintain referential integrity
        $commitOrder = $this->getCommitOrder();

        $conn = $this->em->getConnection();
        $conn->beginTransaction();

        try {
            if ($this->entityInsertions) {
                foreach ($commitOrder as $class) {
                    $this->executeInserts($class);
                }
            }

            if ($this->entityUpdates) {
                foreach ($commitOrder as $class) {
                    $this->executeUpdates($class);
                }
            }

            // Extra updates that were requested by persisters.
            if ($this->extraUpdates) {
                $this->executeExtraUpdates();
            }

            // Collection deletions (deletions of complete collections)
            foreach ($this->collectionDeletions as $collectionToDelete) {
                $this->getCollectionPersister($collectionToDelete->getMapping())->delete($collectionToDelete);
            }
            // Collection updates (deleteRows, updateRows, insertRows)
            foreach ($this->collectionUpdates as $collectionToUpdate) {
                $this->getCollectionPersister($collectionToUpdate->getMapping())->update($collectionToUpdate);
            }

            // Entity deletions come last and need to be in reverse commit order
            if ($this->entityDeletions) {
                for ($count = count($commitOrder), $i = $count - 1; $i >= 0; --$i) {
                    $this->executeDeletions($commitOrder[$i]);
                }
            }

            $conn->commit();
        } catch (Exception $e) {
            $this->em->close();
            $conn->rollback();

            throw $e;
        }

        // Take new snapshots from visited collections
        foreach ($this->visitedCollections as $coll) {
            $coll->takeSnapshot();
        }

        // Raise postFlush
        if ($this->evm->hasListeners(Events::postFlush)) {
            $this->evm->dispatchEvent(Events::postFlush, new Event\PostFlushEventArgs($this->em));
        }

        // Clear up
        $this->entityInsertions =
        $this->entityUpdates =
        $this->entityDeletions =
        $this->extraUpdates =
        $this->entityChangeSets =
        $this->collectionUpdates =
        $this->collectionDeletions =
        $this->visitedCollections =
        $this->scheduledForDirtyCheck =
        $this->orphanRemovals = array();
    }

    /**
     * Compute the changesets of all entities scheduled for insertion
     *
     * @return void
     */
    private function computeScheduleInsertsChangeSets()
    {
        foreach ($this->entityInsertions as $entity) {
            $class = $this->em->getClassMetadata(get_class($entity));

            $this->computeChangeSet($class, $entity);
        }
    }

    /**
     * Only flush the given entity according to a ruleset that keeps the UoW consistent.
     *
     * 1. All entities scheduled for insertion, (orphan) removals and changes in collections are processed as well!
     * 2. Read Only entities are skipped.
     * 3. Proxies are skipped.
     * 4. Only if entity is properly managed.
     *
     * @param  object $entity
     * @return void
     */
    private function computeSingleEntityChangeSet($entity)
    {
        if ( $this->getEntityState($entity) !== self::STATE_MANAGED) {
            throw new \InvalidArgumentException("Entity has to be managed for single computation " . self::objToStr($entity));
        }

        $class = $this->em->getClassMetadata(get_class($entity));

        if ($class->isChangeTrackingDeferredImplicit()) {
            $this->persist($entity);
        }

        // Compute changes for INSERTed entities first. This must always happen even in this case.
        $this->computeScheduleInsertsChangeSets();

        if ($class->isReadOnly) {
            return;
        }

        // Ignore uninitialized proxy objects
        if ($entity instanceof Proxy && ! $entity->__isInitialized__) {
            return;
        }

        // Only MANAGED entities that are NOT SCHEDULED FOR INSERTION are processed here.
        $oid = spl_object_hash($entity);

        if ( ! isset($this->entityInsertions[$oid]) && isset($this->entityStates[$oid])) {
            $this->computeChangeSet($class, $entity);
        }
    }

    /**
     * Executes any extra updates that have been scheduled.
     */
    private function executeExtraUpdates()
    {
        foreach ($this->extraUpdates as $oid => $update) {
            list ($entity, $changeset) = $update;

            $this->entityChangeSets[$oid] = $changeset;
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

        if (isset($this->entityChangeSets[$oid])) {
            return $this->entityChangeSets[$oid];
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
     * @ignore
     * @internal Don't call from the outside.
     * @param ClassMetadata $class The class descriptor of the entity.
     * @param object $entity The entity for which to compute the changes.
     */
    public function computeChangeSet(ClassMetadata $class, $entity)
    {
        $oid = spl_object_hash($entity);

        if (isset($this->readOnlyObjects[$oid])) {
            return;
        }

        if ( ! $class->isInheritanceTypeNone()) {
            $class = $this->em->getClassMetadata(get_class($entity));
        }

        // Fire PreFlush lifecycle callbacks
        if (isset($class->lifecycleCallbacks[Events::preFlush])) {
            $class->invokeLifecycleCallbacks(Events::preFlush, $entity);
        }

        $actualData = array();

        foreach ($class->reflFields as $name => $refProp) {
            $value = $refProp->getValue($entity);

            if ($class->isCollectionValuedAssociation($name) && $value !== null && ! ($value instanceof PersistentCollection)) {
                // If $value is not a Collection then use an ArrayCollection.
                if ( ! $value instanceof Collection) {
                    $value = new ArrayCollection($value);
                }

                $assoc = $class->associationMappings[$name];

                // Inject PersistentCollection
                $value = new PersistentCollection(
                    $this->em, $this->em->getClassMetadata($assoc['targetEntity']), $value
                );
                $value->setOwner($entity, $assoc);
                $value->setDirty( ! $value->isEmpty());

                $class->reflFields[$name]->setValue($entity, $value);

                $actualData[$name] = $value;

                continue;
            }

            if (( ! $class->isIdentifier($name) || ! $class->isIdGeneratorIdentity()) && ($name !== $class->versionField)) {
                $actualData[$name] = $value;
            }
        }

        if ( ! isset($this->originalEntityData[$oid])) {
            // Entity is either NEW or MANAGED but not yet fully persisted (only has an id).
            // These result in an INSERT.
            $this->originalEntityData[$oid] = $actualData;
            $changeSet = array();

            foreach ($actualData as $propName => $actualValue) {
                if ( ! isset($class->associationMappings[$propName])) {
                    $changeSet[$propName] = array(null, $actualValue);

                    continue;
                }

                $assoc = $class->associationMappings[$propName];

                if ($assoc['isOwningSide'] && $assoc['type'] & ClassMetadata::TO_ONE) {
                    $changeSet[$propName] = array(null, $actualValue);
                }
            }

            $this->entityChangeSets[$oid] = $changeSet;
        } else {
            // Entity is "fully" MANAGED: it was already fully persisted before
            // and we have a copy of the original data
            $originalData           = $this->originalEntityData[$oid];
            $isChangeTrackingNotify = $class->isChangeTrackingNotify();
            $changeSet              = ($isChangeTrackingNotify && isset($this->entityChangeSets[$oid]))
                ? $this->entityChangeSets[$oid]
                : array();

            foreach ($actualData as $propName => $actualValue) {
                // skip field, its a partially omitted one!
                if ( ! (isset($originalData[$propName]) || array_key_exists($propName, $originalData))) {
                    continue;
                }

                $orgValue = $originalData[$propName];

                // skip if value havent changed
                if ($orgValue === $actualValue) {
                    continue;
                }

                // if regular field
                if ( ! isset($class->associationMappings[$propName])) {
                    if ($isChangeTrackingNotify) {
                        continue;
                    }

                    $changeSet[$propName] = array($orgValue, $actualValue);

                    continue;
                }

                $assoc = $class->associationMappings[$propName];

                // Persistent collection was exchanged with the "originally"
                // created one. This can only mean it was cloned and replaced
                // on another entity.
                if ($actualValue instanceof PersistentCollection) {
                    $owner = $actualValue->getOwner();
                    if ($owner === null) { // cloned
                        $actualValue->setOwner($entity, $assoc);
                    } else if ($owner !== $entity) { // no clone, we have to fix
                        if (!$actualValue->isInitialized()) {
                            $actualValue->initialize(); // we have to do this otherwise the cols share state
                        }
                        $newValue = clone $actualValue;
                        $newValue->setOwner($entity, $assoc);
                        $class->reflFields[$propName]->setValue($entity, $newValue);
                    }
                }

                if ($orgValue instanceof PersistentCollection) {
                    // A PersistentCollection was de-referenced, so delete it.
                    $coid = spl_object_hash($orgValue);

                    if (isset($this->collectionDeletions[$coid])) {
                        continue;
                    }

                    $this->collectionDeletions[$coid] = $orgValue;
                    $changeSet[$propName] = $orgValue; // Signal changeset, to-many assocs will be ignored.

                    continue;
                }

                if ($assoc['type'] & ClassMetadata::TO_ONE) {
                    if ($assoc['isOwningSide']) {
                        $changeSet[$propName] = array($orgValue, $actualValue);
                    }

                    if ($orgValue !== null && $assoc['orphanRemoval']) {
                        $this->scheduleOrphanRemoval($orgValue);
                    }
                }
            }

            if ($changeSet) {
                $this->entityChangeSets[$oid]   = $changeSet;
                $this->originalEntityData[$oid] = $actualData;
                $this->entityUpdates[$oid]      = $entity;
            }
        }

        // Look for changes in associations of the entity
        foreach ($class->associationMappings as $field => $assoc) {
            if (($val = $class->reflFields[$field]->getValue($entity)) !== null) {
                $this->computeAssociationChanges($assoc, $val);
                if (!isset($this->entityChangeSets[$oid]) &&
                    $assoc['isOwningSide'] &&
                    $assoc['type'] == ClassMetadata::MANY_TO_MANY &&
                    $val instanceof PersistentCollection &&
                    $val->isDirty()) {
                    $this->entityChangeSets[$oid]   = array();
                    $this->originalEntityData[$oid] = $actualData;
                    $this->entityUpdates[$oid]      = $entity;
                }
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
        $this->computeScheduleInsertsChangeSets();

        // Compute changes for other MANAGED entities. Change tracking policies take effect here.
        foreach ($this->identityMap as $className => $entities) {
            $class = $this->em->getClassMetadata($className);

            // Skip class if instances are read-only
            if ($class->isReadOnly) {
                continue;
            }

            // If change tracking is explicit or happens through notification, then only compute
            // changes on entities of that type that are explicitly marked for synchronization.
            switch (true) {
                case ($class->isChangeTrackingDeferredImplicit()):
                    $entitiesToProcess = $entities;
                    break;

                case (isset($this->scheduledForDirtyCheck[$className])):
                    $entitiesToProcess = $this->scheduledForDirtyCheck[$className];
                    break;

                default:
                    $entitiesToProcess = array();

            }

            foreach ($entitiesToProcess as $entity) {
                // Ignore uninitialized proxy objects
                if ($entity instanceof Proxy && ! $entity->__isInitialized__) {
                    continue;
                }

                // Only MANAGED entities that are NOT SCHEDULED FOR INSERTION are processed here.
                $oid = spl_object_hash($entity);

                if ( ! isset($this->entityInsertions[$oid]) && isset($this->entityStates[$oid])) {
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
    private function computeAssociationChanges($assoc, $value)
    {
        if ($value instanceof Proxy && ! $value->__isInitialized__) {
            return;
        }

        if ($value instanceof PersistentCollection && $value->isDirty()) {
            $coid = spl_object_hash($value);

            if ($assoc['isOwningSide']) {
                $this->collectionUpdates[$coid] = $value;
            }

            $this->visitedCollections[$coid] = $value;
        }

        // Look through the entities, and in any of their associations,
        // for transient (new) entities, recursively. ("Persistence by reachability")
        // Unwrap. Uninitialized collections will simply be empty.
        $unwrappedValue = ($assoc['type'] & ClassMetadata::TO_ONE) ? array($value) : $value->unwrap();
        $targetClass    = $this->em->getClassMetadata($assoc['targetEntity']);

        foreach ($unwrappedValue as $key => $entry) {
            $state = $this->getEntityState($entry, self::STATE_NEW);
            $oid   = spl_object_hash($entry);

            if (!($entry instanceof $assoc['targetEntity'])) {
                throw new ORMException(sprintf("Found entity of type %s on association %s#%s, but expecting %s",
                    get_class($entry),
                    $assoc['sourceEntity'],
                    $assoc['fieldName'],
                    $targetClass->name
                ));
            }

            switch ($state) {
                case self::STATE_NEW:
                    if ( ! $assoc['isCascadePersist']) {
                        throw ORMInvalidArgumentException::newEntityFoundThroughRelationship($assoc, $entry);
                    }

                    $this->persistNew($targetClass, $entry);
                    $this->computeChangeSet($targetClass, $entry);
                    break;

                case self::STATE_REMOVED:
                    // Consume the $value as array (it's either an array or an ArrayAccess)
                    // and remove the element from Collection.
                    if ($assoc['type'] & ClassMetadata::TO_MANY) {
                        unset($value[$key]);
                    }
                    break;

                case self::STATE_DETACHED:
                    // Can actually not happen right now as we assume STATE_NEW,
                    // so the exception will be raised from the DBAL layer (constraint violation).
                    throw ORMInvalidArgumentException::detachedEntityFoundThroughRelationship($assoc, $entry);
                    break;

                default:
                    // MANAGED associated entities are already taken into account
                    // during changeset calculation anyway, since they are in the identity map.
            }
        }
    }

    private function persistNew($class, $entity)
    {
        $oid = spl_object_hash($entity);

        if (isset($class->lifecycleCallbacks[Events::prePersist])) {
            $class->invokeLifecycleCallbacks(Events::prePersist, $entity);
        }

        if ($this->evm->hasListeners(Events::prePersist)) {
            $this->evm->dispatchEvent(Events::prePersist, new LifecycleEventArgs($entity, $this->em));
        }

        $idGen = $class->idGenerator;

        if ( ! $idGen->isPostInsertGenerator()) {
            $idValue = $idGen->generate($this->em, $entity);

            if ( ! $idGen instanceof \Doctrine\ORM\Id\AssignedGenerator) {
                $idValue = array($class->identifier[0] => $idValue);

                $class->setIdentifierValues($entity, $idValue);
            }

            $this->entityIdentifiers[$oid] = $idValue;
        }

        $this->entityStates[$oid] = self::STATE_MANAGED;

        $this->scheduleForInsert($entity);
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
    public function recomputeSingleEntityChangeSet(ClassMetadata $class, $entity)
    {
        $oid = spl_object_hash($entity);

        if ( ! isset($this->entityStates[$oid]) || $this->entityStates[$oid] != self::STATE_MANAGED) {
            throw ORMInvalidArgumentException::entityNotManaged($entity);
        }

        // skip if change tracking is "NOTIFY"
        if ($class->isChangeTrackingNotify()) {
            return;
        }

        if ( ! $class->isInheritanceTypeNone()) {
            $class = $this->em->getClassMetadata(get_class($entity));
        }

        $actualData = array();

        foreach ($class->reflFields as $name => $refProp) {
            if ( ! $class->isIdentifier($name) || ! $class->isIdGeneratorIdentity()) {
                $actualData[$name] = $refProp->getValue($entity);
            }
        }

        $originalData = $this->originalEntityData[$oid];
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
            if (isset($this->entityChangeSets[$oid])) {
                $this->entityChangeSets[$oid] = array_merge($this->entityChangeSets[$oid], $changeSet);
            }

            $this->originalEntityData[$oid] = $actualData;
        }
    }

    /**
     * Executes all entity insertions for entities of the specified type.
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $class
     */
    private function executeInserts($class)
    {
        $className = $class->name;
        $persister = $this->getEntityPersister($className);
        $entities  = array();

        $hasLifecycleCallbacks = isset($class->lifecycleCallbacks[Events::postPersist]);
        $hasListeners          = $this->evm->hasListeners(Events::postPersist);

        foreach ($this->entityInsertions as $oid => $entity) {
            if (get_class($entity) !== $className) {
                continue;
            }

            $persister->addInsert($entity);

            unset($this->entityInsertions[$oid]);

            if ($hasLifecycleCallbacks || $hasListeners) {
                $entities[] = $entity;
            }
        }

        $postInsertIds = $persister->executeInserts();

        if ($postInsertIds) {
            // Persister returned post-insert IDs
            foreach ($postInsertIds as $id => $entity) {
                $oid     = spl_object_hash($entity);
                $idField = $class->identifier[0];

                $class->reflFields[$idField]->setValue($entity, $id);

                $this->entityIdentifiers[$oid] = array($idField => $id);
                $this->entityStates[$oid] = self::STATE_MANAGED;
                $this->originalEntityData[$oid][$idField] = $id;

                $this->addToIdentityMap($entity);
            }
        }

        foreach ($entities as $entity) {
            if ($hasLifecycleCallbacks) {
                $class->invokeLifecycleCallbacks(Events::postPersist, $entity);
            }

            if ($hasListeners) {
                $this->evm->dispatchEvent(Events::postPersist, new LifecycleEventArgs($entity, $this->em));
            }
        }
    }

    /**
     * Executes all entity updates for entities of the specified type.
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $class
     */
    private function executeUpdates($class)
    {
        $className = $class->name;
        $persister = $this->getEntityPersister($className);

        $hasPreUpdateLifecycleCallbacks = isset($class->lifecycleCallbacks[Events::preUpdate]);
        $hasPreUpdateListeners          = $this->evm->hasListeners(Events::preUpdate);

        $hasPostUpdateLifecycleCallbacks = isset($class->lifecycleCallbacks[Events::postUpdate]);
        $hasPostUpdateListeners          = $this->evm->hasListeners(Events::postUpdate);

        foreach ($this->entityUpdates as $oid => $entity) {
            if ( ! (get_class($entity) === $className || $entity instanceof Proxy && get_parent_class($entity) === $className)) {
                continue;
            }

            if ($hasPreUpdateLifecycleCallbacks) {
                $class->invokeLifecycleCallbacks(Events::preUpdate, $entity);

                $this->recomputeSingleEntityChangeSet($class, $entity);
            }

            if ($hasPreUpdateListeners) {
                $this->evm->dispatchEvent(
                    Events::preUpdate,
                    new Event\PreUpdateEventArgs($entity, $this->em, $this->entityChangeSets[$oid])
                );
            }

            if ($this->entityChangeSets[$oid]) {
                $persister->update($entity);
            }

            unset($this->entityUpdates[$oid]);

            if ($hasPostUpdateLifecycleCallbacks) {
                $class->invokeLifecycleCallbacks(Events::postUpdate, $entity);
            }

            if ($hasPostUpdateListeners) {
                $this->evm->dispatchEvent(Events::postUpdate, new LifecycleEventArgs($entity, $this->em));
            }
        }
    }

    /**
     * Executes all entity deletions for entities of the specified type.
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $class
     */
    private function executeDeletions($class)
    {
        $className = $class->name;
        $persister = $this->getEntityPersister($className);

        $hasLifecycleCallbacks = isset($class->lifecycleCallbacks[Events::postRemove]);
        $hasListeners = $this->evm->hasListeners(Events::postRemove);

        foreach ($this->entityDeletions as $oid => $entity) {
            if ( ! (get_class($entity) == $className || $entity instanceof Proxy && get_parent_class($entity) == $className)) {
                continue;
            }

            $persister->delete($entity);

            unset(
                $this->entityDeletions[$oid],
                $this->entityIdentifiers[$oid],
                $this->originalEntityData[$oid],
                $this->entityStates[$oid]
            );

            // Entity with this $oid after deletion treated as NEW, even if the $oid
            // is obtained by a new entity because the old one went out of scope.
            //$this->entityStates[$oid] = self::STATE_NEW;
            if ( ! $class->isIdentifierNatural()) {
                $class->reflFields[$class->identifier[0]]->setValue($entity, null);
            }

            if ($hasLifecycleCallbacks) {
                $class->invokeLifecycleCallbacks(Events::postRemove, $entity);
            }

            if ($hasListeners) {
                $this->evm->dispatchEvent(Events::postRemove, new LifecycleEventArgs($entity, $this->em));
            }
        }
    }

    /**
     * Gets the commit order.
     *
     * @return array
     */
    private function getCommitOrder(array $entityChangeSet = null)
    {
        if ($entityChangeSet === null) {
            $entityChangeSet = array_merge($this->entityInsertions, $this->entityUpdates, $this->entityDeletions);
        }

        $calc = $this->getCommitOrderCalculator();

        // See if there are any new classes in the changeset, that are not in the
        // commit order graph yet (dont have a node).
        // We have to inspect changeSet to be able to correctly build dependencies.
        // It is not possible to use IdentityMap here because post inserted ids
        // are not yet available.
        $newNodes = array();

        foreach ($entityChangeSet as $oid => $entity) {
            $className = get_class($entity);

            if ($calc->hasClass($className)) {
                continue;
            }

            $class = $this->em->getClassMetadata($className);
            $calc->addClass($class);

            $newNodes[] = $class;
        }

        // Calculate dependencies for new nodes
        while ($class = array_pop($newNodes)) {
            foreach ($class->associationMappings as $assoc) {
                if ( ! ($assoc['isOwningSide'] && $assoc['type'] & ClassMetadata::TO_ONE)) {
                    continue;
                }

                $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);

                if ( ! $calc->hasClass($targetClass->name)) {
                    $calc->addClass($targetClass);

                    $newNodes[] = $targetClass;
                }

                $calc->addDependency($targetClass, $class);

                // If the target class has mapped subclasses, these share the same dependency.
                if ( ! $targetClass->subClasses) {
                    continue;
                }

                foreach ($targetClass->subClasses as $subClassName) {
                    $targetSubClass = $this->em->getClassMetadata($subClassName);

                    if ( ! $calc->hasClass($subClassName)) {
                        $calc->addClass($targetSubClass);

                        $newNodes[] = $targetSubClass;
                    }

                    $calc->addDependency($targetSubClass, $class);
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

        if (isset($this->entityUpdates[$oid])) {
            throw new InvalidArgumentException("Dirty entity can not be scheduled for insertion.");
        }

        if (isset($this->entityDeletions[$oid])) {
            throw ORMInvalidArgumentException::scheduleInsertForRemovedEntity($entity);
        }
        if (isset($this->originalEntityData[$oid]) && ! isset($this->entityInsertions[$oid])) {
            throw ORMInvalidArgumentException::scheduleInsertForManagedEntity($entity);
        }

        if (isset($this->entityInsertions[$oid])) {
            throw ORMInvalidArgumentException::scheduleInsertTwice($entity);
        }

        $this->entityInsertions[$oid] = $entity;

        if (isset($this->entityIdentifiers[$oid])) {
            $this->addToIdentityMap($entity);
        }

        if ($entity instanceof NotifyPropertyChanged) {
            $entity->addPropertyChangedListener($this);
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
        return isset($this->entityInsertions[spl_object_hash($entity)]);
    }

    /**
     * Schedules an entity for being updated.
     *
     * @param object $entity The entity to schedule for being updated.
     */
    public function scheduleForUpdate($entity)
    {
        $oid = spl_object_hash($entity);

        if ( ! isset($this->entityIdentifiers[$oid])) {
            throw ORMInvalidArgumentException::entityHasNoIdentity($entity, "scheduling for update");
        }

        if (isset($this->entityDeletions[$oid])) {
            throw ORMInvalidArgumentException::entityIsRemoved($entity, "schedule for update");
        }

        if ( ! isset($this->entityUpdates[$oid]) && ! isset($this->entityInsertions[$oid])) {
            $this->entityUpdates[$oid] = $entity;
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
        $oid         = spl_object_hash($entity);
        $extraUpdate = array($entity, $changeset);

        if (isset($this->extraUpdates[$oid])) {
            list($ignored, $changeset2) = $this->extraUpdates[$oid];

            $extraUpdate = array($entity, $changeset + $changeset2);
        }

        $this->extraUpdates[$oid] = $extraUpdate;
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
        return isset($this->entityUpdates[spl_object_hash($entity)]);
    }


    /**
     * Checks whether an entity is registered to be checked in the unit of work.
     *
     * @param object $entity
     * @return boolean
     */
    public function isScheduledForDirtyCheck($entity)
    {
        $rootEntityName = $this->em->getClassMetadata(get_class($entity))->rootEntityName;

        return isset($this->scheduledForDirtyCheck[$rootEntityName][spl_object_hash($entity)]);
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

        if (isset($this->entityInsertions[$oid])) {
            if ($this->isInIdentityMap($entity)) {
                $this->removeFromIdentityMap($entity);
            }

            unset($this->entityInsertions[$oid], $this->entityStates[$oid]);

            return; // entity has not been persisted yet, so nothing more to do.
        }

        if ( ! $this->isInIdentityMap($entity)) {
            return;
        }

        $this->removeFromIdentityMap($entity);

        if (isset($this->entityUpdates[$oid])) {
            unset($this->entityUpdates[$oid]);
        }

        if ( ! isset($this->entityDeletions[$oid])) {
            $this->entityDeletions[$oid] = $entity;
            $this->entityStates[$oid]    = self::STATE_REMOVED;
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
        return isset($this->entityDeletions[spl_object_hash($entity)]);
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

        return isset($this->entityInsertions[$oid])
            || isset($this->entityUpdates[$oid])
            || isset($this->entityDeletions[$oid]);
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
        $classMetadata = $this->em->getClassMetadata(get_class($entity));
        $idHash        = implode(' ', $this->entityIdentifiers[spl_object_hash($entity)]);

        if ($idHash === '') {
            throw ORMInvalidArgumentException::entityWithoutIdentity($classMetadata->name, $entity);
        }

        $className = $classMetadata->rootEntityName;

        if (isset($this->identityMap[$className][$idHash])) {
            return false;
        }

        $this->identityMap[$className][$idHash] = $entity;

        return true;
    }

    /**
     * Gets the state of an entity with regard to the current unit of work.
     *
     * @param object $entity
     * @param integer $assume The state to assume if the state is not yet known (not MANAGED or REMOVED).
     *                        This parameter can be set to improve performance of entity state detection
     *                        by potentially avoiding a database lookup if the distinction between NEW and DETACHED
     *                        is either known or does not matter for the caller of the method.
     * @return int The entity state.
     */
    public function getEntityState($entity, $assume = null)
    {
        $oid = spl_object_hash($entity);

        if (isset($this->entityStates[$oid])) {
            return $this->entityStates[$oid];
        }

        if ($assume !== null) {
            return $assume;
        }

        // State can only be NEW or DETACHED, because MANAGED/REMOVED states are known.
        // Note that you can not remember the NEW or DETACHED state in _entityStates since
        // the UoW does not hold references to such objects and the object hash can be reused.
        // More generally because the state may "change" between NEW/DETACHED without the UoW being aware of it.
        $class = $this->em->getClassMetadata(get_class($entity));
        $id    = $class->getIdentifierValues($entity);

        if ( ! $id) {
            return self::STATE_NEW;
        }

        switch (true) {
            case ($class->isIdentifierNatural());
                // Check for a version field, if available, to avoid a db lookup.
                if ($class->isVersioned) {
                    return ($class->getFieldValue($entity, $class->versionField))
                        ? self::STATE_DETACHED
                        : self::STATE_NEW;
                }

                // Last try before db lookup: check the identity map.
                if ($this->tryGetById($id, $class->rootEntityName)) {
                    return self::STATE_DETACHED;
                }

                // db lookup
                if ($this->getEntityPersister(get_class($entity))->exists($entity)) {
                    return self::STATE_DETACHED;
                }

                return self::STATE_NEW;

            case ( ! $class->idGenerator->isPostInsertGenerator()):
                // if we have a pre insert generator we can't be sure that having an id
                // really means that the entity exists. We have to verify this through
                // the last resort: a db lookup

                // Last try before db lookup: check the identity map.
                if ($this->tryGetById($id, $class->rootEntityName)) {
                    return self::STATE_DETACHED;
                }

                // db lookup
                if ($this->getEntityPersister(get_class($entity))->exists($entity)) {
                    return self::STATE_DETACHED;
                }

                return self::STATE_NEW;

            default:
                return self::STATE_DETACHED;
        }
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
        $oid           = spl_object_hash($entity);
        $classMetadata = $this->em->getClassMetadata(get_class($entity));
        $idHash        = implode(' ', $this->entityIdentifiers[$oid]);

        if ($idHash === '') {
            throw ORMInvalidArgumentException::entityHasNoIdentity($entity, "remove from identity map");
        }

        $className = $classMetadata->rootEntityName;

        if (isset($this->identityMap[$className][$idHash])) {
            unset($this->identityMap[$className][$idHash]);
            unset($this->readOnlyObjects[$oid]);

            //$this->entityStates[$oid] = self::STATE_DETACHED;

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
        return $this->identityMap[$rootClassName][$idHash];
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
        if (isset($this->identityMap[$rootClassName][$idHash])) {
            return $this->identityMap[$rootClassName][$idHash];
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

        if ( ! isset($this->entityIdentifiers[$oid])) {
            return false;
        }

        $classMetadata = $this->em->getClassMetadata(get_class($entity));
        $idHash        = implode(' ', $this->entityIdentifiers[$oid]);

        if ($idHash === '') {
            return false;
        }

        return isset($this->identityMap[$classMetadata->rootEntityName][$idHash]);
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
        return isset($this->identityMap[$rootClassName][$idHash]);
    }

    /**
     * Persists an entity as part of the current unit of work.
     *
     * @param object $entity The entity to persist.
     */
    public function persist($entity)
    {
        $visited = array();

        $this->doPersist($entity, $visited);
    }

    /**
     * Persists an entity as part of the current unit of work.
     *
     * This method is internally called during persist() cascades as it tracks
     * the already visited entities to prevent infinite recursions.
     *
     * @param object $entity The entity to persist.
     * @param array $visited The already visited entities.
     */
    private function doPersist($entity, array &$visited)
    {
        $oid = spl_object_hash($entity);

        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $entity; // Mark visited

        $class = $this->em->getClassMetadata(get_class($entity));

        // We assume NEW, so DETACHED entities result in an exception on flush (constraint violation).
        // If we would detect DETACHED here we would throw an exception anyway with the same
        // consequences (not recoverable/programming error), so just assuming NEW here
        // lets us avoid some database lookups for entities with natural identifiers.
        $entityState = $this->getEntityState($entity, self::STATE_NEW);

        switch ($entityState) {
            case self::STATE_MANAGED:
                // Nothing to do, except if policy is "deferred explicit"
                if ($class->isChangeTrackingDeferredExplicit()) {
                    $this->scheduleForDirtyCheck($entity);
                }
                break;

            case self::STATE_NEW:
                $this->persistNew($class, $entity);
                break;

            case self::STATE_REMOVED:
                // Entity becomes managed again
                unset($this->entityDeletions[$oid]);

                $this->entityStates[$oid] = self::STATE_MANAGED;
                break;

            case self::STATE_DETACHED:
                // Can actually not happen right now since we assume STATE_NEW.
                throw ORMInvalidArgumentException::detachedEntityCannot($entity, "persisted");

            default:
                throw new UnexpectedValueException("Unexpected entity state: $entityState." . self::objToStr($entity));
        }

        $this->cascadePersist($entity, $visited);
    }

    /**
     * Deletes an entity as part of the current unit of work.
     *
     * @param object $entity The entity to remove.
     */
    public function remove($entity)
    {
        $visited = array();

        $this->doRemove($entity, $visited);
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
    private function doRemove($entity, array &$visited)
    {
        $oid = spl_object_hash($entity);

        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $entity; // mark visited

        // Cascade first, because scheduleForDelete() removes the entity from the identity map, which
        // can cause problems when a lazy proxy has to be initialized for the cascade operation.
        $this->cascadeRemove($entity, $visited);

        $class       = $this->em->getClassMetadata(get_class($entity));
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

                if ($this->evm->hasListeners(Events::preRemove)) {
                    $this->evm->dispatchEvent(Events::preRemove, new LifecycleEventArgs($entity, $this->em));
                }

                $this->scheduleForDelete($entity);
                break;

            case self::STATE_DETACHED:
                throw ORMInvalidArgumentException::detachedEntityCannot($entity, "removed");
            default:
                throw new UnexpectedValueException("Unexpected entity state: $entityState." . self::objToStr($entity));
        }

    }

    /**
     * Merges the state of the given detached entity into this UnitOfWork.
     *
     * @param object $entity
     * @return object The managed copy of the entity.
     * @throws OptimisticLockException If the entity uses optimistic locking through a version
     *         attribute and the version check against the managed copy fails.
     *
     * @todo Require active transaction!? OptimisticLockException may result in undefined state!?
     */
    public function merge($entity)
    {
        $visited = array();

        return $this->doMerge($entity, $visited);
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
    private function doMerge($entity, array &$visited, $prevManagedCopy = null, $assoc = null)
    {
        $oid = spl_object_hash($entity);

        if (isset($visited[$oid])) {
            return $visited[$oid]; // Prevent infinite recursion
        }

        $visited[$oid] = $entity; // mark visited

        $class = $this->em->getClassMetadata(get_class($entity));

        // First we assume DETACHED, although it can still be NEW but we can avoid
        // an extra db-roundtrip this way. If it is not MANAGED but has an identity,
        // we need to fetch it from the db anyway in order to merge.
        // MANAGED entities are ignored by the merge operation.
        $managedCopy = $entity;

        if ($this->getEntityState($entity, self::STATE_DETACHED) !== self::STATE_MANAGED) {
            if ($entity instanceof Proxy && ! $entity->__isInitialized__) {
                $entity->__load();
            }

            // Try to look the entity up in the identity map.
            $id = $class->getIdentifierValues($entity);

            // If there is no ID, it is actually NEW.
            if ( ! $id) {
                $managedCopy = $this->newInstance($class);

                $this->persistNew($class, $managedCopy);
            } else {
                $flatId = $id;
                if ($class->containsForeignIdentifier) {
                    // convert foreign identifiers into scalar foreign key
                    // values to avoid object to string conversion failures.
                    foreach ($id as $idField => $idValue) {
                        if (isset($class->associationMappings[$idField])) {
                            $targetClassMetadata = $this->em->getClassMetadata($class->associationMappings[$idField]['targetEntity']);
                            $associatedId = $this->getEntityIdentifier($idValue);
                            $flatId[$idField] = $associatedId[$targetClassMetadata->identifier[0]];
                        }
                    }
                }

                $managedCopy = $this->tryGetById($flatId, $class->rootEntityName);

                if ($managedCopy) {
                    // We have the entity in-memory already, just make sure its not removed.
                    if ($this->getEntityState($managedCopy) == self::STATE_REMOVED) {
                        throw ORMInvalidArgumentException::entityIsRemoved($managedCopy, "merge");
                    }
                } else {
                    // We need to fetch the managed copy in order to merge.
                    $managedCopy = $this->em->find($class->name, $flatId);
                }

                if ($managedCopy === null) {
                    // If the identifier is ASSIGNED, it is NEW, otherwise an error
                    // since the managed entity was not found.
                    if ( ! $class->isIdentifierNatural()) {
                        throw new EntityNotFoundException;
                    }

                    $managedCopy = $this->newInstance($class);
                    $class->setIdentifierValues($managedCopy, $id);

                    $this->persistNew($class, $managedCopy);
                } else {
                    if ($managedCopy instanceof Proxy && ! $managedCopy->__isInitialized__) {
                        $managedCopy->__load();
                    }
                }
            }

            if ($class->isVersioned) {
                $managedCopyVersion = $class->reflFields[$class->versionField]->getValue($managedCopy);
                $entityVersion = $class->reflFields[$class->versionField]->getValue($entity);

                // Throw exception if versions dont match.
                if ($managedCopyVersion != $entityVersion) {
                    throw OptimisticLockException::lockFailedVersionMissmatch($entity, $entityVersion, $managedCopyVersion);
                }
            }

            // Merge state of $entity into existing (managed) entity
            foreach ($class->reflFields as $name => $prop) {
                if ( ! isset($class->associationMappings[$name])) {
                    if ( ! $class->isIdentifier($name)) {
                        $prop->setValue($managedCopy, $prop->getValue($entity));
                    }
                } else {
                    $assoc2 = $class->associationMappings[$name];
                    if ($assoc2['type'] & ClassMetadata::TO_ONE) {
                        $other = $prop->getValue($entity);
                        if ($other === null) {
                            $prop->setValue($managedCopy, null);
                        } else if ($other instanceof Proxy && !$other->__isInitialized__) {
                            // do not merge fields marked lazy that have not been fetched.
                            continue;
                        } else if ( ! $assoc2['isCascadeMerge']) {
                            if ($this->getEntityState($other, self::STATE_DETACHED) !== self::STATE_MANAGED) {
                                $targetClass = $this->em->getClassMetadata($assoc2['targetEntity']);
                                $relatedId = $targetClass->getIdentifierValues($other);

                                if ($targetClass->subClasses) {
                                    $other = $this->em->find($targetClass->name, $relatedId);
                                } else {
                                    $other = $this->em->getProxyFactory()->getProxy($assoc2['targetEntity'], $relatedId);
                                    $this->registerManaged($other, $relatedId, array());
                                }
                            }
                            $prop->setValue($managedCopy, $other);
                        }
                    } else {
                        $mergeCol = $prop->getValue($entity);
                        if ($mergeCol instanceof PersistentCollection && !$mergeCol->isInitialized()) {
                            // do not merge fields marked lazy that have not been fetched.
                            // keep the lazy persistent collection of the managed copy.
                            continue;
                        }

                        $managedCol = $prop->getValue($managedCopy);
                        if (!$managedCol) {
                            $managedCol = new PersistentCollection($this->em,
                                    $this->em->getClassMetadata($assoc2['targetEntity']),
                                    new ArrayCollection
                                    );
                            $managedCol->setOwner($managedCopy, $assoc2);
                            $prop->setValue($managedCopy, $managedCol);
                            $this->originalEntityData[$oid][$name] = $managedCol;
                        }
                        if ($assoc2['isCascadeMerge']) {
                            $managedCol->initialize();

                            // clear and set dirty a managed collection if its not also the same collection to merge from.
                            if (!$managedCol->isEmpty() && $managedCol !== $mergeCol) {
                                $managedCol->unwrap()->clear();
                                $managedCol->setDirty(true);

                                if ($assoc2['isOwningSide'] && $assoc2['type'] == ClassMetadata::MANY_TO_MANY && $class->isChangeTrackingNotify()) {
                                    $this->scheduleForDirtyCheck($managedCopy);
                                }
                            }
                        }
                    }
                }

                if ($class->isChangeTrackingNotify()) {
                    // Just treat all properties as changed, there is no other choice.
                    $this->propertyChanged($managedCopy, $name, null, $prop->getValue($managedCopy));
                }
            }

            if ($class->isChangeTrackingDeferredExplicit()) {
                $this->scheduleForDirtyCheck($entity);
            }
        }

        if ($prevManagedCopy !== null) {
            $assocField = $assoc['fieldName'];
            $prevClass = $this->em->getClassMetadata(get_class($prevManagedCopy));

            if ($assoc['type'] & ClassMetadata::TO_ONE) {
                $prevClass->reflFields[$assocField]->setValue($prevManagedCopy, $managedCopy);
            } else {
                $prevClass->reflFields[$assocField]->getValue($prevManagedCopy)->add($managedCopy);

                if ($assoc['type'] == ClassMetadata::ONE_TO_MANY) {
                    $class->reflFields[$assoc['mappedBy']]->setValue($managedCopy, $prevManagedCopy);
                }
            }
        }

        // Mark the managed copy visited as well
        $visited[spl_object_hash($managedCopy)] = true;

        $this->cascadeMerge($entity, $managedCopy, $visited);

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

        $this->doDetach($entity, $visited);
    }

    /**
     * Executes a detach operation on the given entity.
     *
     * @param object $entity
     * @param array $visited
     * @param boolean $noCascade if true, don't cascade detach operation
     */
    private function doDetach($entity, array &$visited, $noCascade = false)
    {
        $oid = spl_object_hash($entity);

        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $entity; // mark visited

        switch ($this->getEntityState($entity, self::STATE_DETACHED)) {
            case self::STATE_MANAGED:
                if ($this->isInIdentityMap($entity)) {
                    $this->removeFromIdentityMap($entity);
                }

                unset(
                    $this->entityInsertions[$oid],
                    $this->entityUpdates[$oid],
                    $this->entityDeletions[$oid],
                    $this->entityIdentifiers[$oid],
                    $this->entityStates[$oid],
                    $this->originalEntityData[$oid]
                );
                break;
            case self::STATE_NEW:
            case self::STATE_DETACHED:
                return;
        }

        if ( ! $noCascade) {
            $this->cascadeDetach($entity, $visited);
        }
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

        $this->doRefresh($entity, $visited);
    }

    /**
     * Executes a refresh operation on an entity.
     *
     * @param object $entity The entity to refresh.
     * @param array $visited The already visited entities during cascades.
     * @throws InvalidArgumentException If the entity is not MANAGED.
     */
    private function doRefresh($entity, array &$visited)
    {
        $oid = spl_object_hash($entity);

        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $entity; // mark visited

        $class = $this->em->getClassMetadata(get_class($entity));

        if ($this->getEntityState($entity) !== self::STATE_MANAGED) {
            throw ORMInvalidArgumentException::entityNotManaged($entity);
        }

        $this->getEntityPersister($class->name)->refresh(
            array_combine($class->getIdentifierFieldNames(), $this->entityIdentifiers[$oid]),
            $entity
        );

        $this->cascadeRefresh($entity, $visited);
    }

    /**
     * Cascades a refresh operation to associated entities.
     *
     * @param object $entity
     * @param array $visited
     */
    private function cascadeRefresh($entity, array &$visited)
    {
        $class = $this->em->getClassMetadata(get_class($entity));

        $associationMappings = array_filter(
            $class->associationMappings,
            function ($assoc) { return $assoc['isCascadeRefresh']; }
        );

        foreach ($associationMappings as $assoc) {
            $relatedEntities = $class->reflFields[$assoc['fieldName']]->getValue($entity);

            switch (true) {
                case ($relatedEntities instanceof PersistentCollection):
                    // Unwrap so that foreach() does not initialize
                    $relatedEntities = $relatedEntities->unwrap();
                    // break; is commented intentionally!

                case ($relatedEntities instanceof Collection):
                case (is_array($relatedEntities)):
                    foreach ($relatedEntities as $relatedEntity) {
                        $this->doRefresh($relatedEntity, $visited);
                    }
                    break;

                case ($relatedEntities !== null):
                    $this->doRefresh($relatedEntities, $visited);
                    break;

                default:
                    // Do nothing
            }
        }
    }

    /**
     * Cascades a detach operation to associated entities.
     *
     * @param object $entity
     * @param array $visited
     */
    private function cascadeDetach($entity, array &$visited)
    {
        $class = $this->em->getClassMetadata(get_class($entity));

        $associationMappings = array_filter(
            $class->associationMappings,
            function ($assoc) { return $assoc['isCascadeDetach']; }
        );

        foreach ($associationMappings as $assoc) {
            $relatedEntities = $class->reflFields[$assoc['fieldName']]->getValue($entity);

            switch (true) {
                case ($relatedEntities instanceof PersistentCollection):
                    // Unwrap so that foreach() does not initialize
                    $relatedEntities = $relatedEntities->unwrap();
                    // break; is commented intentionally!

                case ($relatedEntities instanceof Collection):
                case (is_array($relatedEntities)):
                    foreach ($relatedEntities as $relatedEntity) {
                        $this->doDetach($relatedEntity, $visited);
                    }
                    break;

                case ($relatedEntities !== null):
                    $this->doDetach($relatedEntities, $visited);
                    break;

                default:
                    // Do nothing
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
    private function cascadeMerge($entity, $managedCopy, array &$visited)
    {
        $class = $this->em->getClassMetadata(get_class($entity));

        $associationMappings = array_filter(
            $class->associationMappings,
            function ($assoc) { return $assoc['isCascadeMerge']; }
        );

        foreach ($associationMappings as $assoc) {
            $relatedEntities = $class->reflFields[$assoc['fieldName']]->getValue($entity);

            if ($relatedEntities instanceof Collection) {
                if ($relatedEntities === $class->reflFields[$assoc['fieldName']]->getValue($managedCopy)) {
                    continue;
                }

                if ($relatedEntities instanceof PersistentCollection) {
                    // Unwrap so that foreach() does not initialize
                    $relatedEntities = $relatedEntities->unwrap();
                }

                foreach ($relatedEntities as $relatedEntity) {
                    $this->doMerge($relatedEntity, $visited, $managedCopy, $assoc);
                }
            } else if ($relatedEntities !== null) {
                $this->doMerge($relatedEntities, $visited, $managedCopy, $assoc);
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
    private function cascadePersist($entity, array &$visited)
    {
        $class = $this->em->getClassMetadata(get_class($entity));

        $associationMappings = array_filter(
            $class->associationMappings,
            function ($assoc) { return $assoc['isCascadePersist']; }
        );

        foreach ($associationMappings as $assoc) {
            $relatedEntities = $class->reflFields[$assoc['fieldName']]->getValue($entity);

            switch (true) {
                case ($relatedEntities instanceof PersistentCollection):
                    // Unwrap so that foreach() does not initialize
                    $relatedEntities = $relatedEntities->unwrap();
                    // break; is commented intentionally!

                case ($relatedEntities instanceof Collection):
                case (is_array($relatedEntities)):
                    foreach ($relatedEntities as $relatedEntity) {
                        $this->doPersist($relatedEntity, $visited);
                    }
                    break;

                case ($relatedEntities !== null):
                    $this->doPersist($relatedEntities, $visited);
                    break;

                default:
                    // Do nothing
            }
        }
    }

    /**
     * Cascades the delete operation to associated entities.
     *
     * @param object $entity
     * @param array $visited
     */
    private function cascadeRemove($entity, array &$visited)
    {
        $class = $this->em->getClassMetadata(get_class($entity));

        $associationMappings = array_filter(
            $class->associationMappings,
            function ($assoc) { return $assoc['isCascadeRemove']; }
        );

        foreach ($associationMappings as $assoc) {
            if ($entity instanceof Proxy && !$entity->__isInitialized__) {
                $entity->__load();
            }

            $relatedEntities = $class->reflFields[$assoc['fieldName']]->getValue($entity);

            switch (true) {
                case ($relatedEntities instanceof Collection):
                case (is_array($relatedEntities)):
                    // If its a PersistentCollection initialization is intended! No unwrap!
                    foreach ($relatedEntities as $relatedEntity) {
                        $this->doRemove($relatedEntity, $visited);
                    }
                    break;

                case ($relatedEntities !== null):
                    $this->doRemove($relatedEntities, $visited);
                    break;

                default:
                    // Do nothing
            }
        }
    }

    /**
     * Acquire a lock on the given entity.
     *
     * @param object $entity
     * @param int $lockMode
     * @param int $lockVersion
     */
    public function lock($entity, $lockMode, $lockVersion = null)
    {
        if ($this->getEntityState($entity, self::STATE_DETACHED) != self::STATE_MANAGED) {
            throw ORMInvalidArgumentException::entityNotManaged($entity);
        }

        $entityName = get_class($entity);
        $class = $this->em->getClassMetadata($entityName);

        switch ($lockMode) {
            case \Doctrine\DBAL\LockMode::OPTIMISTIC;
                if ( ! $class->isVersioned) {
                    throw OptimisticLockException::notVersioned($entityName);
                }

                if ($lockVersion === null) {
                    return;
                }

                $entityVersion = $class->reflFields[$class->versionField]->getValue($entity);

                if ($entityVersion != $lockVersion) {
                    throw OptimisticLockException::lockFailedVersionMissmatch($entity, $lockVersion, $entityVersion);
                }

                break;

            case \Doctrine\DBAL\LockMode::PESSIMISTIC_READ:
            case \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE:
                if (!$this->em->getConnection()->isTransactionActive()) {
                    throw TransactionRequiredException::transactionRequired();
                }

                $oid = spl_object_hash($entity);

                $this->getEntityPersister($class->name)->lock(
                    array_combine($class->getIdentifierFieldNames(), $this->entityIdentifiers[$oid]),
                    $lockMode
                );
                break;

            default:
                // Do nothing
        }
    }

    /**
     * Gets the CommitOrderCalculator used by the UnitOfWork to order commits.
     *
     * @return \Doctrine\ORM\Internal\CommitOrderCalculator
     */
    public function getCommitOrderCalculator()
    {
        if ($this->commitOrderCalculator === null) {
            $this->commitOrderCalculator = new Internal\CommitOrderCalculator;
        }

        return $this->commitOrderCalculator;
    }

    /**
     * Clears the UnitOfWork.
     *
     * @param string $entityName if given, only entities of this type will get detached
     */
    public function clear($entityName = null)
    {
        if ($entityName === null) {
            $this->identityMap =
            $this->entityIdentifiers =
            $this->originalEntityData =
            $this->entityChangeSets =
            $this->entityStates =
            $this->scheduledForDirtyCheck =
            $this->entityInsertions =
            $this->entityUpdates =
            $this->entityDeletions =
            $this->collectionDeletions =
            $this->collectionUpdates =
            $this->extraUpdates =
            $this->readOnlyObjects =
            $this->orphanRemovals = array();

            if ($this->commitOrderCalculator !== null) {
                $this->commitOrderCalculator->clear();
            }
        } else {
            $visited = array();
            foreach ($this->identityMap as $className => $entities) {
                if ($className === $entityName) {
                    foreach ($entities as $entity) {
                        $this->doDetach($entity, $visited, true);
                    }
                }
            }
        }

        if ($this->evm->hasListeners(Events::onClear)) {
            $this->evm->dispatchEvent(Events::onClear, new Event\OnClearEventArgs($this->em, $entityName));
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
        $this->orphanRemovals[spl_object_hash($entity)] = $entity;
    }

    /**
     * INTERNAL:
     * Schedules a complete collection for removal when this UnitOfWork commits.
     *
     * @param PersistentCollection $coll
     */
    public function scheduleCollectionDeletion(PersistentCollection $coll)
    {
        $coid = spl_object_hash($coll);

        //TODO: if $coll is already scheduled for recreation ... what to do?
        // Just remove $coll from the scheduled recreations?
        if (isset($this->collectionUpdates[$coid])) {
            unset($this->collectionUpdates[$coid]);
        }

        $this->collectionDeletions[$coid] = $coll;
    }

    public function isCollectionScheduledForDeletion(PersistentCollection $coll)
    {
        return isset($this->collectionsDeletions[spl_object_hash($coll)]);
    }

    /**
     * @param ClassMetadata $class
     */
    private function newInstance($class)
    {
        $entity = $class->newInstance();

        if ($entity instanceof \Doctrine\Common\Persistence\ObjectManagerAware) {
            $entity->injectObjectManager($this->em, $class);
        }

        return $entity;
    }

    /**
     * INTERNAL:
     * Creates an entity. Used for reconstitution of persistent entities.
     *
     * @ignore
     * @param string $className The name of the entity class.
     * @param array $data The data for the entity.
     * @param array $hints Any hints to account for during reconstitution/lookup of the entity.
     * @return object The managed entity instance.
     * @internal Highly performance-sensitive method.
     *
     * @todo Rename: getOrCreateEntity
     */
    public function createEntity($className, array $data, &$hints = array())
    {
        $class = $this->em->getClassMetadata($className);
        //$isReadOnly = isset($hints[Query::HINT_READ_ONLY]);

        if ($class->isIdentifierComposite) {
            $id = array();

            foreach ($class->identifier as $fieldName) {
                $id[$fieldName] = isset($class->associationMappings[$fieldName])
                    ? $data[$class->associationMappings[$fieldName]['joinColumns'][0]['name']]
                    : $data[$fieldName];
            }

            $idHash = implode(' ', $id);
        } else {
            $idHash = isset($class->associationMappings[$class->identifier[0]])
                ? $data[$class->associationMappings[$class->identifier[0]]['joinColumns'][0]['name']]
                : $data[$class->identifier[0]];

            $id = array($class->identifier[0] => $idHash);
        }

        if (isset($this->identityMap[$class->rootEntityName][$idHash])) {
            $entity = $this->identityMap[$class->rootEntityName][$idHash];
            $oid = spl_object_hash($entity);

            if ($entity instanceof Proxy && ! $entity->__isInitialized__) {
                $entity->__isInitialized__ = true;
                $overrideLocalValues = true;

                if ($entity instanceof NotifyPropertyChanged) {
                    $entity->addPropertyChangedListener($this);
                }
            } else {
                $overrideLocalValues = isset($hints[Query::HINT_REFRESH]);

                // If only a specific entity is set to refresh, check that it's the one
                if(isset($hints[Query::HINT_REFRESH_ENTITY])) {
                    $overrideLocalValues = $hints[Query::HINT_REFRESH_ENTITY] === $entity;

                    // inject ObjectManager into just loaded proxies.
                    if ($overrideLocalValues && $entity instanceof ObjectManagerAware) {
                        $entity->injectObjectManager($this->em, $class);
                    }
                }
            }

            if ($overrideLocalValues) {
                $this->originalEntityData[$oid] = $data;
            }
        } else {
            $entity = $this->newInstance($class);
            $oid    = spl_object_hash($entity);

            $this->entityIdentifiers[$oid]  = $id;
            $this->entityStates[$oid]       = self::STATE_MANAGED;
            $this->originalEntityData[$oid] = $data;

            $this->identityMap[$class->rootEntityName][$idHash] = $entity;

            if ($entity instanceof NotifyPropertyChanged) {
                $entity->addPropertyChangedListener($this);
            }

            $overrideLocalValues = true;
        }

        if ( ! $overrideLocalValues) {
            return $entity;
        }

        foreach ($data as $field => $value) {
            if (isset($class->fieldMappings[$field])) {
                $class->reflFields[$field]->setValue($entity, $value);
            }
        }

        // Loading the entity right here, if its in the eager loading map get rid of it there.
        unset($this->eagerLoadingEntities[$class->rootEntityName][$idHash]);

        if (isset($this->eagerLoadingEntities[$class->rootEntityName]) && ! $this->eagerLoadingEntities[$class->rootEntityName]) {
            unset($this->eagerLoadingEntities[$class->rootEntityName]);
        }

        // Properly initialize any unfetched associations, if partial objects are not allowed.
        if (isset($hints[Query::HINT_FORCE_PARTIAL_LOAD])) {
            return $entity;
        }

        foreach ($class->associationMappings as $field => $assoc) {
            // Check if the association is not among the fetch-joined associations already.
            if (isset($hints['fetchAlias']) && isset($hints['fetched'][$hints['fetchAlias']][$field])) {
                continue;
            }

            $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);

            switch (true) {
                case ($assoc['type'] & ClassMetadata::TO_ONE):
                    if ( ! $assoc['isOwningSide']) {
                        // Inverse side of x-to-one can never be lazy
                        $class->reflFields[$field]->setValue($entity, $this->getEntityPersister($assoc['targetEntity'])->loadOneToOneEntity($assoc, $entity));

                        continue 2;
                    }

                    $associatedId = array();

                    // TODO: Is this even computed right in all cases of composite keys?
                    foreach ($assoc['targetToSourceKeyColumns'] as $targetColumn => $srcColumn) {
                        $joinColumnValue = isset($data[$srcColumn]) ? $data[$srcColumn] : null;

                        if ($joinColumnValue !== null) {
                            if ($targetClass->containsForeignIdentifier) {
                                $associatedId[$targetClass->getFieldForColumn($targetColumn)] = $joinColumnValue;
                            } else {
                                $associatedId[$targetClass->fieldNames[$targetColumn]] = $joinColumnValue;
                            }
                        }
                    }

                    if ( ! $associatedId) {
                        // Foreign key is NULL
                        $class->reflFields[$field]->setValue($entity, null);
                        $this->originalEntityData[$oid][$field] = null;

                        continue;
                    }

                    if ( ! isset($hints['fetchMode'][$class->name][$field])) {
                        $hints['fetchMode'][$class->name][$field] = $assoc['fetch'];
                    }

                    // Foreign key is set
                    // Check identity map first
                    // FIXME: Can break easily with composite keys if join column values are in
                    //        wrong order. The correct order is the one in ClassMetadata#identifier.
                    $relatedIdHash = implode(' ', $associatedId);

                    switch (true) {
                        case (isset($this->identityMap[$targetClass->rootEntityName][$relatedIdHash])):
                            $newValue = $this->identityMap[$targetClass->rootEntityName][$relatedIdHash];

                            // if this is an uninitialized proxy, we are deferring eager loads,
                            // this association is marked as eager fetch, and its an uninitialized proxy (wtf!)
                            // then we cann append this entity for eager loading!
                            if ($hints['fetchMode'][$class->name][$field] == ClassMetadata::FETCH_EAGER &&
                                isset($hints['deferEagerLoad']) &&
                                !$targetClass->isIdentifierComposite &&
                                $newValue instanceof Proxy &&
                                $newValue->__isInitialized__ === false) {

                                $this->eagerLoadingEntities[$targetClass->rootEntityName][$relatedIdHash] = current($associatedId);
                            }

                            break;

                        case ($targetClass->subClasses):
                            // If it might be a subtype, it can not be lazy. There isn't even
                            // a way to solve this with deferred eager loading, which means putting
                            // an entity with subclasses at a *-to-one location is really bad! (performance-wise)
                            $newValue = $this->getEntityPersister($assoc['targetEntity'])->loadOneToOneEntity($assoc, $entity, $associatedId);
                            break;

                        default:
                            switch (true) {
                                // We are negating the condition here. Other cases will assume it is valid!
                                case ($hints['fetchMode'][$class->name][$field] !== ClassMetadata::FETCH_EAGER):
                                    $newValue = $this->em->getProxyFactory()->getProxy($assoc['targetEntity'], $associatedId);
                                    break;

                                // Deferred eager load only works for single identifier classes
                                case (isset($hints['deferEagerLoad']) && ! $targetClass->isIdentifierComposite):
                                    // TODO: Is there a faster approach?
                                    $this->eagerLoadingEntities[$targetClass->rootEntityName][$relatedIdHash] = current($associatedId);

                                    $newValue = $this->em->getProxyFactory()->getProxy($assoc['targetEntity'], $associatedId);
                                    break;

                                default:
                                    // TODO: This is very imperformant, ignore it?
                                    $newValue = $this->em->find($assoc['targetEntity'], $associatedId);
                                    break;
                            }

                            // PERF: Inlined & optimized code from UnitOfWork#registerManaged()
                            $newValueOid = spl_object_hash($newValue);
                            $this->entityIdentifiers[$newValueOid] = $associatedId;
                            $this->identityMap[$targetClass->rootEntityName][$relatedIdHash] = $newValue;
                            $this->entityStates[$newValueOid] = self::STATE_MANAGED;
                            // make sure that when an proxy is then finally loaded, $this->originalEntityData is set also!
                            break;
                    }

                    $this->originalEntityData[$oid][$field] = $newValue;
                    $class->reflFields[$field]->setValue($entity, $newValue);

                    if ($assoc['inversedBy'] && $assoc['type'] & ClassMetadata::ONE_TO_ONE) {
                        $inverseAssoc = $targetClass->associationMappings[$assoc['inversedBy']];
                        $targetClass->reflFields[$inverseAssoc['fieldName']]->setValue($newValue, $entity);
                    }

                    break;

                default:
                    // Inject collection
                    $pColl = new PersistentCollection($this->em, $targetClass, new ArrayCollection);
                    $pColl->setOwner($entity, $assoc);
                    $pColl->setInitialized(false);

                    $reflField = $class->reflFields[$field];
                    $reflField->setValue($entity, $pColl);

                    if ($assoc['fetch'] == ClassMetadata::FETCH_EAGER) {
                        $this->loadCollection($pColl);
                        $pColl->takeSnapshot();
                    }

                    $this->originalEntityData[$oid][$field] = $pColl;
                    break;
            }
        }

        if ($overrideLocalValues) {
            if (isset($class->lifecycleCallbacks[Events::postLoad])) {
                $class->invokeLifecycleCallbacks(Events::postLoad, $entity);
            }


            if ($this->evm->hasListeners(Events::postLoad)) {
                $this->evm->dispatchEvent(Events::postLoad, new LifecycleEventArgs($entity, $this->em));
            }
        }

        return $entity;
    }

    /**
     * @return void
     */
    public function triggerEagerLoads()
    {
        if ( ! $this->eagerLoadingEntities) {
            return;
        }

        // avoid infinite recursion
        $eagerLoadingEntities       = $this->eagerLoadingEntities;
        $this->eagerLoadingEntities = array();

        foreach ($eagerLoadingEntities as $entityName => $ids) {
            $class = $this->em->getClassMetadata($entityName);

            if ($ids) {
                $this->getEntityPersister($entityName)->loadAll(
                    array_combine($class->identifier, array(array_values($ids)))
                );
            }
        }
    }

    /**
     * Initializes (loads) an uninitialized persistent collection of an entity.
     *
     * @param PeristentCollection $collection The collection to initialize.
     * @todo Maybe later move to EntityManager#initialize($proxyOrCollection). See DDC-733.
     */
    public function loadCollection(PersistentCollection $collection)
    {
        $assoc     = $collection->getMapping();
        $persister = $this->getEntityPersister($assoc['targetEntity']);

        switch ($assoc['type']) {
            case ClassMetadata::ONE_TO_MANY:
                $persister->loadOneToManyCollection($assoc, $collection->getOwner(), $collection);
                break;

            case ClassMetadata::MANY_TO_MANY:
                $persister->loadManyToManyCollection($assoc, $collection->getOwner(), $collection);
                break;
        }
    }

    /**
     * Gets the identity map of the UnitOfWork.
     *
     * @return array
     */
    public function getIdentityMap()
    {
        return $this->identityMap;
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

        if (isset($this->originalEntityData[$oid])) {
            return $this->originalEntityData[$oid];
        }

        return array();
    }

    /**
     * @ignore
     */
    public function setOriginalEntityData($entity, array $data)
    {
        $this->originalEntityData[spl_object_hash($entity)] = $data;
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
        $this->originalEntityData[$oid][$property] = $value;
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
        return $this->entityIdentifiers[spl_object_hash($entity)];
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

        if (isset($this->identityMap[$rootClassName][$idHash])) {
            return $this->identityMap[$rootClassName][$idHash];
        }

        return false;
    }

    /**
     * Schedules an entity for dirty-checking at commit-time.
     *
     * @param object $entity The entity to schedule for dirty-checking.
     * @todo Rename: scheduleForSynchronization
     */
    public function scheduleForDirtyCheck($entity)
    {
        $rootClassName = $this->em->getClassMetadata(get_class($entity))->rootEntityName;

        $this->scheduledForDirtyCheck[$rootClassName][spl_object_hash($entity)] = $entity;
    }

    /**
     * Checks whether the UnitOfWork has any pending insertions.
     *
     * @return boolean TRUE if this UnitOfWork has pending insertions, FALSE otherwise.
     */
    public function hasPendingInsertions()
    {
        return ! empty($this->entityInsertions);
    }

    /**
     * Calculates the size of the UnitOfWork. The size of the UnitOfWork is the
     * number of entities in the identity map.
     *
     * @return integer
     */
    public function size()
    {
        $countArray = array_map(function ($item) { return count($item); }, $this->identityMap);

        return array_sum($countArray);
    }

    /**
     * Gets the EntityPersister for an Entity.
     *
     * @param string $entityName  The name of the Entity.
     *
     * @return \Doctrine\ORM\Persisters\BasicEntityPersister
     */
    public function getEntityPersister($entityName)
    {
        if (isset($this->persisters[$entityName])) {
            return $this->persisters[$entityName];
        }

        $class = $this->em->getClassMetadata($entityName);

        switch (true) {
            case ($class->isInheritanceTypeNone()):
                $persister = new Persisters\BasicEntityPersister($this->em, $class);
                break;

            case ($class->isInheritanceTypeSingleTable()):
                $persister = new Persisters\SingleTablePersister($this->em, $class);
                break;

            case ($class->isInheritanceTypeJoined()):
                $persister = new Persisters\JoinedSubclassPersister($this->em, $class);
                break;

            default:
                $persister = new Persisters\UnionSubclassPersister($this->em, $class);
        }

        $this->persisters[$entityName] = $persister;

        return $this->persisters[$entityName];
    }

    /**
     * Gets a collection persister for a collection-valued association.
     *
     * @param AssociationMapping $association
     *
     * @return AbstractCollectionPersister
     */
    public function getCollectionPersister(array $association)
    {
        $type = $association['type'];

        if (isset($this->collectionPersisters[$type])) {
            return $this->collectionPersisters[$type];
        }

        switch ($type) {
            case ClassMetadata::ONE_TO_MANY:
                $persister = new Persisters\OneToManyPersister($this->em);
                break;

            case ClassMetadata::MANY_TO_MANY:
                $persister = new Persisters\ManyToManyPersister($this->em);
                break;
        }

        $this->collectionPersisters[$type] = $persister;

        return $this->collectionPersisters[$type];
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

        $this->entityIdentifiers[$oid]  = $id;
        $this->entityStates[$oid]       = self::STATE_MANAGED;
        $this->originalEntityData[$oid] = $data;

        $this->addToIdentityMap($entity);

        if ($entity instanceof NotifyPropertyChanged) {
            $entity->addPropertyChangedListener($this);
        }
    }

    /**
     * INTERNAL:
     * Clears the property changeset of the entity with the given OID.
     *
     * @param string $oid The entity's OID.
     */
    public function clearEntityChangeSet($oid)
    {
        $this->entityChangeSets[$oid] = array();
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
        $oid   = spl_object_hash($entity);
        $class = $this->em->getClassMetadata(get_class($entity));

        $isAssocField = isset($class->associationMappings[$propertyName]);

        if ( ! $isAssocField && ! isset($class->fieldMappings[$propertyName])) {
            return; // ignore non-persistent fields
        }

        // Update changeset and mark entity for synchronization
        $this->entityChangeSets[$oid][$propertyName] = array($oldValue, $newValue);

        if ( ! isset($this->scheduledForDirtyCheck[$class->rootEntityName][$oid])) {
            $this->scheduleForDirtyCheck($entity);
        }
    }

    /**
     * Gets the currently scheduled entity insertions in this UnitOfWork.
     *
     * @return array
     */
    public function getScheduledEntityInsertions()
    {
        return $this->entityInsertions;
    }

    /**
     * Gets the currently scheduled entity updates in this UnitOfWork.
     *
     * @return array
     */
    public function getScheduledEntityUpdates()
    {
        return $this->entityUpdates;
    }

    /**
     * Gets the currently scheduled entity deletions in this UnitOfWork.
     *
     * @return array
     */
    public function getScheduledEntityDeletions()
    {
        return $this->entityDeletions;
    }

    /**
     * Get the currently scheduled complete collection deletions
     *
     * @return array
     */
    public function getScheduledCollectionDeletions()
    {
        return $this->collectionDeletions;
    }

    /**
     * Gets the currently scheduled collection inserts, updates and deletes.
     *
     * @return array
     */
    public function getScheduledCollectionUpdates()
    {
        return $this->collectionUpdates;
    }

    /**
     * Helper method to initialize a lazy loading proxy or persistent collection.
     *
     * @param object
     * @return void
     */
    public function initializeObject($obj)
    {
        if ($obj instanceof Proxy) {
            $obj->__load();

            return;
        }

        if ($obj instanceof PersistentCollection) {
            $obj->initialize();
        }
    }

    /**
     * Helper method to show an object as string.
     *
     * @param  object $obj
     * @return string
     */
    private static function objToStr($obj)
    {
        return method_exists($obj, '__toString') ? (string)$obj : get_class($obj).'@'.spl_object_hash($obj);
    }

    /**
     * Marks an entity as read-only so that it will not be considered for updates during UnitOfWork#commit().
     *
     * This operation cannot be undone as some parts of the UnitOfWork now keep gathering information
     * on this object that might be necessary to perform a correct udpate.
     *
     * @throws \InvalidArgumentException
     * @param $object
     * @return void
     */
    public function markReadOnly($object)
    {
        if ( ! is_object($object) || ! $this->isInIdentityMap($object)) {
            throw ORMInvalidArgumentException::readOnlyRequiresManagedEntity($object);
        }

        $this->readOnlyObjects[spl_object_hash($object)] = true;
    }

    /**
     * Is this entity read only?
     *
     * @throws \InvalidArgumentException
     * @param $object
     * @return void
     */
    public function isReadOnly($object)
    {
        if ( ! is_object($object) ) {
            throw ORMInvalidArgumentException::readOnlyRequiresManagedEntity($object);
        }

        return isset($this->readOnlyObjects[spl_object_hash($object)]);
    }
}
