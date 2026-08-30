<?php

declare(strict_types=1);

namespace Doctrine\ORM;

use Doctrine\ORM\Internal\AssociationBatchLoader;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ToManyAssociationMapping;

use function array_values;
use function assert;
use function explode;
use function spl_object_id;

/**
 * Loads associations of entities that are already in memory, in as few queries
 * as possible.
 *
 * Fetch joins and the eager fetch modes only help while a query is being
 * hydrated. This is for everything else: entities that came from a repository
 * method, a paginator, the second level cache, or a previous preload.
 *
 *     $users = $repository->findAll();
 *     $entityManager->preload($users, ['articles.comments', 'address']);
 *
 * A preload always loads the whole association, because it marks collections as
 * initialized. Use {@see PersistentCollection::matching()} when a filtered
 * subset is what you need.
 */
final class Preloader
{
    private readonly AssociationBatchLoader $batchLoader;
    private readonly UnitOfWork $uow;

    public function __construct(private readonly EntityManagerInterface $em)
    {
        $this->uow         = $em->getUnitOfWork();
        $this->batchLoader = new AssociationBatchLoader($em, $this->uow);
    }

    /**
     * Loads the given association paths for all given entities.
     *
     * A path may walk several associations: 'articles.comments' loads the
     * articles of every entity, then the comments of every article that was
     * loaded. Passing no path at all initializes the entities themselves, which
     * is useful for a list of references.
     *
     * @param iterable<object> $entities
     * @param list<string>     $paths
     */
    public function preload(iterable $entities, array $paths = []): void
    {
        $entities = $this->managedEntities($entities);

        if ($entities === []) {
            return;
        }

        // Preloading is an explicit fetch, so it is never a strict loading violation.
        $this->em->getConfiguration()->getStrictLoading()->allow(function () use ($entities, $paths): void {
            $this->initializeEntities($entities);

            foreach ($paths as $path) {
                $current = $entities;

                foreach (explode('.', $path) as $fieldName) {
                    if ($current === []) {
                        break;
                    }

                    $current = $this->loadAssociation($current, $fieldName, $path);
                }
            }
        });
    }

    /**
     * Loads one association for the given entities and returns the entities on
     * the other side of it.
     *
     * @param iterable<object> $entities
     *
     * @return list<object>
     */
    public function preloadAssociation(iterable $entities, string $fieldName): array
    {
        $entities = $this->managedEntities($entities);

        if ($entities === []) {
            return [];
        }

        return $this->em->getConfiguration()->getStrictLoading()->allow(function () use ($entities, $fieldName): array {
            $this->initializeEntities($entities);

            return $this->loadAssociation($entities, $fieldName, $fieldName);
        });
    }

    /**
     * @param list<object> $entities
     *
     * @return list<object>
     */
    private function loadAssociation(array $entities, string $fieldName, string $path): array
    {
        $targets = [];

        foreach ($this->groupByAssociation($entities, $fieldName, $path) as [$mapping, $group]) {
            foreach ($this->loadGroup($group, $mapping) as $target) {
                $targets[spl_object_id($target)] = $target;
            }
        }

        return array_values($targets);
    }

    /**
     * Entities of different classes - and entities of one class hierarchy - can
     * share an association, but they have to be loaded per mapping.
     *
     * @param list<object> $entities
     *
     * @return list<array{AssociationMapping, list<object>}>
     */
    private function groupByAssociation(array $entities, string $fieldName, string $path): array
    {
        $groups   = [];
        $mappings = [];

        foreach ($entities as $entity) {
            $class = $this->em->getClassMetadata($entity::class);

            if (! isset($class->associationMappings[$fieldName])) {
                throw ORMInvalidArgumentException::preloadOfUnknownAssociation($class->name, $fieldName, $path);
            }

            $mapping = $class->associationMappings[$fieldName];
            $key     = $mapping->sourceEntity . '#' . $fieldName;

            $mappings[$key] = $mapping;
            $groups[$key][] = $entity;
        }

        $result = [];

        foreach ($groups as $key => $group) {
            $result[] = [$mappings[$key], $group];
        }

        return $result;
    }

    /**
     * @param list<object> $entities
     *
     * @return list<object>
     */
    private function loadGroup(array $entities, AssociationMapping $mapping): array
    {
        if ($mapping->isToMany()) {
            return $this->loadCollections($entities, $mapping);
        }

        if (! $mapping->isOwningSide()) {
            return $this->walkInverseToOne($entities, $mapping);
        }

        return $this->loadToOne($entities, $mapping);
    }

