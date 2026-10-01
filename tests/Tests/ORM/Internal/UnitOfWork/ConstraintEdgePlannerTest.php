<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Internal\UnitOfWork;

use Doctrine\ORM\Internal\UnitOfWork\ConstraintEdgePlan;
use Doctrine\ORM\Internal\UnitOfWork\ConstraintEdgePlanner;
use Doctrine\ORM\Internal\UnitOfWork\DeletionCandidate;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\ORM\Mapping\ManyToOneAssociationMapping;
use Doctrine\ORM\Mapping\PropertyAccessors\PropertyAccessorFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function spl_object_id;

#[CoversClass(ConstraintEdgePlanner::class)]
#[CoversClass(ConstraintEdgePlan::class)]
#[Group('#6776')]
final class ConstraintEdgePlannerTest extends TestCase
{
    public function testUniqueFieldCollisionProducesEdgeForEarlyDeletion(): void
    {
        $old    = new PlannerItem();
        $old->f = 'v';
        $new    = new PlannerItem();
        $new->f = 'v';

        $plan = $this->plan([$this->orphan($old)], [$new]);

        self::assertCount(1, $plan->edges);
        self::assertSame($old, $plan->edges[0]->deletion);
        self::assertSame($new, $plan->edges[0]->insertion);
        self::assertSame([$old], $plan->earlyDeletions());
        self::assertSame([], $plan->blockedDeletions);
    }

    public function testNullTupleComponentNeverCollides(): void
    {
        $old    = new PlannerItem();
        $old->f = null;
        $new    = new PlannerItem();
        $new->f = null;

        $plan = $this->plan([$this->orphan($old)], [$new]);

        self::assertSame([], $plan->edges);
        self::assertSame([], $plan->blockedDeletions);
        self::assertSame([], $plan->earlyDeletions());
    }

    public function testTableUniqueConstraintCollisionRequiresFullTuple(): void
    {
        $old    = new PlannerItem();
        $old->a = 'x';
        $old->b = 'y';
        $new    = new PlannerItem();
        $new->a = 'x';
        $new->b = 'y';

        $plan = $this->plan([$this->orphan($old)], [$new]);

        self::assertCount(1, $plan->edges);
        self::assertSame($old, $plan->edges[0]->deletion);
        self::assertSame($new, $plan->edges[0]->insertion);
        self::assertSame([$old], $plan->earlyDeletions());
    }

    public function testTableUniqueConstraintWithPartiallyDifferentTupleDoesNotCollide(): void
    {
        $old    = new PlannerItem();
        $old->a = 'x';
        $old->b = 'y';
        $new    = new PlannerItem();
        $new->a = 'x';
        $new->b = 'z';

        $plan = $this->plan([$this->orphan($old)], [$new]);

        self::assertSame([], $plan->edges);
        self::assertSame([], $plan->blockedDeletions);
    }

    public function testUniqueJoinColumnCollisionByReferencedEntityIdentity(): void
    {
        $ref = new PlannerRef();

        $old      = new PlannerItem();
        $old->ref = $ref;
        $new      = new PlannerItem();
        $new->ref = $ref;

        $plan = $this->plan([$this->orphan($old)], [$new]);

        self::assertCount(1, $plan->edges);
        self::assertSame($old, $plan->edges[0]->deletion);
        self::assertSame($new, $plan->edges[0]->insertion);
        self::assertSame([$old], $plan->earlyDeletions());
    }

    public function testUniqueJoinColumnWithNullAssociationDoesNotCollide(): void
    {
        $old = new PlannerItem();
        $new = new PlannerItem();

        $plan = $this->plan([$this->orphan($old)], [$new]);

        self::assertSame([], $plan->edges);
    }

    public function testCandidateWithoutInsertionsStaysOnBaselineOrder(): void
    {
        $old    = new PlannerItem();
        $old->f = 'v';

        $plan = $this->plan([$this->orphan($old)], []);

        self::assertSame([], $plan->edges);
        self::assertSame([], $plan->blockedDeletions);
        self::assertSame([], $plan->earlyDeletions());
    }

    public function testFkReferenceFromInsertFallsBackToBaselineOrder(): void
    {
        $old    = new PlannerItem();
        $old->f = 'v';

        $new    = new PlannerItem();
        $new->f = 'v';

        $referencing        = new PlannerItem();
        $referencing->f     = 'w';
        $referencing->owner = $old;

        $plan = $this->plan([$this->orphan($old)], [$new, $referencing]);

        self::assertSame([], $plan->edges);
        self::assertSame([$old], $plan->blockedDeletions);
        self::assertSame([], $plan->earlyDeletions());
    }

