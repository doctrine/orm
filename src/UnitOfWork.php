<?php

declare(strict_types=1);

namespace Doctrine\ORM;

use BackedEnum;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connections\PrimaryReadReplicaConnection;
use Doctrine\DBAL\LockMode;
use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\Cache\Persister\CachedPersister;
use Doctrine\ORM\Event\ListenersInvoker;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Exception\EntityIdentityCollisionException;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Exception\UnexpectedAssociationValue;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Internal\HydrationCompleteHandler;
use Doctrine\ORM\Internal\StronglyConnectedComponents;
use Doctrine\ORM\Internal\TopologicalSort;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Mapping\Reflection\ReflectionPropertiesGetter;
use Doctrine\ORM\Persisters\Collection\CollectionPersister;
use Doctrine\ORM\Persisters\Collection\ManyToManyPersister;
use Doctrine\ORM\Persisters\Collection\OneToManyPersister;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\Persisters\Entity\JoinedSubclassPersister;
use Doctrine\ORM\Persisters\Entity\SingleTablePersister;
use Doctrine\ORM\Proxy\InternalProxy;
use Doctrine\ORM\Utility\IdentifierFlattener;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\Persistence\NotifyPropertyChanged;
use Doctrine\Persistence\ObjectManagerAware;
use Doctrine\Persistence\PropertyChangedListener;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\VarExporter\Hydrator;
use UnexpectedValueException;

use function array_chunk;
use function array_combine;
use function array_diff_key;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_sum;
use function array_values;
use function assert;
use function count;
use function current;
use function func_get_arg;
use function func_num_args;
use function get_class;
use function get_debug_type;
use function implode;
use function in_array;
use function is_array;
use function is_object;
use function method_exists;
use function reset;
use function spl_object_id;
use function sprintf;
use function strtolower;

use const PHP_VERSION_ID;

