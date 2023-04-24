<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\Hydration;

use BackedEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Query;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Proxy;

use function array_fill_keys;
use function array_keys;
use function count;
use function is_array;
use function key;
use function ltrim;
use function spl_object_id;

/**
 * The ObjectHydrator constructs an object graph out of an SQL result set.
 *
 * Internal note: Highly performance-sensitive code.
 */
class ObjectHydrator extends AbstractHydrator
{
    /** @var mixed[] */
    private $identifierMap = [];

    /** @var mixed[] */
    private $resultPointers = [];

    /** @var mixed[] */
    private $idTemplate = [];

    /** @var int */
    private $resultCounter = 0;

    /** @var mixed[] */
    private $rootAliases = [];

    /** @var mixed[] */
    private $initializedCollections = [];

    /** @var array<string, PersistentCollection> */
    private $uninitializedCollections = [];

    /** @var mixed[] */
    private $existingCollections = [];

    /**
     * {@inheritDoc}
     */
    protected function prepare()
    {
        if (! isset($this->_hints[UnitOfWork::HINT_DEFEREAGERLOAD])) {
            $this->_hints[UnitOfWork::HINT_DEFEREAGERLOAD] = true;
        }

        foreach ($this->resultSetMapping()->aliasMap as $dqlAlias => $className) {
            $this->identifierMap[$dqlAlias] = [];
            $this->idTemplate[$dqlAlias]    = '';

            // Remember which associations are "fetch joined", so that we know where to inject
            // collection stubs or proxies and where not.
            if (! isset($this->resultSetMapping()->relationMap[$dqlAlias])) {
                continue;
            }

            $parent = $this->resultSetMapping()->parentAliasMap[$dqlAlias];

            if (! isset($this->resultSetMapping()->aliasMap[$parent])) {
                throw HydrationException::parentObjectOfRelationNotFound($dqlAlias, $parent);
            }

            $sourceClassName = $this->resultSetMapping()->aliasMap[$parent];
            $sourceClass     = $this->getClassMetadata($sourceClassName);
            $assoc           = $sourceClass->associationMappings[$this->resultSetMapping()->relationMap[$dqlAlias]];

            $this->_hints['fetched'][$parent][$assoc['fieldName']] = true;

            if ($assoc['type'] === ClassMetadata::MANY_TO_MANY) {
                continue;
            }

            // Mark any non-collection opposite sides as fetched, too.
            if ($assoc['mappedBy']) {
                $this->_hints['fetched'][$dqlAlias][$assoc['mappedBy']] = true;

                continue;
            }

            // handle fetch-joined owning side bi-directional one-to-one associations
            if ($assoc['inversedBy']) {
                $class        = $this->getClassMetadata($className);
                $inverseAssoc = $class->associationMappings[$assoc['inversedBy']];

                if (! ($inverseAssoc['type'] & ClassMetadata::TO_ONE)) {
                    continue;
                }

                $this->_hints['fetched'][$dqlAlias][$inverseAssoc['fieldName']] = true;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function cleanup()
    {
        $eagerLoad = isset($this->_hints[UnitOfWork::HINT_DEFEREAGERLOAD]) && $this->_hints[UnitOfWork::HINT_DEFEREAGERLOAD] === true;

        parent::cleanup();

        $this->identifierMap            =
        $this->initializedCollections   =
        $this->uninitializedCollections =
        $this->existingCollections      =
        $this->resultPointers           = [];

        if ($eagerLoad) {
            $this->_uow->triggerEagerLoads();
        }

        $this->_uow->hydrationComplete();
    }

    protected function cleanupAfterRowIteration(): void
    {
        $this->identifierMap            =
        $this->initializedCollections   =
        $this->uninitializedCollections =
        $this->existingCollections      =
        $this->resultPointers           = [];
    }

    /**
     * {@inheritDoc}
     */
    protected function hydrateAllData()
    {
        $result = [];

        while ($row = $this->statement()->fetchAssociative()) {
            $this->hydrateRowData($row, $result);
        }

        // Take snapshots from all newly initialized collections
        foreach ($this->initializedCollections as $coll) {
            $coll->takeSnapshot();
        }

        foreach ($this->uninitializedCollections as $coll) {
            if (! $coll->isInitialized()) {
                $coll->setInitialized(true);
            }
        }

        return $result;
    }

    /**
     * Initializes a related collection.
     *
     * @param object $entity         The entity to which the collection belongs.
     * @param string $fieldName      The name of the field on the entity that holds the collection.
     * @param string $parentDqlAlias Alias of the parent fetch joining this collection.
     */
    private function initRelatedCollection(
        $entity,
        ClassMetadata $class,
        string $fieldName,
        string $parentDqlAlias
    ): PersistentCollection {
        $oid      = spl_object_id($entity);
        $relation = $class->associationMappings[$fieldName];
        $value    = $class->reflFields[$fieldName]->getValue($entity);

        if ($value === null || is_array($value)) {
            $value = new ArrayCollection((array) $value);
        }

        if (! $value instanceof PersistentCollection) {
            $value = new PersistentCollection(
                $this->_em,
                $this->_metadataCache[$relation['targetEntity']],
                $value
            );
            $value->setOwner($entity, $relation);

            $class->reflFields[$fieldName]->setValue($entity, $value);
            $this->_uow->setOriginalEntityProperty($oid, $fieldName, $value);

            $this->initializedCollections[$oid . $fieldName] = $value;
        } elseif (
            isset($this->_hints[Query::HINT_REFRESH]) ||
            isset($this->_hints['fetched'][$parentDqlAlias][$fieldName]) &&
             ! $value->isInitialized()
        ) {
            // Is already PersistentCollection, but either REFRESH or FETCH-JOIN and UNINITIALIZED!
            $value->setDirty(false);
            $value->setInitialized(true);
            $value->unwrap()->clear();

            $this->initializedCollections[$oid . $fieldName] = $value;
        } else {
            // Is already PersistentCollection, and DON'T REFRESH or FETCH-JOIN!
            $this->existingCollections[$oid . $fieldName] = $value;
        }

        return $value;
    }

    /**
     * Gets an entity instance.
     *
     * @param string $dqlAlias The DQL alias of the entity's class.
     * @psalm-param array<string, mixed> $data     The instance data.
     *
     * @return object
     *
     * @throws HydrationException
     */
    private function getEntity(array $data, string $dqlAlias)
    {
        $className = $this->resultSetMapping()->aliasMap[$dqlAlias];

        if (isset($this->resultSetMapping()->discriminatorColumns[$dqlAlias])) {
            $fieldName = $this->resultSetMapping()->discriminatorColumns[$dqlAlias];

            if (! isset($this->resultSetMapping()->metaMappings[$fieldName])) {
                throw HydrationException::missingDiscriminatorMetaMappingColumn($className, $fieldName, $dqlAlias);
            }

            $discrColumn = $this->resultSetMapping()->metaMappings[$fieldName];

            if (! isset($data[$discrColumn])) {
                throw HydrationException::missingDiscriminatorColumn($className, $discrColumn, $dqlAlias);
            }

            if ($data[$discrColumn] === '') {
                throw HydrationException::emptyDiscriminatorValue($dqlAlias);
            }

            $discrMap           = $this->_metadataCache[$className]->discriminatorMap;
            $discriminatorValue = $data[$discrColumn];
            if ($discriminatorValue instanceof BackedEnum) {
                $discriminatorValue = $discriminatorValue->value;
            }

            $discriminatorValue = (string) $discriminatorValue;

            if (! isset($discrMap[$discriminatorValue])) {
                throw HydrationException::invalidDiscriminatorValue($discriminatorValue, array_keys($discrMap));
            }

            $className = $discrMap[$discriminatorValue];

            unset($data[$discrColumn]);
        }

        if (isset($this->_hints[Query::HINT_REFRESH_ENTITY], $this->rootAliases[$dqlAlias])) {
            $this->registerManaged($this->_metadataCache[$className], $this->_hints[Query::HINT_REFRESH_ENTITY], $data);
        }

        $this->_hints['fetchAlias'] = $dqlAlias;

        return $this->_uow->createEntity($className, $data, $this->_hints);
    }

    /**
     * @psalm-param class-string $className
     * @psalm-param array<string, mixed> $data
     *
     * @return mixed
     */
    private function getEntityFromIdentityMap(string $className, array $data)
    {
        // TODO: Abstract this code and UnitOfWork::createEntity() equivalent?
        $class = $this->_metadataCache[$className];

        if ($class->isIdentifierComposite) {
            $idHash = '';

            foreach ($class->identifier as $fieldName) {
                $idHash .= ' ' . (isset($class->associationMappings[$fieldName])
                    ? $data[$class->associationMappings[$fieldName]['joinColumns'][0]['name']]
                    : $data[$fieldName]);
            }

            return $this->_uow->tryGetByIdHash(ltrim($idHash), $class->rootEntityName);
        } elseif (isset($class->associationMappings[$class->identifier[0]])) {
            return $this->_uow->tryGetByIdHash($data[$class->associationMappings[$class->identifier[0]]['joinColumns'][0]['name']], $class->rootEntityName);
        }

        return $this->_uow->tryGetByIdHash($data[$class->identifier[0]], $class->rootEntityName);
    }

    /**
     * Hydrates a single row in an SQL result set.
     *
     * @internal
     * First, the data of the row is split into chunks where each chunk contains data
     * that belongs to a particular component/class. Afterwards, all these chunks
     * are processed, one after the other. For each chunk of class data only one of the
     * following code paths is executed:
     *
     * Path A: The data chunk belongs to a joined/associated object and the association
     *         is collection-valued.
     * Path B: The data chunk belongs to a joined/associated object and the association
     *         is single-valued.
     * Path C: The data chunk belongs to a root result element/object that appears in the topmost
     *         level of the hydrated result. A typical example are the objects of the type
     *         specified by the FROM clause in a DQL query.
     *
     * @param mixed[] $row    The data of the row to process.
     * @param mixed[] $result The result array to fill.
     *
     * @return void
     */
    protected function hydrateRowData(array $row, array &$result)
    {
        // Initialize
        $id                 = $this->idTemplate; // initialize the id-memory
        $nonemptyComponents = [];
        // Split the row data into chunks of class data.
        $rowData = $this->gatherRowData($row, $id, $nonemptyComponents);

        // reset result pointers for each data row
        $this->resultPointers = [];

        // Hydrate the data chunks
        foreach ($rowData['data'] as $dqlAlias => $data) {
            $entityName = $this->resultSetMapping()->aliasMap[$dqlAlias];

            if (isset($this->resultSetMapping()->parentAliasMap[$dqlAlias])) {
                // It's a joined result

                $parentAlias = $this->resultSetMapping()->parentAliasMap[$dqlAlias];
                // we need the $path to save into the identifier map which entities were already
                // seen for this parent-child relationship
                $path = $parentAlias . '.' . $dqlAlias;

                // We have a RIGHT JOIN result here. Doctrine cannot hydrate RIGHT JOIN Object-Graphs
                if (! isset($nonemptyComponents[$parentAlias])) {
                    // TODO: Add special case code where we hydrate the right join objects into identity map at least
                    continue;
                }

                $parentClass   = $this->_metadataCache[$this->resultSetMapping()->aliasMap[$parentAlias]];
                $relationField = $this->resultSetMapping()->relationMap[$dqlAlias];
                $relation      = $parentClass->associationMappings[$relationField];
                $reflField     = $parentClass->reflFields[$relationField];

                // Get a reference to the parent object to which the joined element belongs.
                if ($this->resultSetMapping()->isMixed && isset($this->rootAliases[$parentAlias])) {
                    $objectClass  = $this->resultPointers[$parentAlias];
                    $parentObject = $objectClass[key($objectClass)];
                } elseif (isset($this->resultPointers[$parentAlias])) {
                    $parentObject = $this->resultPointers[$parentAlias];
                } else {
                    // Parent object of relation not found, mark as not-fetched again
                    $element = $this->getEntity($data, $dqlAlias);

                    // Update result pointer and provide initial fetch data for parent
                    $this->resultPointers[$dqlAlias]               = $element;
                    $rowData['data'][$parentAlias][$relationField] = $element;

                    // Mark as not-fetched again
                    unset($this->_hints['fetched'][$parentAlias][$relationField]);
                    continue;
                }

                $oid = spl_object_id($parentObject);

                // Check the type of the relation (many or single-valued)
                if (! ($relation['type'] & ClassMetadata::TO_ONE)) {
                    // PATH A: Collection-valued association
                    $reflFieldValue = $reflField->getValue($parentObject);

                    if (isset($nonemptyComponents[$dqlAlias])) {
                        $collKey = $oid . $relationField;
                        if (isset($this->initializedCollections[$collKey])) {
                            $reflFieldValue = $this->initializedCollections[$collKey];
                        } elseif (! isset($this->existingCollections[$collKey])) {
                            $reflFieldValue = $this->initRelatedCollection($parentObject, $parentClass, $relationField, $parentAlias);
                        }

                        $indexExists  = isset($this->identifierMap[$path][$id[$parentAlias]][$id[$dqlAlias]]);
                        $index        = $indexExists ? $this->identifierMap[$path][$id[$parentAlias]][$id[$dqlAlias]] : false;
                        $indexIsValid = $index !== false ? isset($reflFieldValue[$index]) : false;

                        if (! $indexExists || ! $indexIsValid) {
                            if (isset($this->existingCollections[$collKey])) {
                                // Collection exists, only look for the element in the identity map.
                                $element = $this->getEntityFromIdentityMap($entityName, $data);
                                if ($element) {
                                    $this->resultPointers[$dqlAlias] = $element;
                                } else {
                                    unset($this->resultPointers[$dqlAlias]);
                                }
                            } else {
                                $element = $this->getEntity($data, $dqlAlias);

                                if (isset($this->resultSetMapping()->indexByMap[$dqlAlias])) {
                                    $indexValue = $row[$this->resultSetMapping()->indexByMap[$dqlAlias]];
                                    $reflFieldValue->hydrateSet($indexValue, $element);
                                    $this->identifierMap[$path][$id[$parentAlias]][$id[$dqlAlias]] = $indexValue;
                                } else {
                                    if (! $reflFieldValue->contains($element)) {
                                        $reflFieldValue->hydrateAdd($element);
                                        $reflFieldValue->last();
                                    }

                                    $this->identifierMap[$path][$id[$parentAlias]][$id[$dqlAlias]] = $reflFieldValue->key();
                                }

                                // Update result pointer
                                $this->resultPointers[$dqlAlias] = $element;
                            }
                        } else {
                            // Update result pointer
                            $this->resultPointers[$dqlAlias] = $reflFieldValue[$index];
                        }
                    } elseif (! $reflFieldValue) {
                        $this->initRelatedCollection($parentObject, $parentClass, $relationField, $parentAlias);
                    } elseif ($reflFieldValue instanceof PersistentCollection && $reflFieldValue->isInitialized() === false && ! isset($this->uninitializedCollections[$oid . $relationField])) {
                        $this->uninitializedCollections[$oid . $relationField] = $reflFieldValue;
                    }
                } else {
                    // PATH B: Single-valued association
                    $reflFieldValue = $reflField->getValue($parentObject);

                    if (! $reflFieldValue || isset($this->_hints[Query::HINT_REFRESH]) || ($reflFieldValue instanceof Proxy && ! $reflFieldValue->__isInitialized())) {
                        // we only need to take action if this value is null,
                        // we refresh the entity or its an uninitialized proxy.
                        if (isset($nonemptyComponents[$dqlAlias])) {
                            $element = $this->getEntity($data, $dqlAlias);
                            $reflField->setValue($parentObject, $element);
                            $this->_uow->setOriginalEntityProperty($oid, $relationField, $element);
                            $targetClass = $this->_metadataCache[$relation['targetEntity']];

                            if ($relation['isOwningSide']) {
                                // TODO: Just check hints['fetched'] here?
                                // If there is an inverse mapping on the target class its bidirectional
                                if ($relation['inversedBy']) {
                                    $inverseAssoc = $targetClass->associationMappings[$relation['inversedBy']];
                                    if ($inverseAssoc['type'] & ClassMetadata::TO_ONE) {
                                        $targetClass->reflFields[$inverseAssoc['fieldName']]->setValue($element, $parentObject);
                                        $this->_uow->setOriginalEntityProperty(spl_object_id($element), $inverseAssoc['fieldName'], $parentObject);
                                    }
                                } elseif ($parentClass === $targetClass && $relation['mappedBy']) {
                                    // Special case: bi-directional self-referencing one-one on the same class
                                    $targetClass->reflFields[$relationField]->setValue($element, $parentObject);
                                }
                            } else {
                                // For sure bidirectional, as there is no inverse side in unidirectional mappings
                                $targetClass->reflFields[$relation['mappedBy']]->setValue($element, $parentObject);
                                $this->_uow->setOriginalEntityProperty(spl_object_id($element), $relation['mappedBy'], $parentObject);
                            }

                            // Update result pointer
                            $this->resultPointers[$dqlAlias] = $element;
                        } else {
                            $this->_uow->setOriginalEntityProperty($oid, $relationField, null);
                            $reflField->setValue($parentObject, null);
                        }
                        // else leave $reflFieldValue null for single-valued associations
                    } else {
                        // Update result pointer
                        $this->resultPointers[$dqlAlias] = $reflFieldValue;
                    }
                }
            } else {
                // PATH C: Its a root result element
                $this->rootAliases[$dqlAlias] = true; // Mark as root alias
                $entityKey                    = $this->resultSetMapping()->entityMappings[$dqlAlias] ?: 0;

                // if this row has a NULL value for the root result id then make it a null result.
                if (! isset($nonemptyComponents[$dqlAlias])) {
                    if ($this->resultSetMapping()->isMixed) {
                        $result[] = [$entityKey => null];
                    } else {
                        $result[] = null;
                    }

                    $resultKey = $this->resultCounter;
                    ++$this->resultCounter;
                    continue;
                }

                // check for existing result from the iterations before
                if (! isset($this->identifierMap[$dqlAlias][$id[$dqlAlias]])) {
                    $element = $this->getEntity($data, $dqlAlias);

                    if ($this->resultSetMapping()->isMixed) {
                        $element = [$entityKey => $element];
                    }

                    if (isset($this->resultSetMapping()->indexByMap[$dqlAlias])) {
                        $resultKey = $row[$this->resultSetMapping()->indexByMap[$dqlAlias]];

                        if (isset($this->_hints['collection'])) {
                            $this->_hints['collection']->hydrateSet($resultKey, $element);
                        }

                        $result[$resultKey] = $element;
                    } else {
                        $resultKey = $this->resultCounter;
                        ++$this->resultCounter;

                        if (isset($this->_hints['collection'])) {
                            $this->_hints['collection']->hydrateAdd($element);
                        }

                        $result[] = $element;
                    }

                    $this->identifierMap[$dqlAlias][$id[$dqlAlias]] = $resultKey;

                    // Update result pointer
                    $this->resultPointers[$dqlAlias] = $element;
                } else {
                    // Update result pointer
                    $index                           = $this->identifierMap[$dqlAlias][$id[$dqlAlias]];
                    $this->resultPointers[$dqlAlias] = $result[$index];
                    $resultKey                       = $index;
                }
            }

            if (isset($this->_hints[Query::HINT_INTERNAL_ITERATION]) && $this->_hints[Query::HINT_INTERNAL_ITERATION]) {
                $this->_uow->hydrationComplete();
            }
        }

        if (! isset($resultKey)) {
            $this->resultCounter++;
        }

        // Append scalar values to mixed result sets
        if (isset($rowData['scalars'])) {
            if (! isset($resultKey)) {
                $resultKey = isset($this->resultSetMapping()->indexByMap['scalars'])
                    ? $row[$this->resultSetMapping()->indexByMap['scalars']]
                    : $this->resultCounter - 1;
            }

            foreach ($rowData['scalars'] as $name => $value) {
                $result[$resultKey][$name] = $value;
            }
        }

        // Append new object to mixed result sets
        if (isset($rowData['newObjects'])) {
            if (! isset($resultKey)) {
                $resultKey = $this->resultCounter - 1;
            }

            $scalarCount = (isset($rowData['scalars']) ? count($rowData['scalars']) : 0);

            foreach ($rowData['newObjects'] as $objIndex => $newObject) {
                $class = $newObject['class'];
                $args  = $newObject['args'];
                $obj   = $class->newInstanceArgs($args);

                if ($scalarCount === 0 && count($rowData['newObjects']) === 1) {
                    $result[$resultKey] = $obj;

                    continue;
                }

                $result[$resultKey][$objIndex] = $obj;
            }
        }
    }

    /**
     * When executed in a hydrate() loop we may have to clear internal state to
     * decrease memory consumption.
     *
     * @param mixed $eventArgs
     *
     * @return void
     */
    public function onClear($eventArgs)
    {
        parent::onClear($eventArgs);

        $aliases = array_keys($this->identifierMap);

        $this->identifierMap = array_fill_keys($aliases, []);
    }
}
