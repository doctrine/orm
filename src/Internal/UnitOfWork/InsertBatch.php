<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\UnitOfWork;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * An {@see InsertBatch} represents a set of entities that are safe to be batched
 * together in a single query.
 *
 * These entities are only those that have all fields already assigned, including the
 * identifier field(s).
 *
 * This data structure only exists for internal {@see UnitOfWork} optimisations, and
 * should not be relied upon outside the ORM.
 *
 * @internal
 *
 * @template TEntity of object
 */
final class InsertBatch
{
    /**
     * @param ClassMetadata<TEntity>  $class
     * @param non-empty-list<TEntity> $entities
     */
    public function __construct(
        public readonly ClassMetadata $class,
        public array $entities,
    ) {
    }

    /**
     * Note: Code in here is procedural/ugly due to it being in a hot path of the {@see UnitOfWork}
     *
     * This method will batch the given entity set by type, preserving their order. For example,
     * given an input [A1, A2, A3, B1, B2, A4, A5], it will create an [[A1, A2, A3], [B1, B2], [A4, A5]] batch.
     *
     * Entities for which the identifier needs to be generated or fetched by a sequence are put as single
     * items in a batch of their own, since it is unsafe to batch-insert them.
     *
     * @param list<TEntities> $entities
     *
     * @return list<self<TEntities>>
     *
     * @template TEntities of object
     */
    public static function batchByEntityType(
        EntityManagerInterface $entityManager,
        array $entities,
    ): array {
        $currentClass = null;
        $batches      = [];
        $batchIndex   = -1;

        foreach ($entities as $entity) {
            $entityClass = $entityManager->getClassMetadata($entity::class);

            if (
                $currentClass?->name !== $entityClass->name
                || ! $entityClass->idGenerator instanceof AssignedGenerator
            ) {
                $currentClass = $entityClass;
                $batches[]    = new InsertBatch($entityClass, [$entity]);
                $batchIndex  += 1;

                continue;
            }

            $batches[$batchIndex]->entities[] = $entity;
        }

        return $batches;
    }
}