/**
 * The UnitOfWork is responsible for tracking changes to objects during an
 * "object-level" transaction and for writing out changes to the database
 * in the correct order.
 *
 * Internal note: This class contains highly performance-sensitive code.
 *
 * @phpstan-import-type AssociationMapping from ClassMetadata
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
     * @var array<class-string, array<string, object>>
     */
    private $identityMap = [];

    /**
     * Map of all identifiers of managed entities.
     * Keys are object ids (spl_object_id).
     *
     * @var mixed[]
     * @phpstan-var array<int, array<string, mixed>>
     */
    private $entityIdentifiers = [];

    /**
     * Map of the original entity data of managed entities.
     * Keys are object ids (spl_object_id). This is used for calculating changesets
     * at commit time.
     *
     * Internal note: Note that PHPs "copy-on-write" behavior helps a lot with memory usage.
     *                A value will only really be copied if the value in the entity is modified
     *                by the user.
     *
     * @phpstan-var array<int, array<string, mixed>>
     */
    private $originalEntityData = [];

    /**
     * Map of entity changes. Keys are object ids (spl_object_id).
     * Filled at the beginning of a commit of the UnitOfWork and cleaned at the end.
     *
     * @phpstan-var array<int, array<string, array{mixed, mixed}>>
     */
    private $entityChangeSets = [];

    /**
     * The (cached) states of any known entities.
     * Keys are object ids (spl_object_id).
     *
     * @phpstan-var array<int, self::STATE_*>
     */
    private $entityStates = [];

    /**
     * Map of entities that are scheduled for dirty checking at commit time.
     * This is only used for entities with a change tracking policy of DEFERRED_EXPLICIT.
     * Keys are object ids (spl_object_id).
     *
     * @var array<class-string, array<int, mixed>>
     */
    private $scheduledForSynchronization = [];

    /**
     * A list of all pending entity insertions.
     *
     * @phpstan-var array<int, object>
     */
    private $entityInsertions = [];

    /**
     * A list of all pending entity updates.
     *
     * @phpstan-var array<int, object>
     */
    private $entityUpdates = [];

    /**
     * Any pending extra updates that have been scheduled by persisters.
     *
     * @phpstan-var array<int, array{object, array<string, array{mixed, mixed}>}>
     */
    private $extraUpdates = [];

    /**
     * A list of all pending entity deletions.
     *
     * @phpstan-var array<int, object>
     */
    private $entityDeletions = [];

    /**
     * New entities that were discovered through relationships that were not
     * marked as cascade-persist. During flush, this array is populated and
     * then pruned of any entities that were discovered through a valid
     * cascade-persist path. (Leftovers cause an error.)
     *
     * Keys are OIDs, payload is a two-item array describing the association
     * and the entity.
     *
     * @var array<int, array{AssociationMapping, object}> indexed by respective object spl_object_id()
     */
    private $nonCascadedNewDetectedEntities = [];

    /**
     * All pending collection deletions.
     *
     * @phpstan-var array<int, PersistentCollection<array-key, object>>
     */
    private $collectionDeletions = [];

    /**
     * All pending collection updates.
     *
     * @phpstan-var array<int, PersistentCollection<array-key, object>>
     */
    private $collectionUpdates = [];

    /**
     * List of collections visited during changeset calculation on a commit-phase of a UnitOfWork.
     * At the end of the UnitOfWork all these collections will make new snapshots
     * of their data.
     *
     * @phpstan-var array<int, PersistentCollection<array-key, object>>
     */
    private $visitedCollections = [];

    /**
     * List of collections visited during the changeset calculation that contain to-be-removed
     * entities and need to have keys removed post commit.
     *
     * Indexed by Collection object ID, which also serves as the key in self::$visitedCollections;
     * values are the key names that need to be removed.
     *
     * @phpstan-var array<int, array<array-key, true>>
     */
    private $pendingCollectionElementRemovals = [];

    /**
     * The EntityManager that "owns" this UnitOfWork instance.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * The entity persister instances used to persist entity instances.
     *
     * @phpstan-var array<string, EntityPersister>
     */
    private $persisters = [];

    /**
     * The collection persister instances used to persist collections.
     *
     * @phpstan-var array<array-key, CollectionPersister>
     */
    private $collectionPersisters = [];

    /**
     * The EventManager used for dispatching events.
     *
     * @var EventManager
     */
    private $evm;

    /**
     * The ListenersInvoker used for dispatching events.
     *
     * @var ListenersInvoker
     */
    private $listenersInvoker;

    /**
     * The IdentifierFlattener used for manipulating identifiers
     *
     * @var IdentifierFlattener
     */
    private $identifierFlattener;

    /**
     * Orphaned entities that are scheduled for removal.
     *
     * @phpstan-var array<int, object>
     */
    private $orphanRemovals = [];

    /**
     * Read-Only objects are never evaluated
     *
     * @var array<int, true>
     */
    private $readOnlyObjects = [];

    /**
     * Map of Entity Class-Names and corresponding IDs that should eager loaded when requested.
     *
     * @var array<class-string, array<string, mixed>>
     */
    private $eagerLoadingEntities = [];

    /** @var array<string, array<string, mixed>> */
    private $eagerLoadingCollections = [];

    /** @var bool */
    protected $hasCache = false;

    /**
     * Helper for handling completion of hydration
     *
     * @var HydrationCompleteHandler
     */
    private $hydrationCompleteHandler;

    /** @var ReflectionPropertiesGetter */
    private $reflectionPropertiesGetter;

    /**
     * Initializes a new UnitOfWork instance, bound to the given EntityManager.
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em                         = $em;
        $this->evm                        = $em->getEventManager();
        $this->listenersInvoker           = new ListenersInvoker($em);
        $this->hasCache                   = $em->getConfiguration()->isSecondLevelCacheEnabled();
        $this->identifierFlattener        = new IdentifierFlattener($this, $em->getMetadataFactory());
        $this->hydrationCompleteHandler   = new HydrationCompleteHandler($this->listenersInvoker, $em);
        $this->reflectionPropertiesGetter = new ReflectionPropertiesGetter(new RuntimeReflectionService());
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
     * @param object|mixed[]|null $entity
     *
     * @return void
     *
     * @throws Exception
     */
    public function commit($entity = null)
    {
        if ($entity !== null) {
            Deprecation::triggerIfCalledFromOutside(
                'doctrine/orm',
                'https://github.com/doctrine/orm/issues/8459',
                'Calling %s() with any arguments to commit specific entities is deprecated and will not be supported in Doctrine ORM 3.0.',
                __METHOD__
            );
        }

        $connection = $this->em->getConnection();

        if ($connection instanceof PrimaryReadReplicaConnection) {
            $connection->ensureConnectedToPrimary();
        }

        // Raise preFlush
        if ($this->evm->hasListeners(Events::preFlush)) {
            $this->evm->dispatchEvent(Events::preFlush, new PreFlushEventArgs($this->em));
        }

        // Compute changes done since last commit.
        if ($entity === null) {
            $this->computeChangeSets();
        } elseif (is_object($entity)) {
            $this->computeSingleEntityChangeSet($entity);
        } elseif (is_array($entity)) {
            foreach ($entity as $object) {
                $this->computeSingleEntityChangeSet($object);
            }
        }

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

            $this->postCommitCleanup($entity);

            return; // Nothing to do.
        }

        $this->assertThatThereAreNoUnintentionallyNonPersistedAssociations();

        if ($this->orphanRemovals) {
            foreach ($this->orphanRemovals as $orphan) {
                $this->remove($orphan);
            }
        }

        $this->dispatchOnFlushEvent();

        $conn = $this->em->getConnection();
        $conn->beginTransaction();

        $successful = false;

        try {
            // Collection deletions (deletions of complete collections)
            foreach ($this->collectionDeletions as $collectionToDelete) {
                // Deferred explicit tracked collections can be removed only when owning relation was persisted
                $owner = $collectionToDelete->getOwner();

                if ($this->em->getClassMetadata(get_class($owner))->isChangeTrackingDeferredImplicit() || $this->isScheduledForDirtyCheck($owner)) {
                    $this->getCollectionPersister($collectionToDelete->getMapping())->delete($collectionToDelete);
                }
            }

            if ($this->entityInsertions) {
                // Perform entity insertions first, so that all new entities have their rows in the database
                // and can be referred to by foreign keys. The commit order only needs to take new entities
                // into account (new entities referring to other new entities), since all other types (entities
                // with updates or scheduled deletions) are currently not a problem, since they are already
                // in the database.
                $this->executeInserts();
            }

            if ($this->entityUpdates) {
                // Updates do not need to follow a particular order
                $this->executeUpdates();
            }

            // Extra updates that were requested by persisters.
            // This may include foreign keys that could not be set when an entity was inserted,
            // which may happen in the case of circular foreign key relationships.
            if ($this->extraUpdates) {
                $this->executeExtraUpdates();
            }

            // Collection updates (deleteRows, updateRows, insertRows)
            // No particular order is necessary, since all entities themselves are already
            // in the database
            foreach ($this->collectionUpdates as $collectionToUpdate) {
                $this->getCollectionPersister($collectionToUpdate->getMapping())->update($collectionToUpdate);
            }

            // Entity deletions come last. Their order only needs to take care of other deletions
            // (first delete entities depending upon others, before deleting depended-upon entities).
            if ($this->entityDeletions) {
                $this->executeDeletions();
            }

            // Commit failed silently
            if ($conn->commit() === false) {
                $object = is_object($entity) ? $entity : null;

                throw new OptimisticLockException('Commit failed', $object);
            }

            $successful = true;
        } finally {
            if (! $successful) {
                $this->em->close();

                if ($conn->isTransactionActive()) {
                    $conn->rollBack();
                }

                $this->afterTransactionRolledBack();
            }
        }

        $this->afterTransactionComplete();

        // Unset removed entities from collections, and take new snapshots from
        // all visited collections.
        foreach ($this->visitedCollections as $coid => $coll) {
            if (isset($this->pendingCollectionElementRemovals[$coid])) {
                foreach ($this->pendingCollectionElementRemovals[$coid] as $key => $valueIgnored) {
                    unset($coll[$key]);
                }
            }

            $coll->takeSnapshot();
        }

        $this->dispatchPostFlushEvent();

        $this->postCommitCleanup($entity);
    }

    /** @param object|object[]|null $entity */
    private function postCommitCleanup($entity): void
    {
        $this->entityInsertions                 =
        $this->entityUpdates                    =
        $this->entityDeletions                  =
        $this->extraUpdates                     =
        $this->collectionUpdates                =
        $this->nonCascadedNewDetectedEntities   =
        $this->collectionDeletions              =
        $this->pendingCollectionElementRemovals =
        $this->visitedCollections               =
        $this->orphanRemovals                   = [];

        if ($entity === null) {
            $this->entityChangeSets = $this->scheduledForSynchronization = [];

            return;
        }

        $entities = is_object($entity)
            ? [$entity]
            : $entity;

        foreach ($entities as $object) {
            $oid = spl_object_id($object);

            $this->clearEntityChangeSet($oid);

            unset($this->scheduledForSynchronization[$this->em->getClassMetadata(get_class($object))->rootEntityName][$oid]);
        }
    }

    /**
     * Computes the changesets of all entities scheduled for insertion.
     */
    private function computeScheduleInsertsChangeSets(): void
    {
        foreach ($this->entityInsertions as $entity) {
            $class = $this->em->getClassMetadata(get_class($entity));

            $this->computeChangeSet($class, $entity);
        }
    }

    /**
     * Only flushes the given entity according to a ruleset that keeps the UoW consistent.
     *
     * 1. All entities scheduled for insertion, (orphan) removals and changes in collections are processed as well!
     * 2. Read Only entities are skipped.
     * 3. Proxies are skipped.
     * 4. Only if entity is properly managed.
     *
     * @param object $entity
     *
     * @throws InvalidArgumentException
     */
    private function computeSingleEntityChangeSet($entity): void
    {
        $state = $this->getEntityState($entity);

        if ($state !== self::STATE_MANAGED && $state !== self::STATE_REMOVED) {
            throw new InvalidArgumentException('Entity has to be managed or scheduled for removal for single computation ' . self::objToStr($entity));
        }

        $class = $this->em->getClassMetadata(get_class($entity));

        if ($state === self::STATE_MANAGED && $class->isChangeTrackingDeferredImplicit()) {
            $this->persist($entity);
        }

        // Compute changes for INSERTed entities first. This must always happen even in this case.
        $this->computeScheduleInsertsChangeSets();

        if ($class->isReadOnly) {
            return;
        }

        // Ignore uninitialized proxy objects
        if ($this->isUninitializedObject($entity)) {
            return;
        }

        // Only MANAGED entities that are NOT SCHEDULED FOR INSERTION OR DELETION are processed here.
        $oid = spl_object_id($entity);

        if (! isset($this->entityInsertions[$oid]) && ! isset($this->entityDeletions[$oid]) && isset($this->entityStates[$oid])) {
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
            $this->getEntityPersister(get_class($entity))->update($entity);
        }

        $this->extraUpdates = [];
    }

    /**
     * Gets the changeset for an entity.
     *
     * @param object $entity
     *
     * @return mixed[][]
     * @phpstan-return array<string, array{mixed, mixed}|PersistentCollection>
     */
    public function & getEntityChangeSet($entity)
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
     * @phpstan-param ClassMetadata<T> $class
     * @phpstan-param T $entity
     *
     * @return void
     *
     * @template T of object
     *
     * @ignore
     */
    public function computeChangeSet(ClassMetadata $class, $entity)
    {
        $oid = spl_object_id($entity);

        if (isset($this->readOnlyObjects[$oid])) {
            return;
        }

        if (! $class->isInheritanceTypeNone()) {
            $class = $this->em->getClassMetadata(get_class($entity));
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
                        $actualData[$name] = $value;
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

                $refProp->setValue($entity, $value);

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

                if (! empty($class->fieldMappings[$propName]['enumType'])) {
                    if (is_array($orgValue)) {
                        foreach ($orgValue as $id => $val) {
                            if ($val instanceof BackedEnum) {
                                $orgValue[$id] = $val->value;
                            }
                        }
                    } else {
                        if ($orgValue instanceof BackedEnum) {
                            $orgValue = $orgValue->value;
                        }
                    }
                }

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
                        assert(is_object($orgValue));
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
     *
     * @return void
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
                case $class->isChangeTrackingDeferredImplicit():
                    $entitiesToProcess = $entities;
                    break;

                case isset($this->scheduledForSynchronization[$className]):
                    $entitiesToProcess = $this->scheduledForSynchronization[$className];
                    break;

                default:
                    $entitiesToProcess = [];
            }

            foreach ($entitiesToProcess as $entity) {
                // Ignore uninitialized proxy objects
                if ($this->isUninitializedObject($entity)) {
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
     * @phpstan-param AssociationMapping $assoc The association mapping.
     *
     * @throws ORMInvalidArgumentException
     * @throws ORMException
     */
    private function computeAssociationChanges(array $assoc, $value): void
    {
        if ($this->isUninitializedObject($value)) {
            return;
        }

        // If this collection is dirty, schedule it for updates
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
                    if (! ($assoc['type'] & ClassMetadata::TO_MANY)) {
                        break;
                    }

                    $coid                            = spl_object_id($value);
                    $this->visitedCollections[$coid] = $value;

                    if (! isset($this->pendingCollectionElementRemovals[$coid])) {
                        $this->pendingCollectionElementRemovals[$coid] = [];
                    }

                    $this->pendingCollectionElementRemovals[$coid][$key] = true;
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
     * @param object $entity
     * @phpstan-param ClassMetadata<T> $class
     * @phpstan-param T $entity
     *
     * @template T of object
     */
    private function persistNew(ClassMetadata $class, $entity): void
    {
        $oid    = spl_object_id($entity);
        $invoke = $this->listenersInvoker->getSubscribedSystems($class, Events::prePersist);

        if ($invoke !== ListenersInvoker::INVOKE_NONE) {
            $this->listenersInvoker->invoke($class, Events::prePersist, $entity, new PrePersistEventArgs($entity, $this->em), $invoke);
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

        if (! isset($this->entityInsertions[$oid])) {
            $this->scheduleForInsert($entity);
        }
    }

    /** @param mixed[] $idValue */
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
     * @phpstan-param ClassMetadata<T> $class
     * @phpstan-param T $entity
     *
     * @return void
     *
     * @throws ORMInvalidArgumentException If the passed entity is not MANAGED.
     *
     * @template T of object
     * @ignore
     */
    public function recomputeSingleEntityChangeSet(ClassMetadata $class, $entity)
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
            $class = $this->em->getClassMetadata(get_class($entity));
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

            if (isset($class->fieldMappings[$propName]['enumType'])) {
                if (is_array($orgValue)) {
                    foreach ($orgValue as $id => $val) {
                        if ($val instanceof BackedEnum) {
                            $orgValue[$id] = $val->value;
                        }
                    }
                } else {
                    if ($orgValue instanceof BackedEnum) {
                        $orgValue = $orgValue->value;
                    }
                }
            }

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
     * Executes entity insertions
     */
    private function executeInserts(): void
    {
        $entities         = $this->computeInsertExecutionOrder();
        $eventsToDispatch = [];

        foreach ($entities as $entity) {
            $oid       = spl_object_id($entity);
            $class     = $this->em->getClassMetadata(get_class($entity));
            $persister = $this->getEntityPersister($class->name);

            $persister->addInsert($entity);

            unset($this->entityInsertions[$oid]);

            $postInsertIds = $persister->executeInserts();

            if (is_array($postInsertIds)) {
                Deprecation::trigger(
                    'doctrine/orm',
                    'https://github.com/doctrine/orm/pull/10743/',
                    'Returning post insert IDs from \Doctrine\ORM\Persisters\Entity\EntityPersister::executeInserts() is deprecated and will not be supported in Doctrine ORM 3.0. Make the persister call Doctrine\ORM\UnitOfWork::assignPostInsertId() instead.'
                );

                // Persister returned post-insert IDs
                foreach ($postInsertIds as $postInsertId) {
                    $this->assignPostInsertId($postInsertId['entity'], $postInsertId['generatedId']);
                }
            }

            if (! isset($this->entityIdentifiers[$oid])) {
                //entity was not added to identity map because some identifiers are foreign keys to new entities.
                //add it now
                $this->addToEntityIdentifiersAndEntityMap($class, $oid, $entity);
            }

            $invoke = $this->listenersInvoker->getSubscribedSystems($class, Events::postPersist);

            if ($invoke !== ListenersInvoker::INVOKE_NONE) {
                $eventsToDispatch[] = ['class' => $class, 'entity' => $entity, 'invoke' => $invoke];
            }
        }

        // Defer dispatching `postPersist` events to until all entities have been inserted and post-insert
        // IDs have been assigned.
        foreach ($eventsToDispatch as $event) {
            $this->listenersInvoker->invoke(
                $event['class'],
                Events::postPersist,
                $event['entity'],
                new PostPersistEventArgs($event['entity'], $this->em),
                $event['invoke']
            );
        }
    }

    /**
     * @param object $entity
     * @phpstan-param ClassMetadata<T> $class
     * @phpstan-param T $entity
     *
     * @template T of object
     */
    private function addToEntityIdentifiersAndEntityMap(
        ClassMetadata $class,
        int $oid,
        $entity
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
     * Executes all entity updates
     */
    private function executeUpdates(): void
    {
        foreach ($this->entityUpdates as $oid => $entity) {
            $class            = $this->em->getClassMetadata(get_class($entity));
            $persister        = $this->getEntityPersister($class->name);
            $preUpdateInvoke  = $this->listenersInvoker->getSubscribedSystems($class, Events::preUpdate);
            $postUpdateInvoke = $this->listenersInvoker->getSubscribedSystems($class, Events::postUpdate);

            if ($preUpdateInvoke !== ListenersInvoker::INVOKE_NONE) {
                $this->listenersInvoker->invoke($class, Events::preUpdate, $entity, new PreUpdateEventArgs($entity, $this->em, $this->getEntityChangeSet($entity)), $preUpdateInvoke);

                $this->recomputeSingleEntityChangeSet($class, $entity);
            }

            if (! empty($this->entityChangeSets[$oid])) {
                $persister->update($entity);
            }

            unset($this->entityUpdates[$oid]);

            if ($postUpdateInvoke !== ListenersInvoker::INVOKE_NONE) {
                $this->listenersInvoker->invoke($class, Events::postUpdate, $entity, new PostUpdateEventArgs($entity, $this->em), $postUpdateInvoke);
            }
        }
    }

    /**
     * Executes all entity deletions
     */
    private function executeDeletions(): void
    {
        $entities         = $this->computeDeleteExecutionOrder();
        $eventsToDispatch = [];

        foreach ($entities as $entity) {
            $this->removeFromIdentityMap($entity);

            $oid       = spl_object_id($entity);
            $class     = $this->em->getClassMetadata(get_class($entity));
            $persister = $this->getEntityPersister($class->name);
            $invoke    = $this->listenersInvoker->getSubscribedSystems($class, Events::postRemove);

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
                $eventsToDispatch[] = ['class' => $class, 'entity' => $entity, 'invoke' => $invoke];
            }
        }

        // Defer dispatching `postRemove` events to until all entities have been removed.
        foreach ($eventsToDispatch as $event) {
            $this->listenersInvoker->invoke(
                $event['class'],
                Events::postRemove,
                $event['entity'],
                new PostRemoveEventArgs($event['entity'], $this->em),
                $event['invoke']
            );
        }
    }

    /** @return list<object> */
    private function computeInsertExecutionOrder(): array
    {
        $sort = new TopologicalSort();

        // First make sure we have all the nodes
        foreach ($this->entityInsertions as $entity) {
            $sort->addNode($entity);
        }

        // Now add edges
        foreach ($this->entityInsertions as $entity) {
            $class = $this->em->getClassMetadata(get_class($entity));

            foreach ($class->associationMappings as $assoc) {
                // We only need to consider the owning sides of to-one associations,
                // since many-to-many associations are persisted at a later step and
                // have no insertion order problems (all entities already in the database
                // at that time).
                if (! ($assoc['isOwningSide'] && $assoc['type'] & ClassMetadata::TO_ONE)) {
                    continue;
                }

                $targetEntity = $class->getFieldValue($entity, $assoc['fieldName']);

                // If there is no entity that we need to refer to, or it is already in the
                // database (i. e. does not have to be inserted), no need to consider it.
                if ($targetEntity === null || ! $sort->hasNode($targetEntity)) {
                    continue;
                }

                // An entity that references back to itself _and_ uses an application-provided ID
                // (the "NONE" generator strategy) can be exempted from commit order computation.
                // See https://github.com/doctrine/orm/pull/10735/ for more details on this edge case.
                // A non-NULLable self-reference would be a cycle in the graph.
                if ($targetEntity === $entity && $class->isIdentifierNatural()) {
                    continue;
                }

                // According to https://www.doctrine-project.org/projects/doctrine-orm/en/2.14/reference/annotations-reference.html#annref_joincolumn,
                // the default for "nullable" is true. Unfortunately, it seems this default is not applied at the metadata driver, factory or other
                // level, but in fact we may have an undefined 'nullable' key here, so we must assume that default here as well.
                //
                // Same in \Doctrine\ORM\Tools\EntityGenerator::isAssociationIsNullable or \Doctrine\ORM\Persisters\Entity\BasicEntityPersister::getJoinSQLForJoinColumns,
                // to give two examples.
                assert(isset($assoc['joinColumns']));
                $joinColumns = reset($assoc['joinColumns']);
                $isNullable  = ! isset($joinColumns['nullable']) || $joinColumns['nullable'];

                // Add dependency. The dependency direction implies that "$entity depends on $targetEntity". The
                // topological sort result will output the depended-upon nodes first, which means we can insert
                // entities in that order.
                $sort->addEdge($entity, $targetEntity, $isNullable);
            }
        }

        return $sort->sort();
    }

    /** @return list<object> */
    private function computeDeleteExecutionOrder(): array
    {
        $stronglyConnectedComponents = new StronglyConnectedComponents();
        $sort                        = new TopologicalSort();

        foreach ($this->entityDeletions as $entity) {
            $stronglyConnectedComponents->addNode($entity);
            $sort->addNode($entity);
        }

        // First, consider only "on delete cascade" associations between entities
        // and find strongly connected groups. Once we delete any one of the entities
        // in such a group, _all_ of the other entities will be removed as well. So,
        // we need to treat those groups like a single entity when performing delete
        // order topological sorting.
        foreach ($this->entityDeletions as $entity) {
            $class = $this->em->getClassMetadata(get_class($entity));

            foreach ($class->associationMappings as $assoc) {
                // We only need to consider the owning sides of to-one associations,
                // since many-to-many associations can always be (and have already been)
                // deleted in a preceding step.
                if (! ($assoc['isOwningSide'] && $assoc['type'] & ClassMetadata::TO_ONE)) {
                    continue;
                }

                assert(isset($assoc['joinColumns']));
                $joinColumns = reset($assoc['joinColumns']);
                if (! isset($joinColumns['onDelete'])) {
                    continue;
                }

                $onDeleteOption = strtolower($joinColumns['onDelete']);
                if ($onDeleteOption !== 'cascade') {
                    continue;
                }

                $targetEntity = $class->getFieldValue($entity, $assoc['fieldName']);

                // If the association does not refer to another entity or that entity
                // is not to be deleted, there is no ordering problem and we can
                // skip this particular association.
                if ($targetEntity === null || ! $stronglyConnectedComponents->hasNode($targetEntity)) {
                    continue;
                }

                $stronglyConnectedComponents->addEdge($entity, $targetEntity);
            }
        }

        $stronglyConnectedComponents->findStronglyConnectedComponents();

        // Now do the actual topological sorting to find the delete order.
        foreach ($this->entityDeletions as $entity) {
            $class = $this->em->getClassMetadata(get_class($entity));

            // Get the entities representing the SCC
            $entityComponent = $stronglyConnectedComponents->getNodeRepresentingStronglyConnectedComponent($entity);

            // When $entity is part of a non-trivial strongly connected component group
            // (a group containing not only those entities alone), make sure we process it _after_ the
            // entity representing the group.
            // The dependency direction implies that "$entity depends on $entityComponent
            // being deleted first". The topological sort will output the depended-upon nodes first.
            if ($entityComponent !== $entity) {
                $sort->addEdge($entity, $entityComponent, false);
            }

            foreach ($class->associationMappings as $assoc) {
                // We only need to consider the owning sides of to-one associations,
                // since many-to-many associations can always be (and have already been)
                // deleted in a preceding step.
                if (! ($assoc['isOwningSide'] && $assoc['type'] & ClassMetadata::TO_ONE)) {
                    continue;
                }

                // For associations that implement a database-level set null operation,
                // we do not have to follow a particular order: If the referred-to entity is
                // deleted first, the DBMS will temporarily set the foreign key to NULL (SET NULL).
                // So, we can skip it in the computation.
                assert(isset($assoc['joinColumns']));
                $joinColumns = reset($assoc['joinColumns']);
                if (isset($joinColumns['onDelete'])) {
                    $onDeleteOption = strtolower($joinColumns['onDelete']);
                    if ($onDeleteOption === 'set null') {
                        continue;
                    }
                }

                $targetEntity = $class->getFieldValue($entity, $assoc['fieldName']);

                // If the association does not refer to another entity or that entity
                // is not to be deleted, there is no ordering problem and we can
                // skip this particular association.
                if ($targetEntity === null || ! $sort->hasNode($targetEntity)) {
                    continue;
                }

                // Get the entities representing the SCC
                $targetEntityComponent = $stronglyConnectedComponents->getNodeRepresentingStronglyConnectedComponent($targetEntity);

                // When we have a dependency between two different groups of strongly connected nodes,
                // add it to the computation.
                // The dependency direction implies that "$targetEntityComponent depends on $entityComponent
                // being deleted first". The topological sort will output the depended-upon nodes first,
                // so we can work through the result in the returned order.
                if ($targetEntityComponent !== $entityComponent) {
                    $sort->addEdge($targetEntityComponent, $entityComponent, false);
                }
            }
        }

        return $sort->sort();
    }

    /**
     * Schedules an entity for insertion into the database.
     * If the entity already has an identifier, it will be added to the identity map.
     *
     * @param object $entity The entity to schedule for insertion.
     *
     * @return void
     *
     * @throws ORMInvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function scheduleForInsert($entity)
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
     *
     * @param object $entity
     *
     * @return bool
     */
    public function isScheduledForInsert($entity)
    {
        return isset($this->entityInsertions[spl_object_id($entity)]);
    }

    /**
     * Schedules an entity for being updated.
     *
     * @param object $entity The entity to schedule for being updated.
     *
     * @return void
     *
     * @throws ORMInvalidArgumentException
     */
    public function scheduleForUpdate($entity)
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
     * @param object $entity The entity for which to schedule an extra update.
     * @phpstan-param array<string, array{mixed, mixed}>  $changeset The changeset of the entity (what to update).
     *
     * @return void
     *
     * @ignore
     */
    public function scheduleExtraUpdate($entity, array $changeset)
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
     *
     * @param object $entity
     *
     * @return bool
     */
    public function isScheduledForUpdate($entity)
    {
        return isset($this->entityUpdates[spl_object_id($entity)]);
    }

    /**
     * Checks whether an entity is registered to be checked in the unit of work.
     *
     * @param object $entity
     *
     * @return bool
     */
    public function isScheduledForDirtyCheck($entity)
    {
        $rootEntityName = $this->em->getClassMetadata(get_class($entity))->rootEntityName;

        return isset($this->scheduledForSynchronization[$rootEntityName][spl_object_id($entity)]);
    }

    /**
     * INTERNAL:
     * Schedules an entity for deletion.
     *
     * @param object $entity
     *
     * @return void
     */
    public function scheduleForDelete($entity)
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

        unset($this->entityUpdates[$oid]);

        if (! isset($this->entityDeletions[$oid])) {
            $this->entityDeletions[$oid] = $entity;
            $this->entityStates[$oid]    = self::STATE_REMOVED;
        }
    }

    /**
     * Checks whether an entity is registered as removed/deleted with the unit
     * of work.
     *
     * @param object $entity
     *
     * @return bool
     */
    public function isScheduledForDelete($entity)
    {
        return isset($this->entityDeletions[spl_object_id($entity)]);
    }

    /**
     * Checks whether an entity is scheduled for insertion, update or deletion.
     *
     * @param object $entity
     *
     * @return bool
     */
    public function isEntityScheduled($entity)
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
     * @param object $entity The entity to register.
     *
     * @return bool TRUE if the registration was successful, FALSE if the identity of
     * the entity in question is already managed.
     *
     * @throws ORMInvalidArgumentException
     * @throws EntityIdentityCollisionException
     *
     * @ignore
     */
    public function addToIdentityMap($entity)
    {
        $classMetadata = $this->em->getClassMetadata(get_class($entity));
        $idHash        = $this->getIdHashByEntity($entity);
        $className     = $classMetadata->rootEntityName;

        if (isset($this->identityMap[$className][$idHash])) {
            if ($this->identityMap[$className][$idHash] !== $entity) {
                if ($this->em->getConfiguration()->isRejectIdCollisionInIdentityMapEnabled()) {
                    throw EntityIdentityCollisionException::create($this->identityMap[$className][$idHash], $entity, $idHash);
                }

                Deprecation::trigger(
                    'doctrine/orm',
                    'https://github.com/doctrine/orm/pull/10785',
                    <<<'EXCEPTION'
While adding an entity of class %s with an ID hash of "%s" to the identity map,
another object of class %s was already present for the same ID. This will trigger
an exception in ORM 3.0.

IDs should uniquely map to entity object instances. This problem may occur if:

- you use application-provided IDs and reuse ID values;
- database-provided IDs are reassigned after truncating the database without
clearing the EntityManager;
- you might have been using EntityManager#getReference() to create a reference
for a nonexistent ID that was subsequently (by the RDBMS) assigned to another
entity.

Otherwise, it might be an ORM-internal inconsistency, please report it.

To opt-in to the new exception, call
\Doctrine\ORM\Configuration::setRejectIdCollisionInIdentityMap on the entity
manager's configuration.
EXCEPTION
                    ,
                    get_class($entity),
                    $idHash,
                    get_class($this->identityMap[$className][$idHash])
                );
            }

            return false;
        }

        $this->identityMap[$className][$idHash] = $entity;

        return true;
    }

    /**
     * Gets the id hash of an entity by its identifier.
     *
     * @param array<string|int, mixed> $identifier The identifier of an entity
     *
     * @return string The entity id hash.
     */
    final public static function getIdHashByIdentifier(array $identifier): string
    {
        foreach ($identifier as $k => $value) {
            if ($value instanceof BackedEnum) {
                $identifier[$k] = $value->value;
            }
        }

        return implode(
            ' ',
            $identifier
        );
    }

    /**
     * Gets the id hash of an entity.
     *
     * @param object $entity The entity managed by Unit Of Work
     *
     * @return string The entity id hash.
     */
    public function getIdHashByEntity($entity): string
    {
        $identifier = $this->entityIdentifiers[spl_object_id($entity)];

        if (empty($identifier) || in_array(null, $identifier, true)) {
            $classMetadata = $this->em->getClassMetadata(get_class($entity));

            throw ORMInvalidArgumentException::entityWithoutIdentity($classMetadata->name, $entity);
        }

        return self::getIdHashByIdentifier($identifier);
    }

    /**
     * Gets the state of an entity with regard to the current unit of work.
     *
     * @param object   $entity
     * @param int|null $assume The state to assume if the state is not yet known (not MANAGED or REMOVED).
     *                         This parameter can be set to improve performance of entity state detection
     *                         by potentially avoiding a database lookup if the distinction between NEW and DETACHED
     *                         is either known or does not matter for the caller of the method.
     * @phpstan-param self::STATE_*|null $assume
     *
     * @return int The entity state.
     * @phpstan-return self::STATE_*
     */
    public function getEntityState($entity, $assume = null)
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
        $class = $this->em->getClassMetadata(get_class($entity));
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
     * @param object $entity
     *
     * @return bool
     *
     * @throws ORMInvalidArgumentException
     *
     * @ignore
     */
    public function removeFromIdentityMap($entity)
    {
        $oid           = spl_object_id($entity);
        $classMetadata = $this->em->getClassMetadata(get_class($entity));
        $idHash        = self::getIdHashByIdentifier($this->entityIdentifiers[$oid]);

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
     * @param string $idHash
     * @param string $rootClassName
     *
     * @return object
     *
     * @ignore
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
     * @param mixed  $idHash        (must be possible to cast it to string)
     * @param string $rootClassName
     *
     * @return false|object The found entity or FALSE.
     *
     * @ignore
     */
    public function tryGetByIdHash($idHash, $rootClassName)
    {
        $stringIdHash = (string) $idHash;

        return $this->identityMap[$rootClassName][$stringIdHash] ?? false;
    }

    /**
     * Checks whether an entity is registered in the identity map of this UnitOfWork.
     *
     * @param object $entity
     *
     * @return bool
     */
    public function isInIdentityMap($entity)
    {
        $oid = spl_object_id($entity);

        if (empty($this->entityIdentifiers[$oid])) {
            return false;
        }

        $classMetadata = $this->em->getClassMetadata(get_class($entity));
        $idHash        = self::getIdHashByIdentifier($this->entityIdentifiers[$oid]);

        return isset($this->identityMap[$classMetadata->rootEntityName][$idHash]);
    }

    /**
     * INTERNAL:
     * Checks whether an identifier hash exists in the identity map.
     *
     * @param string $idHash
     * @param string $rootClassName
     *
     * @return bool
     *
     * @ignore
     */
    public function containsIdHash($idHash, $rootClassName)
    {
        return isset($this->identityMap[$rootClassName][$idHash]);
    }

    /**
     * Persists an entity as part of the current unit of work.
     *
     * @param object $entity The entity to persist.
     *
     * @return void
     */
    public function persist($entity)
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
     * @param object $entity The entity to persist.
     * @phpstan-param array<int, object> $visited The already visited entities.
     *
     * @throws ORMInvalidArgumentException
     * @throws UnexpectedValueException
     */
    private function doPersist($entity, array &$visited): void
    {
        $oid = spl_object_id($entity);

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
     *
     * @param object $entity The entity to remove.
     *
     * @return void
     */
    public function remove($entity)
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
     * @param object $entity The entity to delete.
     * @phpstan-param array<int, object> $visited The map of the already visited entities.
     *
     * @throws ORMInvalidArgumentException If the instance is a detached entity.
     * @throws UnexpectedValueException
     */
    private function doRemove($entity, array &$visited): void
    {
        $oid = spl_object_id($entity);

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
                $invoke = $this->listenersInvoker->getSubscribedSystems($class, Events::preRemove);

                if ($invoke !== ListenersInvoker::INVOKE_NONE) {
                    $this->listenersInvoker->invoke($class, Events::preRemove, $entity, new PreRemoveEventArgs($entity, $this->em), $invoke);
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
     * Merges the state of the given detached entity into this UnitOfWork.
     *
     * @deprecated 2.7 This method is being removed from the ORM and won't have any replacement
     *
     * @param object $entity
     *
     * @return object The managed copy of the entity.
     *
     * @throws OptimisticLockException If the entity uses optimistic locking through a version
     *         attribute and the version check against the managed copy fails.
     */
    public function merge($entity)
    {
        $visited = [];

        return $this->doMerge($entity, $visited);
    }

    /**
     * Executes a merge operation on an entity.
     *
     * @param object $entity
     * @phpstan-param AssociationMapping|null $assoc
     * @phpstan-param array<int, object> $visited
     *
     * @return object The managed copy of the entity.
     *
     * @throws OptimisticLockException If the entity uses optimistic locking through a version
     *         attribute and the version check against the managed copy fails.
     * @throws ORMInvalidArgumentException If the entity instance is NEW.
     * @throws EntityNotFoundException if an assigned identifier is used in the entity, but none is provided.
     */
    private function doMerge(
        $entity,
        array &$visited,
        $prevManagedCopy = null,
        ?array $assoc = null
    ) {
        $oid = spl_object_id($entity);

        if (isset($visited[$oid])) {
            $managedCopy = $visited[$oid];

            if ($prevManagedCopy !== null) {
                $this->updateAssociationWithMergedEntity($entity, $assoc, $prevManagedCopy, $managedCopy);
            }

            return $managedCopy;
        }

        $class = $this->em->getClassMetadata(get_class($entity));

        // First we assume DETACHED, although it can still be NEW but we can avoid
        // an extra db-roundtrip this way. If it is not MANAGED but has an identity,
        // we need to fetch it from the db anyway in order to merge.
        // MANAGED entities are ignored by the merge operation.
        $managedCopy = $entity;

        if ($this->getEntityState($entity, self::STATE_DETACHED) !== self::STATE_MANAGED) {
            // Try to look the entity up in the identity map.
            $id = $class->getIdentifierValues($entity);

            // If there is no ID, it is actually NEW.
            if (! $id) {
                $managedCopy = $this->newInstance($class);

                $this->mergeEntityStateIntoManagedCopy($entity, $managedCopy);
                $this->persistNew($class, $managedCopy);
            } else {
                $flatId = $class->containsForeignIdentifier || $class->containsEnumIdentifier
                    ? $this->identifierFlattener->flattenIdentifier($class, $id)
                    : $id;

                $managedCopy = $this->tryGetById($flatId, $class->rootEntityName);

                if ($managedCopy) {
                    // We have the entity in-memory already, just make sure its not removed.
                    if ($this->getEntityState($managedCopy) === self::STATE_REMOVED) {
                        throw ORMInvalidArgumentException::entityIsRemoved($managedCopy, 'merge');
                    }
                } else {
                    // We need to fetch the managed copy in order to merge.
                    $managedCopy = $this->em->find($class->name, $flatId);
                }

                if ($managedCopy === null) {
                    // If the identifier is ASSIGNED, it is NEW, otherwise an error
                    // since the managed entity was not found.
                    if (! $class->isIdentifierNatural()) {
                        throw EntityNotFoundException::fromClassNameAndIdentifier(
                            $class->getName(),
                            $this->identifierFlattener->flattenIdentifier($class, $id)
                        );
                    }

                    $managedCopy = $this->newInstance($class);
                    $class->setIdentifierValues($managedCopy, $id);

                    $this->mergeEntityStateIntoManagedCopy($entity, $managedCopy);
                    $this->persistNew($class, $managedCopy);
                } else {
                    $this->ensureVersionMatch($class, $entity, $managedCopy);
                    $this->mergeEntityStateIntoManagedCopy($entity, $managedCopy);
                }
            }

            $visited[$oid] = $managedCopy; // mark visited

            if ($class->isChangeTrackingDeferredExplicit()) {
                $this->scheduleForDirtyCheck($entity);
            }
        }

        if ($prevManagedCopy !== null) {
            $this->updateAssociationWithMergedEntity($entity, $assoc, $prevManagedCopy, $managedCopy);
        }

        // Mark the managed copy visited as well
        $visited[spl_object_id($managedCopy)] = $managedCopy;

        $this->cascadeMerge($entity, $managedCopy, $visited);

        return $managedCopy;
    }

    /**
     * @param object $entity
     * @param object $managedCopy
     * @phpstan-param ClassMetadata<T> $class
     * @phpstan-param T $entity
     * @phpstan-param T $managedCopy
     *
     * @throws OptimisticLockException
     *
     * @template T of object
     */
    private function ensureVersionMatch(
        ClassMetadata $class,
        $entity,
        $managedCopy
    ): void {
        if (! ($class->isVersioned && ! $this->isUninitializedObject($managedCopy) && ! $this->isUninitializedObject($entity))) {
            return;
        }

        assert($class->versionField !== null);
        $reflField          = $class->reflFields[$class->versionField];
        $managedCopyVersion = $reflField->getValue($managedCopy);
        $entityVersion      = $reflField->getValue($entity);

        // Throw exception if versions don't match.
        // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
        if ($managedCopyVersion == $entityVersion) {
            return;
        }

        throw OptimisticLockException::lockFailedVersionMismatch($entity, $entityVersion, $managedCopyVersion);
    }

    /**
     * Sets/adds associated managed copies into the previous entity's association field
     *
     * @param object $entity
     * @phpstan-param AssociationMapping $association
     */
    private function updateAssociationWithMergedEntity(
        $entity,
        array $association,
        $previousManagedCopy,
        $managedCopy
    ): void {
        $assocField = $association['fieldName'];
        $prevClass  = $this->em->getClassMetadata(get_class($previousManagedCopy));

        if ($association['type'] & ClassMetadata::TO_ONE) {
            $prevClass->reflFields[$assocField]->setValue($previousManagedCopy, $managedCopy);

            return;
        }

        $value   = $prevClass->reflFields[$assocField]->getValue($previousManagedCopy);
        $value[] = $managedCopy;

        if ($association['type'] === ClassMetadata::ONE_TO_MANY) {
            $class = $this->em->getClassMetadata(get_class($entity));

            $class->reflFields[$association['mappedBy']]->setValue($managedCopy, $previousManagedCopy);
        }
    }

    /**
     * Detaches an entity from the persistence management. It's persistence will
     * no longer be managed by Doctrine.
     *
     * @param object $entity The entity to detach.
     *
     * @return void
     */
    public function detach($entity)
    {
        $visited = [];

        $this->doDetach($entity, $visited);
    }

    /**
     * Executes a detach operation on the given entity.
     *
     * @param object  $entity
     * @param mixed[] $visited
     * @param bool    $noCascade if true, don't cascade detach operation.
     */
    private function doDetach(
        $entity,
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
     * @param object $entity The entity to refresh
     *
     * @return void
     *
     * @throws InvalidArgumentException If the entity is not MANAGED.
     * @throws TransactionRequiredException
     */
    public function refresh($entity)
    {
        $visited = [];

        $lockMode = null;

        if (func_num_args() > 1) {
            $lockMode = func_get_arg(1);
        }

        $this->doRefresh($entity, $visited, $lockMode);
    }

    /**
     * Executes a refresh operation on an entity.
     *
     * @param object $entity The entity to refresh.
     * @phpstan-param array<int, object>  $visited The already visited entities during cascades.
     * @phpstan-param LockMode::*|null $lockMode
     *
     * @throws ORMInvalidArgumentException If the entity is not MANAGED.
     * @throws TransactionRequiredException
     */
    private function doRefresh($entity, array &$visited, ?int $lockMode = null): void
    {
        switch (true) {
            case $lockMode === LockMode::PESSIMISTIC_READ:
            case $lockMode === LockMode::PESSIMISTIC_WRITE:
                if (! $this->em->getConnection()->isTransactionActive()) {
                    throw TransactionRequiredException::transactionRequired();
                }
        }

        $oid = spl_object_id($entity);

        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $entity; // mark visited

        $class = $this->em->getClassMetadata(get_class($entity));

        if ($this->getEntityState($entity) !== self::STATE_MANAGED) {
            throw ORMInvalidArgumentException::entityNotManaged($entity);
        }

        $this->cascadeRefresh($entity, $visited, $lockMode);

        $this->getEntityPersister($class->name)->refresh(
            array_combine($class->getIdentifierFieldNames(), $this->entityIdentifiers[$oid]),
            $entity,
            $lockMode
        );
    }

    /**
     * Cascades a refresh operation to associated entities.
     *
     * @param object $entity
     * @phpstan-param array<int, object> $visited
     * @phpstan-param LockMode::*|null $lockMode
     */
    private function cascadeRefresh($entity, array &$visited, ?int $lockMode = null): void
    {
        $class = $this->em->getClassMetadata(get_class($entity));

        $associationMappings = array_filter(
            $class->associationMappings,
            static function ($assoc) {
                return $assoc['isCascadeRefresh'];
            }
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
                        $this->doRefresh($relatedEntity, $visited, $lockMode);
                    }

                    break;

                case $relatedEntities !== null:
                    $this->doRefresh($relatedEntities, $visited, $lockMode);
                    break;

                default:
                    // Do nothing
            }
        }
    }

    /**
     * Cascades a detach operation to associated entities.
     *
     * @param object             $entity
     * @param array<int, object> $visited
     */
    private function cascadeDetach($entity, array &$visited): void
    {
        $class = $this->em->getClassMetadata(get_class($entity));

        $associationMappings = array_filter(
            $class->associationMappings,
            static function ($assoc) {
                return $assoc['isCascadeDetach'];
            }
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
     * Cascades a merge operation to associated entities.
     *
     * @param object $entity
     * @param object $managedCopy
     * @phpstan-param array<int, object> $visited
     */
    private function cascadeMerge($entity, $managedCopy, array &$visited): void
    {
        $class = $this->em->getClassMetadata(get_class($entity));

        $associationMappings = array_filter(
            $class->associationMappings,
            static function ($assoc) {
                return $assoc['isCascadeMerge'];
            }
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
            } elseif ($relatedEntities !== null) {
                $this->doMerge($relatedEntities, $visited, $managedCopy, $assoc);
            }
        }
    }

    /**
     * Cascades the save operation to associated entities.
     *
     * @param object $entity
     * @phpstan-param array<int, object> $visited
     */
    private function cascadePersist($entity, array &$visited): void
    {
        if ($this->isUninitializedObject($entity)) {
            // nothing to do - proxy is not initialized, therefore we don't do anything with it
            return;
        }

        $class = $this->em->getClassMetadata(get_class($entity));

        $associationMappings = array_filter(
            $class->associationMappings,
            static function ($assoc) {
                return $assoc['isCascadePersist'];
            }
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
     * @param object $entity
     * @phpstan-param array<int, object> $visited
     */
    private function cascadeRemove($entity, array &$visited): void
    {
        $class = $this->em->getClassMetadata(get_class($entity));

        $associationMappings = array_filter(
            $class->associationMappings,
            static function ($assoc) {
                return $assoc['isCascadeRemove'];
            }
        );

        if ($associationMappings) {
            $this->initializeObject($entity);
        }

        $entitiesToCascade = [];

        foreach ($associationMappings as $assoc) {
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
     * @param object                     $entity
     * @param int|DateTimeInterface|null $lockVersion
     * @phpstan-param LockMode::* $lockMode
     *
     * @throws ORMInvalidArgumentException
     * @throws TransactionRequiredException
     * @throws OptimisticLockException
     */
    public function lock($entity, int $lockMode, $lockVersion = null): void
    {
        if ($this->getEntityState($entity, self::STATE_DETACHED) !== self::STATE_MANAGED) {
            throw ORMInvalidArgumentException::entityNotManaged($entity);
        }

        $class = $this->em->getClassMetadata(get_class($entity));

        switch (true) {
            case $lockMode === LockMode::OPTIMISTIC:
                if (! $class->isVersioned) {
                    throw OptimisticLockException::notVersioned($class->name);
                }

                if ($lockVersion === null) {
                    return;
                }

                $this->initializeObject($entity);

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
     * Clears the UnitOfWork.
     *
     * @param string|null $entityName if given, only entities of this type will get detached.
     *
     * @return void
     *
     * @throws ORMInvalidArgumentException if an invalid entity name is given.
     */
    public function clear($entityName = null)
    {
        if ($entityName === null) {
            $this->identityMap                      =
            $this->entityIdentifiers                =
            $this->originalEntityData               =
            $this->entityChangeSets                 =
            $this->entityStates                     =
            $this->scheduledForSynchronization      =
            $this->entityInsertions                 =
            $this->entityUpdates                    =
            $this->entityDeletions                  =
            $this->nonCascadedNewDetectedEntities   =
            $this->collectionDeletions              =
            $this->collectionUpdates                =
            $this->extraUpdates                     =
            $this->readOnlyObjects                  =
            $this->pendingCollectionElementRemovals =
            $this->visitedCollections               =
            $this->eagerLoadingEntities             =
            $this->eagerLoadingCollections          =
            $this->orphanRemovals                   = [];
        } else {
            Deprecation::triggerIfCalledFromOutside(
                'doctrine/orm',
                'https://github.com/doctrine/orm/issues/8460',
                'Calling %s() with any arguments to clear specific entities is deprecated and will not be supported in Doctrine ORM 3.0.',
                __METHOD__
            );

            $this->clearIdentityMapForEntityName($entityName);
            $this->clearEntityInsertionsForEntityName($entityName);
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
     * @param object $entity
     *
     * @return void
     *
     * @ignore
     */
    public function scheduleOrphanRemoval($entity)
    {
        $this->orphanRemovals[spl_object_id($entity)] = $entity;
    }

    /**
     * INTERNAL:
     * Cancels a previously scheduled orphan removal.
     *
     * @param object $entity
     *
     * @return void
     *
     * @ignore
     */
    public function cancelOrphanRemoval($entity)
    {
        unset($this->orphanRemovals[spl_object_id($entity)]);
    }

    /**
     * INTERNAL:
     * Schedules a complete collection for removal when this UnitOfWork commits.
     *
     * @return void
     */
    public function scheduleCollectionDeletion(PersistentCollection $coll)
    {
        $coid = spl_object_id($coll);

        // TODO: if $coll is already scheduled for recreation ... what to do?
        // Just remove $coll from the scheduled recreations?
        unset($this->collectionUpdates[$coid]);

        $this->collectionDeletions[$coid] = $coll;
    }

    /** @return bool */
    public function isCollectionScheduledForDeletion(PersistentCollection $coll)
    {
        return isset($this->collectionDeletions[spl_object_id($coll)]);
    }

    /** @return object */
    private function newInstance(ClassMetadata $class)
    {
        $entity = $class->newInstance();

        if ($entity instanceof ObjectManagerAware) {
            $entity->injectObjectManager($this->em, $class);
        }

        return $entity;
    }

    /**
     * INTERNAL:
     * Creates an entity. Used for reconstitution of persistent entities.
     *
     * Internal note: Highly performance-sensitive method.
     *
     * @param class-string         $className The name of the entity class.
     * @param mixed[]              $data      The data for the entity.
     * @param array<string, mixed> $hints     Any hints to account for during reconstitution/lookup of the entity.
     *
     * @return object The managed entity instance.
     *
     * @ignore
     * @todo Rename: getOrCreateEntity
     */
    public function createEntity($className, array $data, &$hints = [])
    {
        $class = $this->em->getClassMetadata($className);

        $id     = $this->identifierFlattener->flattenIdentifier($class, $data);
        $idHash = self::getIdHashByIdentifier($id);

        if (isset($this->identityMap[$class->rootEntityName][$idHash])) {
            $entity = $this->identityMap[$class->rootEntityName][$idHash];
            $oid    = spl_object_id($entity);

            if (
                isset($hints[Query::HINT_REFRESH], $hints[Query::HINT_REFRESH_ENTITY])
            ) {
                $unmanagedProxy = $hints[Query::HINT_REFRESH_ENTITY];
                if (
                    $unmanagedProxy !== $entity
                    && $this->isIdentifierEquals($unmanagedProxy, $entity)
                ) {
                    // We will hydrate the given un-managed proxy anyway:
                    // continue work, but consider it the entity from now on
                    $entity = $unmanagedProxy;
                }
            }

            if ($this->isUninitializedObject($entity)) {
                $entity->__setInitialized(true);

                if ($this->em->getConfiguration()->isLazyGhostObjectEnabled()) {
                    // Initialize properties that have default values to their default value (similar to what
                    Hydrator::hydrate($entity, (array) $class->reflClass->newInstanceWithoutConstructor());
                }
            } else {
                if (
                    ! isset($hints[Query::HINT_REFRESH])
                    || (isset($hints[Query::HINT_REFRESH_ENTITY]) && $hints[Query::HINT_REFRESH_ENTITY] !== $entity)
                ) {
                    return $entity;
                }
            }

            // inject ObjectManager upon refresh.
            if ($entity instanceof ObjectManagerAware) {
                $entity->injectObjectManager($this->em, $class);
            }

            $this->originalEntityData[$oid] = $data;

            if ($entity instanceof NotifyPropertyChanged) {
                $entity->addPropertyChangedListener($this);
            }
        } else {
            $entity = $this->newInstance($class);
            $oid    = spl_object_id($entity);
            $this->registerManaged($entity, $id, $data);

            if (isset($hints[Query::HINT_READ_ONLY]) && $hints[Query::HINT_READ_ONLY] === true) {
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

            if (! isset($hints['fetchMode'][$class->name][$field])) {
                $hints['fetchMode'][$class->name][$field] = $assoc['fetch'];
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
                            if ($joinColumnValue instanceof BackedEnum) {
                                $joinColumnValue = $joinColumnValue->value;
                            }

                            if ($targetClass->containsForeignIdentifier) {
                                $associatedId[$targetClass->getFieldForColumn($targetColumn)] = $joinColumnValue;
                            } else {
                                $associatedId[$targetClass->fieldNames[$targetColumn]] = $joinColumnValue;
                            }
                        } elseif (in_array($targetClass->getFieldForColumn($targetColumn), $targetClass->identifier, true)) {
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

                    // Foreign key is set
                    // Check identity map first
                    // FIXME: Can break easily with composite keys if join column values are in
                    //        wrong order. The correct order is the one in ClassMetadata#identifier.
                    $relatedIdHash = self::getIdHashByIdentifier($associatedId);

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
                                $this->isUninitializedObject($newValue)
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
                            $normalizedAssociatedId = $this->normalizeIdentifier($targetClass, $associatedId);

                            switch (true) {
                                // We are negating the condition here. Other cases will assume it is valid!
                                case $hints['fetchMode'][$class->name][$field] !== ClassMetadata::FETCH_EAGER:
                                    $newValue = $this->em->getProxyFactory()->getProxy($assoc['targetEntity'], $normalizedAssociatedId);
                                    $this->registerManaged($newValue, $associatedId, []);
                                    break;

                                // Deferred eager load only works for single identifier classes
                                case isset($hints[self::HINT_DEFEREAGERLOAD]) &&
                                    $hints[self::HINT_DEFEREAGERLOAD] &&
                                    ! $targetClass->isIdentifierComposite:
                                    // TODO: Is there a faster approach?
                                    $this->eagerLoadingEntities[$targetClass->rootEntityName][$relatedIdHash] = current($normalizedAssociatedId);

                                    $newValue = $this->em->getProxyFactory()->getProxy($assoc['targetEntity'], $normalizedAssociatedId);
                                    $this->registerManaged($newValue, $associatedId, []);
                                    break;

                                default:
                                    // TODO: This is very imperformant, ignore it?
                                    $newValue = $this->em->find($assoc['targetEntity'], $normalizedAssociatedId);
                                    break;
                            }
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

                    if ($hints['fetchMode'][$class->name][$field] === ClassMetadata::FETCH_EAGER) {
                        if (
                            $assoc['type'] === ClassMetadata::ONE_TO_MANY
                            // is iteration
                            && ! (isset($hints[Query::HINT_INTERNAL_ITERATION]) && $hints[Query::HINT_INTERNAL_ITERATION])
                            // is foreign key composite
                            && ! ($targetClass->hasAssociation($assoc['mappedBy']) && count($targetClass->getAssociationMapping($assoc['mappedBy'])['joinColumns'] ?? []) > 1)
                            && ! isset($assoc['indexBy'])
                        ) {
                            $this->scheduleCollectionForBatchLoading($pColl, $class);
                        } else {
                            $this->loadCollection($pColl);
                            $pColl->takeSnapshot();
                        }
                    }

                    $this->originalEntityData[$oid][$field] = $pColl;
                    break;
            }
        }

        // defer invoking of postLoad event to hydration complete step
        $this->hydrationCompleteHandler->deferPostLoadInvoking($class, $entity);

        return $entity;
    }

    /** @return void */
    public function triggerEagerLoads()
    {
        if (! $this->eagerLoadingEntities && ! $this->eagerLoadingCollections) {
            return;
        }

        // avoid infinite recursion
        $eagerLoadingEntities       = $this->eagerLoadingEntities;
        $this->eagerLoadingEntities = [];

        foreach ($eagerLoadingEntities as $entityName => $ids) {
            if (! $ids) {
                continue;
            }

            $class   = $this->em->getClassMetadata($entityName);
            $batches = array_chunk($ids, $this->em->getConfiguration()->getEagerFetchBatchSize());

            foreach ($batches as $batchedIds) {
                $this->getEntityPersister($entityName)->loadAll(
                    array_combine($class->identifier, [$batchedIds])
                );
            }
        }

        $eagerLoadingCollections       = $this->eagerLoadingCollections; // avoid recursion
        $this->eagerLoadingCollections = [];

        foreach ($eagerLoadingCollections as $group) {
            $this->eagerLoadCollections($group['items'], $group['mapping']);
        }
    }

    /**
     * Load all data into the given collections, according to the specified mapping
     *
     * @param PersistentCollection[] $collections
     * @param array<string, mixed>   $mapping
     * @phpstan-param array{
     *     targetEntity: class-string,
     *     sourceEntity: class-string,
     *     mappedBy: string,
     *     indexBy: string|null,
     *     orderBy: array<string, string>|null
     * } $mapping
     */
    private function eagerLoadCollections(array $collections, array $mapping): void
    {
        $targetEntity = $mapping['targetEntity'];
        $class        = $this->em->getClassMetadata($mapping['sourceEntity']);
        $mappedBy     = $mapping['mappedBy'];

        $batches = array_chunk($collections, $this->em->getConfiguration()->getEagerFetchBatchSize(), true);

        foreach ($batches as $collectionBatch) {
            $entities = [];

            foreach ($collectionBatch as $collection) {
                $entities[] = $collection->getOwner();
            }

            $found = $this->getEntityPersister($targetEntity)->loadAll([$mappedBy => $entities], $mapping['orderBy'] ?? null);

            $targetClass    = $this->em->getClassMetadata($targetEntity);
            $targetProperty = $targetClass->getReflectionProperty($mappedBy);

            foreach ($found as $targetValue) {
                $sourceEntity = $targetProperty->getValue($targetValue);

                if ($sourceEntity === null && isset($targetClass->associationMappings[$mappedBy]['joinColumns'])) {
                    // case where the hydration $targetValue itself has not yet fully completed, for example
                    // in case a bi-directional association is being hydrated and deferring eager loading is
                    // not possible due to subclassing.
                    $data = $this->getOriginalEntityData($targetValue);
                    $id   = [];
                    foreach ($targetClass->associationMappings[$mappedBy]['joinColumns'] as $joinColumn) {
                        $id[] = $data[$joinColumn['name']];
                    }
                } else {
                    $id = $this->identifierFlattener->flattenIdentifier($class, $class->getIdentifierValues($sourceEntity));
                }

                $idHash = implode(' ', $id);

                if (isset($mapping['indexBy'])) {
                    $indexByProperty = $targetClass->getReflectionProperty($mapping['indexBy']);
                    $collectionBatch[$idHash]->hydrateSet($indexByProperty->getValue($targetValue), $targetValue);
                } else {
                    $collectionBatch[$idHash]->add($targetValue);
                }
            }
        }

        foreach ($collections as $association) {
            $association->setInitialized(true);
            $association->takeSnapshot();
        }
    }

    /**
     * Initializes (loads) an uninitialized persistent collection of an entity.
     *
     * @param PersistentCollection $collection The collection to initialize.
     *
     * @return void
     *
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

        $collection->setInitialized(true);
    }

    /**
     * Schedule this collection for batch loading at the end of the UnitOfWork
     */
    private function scheduleCollectionForBatchLoading(PersistentCollection $collection, ClassMetadata $sourceClass): void
    {
        $mapping = $collection->getMapping();
        $name    = $mapping['sourceEntity'] . '#' . $mapping['fieldName'];

        if (! isset($this->eagerLoadingCollections[$name])) {
            $this->eagerLoadingCollections[$name] = [
                'items'   => [],
                'mapping' => $mapping,
            ];
        }

        $owner = $collection->getOwner();
        assert($owner !== null);

        $id     = $this->identifierFlattener->flattenIdentifier(
            $sourceClass,
            $sourceClass->getIdentifierValues($owner)
        );
        $idHash = implode(' ', $id);

        $this->eagerLoadingCollections[$name]['items'][$idHash] = $collection;
    }

    /**
     * Gets the identity map of the UnitOfWork.
     *
     * @return array<class-string, array<string, object>>
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
     *
     * @return mixed[]
     * @phpstan-return array<string, mixed>
     */
    public function getOriginalEntityData($entity)
    {
        $oid = spl_object_id($entity);

        return $this->originalEntityData[$oid] ?? [];
    }

    /**
     * @param object  $entity
     * @param mixed[] $data
     *
     * @return void
     *
     * @ignore
     */
    public function setOriginalEntityData($entity, array $data)
    {
        $this->originalEntityData[spl_object_id($entity)] = $data;
    }

    /**
     * INTERNAL:
     * Sets a property value of the original data array of an entity.
     *
     * @param int    $oid
     * @param string $property
     * @param mixed  $value
     *
     * @return void
     *
     * @ignore
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
     *
     * @return mixed[] The identifier values.
     */
    public function getEntityIdentifier($entity)
    {
        if (! isset($this->entityIdentifiers[spl_object_id($entity)])) {
            throw EntityNotFoundException::noIdentifierFound(get_debug_type($entity));
        }

        return $this->entityIdentifiers[spl_object_id($entity)];
    }

    /**
     * Processes an entity instance to extract their identifier values.
     *
     * @param object $entity The entity instance.
     *
     * @return mixed A scalar value.
     *
     * @throws ORMInvalidArgumentException
     */
    public function getSingleIdentifierValue($entity)
    {
        $class = $this->em->getClassMetadata(get_class($entity));

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
     * @param mixed        $id            The entity identifier to look for.
     * @param class-string $rootClassName The name of the root class of the mapped entity hierarchy.
     *
     * @return object|false Returns the entity with the specified identifier if it exists in
     *                      this UnitOfWork, FALSE otherwise.
     */
    public function tryGetById($id, $rootClassName)
    {
        $idHash = self::getIdHashByIdentifier((array) $id);

        return $this->identityMap[$rootClassName][$idHash] ?? false;
    }

    /**
     * Schedules an entity for dirty-checking at commit-time.
     *
     * @param object $entity The entity to schedule for dirty-checking.
     *
     * @return void
     *
     * @todo Rename: scheduleForSynchronization
     */
    public function scheduleForDirtyCheck($entity)
    {
        $rootClassName = $this->em->getClassMetadata(get_class($entity))->rootEntityName;

        $this->scheduledForSynchronization[$rootClassName][spl_object_id($entity)] = $entity;
    }

    /**
     * Checks whether the UnitOfWork has any pending insertions.
     *
     * @return bool TRUE if this UnitOfWork has pending insertions, FALSE otherwise.
     */
    public function hasPendingInsertions()
    {
        return ! empty($this->entityInsertions);
    }

    /**
     * Calculates the size of the UnitOfWork. The size of the UnitOfWork is the
     * number of entities in the identity map.
     *
     * @return int
     */
    public function size()
    {
        return array_sum(array_map('count', $this->identityMap));
    }

    /**
     * Gets the EntityPersister for an Entity.
     *
     * @param class-string $entityName The name of the Entity.
     *
     * @return EntityPersister
     */
    public function getEntityPersister($entityName)
    {
        if (isset($this->persisters[$entityName])) {
            return $this->persisters[$entityName];
        }

        $class = $this->em->getClassMetadata($entityName);

        switch (true) {
            case $class->isInheritanceTypeNone():
                $persister = new BasicEntityPersister($this->em, $class);
                break;

            case $class->isInheritanceTypeSingleTable():
                $persister = new SingleTablePersister($this->em, $class);
                break;

            case $class->isInheritanceTypeJoined():
                $persister = new JoinedSubclassPersister($this->em, $class);
                break;

            default:
                throw new RuntimeException('No persister found for entity.');
        }

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
     * @phpstan-param AssociationMapping $association
     *
     * @return CollectionPersister
     */
    public function getCollectionPersister(array $association)
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
     * @param object  $entity The entity.
     * @param mixed[] $id     The identifier values.
     * @param mixed[] $data   The original entity data.
     *
     * @return void
     */
    public function registerManaged($entity, array $id, array $data)
    {
        $oid = spl_object_id($entity);

        $this->entityIdentifiers[$oid]  = $id;
        $this->entityStates[$oid]       = self::STATE_MANAGED;
        $this->originalEntityData[$oid] = $data;

        $this->addToIdentityMap($entity);

        if ($entity instanceof NotifyPropertyChanged && ! $this->isUninitializedObject($entity)) {
            $entity->addPropertyChangedListener($this);
        }
    }

    /**
     * INTERNAL:
     * Clears the property changeset of the entity with the given OID.
     *
     * @param int $oid The entity's OID.
     *
     * @return void
     */
    public function clearEntityChangeSet($oid)
    {
        unset($this->entityChangeSets[$oid]);
    }

    /* PropertyChangedListener implementation */

    /**
     * Notifies this UnitOfWork of a property change in an entity.
     *
     * @param object $sender       The entity that owns the property.
     * @param string $propertyName The name of the property that changed.
     * @param mixed  $oldValue     The old value of the property.
     * @param mixed  $newValue     The new value of the property.
     *
     * @return void
     */
    public function propertyChanged($sender, $propertyName, $oldValue, $newValue)
    {
        $oid   = spl_object_id($sender);
        $class = $this->em->getClassMetadata(get_class($sender));

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
     * @phpstan-return array<int, object>
     */
    public function getScheduledEntityInsertions()
    {
        return $this->entityInsertions;
    }

    /**
     * Gets the currently scheduled entity updates in this UnitOfWork.
     *
     * @phpstan-return array<int, object>
     */
    public function getScheduledEntityUpdates()
    {
        return $this->entityUpdates;
    }

    /**
     * Gets the currently scheduled entity deletions in this UnitOfWork.
     *
     * @phpstan-return array<int, object>
     */
    public function getScheduledEntityDeletions()
    {
        return $this->entityDeletions;
    }

    /**
     * Gets the currently scheduled complete collection deletions
     *
     * @phpstan-return array<int, PersistentCollection<array-key, object>>
     */
    public function getScheduledCollectionDeletions()
    {
        return $this->collectionDeletions;
    }

    /**
     * Gets the currently scheduled collection inserts, updates and deletes.
     *
     * @phpstan-return array<int, PersistentCollection<array-key, object>>
     */
    public function getScheduledCollectionUpdates()
    {
        return $this->collectionUpdates;
    }

    /**
     * Helper method to initialize a lazy loading proxy or persistent collection.
     *
     * @param object $obj
     *
     * @return void
     */
    public function initializeObject($obj)
    {
        if ($obj instanceof InternalProxy) {
            $obj->__load();

            return;
        }

        if ($obj instanceof PersistentCollection) {
            $obj->initialize();
        }
    }

    /**
     * Tests if a value is an uninitialized entity.
     *
     * @param mixed $obj
     *
     * @phpstan-assert-if-true InternalProxy $obj
     */
    public function isUninitializedObject($obj): bool
    {
        return $obj instanceof InternalProxy && ! $obj->__isInitialized();
    }

    /**
     * Helper method to show an object as string.
     *
     * @param object $obj
     */
    private static function objToStr($obj): string
    {
        return method_exists($obj, '__toString') ? (string) $obj : get_debug_type($obj) . '@' . spl_object_id($obj);
    }

    /**
     * Marks an entity as read-only so that it will not be considered for updates during UnitOfWork#commit().
     *
     * This operation cannot be undone as some parts of the UnitOfWork now keep gathering information
     * on this object that might be necessary to perform a correct update.
     *
     * @param object $object
     *
     * @return void
     *
     * @throws ORMInvalidArgumentException
     */
    public function markReadOnly($object)
    {
        if (! is_object($object) || ! $this->isInIdentityMap($object)) {
            throw ORMInvalidArgumentException::readOnlyRequiresManagedEntity($object);
        }

        $this->readOnlyObjects[spl_object_id($object)] = true;
    }

    /**
     * Is this entity read only?
     *
     * @param object $object
     *
     * @return bool
     *
     * @throws ORMInvalidArgumentException
     */
    public function isReadOnly($object)
    {
        if (! is_object($object)) {
            throw ORMInvalidArgumentException::readOnlyRequiresManagedEntity($object);
        }

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
     *
     * @param object $entity1
     * @param object $entity2
     */
    private function isIdentifierEquals($entity1, $entity2): bool
    {
        if ($entity1 === $entity2) {
            return true;
        }

        $class = $this->em->getClassMetadata(get_class($entity1));

        if ($class !== $this->em->getClassMetadata(get_class($entity2))) {
            return false;
        }

        $oid1 = spl_object_id($entity1);
        $oid2 = spl_object_id($entity2);

        $id1 = $this->entityIdentifiers[$oid1] ?? $this->identifierFlattener->flattenIdentifier($class, $class->getIdentifierValues($entity1));
        $id2 = $this->entityIdentifiers[$oid2] ?? $this->identifierFlattener->flattenIdentifier($class, $class->getIdentifierValues($entity2));

        return $id1 === $id2 || self::getIdHashByIdentifier($id1) === self::getIdHashByIdentifier($id2);
    }

    /** @throws ORMInvalidArgumentException */
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
     * @param object $entity
     * @param object $managedCopy
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    private function mergeEntityStateIntoManagedCopy($entity, $managedCopy): void
    {
        if ($this->isUninitializedObject($entity)) {
            return;
        }

        $this->initializeObject($managedCopy);

        $class = $this->em->getClassMetadata(get_class($entity));

        foreach ($this->reflectionPropertiesGetter->getProperties($class->name) as $prop) {
            $name = $prop->name;

            if (PHP_VERSION_ID < 80100) {
                $prop->setAccessible(true);
            }

            if (! isset($class->associationMappings[$name])) {
                if (! $class->isIdentifier($name)) {
                    $prop->setValue($managedCopy, $prop->getValue($entity));
                }
            } else {
                $assoc2 = $class->associationMappings[$name];

                if ($assoc2['type'] & ClassMetadata::TO_ONE) {
                    $other = $prop->getValue($entity);
                    if ($other === null) {
                        $prop->setValue($managedCopy, null);
                    } else {
                        if ($this->isUninitializedObject($other)) {
                            // do not merge fields marked lazy that have not been fetched.
                            continue;
                        }

                        if (! $assoc2['isCascadeMerge']) {
                            if ($this->getEntityState($other) === self::STATE_DETACHED) {
                                $targetClass = $this->em->getClassMetadata($assoc2['targetEntity']);
                                $relatedId   = $targetClass->getIdentifierValues($other);

                                $other = $this->tryGetById($relatedId, $targetClass->name);

                                if (! $other) {
                                    if ($targetClass->subClasses) {
                                        $other = $this->em->find($targetClass->name, $relatedId);
                                    } else {
                                        $other = $this->em->getProxyFactory()->getProxy(
                                            $assoc2['targetEntity'],
                                            $relatedId
                                        );
                                        $this->registerManaged($other, $relatedId, []);
                                    }
                                }
                            }

                            $prop->setValue($managedCopy, $other);
                        }
                    }
                } else {
                    $mergeCol = $prop->getValue($entity);

                    if ($mergeCol instanceof PersistentCollection && ! $mergeCol->isInitialized()) {
                        // do not merge fields marked lazy that have not been fetched.
                        // keep the lazy persistent collection of the managed copy.
                        continue;
                    }

                    $managedCol = $prop->getValue($managedCopy);

                    if (! $managedCol) {
                        $managedCol = new PersistentCollection(
                            $this->em,
                            $this->em->getClassMetadata($assoc2['targetEntity']),
                            new ArrayCollection()
                        );
                        $managedCol->setOwner($managedCopy, $assoc2);
                        $prop->setValue($managedCopy, $managedCol);
                    }

                    if ($assoc2['isCascadeMerge']) {
                        $managedCol->initialize();

                        // clear and set dirty a managed collection if its not also the same collection to merge from.
                        if (! $managedCol->isEmpty() && $managedCol !== $mergeCol) {
                            $managedCol->unwrap()->clear();
                            $managedCol->setDirty(true);

                            if (
                                $assoc2['isOwningSide']
                                && $assoc2['type'] === ClassMetadata::MANY_TO_MANY
                                && $class->isChangeTrackingNotify()
                            ) {
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
    }

    /**
     * This method called by hydrators, and indicates that hydrator totally completed current hydration cycle.
     * Unit of work able to fire deferred events, related to loading events here.
     *
     * @internal should be called internally from object hydrators
     *
     * @return void
     */
    public function hydrationComplete()
    {
        $this->hydrationCompleteHandler->hydrationComplete();
    }

    private function clearIdentityMapForEntityName(string $entityName): void
    {
        if (! isset($this->identityMap[$entityName])) {
            return;
        }

        $visited = [];

        foreach ($this->identityMap[$entityName] as $entity) {
            $this->doDetach($entity, $visited, false);
        }
    }

    private function clearEntityInsertionsForEntityName(string $entityName): void
    {
        foreach ($this->entityInsertions as $hash => $entity) {
            // note: performance optimization - `instanceof` is much faster than a function call
            if ($entity instanceof $entityName && get_class($entity) === $entityName) {
                unset($this->entityInsertions[$hash]);
            }
        }
    }

    /**
     * @param mixed $identifierValue
     *
     * @return mixed the identifier after type conversion
     *
     * @throws MappingException if the entity has more than a single identifier.
     */
    private function convertSingleFieldIdentifierToPHPValue(ClassMetadata $class, $identifierValue)
    {
        return $this->em->getConnection()->convertToPHPValue(
            $identifierValue,
            $class->getTypeOfField($class->getSingleIdentifierFieldName())
        );
    }

    /**
     * Given a flat identifier, this method will produce another flat identifier, but with all
     * association fields that are mapped as identifiers replaced by entity references, recursively.
     *
     * @param mixed[] $flatIdentifier
     *
     * @return array<string, mixed>
     */
    private function normalizeIdentifier(ClassMetadata $targetClass, array $flatIdentifier): array
    {
        $normalizedAssociatedId = [];

        foreach ($targetClass->getIdentifierFieldNames() as $name) {
            if (! array_key_exists($name, $flatIdentifier)) {
                continue;
            }

            if (! $targetClass->isSingleValuedAssociation($name)) {
                $normalizedAssociatedId[$name] = $flatIdentifier[$name];
                continue;
            }

            $targetIdMetadata = $this->em->getClassMetadata($targetClass->getAssociationTargetClass($name));

            // Note: the ORM prevents using an entity with a composite identifier as an identifier association
            //       therefore, reset($targetIdMetadata->identifier) is always correct
            $normalizedAssociatedId[$name] = $this->em->getReference(
                $targetIdMetadata->getName(),
                $this->normalizeIdentifier(
                    $targetIdMetadata,
                    [(string) reset($targetIdMetadata->identifier) => $flatIdentifier[$name]]
                )
            );
        }

        return $normalizedAssociatedId;
    }

    /**
     * Assign a post-insert generated ID to an entity
     *
     * This is used by EntityPersisters after they inserted entities into the database.
     * It will place the assigned ID values in the entity's fields and start tracking
     * the entity in the identity map.
     *
     * @param object $entity
     * @param mixed  $generatedId
     */
    final public function assignPostInsertId($entity, $generatedId): void
    {
        $class   = $this->em->getClassMetadata(get_class($entity));
        $idField = $class->getSingleIdentifierFieldName();
        $idValue = $this->convertSingleFieldIdentifierToPHPValue($class, $generatedId);
        $oid     = spl_object_id($entity);

        $class->reflFields[$idField]->setValue($entity, $idValue);

        $this->entityIdentifiers[$oid]            = [$idField => $idValue];
        $this->entityStates[$oid]                 = self::STATE_MANAGED;
        $this->originalEntityData[$oid][$idField] = $idValue;

        $this->addToIdentityMap($entity);
    }
}
