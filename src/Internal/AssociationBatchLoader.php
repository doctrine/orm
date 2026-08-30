<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\JoinColumnMapping;
use Doctrine\ORM\Mapping\ManyToManyAssociationMapping;
use Doctrine\ORM\Mapping\ToManyAssociationMapping;
use Doctrine\ORM\Mapping\ToManyInverseSideMapping;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Utility\IdentifierFlattener;
use Doctrine\ORM\Utility\PersisterHelper;

use function array_chunk;
use function array_combine;
use function array_values;
use function assert;
use function count;
use function implode;
use function max;
use function reset;

/**
 * Loads associations for many entities at once instead of one query per entity.
 *
 * This is the machinery behind the eager fetch modes (see
 * {@see UnitOfWork::triggerEagerLoads()}), which collect associations while
 * hydrating and load them in batches once hydration completes.
 *
 * @internal
 */
final class AssociationBatchLoader
{
    private readonly IdentifierFlattener $identifierFlattener;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UnitOfWork $uow,
    ) {
        $this->identifierFlattener = new IdentifierFlattener($uow, $em->getMetadataFactory());
    }

    /**
     * Whether this loader can load the given to-many association in batches.
     *
     * Composite keys are the limitation: neither the identifier list of a batch
     * nor the join table lookup of a many-to-many can be expressed as a single
     * IN () condition when more than one column is involved.
     */
    public function canBatchLoad(AssociationMapping&ToManyAssociationMapping $mapping): bool
    {
        if ($mapping->isIndexed()) {
            // A batch distributes rows to collections in PHP, so it can only
            // index by something readable from the target entity. indexBy is
            // also allowed to name a column - a join column of a to-one
            // association, say - which only the SQL result set can resolve.
            $indexBy = $mapping->indexBy();

            if (! isset($this->em->getClassMetadata($mapping->targetEntity)->fieldMappings[$indexBy])) {
                return false;
            }
        }

        if ($mapping->isManyToMany()) {
            $joinColumns = $this->manyToManyJoinColumns($mapping);

            return $joinColumns !== null
                && ! $this->em->getClassMetadata($mapping->targetEntity)->isIdentifierComposite;
        }

        assert($mapping instanceof ToManyInverseSideMapping);
        $targetClass = $this->em->getClassMetadata($mapping->targetEntity);

        // A composite foreign key on the owning side cannot be filtered with IN ().
        return ! $targetClass->hasAssociation($mapping->mappedBy)
            || count($targetClass->getAssociationMapping($mapping->mappedBy)->joinColumns) === 1;
    }

    /**
     * Loads uninitialized entities of one class by their identifiers.
     *
     * Hydration fills the entities that are already in the identity map, so the
     * references handed out earlier are initialized by this.
     *
     * @param class-string $entityName
     * @param array<mixed> $ids        Single-column identifier values.
     */
    public function initializeEntities(string $entityName, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $class     = $this->em->getClassMetadata($entityName);
        $persister = $this->uow->getEntityPersister($entityName);

        foreach (array_chunk(array_values($ids), $this->batchSize()) as $batch) {
            $persister->loadAll(array_combine($class->identifier, [$batch]));
        }
    }

    /**
     * Loads the given collections, which all belong to the same association.
     *
     * @param array<string, PersistentCollection<array-key, object>> $collections Keyed by owner identifier hash.
     */
    public function loadCollections(array $collections, AssociationMapping&ToManyAssociationMapping $mapping): void
    {
        if ($collections === []) {
            return;
        }

        if ($mapping->isManyToMany()) {
            $this->loadManyToManyCollections($collections, $mapping);
        } else {
            assert($mapping instanceof ToManyInverseSideMapping);
            $this->loadOneToManyCollections($collections, $mapping);
        }

        foreach ($collections as $collection) {
            $collection->setInitialized(true);
            $collection->takeSnapshot();
        }
    }

    /** @param array<string, PersistentCollection<array-key, object>> $collections */
    private function loadOneToManyCollections(array $collections, AssociationMapping&ToManyInverseSideMapping $mapping): void
    {
        $targetEntity = $mapping->targetEntity;
        $class        = $this->em->getClassMetadata($mapping->sourceEntity);
        $mappedBy     = $mapping->mappedBy;

        $batches = array_chunk($collections, $this->batchSize(), true);

        foreach ($batches as $collectionBatch) {
            $entities = [];

            foreach ($collectionBatch as $collection) {
                $entities[] = $collection->getOwner();
            }

            $found = $this->uow->getEntityPersister($targetEntity)->loadAll([$mappedBy => $entities], $mapping->orderBy);

            $targetClass    = $this->em->getClassMetadata($targetEntity);
            $targetProperty = $targetClass->getPropertyAccessor($mappedBy);
            assert($targetProperty !== null);

            foreach ($found as $targetValue) {
                $sourceEntity = $targetProperty->getValue($targetValue);

                if ($sourceEntity === null && isset($targetClass->associationMappings[$mappedBy]->joinColumns)) {
                    // case where the hydration $targetValue itself has not yet fully completed, for example
                    // in case a bi-directional association is being hydrated and deferring eager loading is
                    // not possible due to subclassing.
                    $data = $this->uow->getOriginalEntityData($targetValue);
                    $id   = [];
                    foreach ($targetClass->associationMappings[$mappedBy]->joinColumns as $joinColumn) {
                        $id[] = $data[$joinColumn->name];
                    }
                } else {
                    $id = $this->identifierFlattener->flattenIdentifier($class, $class->getIdentifierValues($sourceEntity));
                }

                $idHash = implode(' ', $id);

                if (! isset($collectionBatch[$idHash])) {
                    continue;
                }

                $this->addToCollection($collectionBatch[$idHash], $targetValue, $mapping, $targetClass);
            }
        }
    }

    /**
     * Loads many-to-many collections in two steps: the rows of the join table
     * for all owners at once, then the targets those rows point to.
     *
     * @param array<string, PersistentCollection<array-key, object>> $collections
     */
    private function loadManyToManyCollections(array $collections, AssociationMapping&ManyToManyAssociationMapping $mapping): void
    {
        $joinColumns = $this->manyToManyJoinColumns($mapping);
        assert($joinColumns !== null);

        [$sourceJoinColumn, $targetJoinColumn] = $joinColumns;

        $sourceClass    = $this->em->getClassMetadata($mapping->sourceEntity);
        $targetClass    = $this->em->getClassMetadata($mapping->targetEntity);
        $owningMapping  = $this->em->getMetadataFactory()->getOwningSide($mapping);
        $joinTableOwner = $this->em->getClassMetadata($owningMapping->sourceEntity);

        $platform      = $this->em->getConnection()->getDatabasePlatform();
        $quoteStrategy = $this->em->getConfiguration()->getQuoteStrategy();

        $joinTable       = $quoteStrategy->getJoinTableName($owningMapping, $joinTableOwner, $platform);
        $sourceColumn    = $quoteStrategy->getJoinColumnName($sourceJoinColumn, $joinTableOwner, $platform);
        $targetColumn    = $quoteStrategy->getJoinColumnName($targetJoinColumn, $joinTableOwner, $platform);
        $sourceIdField   = $sourceClass->fieldNames[$sourceJoinColumn->referencedColumnName];
        $targetIdField   = $targetClass->fieldNames[$targetJoinColumn->referencedColumnName];
        $targetFieldType = Type::getType($targetClass->fieldMappings[$targetIdField]->type);

        foreach (array_chunk($collections, $this->batchSize(), true) as $collectionBatch) {
            $ownerKeys = [];

            foreach ($collectionBatch as $idHash => $collection) {
                $owner = $collection->getOwner();
                assert($owner !== null);
                $ownerKeys[$this->databaseKey($sourceClass, $sourceIdField, $owner)] = $idHash;
            }

            $rows = $this->fetchJoinTableRows(
                $joinTable,
                $sourceColumn,
                $targetColumn,
                $sourceIdField,
                $sourceClass,
                $collectionBatch,
            );

            if ($rows === []) {
                continue;
            }

            // Which owners reference which target.
            $ownersByTargetKey = [];
            $targetIds         = [];

            foreach ($rows as $row) {
                $targetKey = (string) $row['doctrine_target'];

                if (! isset($targetIds[$targetKey])) {
                    $targetIds[$targetKey] = $targetFieldType->convertToPHPValue($row['doctrine_target'], $platform);
                }

                $ownerKey = (string) $row['doctrine_source'];

                if (! isset($ownerKeys[$ownerKey])) {
                    continue;
                }

                $ownersByTargetKey[$targetKey][] = $ownerKeys[$ownerKey];
            }

            // Loading the targets ordered means the collections end up ordered,
            // because the relative order of the targets is preserved below.
            $targets = $this->uow->getEntityPersister($mapping->targetEntity)->loadAll(
                [$targetIdField => array_values($targetIds)],
                $mapping->orderBy(),
            );

            foreach ($targets as $target) {
                $targetKey = $this->databaseKey($targetClass, $targetIdField, $target);

                foreach ($ownersByTargetKey[$targetKey] ?? [] as $idHash) {
                    $this->addToCollection($collectionBatch[$idHash], $target, $mapping, $targetClass);
                }
            }
        }
    }

    /**
     * @param ClassMetadata<object>                                  $sourceClass
     * @param array<string, PersistentCollection<array-key, object>> $collections
     *
     * @return list<array<string, mixed>>
     */
    private function fetchJoinTableRows(
        string $joinTable,
        string $sourceColumn,
        string $targetColumn,
        string $sourceIdField,
        ClassMetadata $sourceClass,
        array $collections,
    ): array {
        $identifiers = [];

        foreach ($collections as $collection) {
            $owner = $collection->getOwner();
            assert($owner !== null);
            $identifiers[] = $sourceClass->getPropertyAccessor($sourceIdField)?->getValue($owner);
        }

        $sql = 'SELECT ' . $sourceColumn . ' AS doctrine_source, ' . $targetColumn . ' AS doctrine_target'
            . ' FROM ' . $joinTable
            . ' WHERE ' . $sourceColumn . ' IN (?)';

        return $this->em->getConnection()->executeQuery(
            $sql,
            PersisterHelper::convertToParameterValue($identifiers, $this->em),
            PersisterHelper::inferParameterTypes($sourceIdField, $identifiers, $sourceClass, $this->em),
        )->fetchAllAssociative();
    }

    /**
     * The pair of single join columns of a many-to-many association, pointing at
     * the source and at the target, or null when either side is composite.
     *
     * @return array{JoinColumnMapping, JoinColumnMapping}|null
     */
    private function manyToManyJoinColumns(AssociationMapping&ToManyAssociationMapping $mapping): array|null
    {
        assert($mapping instanceof ManyToManyAssociationMapping);
        $owningMapping = $this->em->getMetadataFactory()->getOwningSide($mapping);

        $sourceColumns = $mapping->isOwningSide()
            ? $owningMapping->joinTable->joinColumns
            : $owningMapping->joinTable->inverseJoinColumns;
        $targetColumns = $mapping->isOwningSide()
            ? $owningMapping->joinTable->inverseJoinColumns
            : $owningMapping->joinTable->joinColumns;

        if (count($sourceColumns) !== 1 || count($targetColumns) !== 1) {
            return null;
        }

        return [reset($sourceColumns), reset($targetColumns)];
    }

    /**
     * @param PersistentCollection<array-key, object> $collection
     * @param ClassMetadata<object>                   $targetClass
     */
    private function addToCollection(
        PersistentCollection $collection,
        object $target,
        ToManyAssociationMapping $mapping,
        ClassMetadata $targetClass,
    ): void {
        if (! $mapping->isIndexed()) {
            $collection->add($target);

            return;
        }

        $indexByProperty = $targetClass->getPropertyAccessor($mapping->indexBy());
        assert($indexByProperty !== null);

        $collection->hydrateSet($indexByProperty->getValue($target), $target);
    }

    /**
     * The identifier hash of an entity, the key collections are grouped by.
     *
     * @param ClassMetadata<object> $class
     */
    public function identifierHash(ClassMetadata $class, object $entity): string
    {
        return implode(' ', $this->identifierFlattener->flattenIdentifier(
            $class,
            $class->getIdentifierValues($entity),
        ));
    }

    /**
     * The identifier of an entity as the database sees it, so that it can be
     * compared with a raw value read from a join table.
     *
     * @param ClassMetadata<object> $class
     */
    private function databaseKey(ClassMetadata $class, string $idField, object $entity): string
    {
        $value = $class->getPropertyAccessor($idField)?->getValue($entity);
        $type  = Type::getType($class->fieldMappings[$idField]->type);

        return (string) $type->convertToDatabaseValue($value, $this->em->getConnection()->getDatabasePlatform());
    }

    /** @return positive-int */
    private function batchSize(): int
    {
        return max(1, $this->em->getConfiguration()->getEagerFetchBatchSize());
    }
}