    public function testFkReferenceFromUpdateOriginalValueFallsBackToBaselineOrder(): void
    {
        $old    = new PlannerItem();
        $old->f = 'v';

        $new    = new PlannerItem();
        $new->f = 'v';

        $updater    = new PlannerItem();
        $updater->f = 'w';

        $plan = $this->plan(
            [$this->orphan($old)],
            [$new],
            [spl_object_id($updater) => $updater],
            [],
            [],
            [spl_object_id($updater) => ['owner' => [$old, null]]],
        );

        self::assertSame([], $plan->edges);
        self::assertSame([$old], $plan->blockedDeletions);
    }

    public function testFkReferenceFromUpdateOriginalEntityDataFallsBackToBaselineOrder(): void
    {
        $old    = new PlannerItem();
        $old->f = 'v';

        $new    = new PlannerItem();
        $new->f = 'v';

        $updater    = new PlannerItem();
        $updater->f = 'w';

        $plan = $this->plan(
            [$this->orphan($old)],
            [$new],
            [spl_object_id($updater) => $updater],
            [],
            [spl_object_id($updater) => ['owner' => $old]],
        );

        self::assertSame([], $plan->edges);
        self::assertSame([$old], $plan->blockedDeletions);
    }

    public function testFkReferenceFromPendingDeletionCurrentValueFallsBackToBaselineOrder(): void
    {
        $old    = new PlannerItem();
        $old->f = 'v';

        $new    = new PlannerItem();
        $new->f = 'v';

        $deleter        = new PlannerItem();
        $deleter->f     = 'w';
        $deleter->owner = $old;

        $plan = $this->plan(
            [$this->orphan($old)],
            [$new],
            [],
            [spl_object_id($deleter) => $deleter],
        );

        self::assertSame([], $plan->edges);
        self::assertSame([$old], $plan->blockedDeletions);
    }

    public function testFkReferenceFromPendingDeletionOriginalValueFallsBackToBaselineOrder(): void
    {
        $old    = new PlannerItem();
        $old->f = 'v';

        $new    = new PlannerItem();
        $new->f = 'v';

        $deleter    = new PlannerItem();
        $deleter->f = 'w';

        $plan = $this->plan(
            [$this->orphan($old)],
            [$new],
            [],
            [spl_object_id($deleter) => $deleter],
            [spl_object_id($deleter) => ['owner' => $old]],
        );

        self::assertSame([], $plan->edges);
        self::assertSame([$old], $plan->blockedDeletions);
    }

    public function testFkReferenceFromPendingManyToManyCollectionOperationFallsBackToBaselineOrder(): void
    {
        $old    = new PlannerItem();
        $old->f = 'v';

        $new    = new PlannerItem();
        $new->f = 'v';

        $plan = $this->plan(
            [$this->orphan($old)],
            [$new],
            [],
            [],
            [],
            [],
            [PlannerItem::class => true],
        );

        self::assertSame([], $plan->edges);
        self::assertSame([$old], $plan->blockedDeletions);
        self::assertSame([], $plan->earlyDeletions());
    }

    public function testOutputIsIndependentOfProvenanceAttribute(): void
    {
        $oldOrphan         = new PlannerItem();
        $oldOrphan->f      = 'v';
        $oldExplicit       = new PlannerItem();
        $oldExplicit->f    = 'v';
        $newForOrphan      = new PlannerItem();
        $newForOrphan->f   = 'v';
        $newForExplicit    = new PlannerItem();
        $newForExplicit->f = 'v';

        $orphanPlan   = $this->plan([new DeletionCandidate($oldOrphan, DeletionCandidate::PROVENANCE_ORPHAN)], [$newForOrphan]);
        $explicitPlan = $this->plan([new DeletionCandidate($oldExplicit, DeletionCandidate::PROVENANCE_EXPLICIT)], [$newForExplicit]);

        self::assertCount(1, $orphanPlan->edges);
        self::assertCount(1, $explicitPlan->edges);
        self::assertSame($oldOrphan, $orphanPlan->earlyDeletions()[0]);
        self::assertSame($oldExplicit, $explicitPlan->earlyDeletions()[0]);
        self::assertSame($orphanPlan->blockedDeletions, $explicitPlan->blockedDeletions);
    }

