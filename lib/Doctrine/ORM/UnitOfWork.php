<?php

declare(strict_types=1);

namespace Doctrine\ORM;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\EventManager;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\DBAL;
use Doctrine\DBAL\Connections\PrimaryReadReplicaConnection;
use Doctrine\DBAL\LockMode;
use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\Cache\Persister\CachedPersister;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\ListenersInvoker;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Exception\UnexpectedAssociationValue;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Internal\CommitOrderCalculator;
use Doctrine\ORM\Internal\HydrationCompleteHandler;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Persisters\Collection\CollectionPersister;
use Doctrine\ORM\Persisters\Collection\ManyToManyPersister;
use Doctrine\ORM\Persisters\Collection\OneToManyPersister;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\Persisters\Entity\JoinedSubclassPersister;
use Doctrine\ORM\Persisters\Entity\SingleTablePersister;
use Doctrine\ORM\Utility\IdentifierFlattener;
use Doctrine\Persistence\NotifyPropertyChanged;
use Doctrine\Persistence\PropertyChangedListener;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Stringable;
use Throwable;
use UnexpectedValueException;

use function array_combine;
use function array_diff_key;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_pop;
use function array_sum;
use function array_values;
use function assert;
use function count;
use function current;
use function get_debug_type;
use function implode;
use function in_array;
use function is_array;
use function is_object;
use function reset;
use function spl_object_id;
use function sprintf;

/**
 * The UnitOfWork is responsible for tracking changes to objects during an
 * "object-level" transaction and for writing out changes to the database
 * in the correct order.
 *
 * Internal note: This class contains highly performance-sensitive code.
 *
 * @psalm-import-type AssociationMapping from ClassMetadataInfo
 */
class UnitOfWork implements PropertyChangedListener
{
    /**
     * An entity is in MANAGED state when its persistence is managed by an EntityManager.
     */
    public const STATE_MANAGED = 1;

    /**
     * An entity is new if it has just been instantiated (i.e. using the "new" operator)
     * and is not (yet) managed by an EntityManager.
     */
    public const STATE_NEW = 2;

    /**
     * A detached entity is an instance with persistent state and identity that is not
     * (or no longer) associated with an EntityManager (and a UnitOfWork).
     */
    public const STATE_DETACHED = 3;

    /**
     * A removed entity instance is an instance with a persistent identity,
     * associated with an EntityManager, whose persistent state will be deleted
     * on commit.
     */
    public const STATE_REMOVED = 4;

    /**
     * Hint used to collect all primary keys of associated entities during hydration
     * and execute it in a dedicated query afterwards
     *
     * @see https://www.doctrine-project.org/projects/doctrine-orm/en/stable/reference/dql-doctrine-query-language.html#temporarily-change-fetch-mode-in-dql
     */
    public const HINT_DEFEREAGERLOAD = 'deferEagerLoad';

    /**
     * The identity map that holds references to all managed entities that have
     * an identity. The entities are grouped by their class name.
     * Since all classes in a hierarchy must share the same identifier set,
     * we always take the root class name of the hierarchy.
     *
     * @psalm-var array<class-string, array<string, object|null>>
     */
    private array $identityMap = [];

    /**
     * Map of all identifiers of managed entities.
     * Keys are object ids (spl_object_id).
     *
     * @psalm-var array<int, array<string, mixed>>
     */
    private array $entityIdentifiers = [];

    /**
     * Map of the original entity data of managed entities.
     * Keys are object ids (spl_object_id). This is used for calculating changesets
     * at commit time.
     *
     * Internal note: Note that PHPs "copy-on-write" behavior helps a lot with memory usage.
     *                A value will only really be copied if the value in the entity is modified
     *                by the user.
     *
     * @psalm-var array<int, array<string, mixed>>
     */
    private array $originalEntityData = [];

    /**
     * Map of entity changes. Keys are object ids (spl_object_id).
     * Filled at the beginning of a commit of the UnitOfWork and cleaned at the end.
     *
     * @psalm-var array<int, array<string, array{mixed, mixed}>>
     */
    private array $entityChangeSets = [];

    /**
     * The (cached) states of any known entities.
     * Keys are object ids (spl_object_id).
     *
     * @psalm-var array<int, self::STATE_*>
     */
    private array $entityStates = [];

    /**
     * Map of entities that are scheduled for dirty checking at commit time.
     * This is only used for entities with a change tracking policy of DEFERRED_EXPLICIT.
     * Keys are object ids (spl_object_id).
     *
     * @psalm-var array<class-string, array<int, mixed>>
     */
    private array $scheduledForSynchronization = [];

    /**
     * A list of all pending entity insertions.
     *
     * @psalm-var array<int, object>
     */
    private array $entityInsertions = [];

    /**
     * A list of all pending entity updates.
     *
     * @psalm-var array<int, object>
     */
    private array $entityUpdates = [];

    /**
     * Any pending extra updates that have been scheduled by persisters.
     *
     * @psalm-var array<int, array{object, array<string, array{mixed, mixed}>}>
     */
    private array $extraUpdates = [];

    /**
     * A list of all pending entity deletions.
     *
     * @psalm-var array<int, object>
     */
    private array $entityDeletions = [];

    /**
     * New entities that were discovered through relationships that were not
     * marked as cascade-persist. During flush, this array is populated and
     * then pruned of any entities that were discovered through a valid
     * cascade-persist path. (Leftovers cause an error.)
     *
     * Keys are OIDs, payload is a two-item array describing the association
     * and the entity.
     *
     * @var object[][]|array[][] indexed by respective object spl_object_id()
     */
    private array $nonCascadedNewDetectedEntities = [];

    /**
     * All pending collection deletions.
     *
     * @psalm-var array<int, Collection<array-key, object>>
     */
    private array $collectionDeletions = [];

    /**
     * All pending collection updates.
     *
     * @psalm-var array<int, Collection<array-key, object>>
     */
    private array $collectionUpdates = [];

    /**
     * List of collections visited during changeset calculation on a commit-phase of a UnitOfWork.
     * At the end of the UnitOfWork all these collections will make new snapshots
     * of their data.
     *
     * @psalm-var array<int, Collection<array-key, object>>
     */
    private array $visitedCollections = [];

    /**
     * The entity persister instances used to persist entity instances.
     *
     * @psalm-var array<string, EntityPersister>
     */
    private array $persisters = [];

    /**
     * The collection persister instances used to persist collections.
     *
     * @psalm-var array<string, CollectionPersister>
     */
    private array $collectionPersisters = [];

    /**
     * The EventManager used for dispatching events.
     */
    private readonly EventManager $evm;

    /**
     * The ListenersInvoker used for dispatching events.
     */
    private readonly ListenersInvoker $listenersInvoker;

    /**
     * The IdentifierFlattener used for manipulating identifiers
     */
    private readonly IdentifierFlattener $identifierFlattener;

    /**
     * Orphaned entities that are scheduled for removal.
     *
     * @psalm-var array<int, object>
     */
    private array $orphanRemovals = [];

    /**
     * Read-Only objects are never evaluated
     *
     * @var array<int, true>
     */
    private array $readOnlyObjects = [];

    /**
     * Map of Entity Class-Names and corresponding IDs that should eager loaded when requested.
     *
     * @psalm-var array<class-string, array<string, mixed>>
     */
    private array $eagerLoadingEntities = [];

    protected bool $hasCache = false;

    /**
     * Helper for handling completion of hydration
     */
    private readonly HydrationCompleteHandler $hydrationCompleteHandler;

    /**
     * Initializes a new UnitOfWork instance, bound to the given EntityManager.
     *
     * @param EntityManagerInterface $em The EntityManager that "owns" this UnitOfWork instance.
     */
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
        $this->evm                      = $em->getEventManager();
        $this->listenersInvoker         = new ListenersInvoker($em);
        $this->hasCache                 = $em->getConfiguration()->isSecondLevelCacheEnabled();
        $this->identifierFlattener      = new IdentifierFlattener($this, $em->getMetadataFactory());
        $this->hydrationCompleteHandler = new HydrationCompleteHandler($this->listenersInvoker, $em);
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
     * @throws Exception
     */
    public function commit(): void
    {
        $connection = $this->em->getConnection();

        if ($connection instanceof PrimaryReadReplicaConnection) {
            $connection->ensureConnectedToPrimary();
        }

        // Raise preFlush
        if ($this->evm->hasListeners(Events::preFlush)) {
            $this->evm->dispatchEvent(Events::preFlush, new PreFlushEventArgs($this->em));
        }

        // Compute changes done since last commit.
        $this->computeChangeSets();

        if (
            ! ($this->entityInsertions ||
                $this->entityDeletions ||
                $this->entityUpdates ||
                $this->collectionUpdates ||
                $this->collectionDeletions ||
                $this->orphanRemovals)
        ) {
            $this->dispatchOnFlushEvent();
            $this->dispatchPostFlushEvent();

            $this->postCommitCleanup();

            return; // Nothing to do.
        }

        $this->assertThatThereAreNoUnintentionallyNonPersistedAssociations();

        if ($this->orphanRemovals) {
            foreach ($this->orphanRemovals as $orphan) {
                $this->remove($orphan);
            }
        }

        $this->dispatchOnFlushEvent();

        // Now we need a commit order to maintain referential integrity
        $commitOrder = $this->getCommitOrder();

        $conn = $this->em->getConnection();
        $conn->beginTransaction();

        try {
            // Collection deletions (deletions of complete collections)
            foreach ($this->collectionDeletions as $collectionToDelete) {
                if (! $collectionToDelete instanceof PersistentCollection) {
                    $this->getCollectionPersister($collectionToDelete->getMapping())->delete($collectionToDelete);

                    continue;
                }

                // Deferred explicit tracked collections can be removed only when owning relation was persisted
                $owner = $collectionToDelete->getOwner();

                if ($this->em->getClassMetadata($owner::class)->isChangeTrackingDeferredImplicit() || $this->isScheduledForDirtyCheck($owner)) {
                    $this->getCollectionPersister($collectionToDelete->getMapping())->delete($collectionToDelete);
                }
            }

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

            // Collection updates (deleteRows, updateRows, insertRows)
            foreach ($this->collectionUpdates as $collectionToUpdate) {
                $this->getCollectionPersister($collectionToUpdate->getMapping())->update($collectionToUpdate);
            }

            // Entity deletions come last and need to be in reverse commit order
            if ($this->entityDeletions) {
                for ($count = count($commitOrder), $i = $count - 1; $i >= 0 && $this->entityDeletions; --$i) {
                    $this->executeDeletions($commitOrder[$i]);
                }
            }

            $commitFailed = false;
            try {
                if ($conn->commit() === false) {
                    $commitFailed = true;
                }
            } catch (DBAL\Exception $e) {
                $commitFailed = true;
            }

            if ($commitFailed) {
                throw new OptimisticLockException('Commit failed', null, $e ?? null);
            }
        } catch (Throwable $e) {
            $this->em->close();

            if ($conn->isTransactionActive()) {
                $conn->rollBack();
            }

            $this->afterTransactionRolledBack();

            throw $e;
        }

        $this->afterTransactionComplete();

        // Take new snapshots from visited collections
        foreach ($this->visitedCollections as $coll) {
            $coll->takeSnapshot();
        }

        $this->dispatchPostFlushEvent();

        $this->postCommitCleanup();
    }