    /**
     * @param list<object> $entities
     *
     * @return list<object>
     */
    private function loadToOne(array $entities, AssociationMapping $mapping): array
    {
        $accessor   = $this->em->getClassMetadata($mapping->sourceEntity)->getPropertyAccessor($mapping->fieldName);
        $targets    = [];
        $idsByClass = [];
        $fallbacks  = [];

        foreach ($entities as $entity) {
            $target = $accessor?->getValue($entity);

            if ($target === null) {
                continue;
            }

            $targets[spl_object_id($target)] = $target;

            if (! $this->uow->isUninitializedObject($target)) {
                continue;
            }

            $targetClass = $this->em->getClassMetadata($target::class);

            if ($targetClass->isIdentifierComposite) {
                // A composite identifier cannot be expressed as one IN () condition.
                $fallbacks[] = $target;

                continue;
            }

            $identifier = $this->uow->getEntityIdentifier($target);

            $idsByClass[$targetClass->name][] = $identifier[$targetClass->identifier[0]];
        }

        foreach ($idsByClass as $className => $ids) {
            $this->batchLoader->initializeEntities($className, $ids);
        }

        foreach ($fallbacks as $target) {
            $this->uow->initializeObject($target);
        }

        return array_values($targets);
    }

    /**
     * The inverse side of a to-one association is never lazy: the ORM loads it
     * while hydrating the owner, with one query per owner (see
     * {@see UnitOfWork::createEntity()}). There is consequently nothing to
     * preload - a null value means there is no row, not that it was not loaded -
     * so this only walks to the other side, for paths that continue there.
     *
     * @param list<object> $entities
     *
     * @return list<object>
     */
    private function walkInverseToOne(array $entities, AssociationMapping $mapping): array
    {
        $accessor = $this->em->getClassMetadata($mapping->sourceEntity)->getPropertyAccessor($mapping->fieldName);
        $targets  = [];

        foreach ($entities as $entity) {
            $target = $accessor?->getValue($entity);

            if ($target !== null) {
                $targets[spl_object_id($target)] = $target;
            }
        }

        return array_values($targets);
    }

    /**
     * @param list<object> $entities
     *
     * @return list<object>
     */
    private function loadCollections(array $entities, AssociationMapping $mapping): array
    {
        assert($mapping instanceof ToManyAssociationMapping);

        $class       = $this->em->getClassMetadata($mapping->sourceEntity);
        $accessor    = $class->getPropertyAccessor($mapping->fieldName);
        $collections = [];
        $loaded      = [];
        $canBatch    = $this->batchLoader->canBatchLoad($mapping);

        foreach ($entities as $entity) {
            $collection = $accessor?->getValue($entity);

            if (! $collection instanceof PersistentCollection) {
                continue;
            }

            $loaded[] = $collection;

            if ($collection->isInitialized()) {
                continue;
            }

            if ($collection->isDirty() || ! $canBatch) {
                // Batch loading a dirty collection would take a snapshot that
                // contradicts the pending changes, and an association with a
                // composite key cannot be batched at all.
                $collection->initialize();

                continue;
            }

            $collections[$this->batchLoader->identifierHash($class, $entity)] = $collection;
        }

        $this->batchLoader->loadCollections($collections, $mapping);

        $targets = [];

        foreach ($loaded as $collection) {
            foreach ($collection as $target) {
                $targets[spl_object_id($target)] = $target;
            }
        }

        return array_values($targets);
    }

    /**
     * Uninitialized entities have to be loaded before their associations can be
     * read - and loading them one by one is the very thing to avoid here.
     *
     * @param list<object> $entities
     */
    private function initializeEntities(array $entities): void
    {
        $idsByClass = [];
        $fallbacks  = [];

        foreach ($entities as $entity) {
            if (! $this->uow->isUninitializedObject($entity)) {
                continue;
            }

            $class = $this->em->getClassMetadata($entity::class);

            if ($class->isIdentifierComposite) {
                $fallbacks[] = $entity;

                continue;
            }

            $identifier                 = $this->uow->getEntityIdentifier($entity);
            $idsByClass[$class->name][] = $identifier[$class->identifier[0]];
        }

        foreach ($idsByClass as $className => $ids) {
            $this->batchLoader->initializeEntities($className, $ids);
        }

        foreach ($fallbacks as $entity) {
            $this->uow->initializeObject($entity);
        }
    }

    /**
     * @param iterable<object> $entities
     *
     * @return list<object>
     */
    private function managedEntities(iterable $entities): array
    {
        $managed = [];

        foreach ($entities as $entity) {
            // New and detached entities have nothing to preload for.
            if ($this->uow->getEntityState($entity, UnitOfWork::STATE_DETACHED) === UnitOfWork::STATE_MANAGED) {
                $managed[] = $entity;
            }
        }

        return $managed;
    }
}