    public function testCollisionIsOnlyMatchedWithinSameClass(): void
    {
        $old    = new PlannerItem();
        $old->f = 'v';
        $new    = new PlannerOther();
        $new->f = 'v';

        $plan = $this->plan([$this->orphan($old)], [$new]);

        self::assertSame([], $plan->edges);
        self::assertSame([], $plan->blockedDeletions);
    }

    /**
     * @param list<DeletionCandidate>                              $candidates
     * @param array<int, object>                                   $insertions
     * @param array<int, object>                                   $updates
     * @param array<int, object>                                   $deletions
     * @param array<int, array<string, mixed>>                     $originalEntityData
     * @param array<int, array<string, array{0: mixed, 1: mixed}>> $entityChangeSets
     * @param array<string, true>                                  $manyToManyTargetClasses
     * @param array<string, ClassMetadata<object>>                 $metadataOverrides
     */
    private function plan(
        array $candidates,
        array $insertions,
        array $updates = [],
        array $deletions = [],
        array $originalEntityData = [],
        array $entityChangeSets = [],
        array $manyToManyTargetClasses = [],
        array $metadataOverrides = [],
    ): ConstraintEdgePlan {
        $metadata = [
            PlannerItem::class => $this->itemMetadata(),
            PlannerRef::class => new ClassMetadata(PlannerRef::class),
            PlannerOther::class => $this->otherMetadata(),
            ...$metadataOverrides,
        ];

        $candidateDeletions = [];
        foreach ($candidates as $candidate) {
            $candidateDeletions[spl_object_id($candidate->entity)] = $candidate->entity;
        }

        return ConstraintEdgePlanner::planEarlyDeletions(
            $candidates,
            $insertions,
            $updates,
            $candidateDeletions + $deletions,
            $originalEntityData,
            $entityChangeSets,
            $manyToManyTargetClasses,
            static fn (string $class): ClassMetadata => $metadata[$class],
        );
    }

    private function orphan(PlannerItem|PlannerOther $entity): DeletionCandidate
    {
        return new DeletionCandidate($entity, DeletionCandidate::PROVENANCE_ORPHAN);
    }

    private function itemMetadata(): ClassMetadata
    {
        $metadata = new ClassMetadata(PlannerItem::class);

        $this->addField($metadata, 'f', true);
        $this->addField($metadata, 'a');
        $this->addField($metadata, 'b');

        $this->addManyToOne($metadata, 'owner', PlannerItem::class);
        $this->addManyToOne($metadata, 'ref', PlannerRef::class, true);

        $metadata->table['uniqueConstraints']['ab_uniq'] = ['columns' => ['a', 'b']];

        return $metadata;
    }

    private function otherMetadata(): ClassMetadata
    {
        $metadata = new ClassMetadata(PlannerOther::class);

        $this->addField($metadata, 'f', true);

        return $metadata;
    }

    private function addField(ClassMetadata $metadata, string $name, bool $unique = false): void
    {
        $class = $metadata->name;

        $metadata->fieldMappings[$name]     = FieldMapping::fromMappingArray([
            'type' => 'string',
            'fieldName' => $name,
            'columnName' => $name,
            'unique' => $unique ? true : null,
        ]);
        $metadata->fieldNames[$name]        = $name;
        $metadata->propertyAccessors[$name] = PropertyAccessorFactory::createPropertyAccessor($class, $name);
    }

    private function addManyToOne(ClassMetadata $metadata, string $name, string $targetClass, bool $uniqueJoinColumn = false): void
    {
        $metadata->associationMappings[$name] = ManyToOneAssociationMapping::fromMappingArray([
            'fieldName' => $name,
            'sourceEntity' => $metadata->name,
            'targetEntity' => $targetClass,
            'joinColumns' => [
                [
                    'name' => $name . '_id',
                    'referencedColumnName' => 'id',
                    'unique' => $uniqueJoinColumn ? true : null,
                ],
            ],
        ]);
        $metadata->propertyAccessors[$name]   = PropertyAccessorFactory::createPropertyAccessor($metadata->name, $name);
    }
}

class PlannerItem
{
    public string|null $f          = null;
    public string|null $a          = null;
    public string|null $b          = null;
    public PlannerItem|null $owner = null;
    public PlannerRef|null $ref    = null;
}

class PlannerRef
{
    public int|null $id = null;
}

class PlannerOther
{
    public string|null $f = null;
}