    private function postCommitCleanup(): void
    {
        $this->entityInsertions               =
        $this->entityUpdates                  =
        $this->entityDeletions                =
        $this->extraUpdates                   =
        $this->collectionUpdates              =
        $this->nonCascadedNewDetectedEntities =
        $this->collectionDeletions            =
        $this->visitedCollections             =
        $this->orphanRemovals                 =
        $this->entityChangeSets               =
        $this->scheduledForSynchronization    = [];
    }

    /**
     * Computes the changesets of all entities scheduled for insertion.
     */
    private function computeScheduleInsertsChangeSets(): void
    {
        foreach ($this->entityInsertions as $entity) {
            $class = $this->em->getClassMetadata($entity::class);

            $this->computeChangeSet($class, $entity);
        }
    }

    /**
     * Executes any extra updates that have been scheduled.
     */
    private function executeExtraUpdates(): void
    {
        foreach ($this->extraUpdates as $oid => $update) {
            [$entity, $changeset] = $update;

            $this->entityChangeSets[$oid] = $changeset;
            $this->getEntityPersister($entity::class)->update($entity);
        }

        $this->extraUpdates = [];
    }

    /**
     * Gets the changeset for an entity.
     *
     * @return mixed[][]
     * @psalm-return array<string, array{mixed, mixed}|PersistentCollection>
     */
    public function & getEntityChangeSet(object $entity): array
    {
        $oid  = spl_object_id($entity);
        $data = [];

        if (! isset($this->entityChangeSets[$oid])) {
            return $data;
        }

        return $this->entityChangeSets[$oid];
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
     * @param ClassMetadata $class  The class descriptor of the entity.
     * @param object        $entity The entity for which to compute the changes.
     * @psalm-param ClassMetadata<T> $class
     * @psalm-param T $entity
     *
     * @template T of object
     *
     * @ignore
     */
    public function computeChangeSet(ClassMetadata $class, object $entity): void
    {
        $oid = spl_object_id($entity);

        if (isset($this->readOnlyObjects[$oid])) {
            return;
        }

        if (! $class->isInheritanceTypeNone()) {
            $class = $this->em->getClassMetadata($entity::class);
        }

        $invoke = $this->listenersInvoker->getSubscribedSystems($class, Events::preFlush) & ~ListenersInvoker::INVOKE_MANAGER;

        if ($invoke !== ListenersInvoker::INVOKE_NONE) {
            $this->listenersInvoker->invoke($class, Events::preFlush, $entity, new PreFlushEventArgs($this->em), $invoke);
        }

        $actualData = [];

        foreach ($class->reflFields as $name => $refProp) {
            $value = $refProp->getValue($entity);

            if ($class->isCollectionValuedAssociation($name) && $value !== null) {
                if ($value instanceof PersistentCollection) {
                    if ($value->getOwner() === $entity) {
                        continue;
                    }

                    $value = new ArrayCollection($value->getValues());
                }

                // If $value is not a Collection then use an ArrayCollection.
                if (! $value instanceof Collection) {
                    $value = new ArrayCollection($value);
                }

                $assoc = $class->associationMappings[$name];

                // Inject PersistentCollection
                $value = new PersistentCollection(
                    $this->em,
                    $this->em->getClassMetadata($assoc['targetEntity']),
                    $value
                );
                $value->setOwner($entity, $assoc);
                $value->setDirty(! $value->isEmpty());

                $class->reflFields[$name]->setValue($entity, $value);

                $actualData[$name] = $value;

                continue;
            }

            if (( ! $class->isIdentifier($name) || ! $class->isIdGeneratorIdentity()) && ($name !== $class->versionField)) {
                $actualData[$name] = $value;
            }
        }

        if (! isset($this->originalEntityData[$oid])) {
            // Entity is either NEW or MANAGED but not yet fully persisted (only has an id).
            // These result in an INSERT.
            $this->originalEntityData[$oid] = $actualData;
            $changeSet                      = [];

            foreach ($actualData as $propName => $actualValue) {
                if (! isset($class->associationMappings[$propName])) {
                    $changeSet[$propName] = [null, $actualValue];

                    continue;
                }

                $assoc = $class->associationMappings[$propName];

                if ($assoc['isOwningSide'] && $assoc['type'] & ClassMetadata::TO_ONE) {
                    $changeSet[$propName] = [null, $actualValue];
                }
            }

            $this->entityChangeSets[$oid] = $changeSet;
        } else {
            // Entity is "fully" MANAGED: it was already fully persisted before
            // and we have a copy of the original data
            $originalData           = $this->originalEntityData[$oid];
            $isChangeTrackingNotify = $class->isChangeTrackingNotify();
            $changeSet              = $isChangeTrackingNotify && isset($this->entityChangeSets[$oid])
                ? $this->entityChangeSets[$oid]
                : [];

            foreach ($actualData as $propName => $actualValue) {
                // skip field, its a partially omitted one!
                if (! (isset($originalData[$propName]) || array_key_exists($propName, $originalData))) {
                    continue;
                }

                $orgValue = $originalData[$propName];

                // skip if value haven't changed
                if ($orgValue === $actualValue) {
                    continue;
                }

                // if regular field
                if (! isset($class->associationMappings[$propName])) {
                    if ($isChangeTrackingNotify) {
                        continue;
                    }

                    $changeSet[$propName] = [$orgValue, $actualValue];

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
                    } elseif ($owner !== $entity) { // no clone, we have to fix
                        if (! $actualValue->isInitialized()) {
                            $actualValue->initialize(); // we have to do this otherwise the cols share state
                        }

                        $newValue = clone $actualValue;
                        $newValue->setOwner($entity, $assoc);
                        $class->reflFields[$propName]->setValue($entity, $newValue);
                    }
                }

                if ($orgValue instanceof PersistentCollection) {
                    // A PersistentCollection was de-referenced, so delete it.
                    $coid = spl_object_id($orgValue);

                    if (isset($this->collectionDeletions[$coid])) {
                        continue;
                    }

                    $this->collectionDeletions[$coid] = $orgValue;
                    $changeSet[$propName]             = $orgValue; // Signal changeset, to-many assocs will be ignored.

                    continue;
                }

                if ($assoc['type'] & ClassMetadata::TO_ONE) {
                    if ($assoc['isOwningSide']) {
                        $changeSet[$propName] = [$orgValue, $actualValue];
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
            $val = $class->reflFields[$field]->getValue($entity);
            if ($val === null) {
                continue;
            }

            $this->computeAssociationChanges($assoc, $val);

            if (
                ! isset($this->entityChangeSets[$oid]) &&
                $assoc['isOwningSide'] &&
                $assoc['type'] === ClassMetadata::MANY_TO_MANY &&
                $val instanceof PersistentCollection &&
                $val->isDirty()
            ) {
                $this->entityChangeSets[$oid]   = [];
                $this->originalEntityData[$oid] = $actualData;
                $this->entityUpdates[$oid]      = $entity;
            }
        }
    }

    /**
     * Computes all the changes that have been done to entities and collections
     * since the last commit and stores these changes in the _entityChangeSet map
     * temporarily for access by the persisters, until the UoW commit is finished.
     */
    public function computeChangeSets(): void
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

            $entitiesToProcess = match (true) {
                $class->isChangeTrackingDeferredImplicit() => $entities,
                isset($this->scheduledForSynchronization[$className]) => $this->scheduledForSynchronization[$className],
                default => [],
            };

            foreach ($entitiesToProcess as $entity) {
                // Ignore uninitialized proxy objects
                if ($entity instanceof Proxy && ! $entity->__isInitialized()) {
                    continue;
                }

                // Only MANAGED entities that are NOT SCHEDULED FOR INSERTION OR DELETION are processed here.
                $oid = spl_object_id($entity);

                if (! isset($this->entityInsertions[$oid]) && ! isset($this->entityDeletions[$oid]) && isset($this->entityStates[$oid])) {
                    $this->computeChangeSet($class, $entity);
                }
            }
        }
    }

    /**
     * Computes the changes of an association.
     *
     * @param mixed $value The value of the association.
     * @psalm-param array<string, mixed> $assoc The association mapping.
     *
     * @throws ORMInvalidArgumentException
     * @throws ORMException
     */
    private function computeAssociationChanges(array $assoc, mixed $value): void
    {
        if ($value instanceof Proxy && ! $value->__isInitialized()) {
            return;
        }

        if ($value instanceof PersistentCollection && $value->isDirty()) {
            $coid = spl_object_id($value);

            $this->collectionUpdates[$coid]  = $value;
            $this->visitedCollections[$coid] = $value;
        }

        // Look through the entities, and in any of their associations,
        // for transient (new) entities, recursively. ("Persistence by reachability")
        // Unwrap. Uninitialized collections will simply be empty.
        $unwrappedValue = $assoc['type'] & ClassMetadata::TO_ONE ? [$value] : $value->unwrap();
        $targetClass    = $this->em->getClassMetadata($assoc['targetEntity']);

        foreach ($unwrappedValue as $key => $entry) {
            if (! ($entry instanceof $targetClass->name)) {
                throw ORMInvalidArgumentException::invalidAssociation($targetClass, $assoc, $entry);
            }

            $state = $this->getEntityState($entry, self::STATE_NEW);

            if (! ($entry instanceof $assoc['targetEntity'])) {
                throw UnexpectedAssociationValue::create(
                    $assoc['sourceEntity'],
                    $assoc['fieldName'],
                    get_debug_type($entry),
                    $assoc['targetEntity']
                );
            }

            switch ($state) {
                case self::STATE_NEW:
                    if (! $assoc['isCascadePersist']) {
                        /*
                         * For now just record the details, because this may
                         * not be an issue if we later discover another pathway
                         * through the object-graph where cascade-persistence
                         * is enabled for this object.
                         */
                        $this->nonCascadedNewDetectedEntities[spl_object_id($entry)] = [$assoc, $entry];

                        break;
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

                default:
                    // MANAGED associated entities are already taken into account
                    // during changeset calculation anyway, since they are in the identity map.
            }
        }
    }

    /**
     * @psalm-param ClassMetadata<T> $class
     * @psalm-param T $entity
     *
     * @template T of object
     */
    private function persistNew(ClassMetadata $class, object $entity): void
    {
        $oid    = spl_object_id($entity);
        $invoke = $this->listenersInvoker->getSubscribedSystems($class, Events::prePersist);

        if ($invoke !== ListenersInvoker::INVOKE_NONE) {
            $this->listenersInvoker->invoke($class, Events::prePersist, $entity, new LifecycleEventArgs($entity, $this->em), $invoke);
        }

        $idGen = $class->idGenerator;

        if (! $idGen->isPostInsertGenerator()) {
            $idValue = $idGen->generateId($this->em, $entity);

            if (! $idGen instanceof AssignedGenerator) {
                $idValue = [$class->getSingleIdentifierFieldName() => $this->convertSingleFieldIdentifierToPHPValue($class, $idValue)];

                $class->setIdentifierValues($entity, $idValue);
            }

            // Some identifiers may be foreign keys to new entities.
            // In this case, we don't have the value yet and should treat it as if we have a post-insert generator
            if (! $this->hasMissingIdsWhichAreForeignKeys($class, $idValue)) {
                $this->entityIdentifiers[$oid] = $idValue;
            }
        }

        $this->entityStates[$oid] = self::STATE_MANAGED;

        $this->scheduleForInsert($entity);
    }

    /**
     * @param mixed[] $idValue
     */
    private function hasMissingIdsWhichAreForeignKeys(ClassMetadata $class, array $idValue): bool
    {
        foreach ($idValue as $idField => $idFieldValue) {
            if ($idFieldValue === null && isset($class->associationMappings[$idField])) {
                return true;
            }
        }

        return false;
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
     * @param ClassMetadata $class  The class descriptor of the entity.
     * @param object        $entity The entity for which to (re)calculate the change set.
     * @psalm-param ClassMetadata<T> $class
     * @psalm-param T $entity
     *
     * @throws ORMInvalidArgumentException If the passed entity is not MANAGED.
     *
     * @template T of object
     * @ignore
     */
    public function recomputeSingleEntityChangeSet(ClassMetadata $class, object $entity): void
    {
        $oid = spl_object_id($entity);

        if (! isset($this->entityStates[$oid]) || $this->entityStates[$oid] !== self::STATE_MANAGED) {
            throw ORMInvalidArgumentException::entityNotManaged($entity);
        }

        // skip if change tracking is "NOTIFY"
        if ($class->isChangeTrackingNotify()) {
            return;
        }

        if (! $class->isInheritanceTypeNone()) {
            $class = $this->em->getClassMetadata($entity::class);
        }

        $actualData = [];

        foreach ($class->reflFields as $name => $refProp) {
            if (
                ( ! $class->isIdentifier($name) || ! $class->isIdGeneratorIdentity())
                && ($name !== $class->versionField)
                && ! $class->isCollectionValuedAssociation($name)
            ) {
                $actualData[$name] = $refProp->getValue($entity);
            }
        }

        if (! isset($this->originalEntityData[$oid])) {
            throw new RuntimeException('Cannot call recomputeSingleEntityChangeSet before computeChangeSet on an entity.');
        }

        $originalData = $this->originalEntityData[$oid];
        $changeSet    = [];

        foreach ($actualData as $propName => $actualValue) {
            $orgValue = $originalData[$propName] ?? null;

            if ($orgValue !== $actualValue) {
                $changeSet[$propName] = [$orgValue, $actualValue];
            }
        }

        if ($changeSet) {
            if (isset($this->entityChangeSets[$oid])) {
                $this->entityChangeSets[$oid] = array_merge($this->entityChangeSets[$oid], $changeSet);
            } elseif (! isset($this->entityInsertions[$oid])) {
                $this->entityChangeSets[$oid] = $changeSet;
                $this->entityUpdates[$oid]    = $entity;
            }

            $this->originalEntityData[$oid] = $actualData;
        }
    }

    /**
     * Executes all entity insertions for entities of the specified type.
     */
    private function executeInserts(ClassMetadata $class): void
    {
        $entities  = [];
        $className = $class->name;
        $persister = $this->getEntityPersister($className);
        $invoke    = $this->listenersInvoker->getSubscribedSystems($class, Events::postPersist);

        $insertionsForClass = [];

        foreach ($this->entityInsertions as $oid => $entity) {
            if ($this->em->getClassMetadata($entity::class)->name !== $className) {
                continue;
            }

            $insertionsForClass[$oid] = $entity;

            $persister->addInsert($entity);

            unset($this->entityInsertions[$oid]);

            if ($invoke !== ListenersInvoker::INVOKE_NONE) {
                $entities[] = $entity;
            }
        }

        $postInsertIds = $persister->executeInserts();

        if ($postInsertIds) {
            // Persister returned post-insert IDs
            foreach ($postInsertIds as $postInsertId) {
                $idField = $class->getSingleIdentifierFieldName();
                $idValue = $this->convertSingleFieldIdentifierToPHPValue($class, $postInsertId['generatedId']);

                $entity = $postInsertId['entity'];
                $oid    = spl_object_id($entity);

                $class->reflFields[$idField]->setValue($entity, $idValue);

                $this->entityIdentifiers[$oid]            = [$idField => $idValue];
                $this->entityStates[$oid]                 = self::STATE_MANAGED;
                $this->originalEntityData[$oid][$idField] = $idValue;

                $this->addToIdentityMap($entity);
            }
        } else {
            foreach ($insertionsForClass as $oid => $entity) {
                if (! isset($this->entityIdentifiers[$oid])) {
                    //entity was not added to identity map because some identifiers are foreign keys to new entities.
                    //add it now
                    $this->addToEntityIdentifiersAndEntityMap($class, $oid, $entity);
                }
            }
        }

        foreach ($entities as $entity) {
            $this->listenersInvoker->invoke($class, Events::postPersist, $entity, new LifecycleEventArgs($entity, $this->em), $invoke);
        }
    }

    /**
     * @psalm-param ClassMetadata<T> $class
     * @psalm-param T $entity
     *
     * @template T of object
     */
    private function addToEntityIdentifiersAndEntityMap(
        ClassMetadata $class,
        int $oid,
        object $entity
    ): void {
        $identifier = [];

        foreach ($class->getIdentifierFieldNames() as $idField) {
            $origValue = $class->getFieldValue($entity, $idField);

            $value = null;
            if (isset($class->associationMappings[$idField])) {
                // NOTE: Single Columns as associated identifiers only allowed - this constraint it is enforced.
                $value = $this->getSingleIdentifierValue($origValue);
            }

            $identifier[$idField]                     = $value ?? $origValue;
            $this->originalEntityData[$oid][$idField] = $origValue;
        }

        $this->entityStates[$oid]      = self::STATE_MANAGED;
        $this->entityIdentifiers[$oid] = $identifier;

        $this->addToIdentityMap($entity);
    }

    /**
     * Executes all entity updates for entities of the specified type.
     */
    private function executeUpdates(ClassMetadata $class): void
    {
        $className        = $class->name;
        $persister        = $this->getEntityPersister($className);
        $preUpdateInvoke  = $this->listenersInvoker->getSubscribedSystems($class, Events::preUpdate);
        $postUpdateInvoke = $this->listenersInvoker->getSubscribedSystems($class, Events::postUpdate);

        foreach ($this->entityUpdates as $oid => $entity) {
            if ($this->em->getClassMetadata($entity::class)->name !== $className) {
                continue;
            }

            if ($preUpdateInvoke !== ListenersInvoker::INVOKE_NONE) {
                $this->listenersInvoker->invoke($class, Events::preUpdate, $entity, new PreUpdateEventArgs($entity, $this->em, $this->getEntityChangeSet($entity)), $preUpdateInvoke);

                $this->recomputeSingleEntityChangeSet($class, $entity);
            }

            if (! empty($this->entityChangeSets[$oid])) {
                $persister->update($entity);
            }

            unset($this->entityUpdates[$oid]);

            if ($postUpdateInvoke !== ListenersInvoker::INVOKE_NONE) {
                $this->listenersInvoker->invoke($class, Events::postUpdate, $entity, new LifecycleEventArgs($entity, $this->em), $postUpdateInvoke);
            }
        }
    }

    /**
     * Executes all entity deletions for entities of the specified type.
     */
    private function executeDeletions(ClassMetadata $class): void
    {
        $className = $class->name;
        $persister = $this->getEntityPersister($className);
        $invoke    = $this->listenersInvoker->getSubscribedSystems($class, Events::postRemove);

        foreach ($this->entityDeletions as $oid => $entity) {
            if ($this->em->getClassMetadata($entity::class)->name !== $className) {
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
            if (! $class->isIdentifierNatural()) {
                $class->reflFields[$class->identifier[0]]->setValue($entity, null);
            }

            if ($invoke !== ListenersInvoker::INVOKE_NONE) {
                $this->listenersInvoker->invoke($class, Events::postRemove, $entity, new LifecycleEventArgs($entity, $this->em), $invoke);
            }
        }
    }

    /**
     * Gets the commit order.
     *
     * @return list<object>
     */
    private function getCommitOrder(): array
    {
        $calc = $this->getCommitOrderCalculator();

        // See if there are any new classes in the changeset, that are not in the
        // commit order graph yet (don't have a node).
        // We have to inspect changeSet to be able to correctly build dependencies.
        // It is not possible to use IdentityMap here because post inserted ids
        // are not yet available.
        $newNodes = [];

        foreach ([...$this->entityInsertions, ...$this->entityUpdates, ...$this->entityDeletions] as $entity) {
            $class = $this->em->getClassMetadata($entity::class);

            if ($calc->hasNode($class->name)) {
                continue;
            }

            $calc->addNode($class->name, $class);

            $newNodes[] = $class;
        }

        // Calculate dependencies for new nodes
        while ($class = array_pop($newNodes)) {
            foreach ($class->associationMappings as $assoc) {
                if (! ($assoc['isOwningSide'] && $assoc['type'] & ClassMetadata::TO_ONE)) {
                    continue;
                }

                $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);

                if (! $calc->hasNode($targetClass->name)) {
                    $calc->addNode($targetClass->name, $targetClass);

                    $newNodes[] = $targetClass;
                }

                $joinColumns = reset($assoc['joinColumns']);

                $calc->addDependency($targetClass->name, $class->name, (int) empty($joinColumns['nullable']));

                // If the target class has mapped subclasses, these share the same dependency.
                if (! $targetClass->subClasses) {
                    continue;
                }

                foreach ($targetClass->subClasses as $subClassName) {
                    $targetSubClass = $this->em->getClassMetadata($subClassName);

                    if (! $calc->hasNode($subClassName)) {
                        $calc->addNode($targetSubClass->name, $targetSubClass);

                        $newNodes[] = $targetSubClass;
                    }

                    $calc->addDependency($targetSubClass->name, $class->name, 1);
                }
            }
        }

        return $calc->sort();
    }

    /**
     * Schedules an entity for insertion into the database.
     * If the entity already has an identifier, it will be added to the identity map.
     *
     * @throws ORMInvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function scheduleForInsert(object $entity): void
    {
        $oid = spl_object_id($entity);

        if (isset($this->entityUpdates[$oid])) {
            throw new InvalidArgumentException('Dirty entity can not be scheduled for insertion.');
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
     */
    public function isScheduledForInsert(object $entity): bool
    {
        return isset($this->entityInsertions[spl_object_id($entity)]);
    }

    /**
     * Schedules an entity for being updated.
     *
     * @throws ORMInvalidArgumentException
     */
    public function scheduleForUpdate(object $entity): void
    {
        $oid = spl_object_id($entity);

        if (! isset($this->entityIdentifiers[$oid])) {
            throw ORMInvalidArgumentException::entityHasNoIdentity($entity, 'scheduling for update');
        }

        if (isset($this->entityDeletions[$oid])) {
            throw ORMInvalidArgumentException::entityIsRemoved($entity, 'schedule for update');
        }

        if (! isset($this->entityUpdates[$oid]) && ! isset($this->entityInsertions[$oid])) {
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
     * @psalm-param array<string, array{mixed, mixed}>  $changeset The changeset of the entity (what to update).
     *
     * @ignore
     */
    public function scheduleExtraUpdate(object $entity, array $changeset): void
    {
        $oid         = spl_object_id($entity);
        $extraUpdate = [$entity, $changeset];

        if (isset($this->extraUpdates[$oid])) {
            [, $changeset2] = $this->extraUpdates[$oid];

            $extraUpdate = [$entity, $changeset + $changeset2];
        }

        $this->extraUpdates[$oid] = $extraUpdate;
    }

    /**
     * Checks whether an entity is registered as dirty in the unit of work.
     * Note: Is not very useful currently as dirty entities are only registered
     * at commit time.
     */
    public function isScheduledForUpdate(object $entity): bool
    {
        return isset($this->entityUpdates[spl_object_id($entity)]);
    }

    /**
     * Checks whether an entity is registered to be checked in the unit of work.
     */
    public function isScheduledForDirtyCheck(object $entity): bool
    {
        $rootEntityName = $this->em->getClassMetadata($entity::class)->rootEntityName;

        return isset($this->scheduledForSynchronization[$rootEntityName][spl_object_id($entity)]);
    }

    /**
     * INTERNAL:
     * Schedules an entity for deletion.
     */
    public function scheduleForDelete(object $entity): void
    {
        $oid = spl_object_id($entity);

        if (isset($this->entityInsertions[$oid])) {
            if ($this->isInIdentityMap($entity)) {
                $this->removeFromIdentityMap($entity);
            }

            unset($this->entityInsertions[$oid], $this->entityStates[$oid]);

            return; // entity has not been persisted yet, so nothing more to do.
        }

        if (! $this->isInIdentityMap($entity)) {
            return;
        }

        $this->removeFromIdentityMap($entity);

        unset($this->entityUpdates[$oid]);

        if (! isset($this->entityDeletions[$oid])) {
            $this->entityDeletions[$oid] = $entity;
            $this->entityStates[$oid]    = self::STATE_REMOVED;
        }
    }

    /**
     * Checks whether an entity is registered as removed/deleted with the unit
     * of work.
     */
    public function isScheduledForDelete(object $entity): bool
    {
        return isset($this->entityDeletions[spl_object_id($entity)]);
    }

    /**
     * Checks whether an entity is scheduled for insertion, update or deletion.
     */
    public function isEntityScheduled(object $entity): bool
    {
        $oid = spl_object_id($entity);

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
     * @return bool TRUE if the registration was successful, FALSE if the identity of
     * the entity in question is already managed.
     *
     * @throws ORMInvalidArgumentException
     *
     * @ignore
     */
    public function addToIdentityMap(object $entity): bool
    {
        $classMetadata = $this->em->getClassMetadata($entity::class);
        $identifier    = $this->entityIdentifiers[spl_object_id($entity)];

        if (empty($identifier) || in_array(null, $identifier, true)) {
            throw ORMInvalidArgumentException::entityWithoutIdentity($classMetadata->name, $entity);
        }

        $idHash    = implode(' ', $identifier);
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
     * @param int|null $assume The state to assume if the state is not yet known (not MANAGED or REMOVED).
     *                         This parameter can be set to improve performance of entity state detection
     *                         by potentially avoiding a database lookup if the distinction between NEW and DETACHED
     *                         is either known or does not matter for the caller of the method.
     * @psalm-param self::STATE_*|null $assume
     *
     * @psalm-return self::STATE_*
     */
    public function getEntityState(object $entity, ?int $assume = null): int
    {
        $oid = spl_object_id($entity);

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
        $class = $this->em->getClassMetadata($entity::class);
        $id    = $class->getIdentifierValues($entity);

        if (! $id) {
            return self::STATE_NEW;
        }

        if ($class->containsForeignIdentifier || $class->containsEnumIdentifier) {
            $id = $this->identifierFlattener->flattenIdentifier($class, $id);
        }

        switch (true) {
            case $class->isIdentifierNatural():
                // Check for a version field, if available, to avoid a db lookup.
                if ($class->isVersioned) {
                    assert($class->versionField !== null);

                    return $class->getFieldValue($entity, $class->versionField)
                        ? self::STATE_DETACHED
                        : self::STATE_NEW;
                }

                // Last try before db lookup: check the identity map.
                if ($this->tryGetById($id, $class->rootEntityName)) {
                    return self::STATE_DETACHED;
                }

                // db lookup
                if ($this->getEntityPersister($class->name)->exists($entity)) {
                    return self::STATE_DETACHED;
                }

                return self::STATE_NEW;

            case ! $class->idGenerator->isPostInsertGenerator():
                // if we have a pre insert generator we can't be sure that having an id
                // really means that the entity exists. We have to verify this through
                // the last resort: a db lookup

                // Last try before db lookup: check the identity map.
                if ($this->tryGetById($id, $class->rootEntityName)) {
                    return self::STATE_DETACHED;
                }

                // db lookup
                if ($this->getEntityPersister($class->name)->exists($entity)) {
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
     * @throws ORMInvalidArgumentException
     *
     * @ignore
     */
    public function removeFromIdentityMap(object $entity): bool
    {
        $oid           = spl_object_id($entity);
        $classMetadata = $this->em->getClassMetadata($entity::class);
        $idHash        = implode(' ', $this->entityIdentifiers[$oid]);

        if ($idHash === '') {
            throw ORMInvalidArgumentException::entityHasNoIdentity($entity, 'remove from identity map');
        }

        $className = $classMetadata->rootEntityName;

        if (isset($this->identityMap[$className][$idHash])) {
            unset($this->identityMap[$className][$idHash], $this->readOnlyObjects[$oid]);

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
     */
    public function getByIdHash(string $idHash, string $rootClassName): ?object
    {
        return $this->identityMap[$rootClassName][$idHash];
    }

    /**
     * INTERNAL:
     * Tries to get an entity by its identifier hash. If no entity is found for
     * the given hash, FALSE is returned.
     *
     * @param mixed $idHash (must be possible to cast it to string)
     *
     * @return false|object The found entity or FALSE.
     *
     * @ignore
     */
    public function tryGetByIdHash(mixed $idHash, string $rootClassName): object|false
    {
        $stringIdHash = (string) $idHash;

        return $this->identityMap[$rootClassName][$stringIdHash] ?? false;
    }

    /**
     * Checks whether an entity is registered in the identity map of this UnitOfWork.
     */
    public function isInIdentityMap(object $entity): bool
    {
        $oid = spl_object_id($entity);

        if (empty($this->entityIdentifiers[$oid])) {
            return false;
        }

        $classMetadata = $this->em->getClassMetadata($entity::class);
        $idHash        = implode(' ', $this->entityIdentifiers[$oid]);

        return isset($this->identityMap[$classMetadata->rootEntityName][$idHash]);
    }

    /**
     * Persists an entity as part of the current unit of work.
     */
    public function persist(object $entity): void
    {
        $visited = [];

        $this->doPersist($entity, $visited);
    }

    /**
     * Persists an entity as part of the current unit of work.
     *
     * This method is internally called during persist() cascades as it tracks
     * the already visited entities to prevent infinite recursions.
     *
     * @psalm-param array<int, object> $visited The already visited entities.
     *
     * @throws ORMInvalidArgumentException
     * @throws UnexpectedValueException
     */
    private function doPersist(object $entity, array &$visited): void
    {
        $oid = spl_object_id($entity);

        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $entity; // Mark visited

        $class = $this->em->getClassMetadata($entity::class);

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
                $this->addToIdentityMap($entity);

                $this->entityStates[$oid] = self::STATE_MANAGED;

                if ($class->isChangeTrackingDeferredExplicit()) {
                    $this->scheduleForDirtyCheck($entity);
                }

                break;

            case self::STATE_DETACHED:
                // Can actually not happen right now since we assume STATE_NEW.
                throw ORMInvalidArgumentException::detachedEntityCannot($entity, 'persisted');

            default:
                throw new UnexpectedValueException(sprintf(
                    'Unexpected entity state: %s. %s',
                    $entityState,
                    self::objToStr($entity)
                ));
        }

        $this->cascadePersist($entity, $visited);
    }

    /**
     * Deletes an entity as part of the current unit of work.
     */
    public function remove(object $entity): void
    {
        $visited = [];

        $this->doRemove($entity, $visited);
    }

    /**
     * Deletes an entity as part of the current unit of work.
     *
     * This method is internally called during delete() cascades as it tracks
     * the already visited entities to prevent infinite recursions.
     *
     * @psalm-param array<int, object> $visited The map of the already visited entities.
     *
     * @throws ORMInvalidArgumentException If the instance is a detached entity.
     * @throws UnexpectedValueException
     */
    private function doRemove(object $entity, array &$visited): void
    {
        $oid = spl_object_id($entity);

        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $entity; // mark visited

        // Cascade first, because scheduleForDelete() removes the entity from the identity map, which
        // can cause problems when a lazy proxy has to be initialized for the cascade operation.
        $this->cascadeRemove($entity, $visited);

        $class       = $this->em->getClassMetadata($entity::class);
        $entityState = $this->getEntityState($entity);

        switch ($entityState) {
            case self::STATE_NEW:
            case self::STATE_REMOVED:
                // nothing to do
                break;

            case self::STATE_MANAGED:
                $invoke = $this->listenersInvoker->getSubscribedSystems($class, Events::preRemove);

                if ($invoke !== ListenersInvoker::INVOKE_NONE) {
                    $this->listenersInvoker->invoke($class, Events::preRemove, $entity, new LifecycleEventArgs($entity, $this->em), $invoke);
                }

                $this->scheduleForDelete($entity);
                break;

            case self::STATE_DETACHED:
                throw ORMInvalidArgumentException::detachedEntityCannot($entity, 'removed');

            default:
                throw new UnexpectedValueException(sprintf(
                    'Unexpected entity state: %s. %s',
                    $entityState,
                    self::objToStr($entity)
                ));
        }
    }

    /**
     * Detaches an entity from the persistence management. It's persistence will
     * no longer be managed by Doctrine.
     */
    public function detach(object $entity): void
    {
        $visited = [];

        $this->doDetach($entity, $visited);
    }

    /**
     * Executes a detach operation on the given entity.
     *
     * @param mixed[] $visited
     * @param bool    $noCascade if true, don't cascade detach operation.
     */
    private function doDetach(
        object $entity,
        array &$visited,
        bool $noCascade = false
    ): void {
        $oid = spl_object_id($entity);

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

        if (! $noCascade) {
            $this->cascadeDetach($entity, $visited);
        }
    }

    /**
     * Refreshes the state of the given entity from the database, overwriting
     * any local, unpersisted changes.
     *
     * @throws InvalidArgumentException If the entity is not MANAGED.
     */
    public function refresh(object $entity): void
    {
        $visited = [];

        $this->doRefresh($entity, $visited);
    }

    /**
     * Executes a refresh operation on an entity.
     *
     * @psalm-param array<int, object>  $visited The already visited entities during cascades.
     *
     * @throws ORMInvalidArgumentException If the entity is not MANAGED.
     */
    private function doRefresh(object $entity, array &$visited): void
    {
        $oid = spl_object_id($entity);

        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $entity; // mark visited

        $class = $this->em->getClassMetadata($entity::class);

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
     * @psalm-param array<int, object> $visited
     */
    private function cascadeRefresh(object $entity, array &$visited): void
    {
        $class = $this->em->getClassMetadata($entity::class);

        $associationMappings = array_filter(
            $class->associationMappings,
            static fn (array $assoc) => $assoc['isCascadeRefresh']
        );

        foreach ($associationMappings as $assoc) {
            $relatedEntities = $class->reflFields[$assoc['fieldName']]->getValue($entity);

            switch (true) {
                case $relatedEntities instanceof PersistentCollection:
                    // Unwrap so that foreach() does not initialize
                    $relatedEntities = $relatedEntities->unwrap();
                    // break; is commented intentionally!

                case $relatedEntities instanceof Collection:
                case is_array($relatedEntities):
                    foreach ($relatedEntities as $relatedEntity) {
                        $this->doRefresh($relatedEntity, $visited);
                    }

                    break;

                case $relatedEntities !== null:
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
     * @param array<int, object> $visited
     */
    private function cascadeDetach(object $entity, array &$visited): void
    {
        $class = $this->em->getClassMetadata($entity::class);

        $associationMappings = array_filter(
            $class->associationMappings,
            static fn (array $assoc) => $assoc['isCascadeDetach']
        );

        foreach ($associationMappings as $assoc) {
            $relatedEntities = $class->reflFields[$assoc['fieldName']]->getValue($entity);

            switch (true) {
                case $relatedEntities instanceof PersistentCollection:
                    // Unwrap so that foreach() does not initialize
                    $relatedEntities = $relatedEntities->unwrap();
                    // break; is commented intentionally!

                case $relatedEntities instanceof Collection:
                case is_array($relatedEntities):
                    foreach ($relatedEntities as $relatedEntity) {
                        $this->doDetach($relatedEntity, $visited);
                    }

                    break;

                case $relatedEntities !== null:
                    $this->doDetach($relatedEntities, $visited);
                    break;

                default:
                    // Do nothing
            }
        }
    }

    /**
     * Cascades the save operation to associated entities.
     *
     * @psalm-param array<int, object> $visited
     */
    private function cascadePersist(object $entity, array &$visited): void
    {
        $class = $this->em->getClassMetadata($entity::class);

        $associationMappings = array_filter(
            $class->associationMappings,
            static fn (array $assoc) => $assoc['isCascadePersist']
        );

        foreach ($associationMappings as $assoc) {
            $relatedEntities = $class->reflFields[$assoc['fieldName']]->getValue($entity);

            switch (true) {
                case $relatedEntities instanceof PersistentCollection:
                    // Unwrap so that foreach() does not initialize
                    $relatedEntities = $relatedEntities->unwrap();
                    // break; is commented intentionally!

                case $relatedEntities instanceof Collection:
                case is_array($relatedEntities):
                    if (($assoc['type'] & ClassMetadata::TO_MANY) <= 0) {
                        throw ORMInvalidArgumentException::invalidAssociation(
                            $this->em->getClassMetadata($assoc['targetEntity']),
                            $assoc,
                            $relatedEntities
                        );
                    }

                    foreach ($relatedEntities as $relatedEntity) {
                        $this->doPersist($relatedEntity, $visited);
                    }

                    break;

                case $relatedEntities !== null:
                    if (! $relatedEntities instanceof $assoc['targetEntity']) {
                        throw ORMInvalidArgumentException::invalidAssociation(
                            $this->em->getClassMetadata($assoc['targetEntity']),
                            $assoc,
                            $relatedEntities
                        );
                    }

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
     * @psalm-param array<int, object> $visited
     */
    private function cascadeRemove(object $entity, array &$visited): void
    {
        $class = $this->em->getClassMetadata($entity::class);

        $associationMappings = array_filter(
            $class->associationMappings,
            static fn (array $assoc) => $assoc['isCascadeRemove']
        );

        $entitiesToCascade = [];

        foreach ($associationMappings as $assoc) {
            if ($entity instanceof Proxy && ! $entity->__isInitialized()) {
                $entity->__load();
            }

            $relatedEntities = $class->reflFields[$assoc['fieldName']]->getValue($entity);

            switch (true) {
                case $relatedEntities instanceof Collection:
                case is_array($relatedEntities):
                    // If its a PersistentCollection initialization is intended! No unwrap!
                    foreach ($relatedEntities as $relatedEntity) {
                        $entitiesToCascade[] = $relatedEntity;
                    }

                    break;

                case $relatedEntities !== null:
                    $entitiesToCascade[] = $relatedEntities;
                    break;

                default:
                    // Do nothing
            }
        }

        foreach ($entitiesToCascade as $relatedEntity) {
            $this->doRemove($relatedEntity, $visited);
        }
    }

    /**
     * Acquire a lock on the given entity.
     *
     * @param int|DateTimeInterface|null $lockVersion
     * @psalm-param LockMode::* $lockMode
     *
     * @throws ORMInvalidArgumentException
     * @throws TransactionRequiredException
     * @throws OptimisticLockException
     */
    public function lock(object $entity, LockMode|int $lockMode, $lockVersion = null): void
    {
        if ($this->getEntityState($entity, self::STATE_DETACHED) !== self::STATE_MANAGED) {
            throw ORMInvalidArgumentException::entityNotManaged($entity);
        }

        $class = $this->em->getClassMetadata($entity::class);

        switch (true) {
            case $lockMode === LockMode::OPTIMISTIC:
                if (! $class->isVersioned) {
                    throw OptimisticLockException::notVersioned($class->name);
                }

                if ($lockVersion === null) {
                    return;
                }

                if ($entity instanceof Proxy && ! $entity->__isInitialized()) {
                    $entity->__load();
                }

                assert($class->versionField !== null);
                $entityVersion = $class->reflFields[$class->versionField]->getValue($entity);

                // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedNotEqualOperator
                if ($entityVersion != $lockVersion) {
                    throw OptimisticLockException::lockFailedVersionMismatch($entity, $lockVersion, $entityVersion);
                }

                break;

            case $lockMode === LockMode::NONE:
            case $lockMode === LockMode::PESSIMISTIC_READ:
            case $lockMode === LockMode::PESSIMISTIC_WRITE:
                if (! $this->em->getConnection()->isTransactionActive()) {
                    throw TransactionRequiredException::transactionRequired();
                }

                $oid = spl_object_id($entity);

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
     */
    public function getCommitOrderCalculator(): CommitOrderCalculator
    {
        return new CommitOrderCalculator();
    }

    /**
     * Clears the UnitOfWork.
     */
    public function clear(): void
    {
        $this->identityMap                    =
        $this->entityIdentifiers              =
        $this->originalEntityData             =
        $this->entityChangeSets               =
        $this->entityStates                   =
        $this->scheduledForSynchronization    =
        $this->entityInsertions               =
        $this->entityUpdates                  =
        $this->entityDeletions                =
        $this->nonCascadedNewDetectedEntities =
        $this->collectionDeletions            =
        $this->collectionUpdates              =
        $this->extraUpdates                   =
        $this->readOnlyObjects                =
        $this->visitedCollections             =
        $this->eagerLoadingEntities           =
        $this->orphanRemovals                 = [];

        if ($this->evm->hasListeners(Events::onClear)) {
            $this->evm->dispatchEvent(Events::onClear, new OnClearEventArgs($this->em));
        }
    }

    /**
     * INTERNAL:
     * Schedules an orphaned entity for removal. The remove() operation will be
     * invoked on that entity at the beginning of the next commit of this
     * UnitOfWork.
     *
     * @ignore
     */
    public function scheduleOrphanRemoval(object $entity): void
    {
        $this->orphanRemovals[spl_object_id($entity)] = $entity;
    }

    /**
     * INTERNAL:
     * Cancels a previously scheduled orphan removal.
     *
     * @ignore
     */
    public function cancelOrphanRemoval(object $entity): void
    {
        unset($this->orphanRemovals[spl_object_id($entity)]);
    }

    /**
     * INTERNAL:
     * Schedules a complete collection for removal when this UnitOfWork commits.
     */
    public function scheduleCollectionDeletion(PersistentCollection $coll): void
    {
        $coid = spl_object_id($coll);

        // TODO: if $coll is already scheduled for recreation ... what to do?
        // Just remove $coll from the scheduled recreations?
        unset($this->collectionUpdates[$coid]);

        $this->collectionDeletions[$coid] = $coll;
    }

    public function isCollectionScheduledForDeletion(PersistentCollection $coll): bool
    {
        return isset($this->collectionDeletions[spl_object_id($coll)]);
    }

    /**
     * INTERNAL:
     * Creates an entity. Used for reconstitution of persistent entities.
     *
     * Internal note: Highly performance-sensitive method.
     *
     * @param string  $className The name of the entity class.
     * @param mixed[] $data      The data for the entity.
     * @param mixed[] $hints     Any hints to account for during reconstitution/lookup of the entity.
     * @psalm-param class-string $className
     * @psalm-param array<string, mixed> $hints
     *
     * @return object The managed entity instance.
     *
     * @ignore
     * @todo Rename: getOrCreateEntity
     */
    public function createEntity(string $className, array $data, array &$hints = []): object
    {
        $class = $this->em->getClassMetadata($className);

        $id     = $this->identifierFlattener->flattenIdentifier($class, $data);
        $idHash = implode(' ', $id);

        if (isset($this->identityMap[$class->rootEntityName][$idHash])) {
            $entity = $this->identityMap[$class->rootEntityName][$idHash];
            $oid    = spl_object_id($entity);

            if (
                isset($hints[Query::HINT_REFRESH], $hints[Query::HINT_REFRESH_ENTITY])
            ) {
                $unmanagedProxy = $hints[Query::HINT_REFRESH_ENTITY];
                if (
                    $unmanagedProxy !== $entity
                    && $unmanagedProxy instanceof Proxy
                    && $this->isIdentifierEquals($unmanagedProxy, $entity)
                ) {
                    // DDC-1238 - we have a managed instance, but it isn't the provided one.
                    // Therefore we clear its identifier. Also, we must re-fetch metadata since the
                    // refreshed object may be anything

                    foreach ($class->identifier as $fieldName) {
                        $class->reflFields[$fieldName]->setValue($unmanagedProxy, null);
                    }

                    return $unmanagedProxy;
                }
            }

            if ($entity instanceof Proxy && ! $entity->__isInitialized()) {
                $entity->__setInitialized(true);

                if ($entity instanceof NotifyPropertyChanged) {
                    $entity->addPropertyChangedListener($this);
                }
            } else {
                if (
                    ! isset($hints[Query::HINT_REFRESH])
                    || (isset($hints[Query::HINT_REFRESH_ENTITY]) && $hints[Query::HINT_REFRESH_ENTITY] !== $entity)
                ) {
                    return $entity;
                }
            }

            $this->originalEntityData[$oid] = $data;
        } else {
            $entity = $class->newInstance();
            $oid    = spl_object_id($entity);

            $this->entityIdentifiers[$oid]  = $id;
            $this->entityStates[$oid]       = self::STATE_MANAGED;
            $this->originalEntityData[$oid] = $data;

            $this->identityMap[$class->rootEntityName][$idHash] = $entity;

            if ($entity instanceof NotifyPropertyChanged) {
                $entity->addPropertyChangedListener($this);
            }

            if (isset($hints[Query::HINT_READ_ONLY])) {
                $this->readOnlyObjects[$oid] = true;
            }
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
            Deprecation::trigger(
                'doctrine/orm',
                'https://github.com/doctrine/orm/issues/8471',
                'Partial Objects are deprecated (here entity %s)',
                $className
            );

            return $entity;
        }

        foreach ($class->associationMappings as $field => $assoc) {
            // Check if the association is not among the fetch-joined associations already.
            if (isset($hints['fetchAlias'], $hints['fetched'][$hints['fetchAlias']][$field])) {
                continue;
            }

            $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);

            switch (true) {
                case $assoc['type'] & ClassMetadata::TO_ONE:
                    if (! $assoc['isOwningSide']) {
                        // use the given entity association
                        if (isset($data[$field]) && is_object($data[$field]) && isset($this->entityStates[spl_object_id($data[$field])])) {
                            $this->originalEntityData[$oid][$field] = $data[$field];

                            $class->reflFields[$field]->setValue($entity, $data[$field]);
                            $targetClass->reflFields[$assoc['mappedBy']]->setValue($data[$field], $entity);

                            continue 2;
                        }

                        // Inverse side of x-to-one can never be lazy
                        $class->reflFields[$field]->setValue($entity, $this->getEntityPersister($assoc['targetEntity'])->loadOneToOneEntity($assoc, $entity));

                        continue 2;
                    }

                    // use the entity association
                    if (isset($data[$field]) && is_object($data[$field]) && isset($this->entityStates[spl_object_id($data[$field])])) {
                        $class->reflFields[$field]->setValue($entity, $data[$field]);
                        $this->originalEntityData[$oid][$field] = $data[$field];

                        break;
                    }

                    $associatedId = [];

                    // TODO: Is this even computed right in all cases of composite keys?
                    foreach ($assoc['targetToSourceKeyColumns'] as $targetColumn => $srcColumn) {
                        $joinColumnValue = $data[$srcColumn] ?? null;

                        if ($joinColumnValue !== null) {
                            if ($targetClass->containsForeignIdentifier) {
                                $associatedId[$targetClass->getFieldForColumn($targetColumn)] = $joinColumnValue;
                            } else {
                                $associatedId[$targetClass->fieldNames[$targetColumn]] = $joinColumnValue;
                            }
                        } elseif (
                            $targetClass->containsForeignIdentifier
                            && in_array($targetClass->getFieldForColumn($targetColumn), $targetClass->identifier, true)
                        ) {
                            // the missing key is part of target's entity primary key
                            $associatedId = [];
                            break;
                        }
                    }

                    if (! $associatedId) {
                        // Foreign key is NULL
                        $class->reflFields[$field]->setValue($entity, null);
                        $this->originalEntityData[$oid][$field] = null;

                        break;
                    }

                    if (! isset($hints['fetchMode'][$class->name][$field])) {
                        $hints['fetchMode'][$class->name][$field] = $assoc['fetch'];
                    }

                    // Foreign key is set
                    // Check identity map first
                    // FIXME: Can break easily with composite keys if join column values are in
                    //        wrong order. The correct order is the one in ClassMetadata#identifier.
                    $relatedIdHash = implode(' ', $associatedId);

                    switch (true) {
                        case isset($this->identityMap[$targetClass->rootEntityName][$relatedIdHash]):
                            $newValue = $this->identityMap[$targetClass->rootEntityName][$relatedIdHash];

                            // If this is an uninitialized proxy, we are deferring eager loads,
                            // this association is marked as eager fetch, and its an uninitialized proxy (wtf!)
                            // then we can append this entity for eager loading!
                            if (
                                $hints['fetchMode'][$class->name][$field] === ClassMetadata::FETCH_EAGER &&
                                isset($hints[self::HINT_DEFEREAGERLOAD]) &&
                                ! $targetClass->isIdentifierComposite &&
                                $newValue instanceof Proxy &&
                                $newValue->__isInitialized() === false
                            ) {
                                $this->eagerLoadingEntities[$targetClass->rootEntityName][$relatedIdHash] = current($associatedId);
                            }

                            break;

                        case $targetClass->subClasses:
                            // If it might be a subtype, it can not be lazy. There isn't even
                            // a way to solve this with deferred eager loading, which means putting
                            // an entity with subclasses at a *-to-one location is really bad! (performance-wise)
                            $newValue = $this->getEntityPersister($assoc['targetEntity'])->loadOneToOneEntity($assoc, $entity, $associatedId);
                            break;

                        default:
                            switch (true) {
                                // We are negating the condition here. Other cases will assume it is valid!
                                case $hints['fetchMode'][$class->name][$field] !== ClassMetadata::FETCH_EAGER:
                                    $newValue = $this->em->getProxyFactory()->getProxy($assoc['targetEntity'], $associatedId);
                                    break;

                                // Deferred eager load only works for single identifier classes
                                case isset($hints[self::HINT_DEFEREAGERLOAD]) && ! $targetClass->isIdentifierComposite:
                                    // TODO: Is there a faster approach?
                                    $this->eagerLoadingEntities[$targetClass->rootEntityName][$relatedIdHash] = current($associatedId);

                                    $newValue = $this->em->getProxyFactory()->getProxy($assoc['targetEntity'], $associatedId);
                                    break;

                                default:
                                    // TODO: This is very imperformant, ignore it?
                                    $newValue = $this->em->find($assoc['targetEntity'], $associatedId);
                                    break;
                            }

                            if ($newValue === null) {
                                break;
                            }

                            // PERF: Inlined & optimized code from UnitOfWork#registerManaged()
                            $newValueOid                                                     = spl_object_id($newValue);
                            $this->entityIdentifiers[$newValueOid]                           = $associatedId;
                            $this->identityMap[$targetClass->rootEntityName][$relatedIdHash] = $newValue;

                            if (
                                $newValue instanceof NotifyPropertyChanged &&
                                ( ! $newValue instanceof Proxy || $newValue->__isInitialized())
                            ) {
                                $newValue->addPropertyChangedListener($this);
                            }

                            $this->entityStates[$newValueOid] = self::STATE_MANAGED;
                            // make sure that when an proxy is then finally loaded, $this->originalEntityData is set also!
                            break;
                    }

                    $this->originalEntityData[$oid][$field] = $newValue;
                    $class->reflFields[$field]->setValue($entity, $newValue);

                    if ($assoc['inversedBy'] && $assoc['type'] & ClassMetadata::ONE_TO_ONE && $newValue !== null) {
                        $inverseAssoc = $targetClass->associationMappings[$assoc['inversedBy']];
                        $targetClass->reflFields[$inverseAssoc['fieldName']]->setValue($newValue, $entity);
                    }

                    break;

                default:
                    // Ignore if its a cached collection
                    if (isset($hints[Query::HINT_CACHE_ENABLED]) && $class->getFieldValue($entity, $field) instanceof PersistentCollection) {
                        break;
                    }

                    // use the given collection
                    if (isset($data[$field]) && $data[$field] instanceof PersistentCollection) {
                        $data[$field]->setOwner($entity, $assoc);

                        $class->reflFields[$field]->setValue($entity, $data[$field]);
                        $this->originalEntityData[$oid][$field] = $data[$field];

                        break;
                    }

                    // Inject collection
                    $pColl = new PersistentCollection($this->em, $targetClass, new ArrayCollection());
                    $pColl->setOwner($entity, $assoc);
                    $pColl->setInitialized(false);

                    $reflField = $class->reflFields[$field];
                    $reflField->setValue($entity, $pColl);

                    if ($assoc['fetch'] === ClassMetadata::FETCH_EAGER) {
                        $this->loadCollection($pColl);
                        $pColl->takeSnapshot();
                    }

                    $this->originalEntityData[$oid][$field] = $pColl;
                    break;
            }
        }

        // defer invoking of postLoad event to hydration complete step
        $this->hydrationCompleteHandler->deferPostLoadInvoking($class, $entity);

        return $entity;
    }

    public function triggerEagerLoads(): void
    {
        if (! $this->eagerLoadingEntities) {
            return;
        }

        // avoid infinite recursion
        $eagerLoadingEntities       = $this->eagerLoadingEntities;
        $this->eagerLoadingEntities = [];

        foreach ($eagerLoadingEntities as $entityName => $ids) {
            if (! $ids) {
                continue;
            }

            $class = $this->em->getClassMetadata($entityName);

            $this->getEntityPersister($entityName)->loadAll(
                array_combine($class->identifier, [array_values($ids)])
            );
        }
    }

    /**
     * Initializes (loads) an uninitialized persistent collection of an entity.
     *
     * @todo Maybe later move to EntityManager#initialize($proxyOrCollection). See DDC-733.
     */
    public function loadCollection(PersistentCollection $collection): void
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

        $collection->setInitialized(true);
    }

    /**
     * Gets the identity map of the UnitOfWork.
     *
     * @psalm-return array<class-string, array<string, object|null>>
     */
    public function getIdentityMap(): array
    {
        return $this->identityMap;
    }

    /**
     * Gets the original data of an entity. The original data is the data that was
     * present at the time the entity was reconstituted from the database.
     *
     * @psalm-return array<string, mixed>
     */
    public function getOriginalEntityData(object $entity): array
    {
        $oid = spl_object_id($entity);

        return $this->originalEntityData[$oid] ?? [];
    }

    /**
     * @param mixed[] $data
     *
     * @ignore
     */
    public function setOriginalEntityData(object $entity, array $data): void
    {
        $this->originalEntityData[spl_object_id($entity)] = $data;
    }

    /**
     * INTERNAL:
     * Sets a property value of the original data array of an entity.
     *
     * @ignore
     */
    public function setOriginalEntityProperty(int $oid, string $property, mixed $value): void
    {
        $this->originalEntityData[$oid][$property] = $value;
    }

    /**
     * Gets the identifier of an entity.
     * The returned value is always an array of identifier values. If the entity
     * has a composite identifier then the identifier values are in the same
     * order as the identifier field names as returned by ClassMetadata#getIdentifierFieldNames().
     *
     * @return mixed[] The identifier values.
     */
    public function getEntityIdentifier(object $entity): array
    {
        return $this->entityIdentifiers[spl_object_id($entity)]
            ?? throw EntityNotFoundException::noIdentifierFound(get_debug_type($entity));
    }

    /**
     * Processes an entity instance to extract their identifier values.
     *
     * @return mixed A scalar value.
     *
     * @throws ORMInvalidArgumentException
     */
    public function getSingleIdentifierValue(object $entity): mixed
    {
        $class = $this->em->getClassMetadata($entity::class);

        if ($class->isIdentifierComposite) {
            throw ORMInvalidArgumentException::invalidCompositeIdentifier();
        }

        $values = $this->isInIdentityMap($entity)
            ? $this->getEntityIdentifier($entity)
            : $class->getIdentifierValues($entity);

        return $values[$class->identifier[0]] ?? null;
    }

    /**
     * Tries to find an entity with the given identifier in the identity map of
     * this UnitOfWork.
     *
     * @param mixed  $id            The entity identifier to look for.
     * @param string $rootClassName The name of the root class of the mapped entity hierarchy.
     * @psalm-param class-string $rootClassName
     *
     * @return object|false Returns the entity with the specified identifier if it exists in
     *                      this UnitOfWork, FALSE otherwise.
     */
    public function tryGetById(mixed $id, string $rootClassName): object|false
    {
        $idHash = implode(' ', (array) $id);

        return $this->identityMap[$rootClassName][$idHash] ?? false;
    }

    /**
     * Schedules an entity for dirty-checking at commit-time.
     *
     * @todo Rename: scheduleForSynchronization
     */
    public function scheduleForDirtyCheck(object $entity): void
    {
        $rootClassName = $this->em->getClassMetadata($entity::class)->rootEntityName;

        $this->scheduledForSynchronization[$rootClassName][spl_object_id($entity)] = $entity;
    }

    /**
     * Checks whether the UnitOfWork has any pending insertions.
     */
    public function hasPendingInsertions(): bool
    {
        return ! empty($this->entityInsertions);
    }

    /**
     * Calculates the size of the UnitOfWork. The size of the UnitOfWork is the
     * number of entities in the identity map.
     */
    public function size(): int
    {
        return array_sum(array_map('count', $this->identityMap));
    }

    /**
     * Gets the EntityPersister for an Entity.
     *
     * @psalm-param class-string $entityName
     */
    public function getEntityPersister(string $entityName): EntityPersister
    {
        if (isset($this->persisters[$entityName])) {
            return $this->persisters[$entityName];
        }

        $class = $this->em->getClassMetadata($entityName);

        $persister = match (true) {
            $class->isInheritanceTypeNone() => new BasicEntityPersister($this->em, $class),
            $class->isInheritanceTypeSingleTable() => new SingleTablePersister($this->em, $class),
            $class->isInheritanceTypeJoined() => new JoinedSubclassPersister($this->em, $class),
            default => throw new RuntimeException('No persister found for entity.'),
        };

        if ($this->hasCache && $class->cache !== null) {
            $persister = $this->em->getConfiguration()
                ->getSecondLevelCacheConfiguration()
                ->getCacheFactory()
                ->buildCachedEntityPersister($this->em, $persister, $class);
        }

        $this->persisters[$entityName] = $persister;

        return $this->persisters[$entityName];
    }

    /**
     * Gets a collection persister for a collection-valued association.
     *
     * @psalm-param array<string, mixed> $association
     */
    public function getCollectionPersister(array $association): CollectionPersister
    {
        $role = isset($association['cache'])
            ? $association['sourceEntity'] . '::' . $association['fieldName']
            : $association['type'];

        if (isset($this->collectionPersisters[$role])) {
            return $this->collectionPersisters[$role];
        }

        $persister = $association['type'] === ClassMetadata::ONE_TO_MANY
            ? new OneToManyPersister($this->em)
            : new ManyToManyPersister($this->em);

        if ($this->hasCache && isset($association['cache'])) {
            $persister = $this->em->getConfiguration()
                ->getSecondLevelCacheConfiguration()
                ->getCacheFactory()
                ->buildCachedCollectionPersister($this->em, $persister, $association);
        }

        $this->collectionPersisters[$role] = $persister;

        return $this->collectionPersisters[$role];
    }

    /**
     * INTERNAL:
     * Registers an entity as managed.
     *
     * @param mixed[] $id   The identifier values.
     * @param mixed[] $data The original entity data.
     */
    public function registerManaged(object $entity, array $id, array $data): void
    {
        $oid = spl_object_id($entity);

        $this->entityIdentifiers[$oid]  = $id;
        $this->entityStates[$oid]       = self::STATE_MANAGED;
        $this->originalEntityData[$oid] = $data;

        $this->addToIdentityMap($entity);

        if ($entity instanceof NotifyPropertyChanged && ( ! $entity instanceof Proxy || $entity->__isInitialized())) {
            $entity->addPropertyChangedListener($this);
        }
    }

    /* PropertyChangedListener implementation */

    /**
     * Notifies this UnitOfWork of a property change in an entity.
     *
     * {@inheritdoc}
     */
    public function propertyChanged(object $sender, string $propertyName, mixed $oldValue, mixed $newValue): void
    {
        $oid   = spl_object_id($sender);
        $class = $this->em->getClassMetadata($sender::class);

        $isAssocField = isset($class->associationMappings[$propertyName]);

        if (! $isAssocField && ! isset($class->fieldMappings[$propertyName])) {
            return; // ignore non-persistent fields
        }

        // Update changeset and mark entity for synchronization
        $this->entityChangeSets[$oid][$propertyName] = [$oldValue, $newValue];

        if (! isset($this->scheduledForSynchronization[$class->rootEntityName][$oid])) {
            $this->scheduleForDirtyCheck($sender);
        }
    }

    /**
     * Gets the currently scheduled entity insertions in this UnitOfWork.
     *
     * @psalm-return array<int, object>
     */
    public function getScheduledEntityInsertions(): array
    {
        return $this->entityInsertions;
    }

    /**
     * Gets the currently scheduled entity updates in this UnitOfWork.
     *
     * @psalm-return array<int, object>
     */
    public function getScheduledEntityUpdates(): array
    {
        return $this->entityUpdates;
    }

    /**
     * Gets the currently scheduled entity deletions in this UnitOfWork.
     *
     * @psalm-return array<int, object>
     */
    public function getScheduledEntityDeletions(): array
    {
        return $this->entityDeletions;
    }

    /**
     * Gets the currently scheduled complete collection deletions
     *
     * @psalm-return array<int, Collection<array-key, object>>
     */
    public function getScheduledCollectionDeletions(): array
    {
        return $this->collectionDeletions;
    }

    /**
     * Gets the currently scheduled collection inserts, updates and deletes.
     *
     * @psalm-return array<int, Collection<array-key, object>>
     */
    public function getScheduledCollectionUpdates(): array
    {
        return $this->collectionUpdates;
    }

    /**
     * Helper method to initialize a lazy loading proxy or persistent collection.
     */
    public function initializeObject(object $obj): void
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
     */
    private static function objToStr(object $obj): string
    {
        return $obj instanceof Stringable ? (string) $obj : get_debug_type($obj) . '@' . spl_object_id($obj);
    }

    /**
     * Marks an entity as read-only so that it will not be considered for updates during UnitOfWork#commit().
     *
     * This operation cannot be undone as some parts of the UnitOfWork now keep gathering information
     * on this object that might be necessary to perform a correct update.
     *
     * @throws ORMInvalidArgumentException
     */
    public function markReadOnly(object $object): void
    {
        if (! $this->isInIdentityMap($object)) {
            throw ORMInvalidArgumentException::readOnlyRequiresManagedEntity($object);
        }

        $this->readOnlyObjects[spl_object_id($object)] = true;
    }

    /**
     * Is this entity read only?
     *
     * @throws ORMInvalidArgumentException
     */
    public function isReadOnly(object $object): bool
    {
        return isset($this->readOnlyObjects[spl_object_id($object)]);
    }

    /**
     * Perform whatever processing is encapsulated here after completion of the transaction.
     */
    private function afterTransactionComplete(): void
    {
        $this->performCallbackOnCachedPersister(static function (CachedPersister $persister) {
            $persister->afterTransactionComplete();
        });
    }

    /**
     * Perform whatever processing is encapsulated here after completion of the rolled-back.
     */
    private function afterTransactionRolledBack(): void
    {
        $this->performCallbackOnCachedPersister(static function (CachedPersister $persister) {
            $persister->afterTransactionRolledBack();
        });
    }

    /**
     * Performs an action after the transaction.
     */
    private function performCallbackOnCachedPersister(callable $callback): void
    {
        if (! $this->hasCache) {
            return;
        }

        foreach (array_merge($this->persisters, $this->collectionPersisters) as $persister) {
            if ($persister instanceof CachedPersister) {
                $callback($persister);
            }
        }
    }

    private function dispatchOnFlushEvent(): void
    {
        if ($this->evm->hasListeners(Events::onFlush)) {
            $this->evm->dispatchEvent(Events::onFlush, new OnFlushEventArgs($this->em));
        }
    }

    private function dispatchPostFlushEvent(): void
    {
        if ($this->evm->hasListeners(Events::postFlush)) {
            $this->evm->dispatchEvent(Events::postFlush, new PostFlushEventArgs($this->em));
        }
    }

    /**
     * Verifies if two given entities actually are the same based on identifier comparison
     */
    private function isIdentifierEquals(object $entity1, object $entity2): bool
    {
        if ($entity1 === $entity2) {
            return true;
        }

        $class = $this->em->getClassMetadata($entity1::class);

        if ($class !== $this->em->getClassMetadata($entity2::class)) {
            return false;
        }

        $oid1 = spl_object_id($entity1);
        $oid2 = spl_object_id($entity2);

        $id1 = $this->entityIdentifiers[$oid1] ?? $this->identifierFlattener->flattenIdentifier($class, $class->getIdentifierValues($entity1));
        $id2 = $this->entityIdentifiers[$oid2] ?? $this->identifierFlattener->flattenIdentifier($class, $class->getIdentifierValues($entity2));

        return $id1 === $id2 || implode(' ', $id1) === implode(' ', $id2);
    }

    /**
     * @throws ORMInvalidArgumentException
     */
    private function assertThatThereAreNoUnintentionallyNonPersistedAssociations(): void
    {
        $entitiesNeedingCascadePersist = array_diff_key($this->nonCascadedNewDetectedEntities, $this->entityInsertions);

        $this->nonCascadedNewDetectedEntities = [];

        if ($entitiesNeedingCascadePersist) {
            throw ORMInvalidArgumentException::newEntitiesFoundThroughRelationships(
                array_values($entitiesNeedingCascadePersist)
            );
        }
    }

    /**
     * This method called by hydrators, and indicates that hydrator totally completed current hydration cycle.
     * Unit of work able to fire deferred events, related to loading events here.
     *
     * @internal should be called internally from object hydrators
     */
    public function hydrationComplete(): void
    {
        $this->hydrationCompleteHandler->hydrationComplete();
    }

    /**
     * @throws MappingException if the entity has more than a single identifier.
     */
    private function convertSingleFieldIdentifierToPHPValue(ClassMetadata $class, mixed $identifierValue): mixed
    {
        return $this->em->getConnection()->convertToPHPValue(
            $identifierValue,
            $class->getTypeOfField($class->getSingleIdentifierFieldName())
        );
    }
}
