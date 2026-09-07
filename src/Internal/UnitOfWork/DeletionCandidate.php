<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\UnitOfWork;

/**
 * A pending deletion considered for constraint-edge planning, carrying the
 * provenance of its scheduling.
 *
 * The provenance is data carried for the orchestrating {@see \Doctrine\ORM\UnitOfWork}:
 * the planner is provenance-agnostic and never reads it. Step 1 of the
 * constraint-edge ladder (#5109) widens the source of candidates without
 * changing the planner.
 *
 * @internal
 */
final class DeletionCandidate
{
    public const PROVENANCE_ORPHAN   = 'orphan';
    public const PROVENANCE_EXPLICIT = 'explicit';

    public function __construct(
        public readonly object $entity,
        public readonly string $provenance,
    ) {
    }
}
