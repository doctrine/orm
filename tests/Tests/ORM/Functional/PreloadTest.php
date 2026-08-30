<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use ArrayIterator;
use Doctrine\ORM\Exception\StrictLoadingViolation;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\ORM\StrictLoading\StrictLoadingMode;
use Doctrine\Tests\Models\BatchLoading\BatchLoadChild;
use Doctrine\Tests\Models\BatchLoading\BatchLoadOwner;
use Doctrine\Tests\Models\BatchLoading\BatchLoadTag;
use LogicException;

use function array_map;
use function count;

/** Explicitly loading associations of entities that are already in memory. */
class PreloadTest extends BatchLoadingTestCase
{
    public function testPreloadACollectionInOneQuery(): void
    {
        $owners = $this->owners();

        $this->getQueryLog()->reset()->enable();

        $this->_em->preload($owners, ['children']);

        $this->assertQueryCount(1, 'One query for all four collections.');

        foreach ($owners as $owner) {
            self::assertTrue($owner->children->isInitialized());
            self::assertCount(2, $owner->children);
        }

        $this->assertQueryCount(1);
    }

    public function testPreloadThroughAFinder(): void
    {
        $this->_em->clear();
        $this->getQueryLog()->reset()->enable();

        $owners = $this->_em->getRepository(BatchLoadOwner::class)->findAll(preload: ['children']);

        // One query for the owners, one for all four collections.
        $this->assertQueryCount(2);

        foreach ($owners as $owner) {
            self::assertTrue($owner->children->isInitialized());
            self::assertCount(2, $owner->children);
        }

        $this->assertQueryCount(2);
    }

    public function testPreloadThroughFindOneBy(): void
    {
        $this->_em->clear();

        $owner = $this->_em->getRepository(BatchLoadOwner::class)
            ->findOneBy(['name' => 'owner 1'], preload: ['children']);

        self::assertNotNull($owner);

        $this->getQueryLog()->reset()->enable();

        self::assertCount(2, $owner->children);

        $this->assertQueryCount(0, 'The collection was already preloaded.');
    }

    public function testPreloadThroughAQuery(): void
    {
        $this->_em->clear();
        $this->getQueryLog()->reset()->enable();

        $owners = $this->_em->createQuery('SELECT o FROM ' . BatchLoadOwner::class . ' o')
            ->preload(['children', 'tags'])
            ->getResult();

        // Five for the owners themselves - the inverse one-to-one profile is still
        // loaded per row - then one for the children and two for the tags.
        $this->assertQueryCount(8);

        foreach ($owners as $owner) {
            self::assertCount(2, $owner->children);
            self::assertCount(3, $owner->tags);
        }

        $this->assertQueryCount(8);
    }

    public function testPreloadThroughTheQueryBuilder(): void
    {
        $this->_em->clear();
        $this->getQueryLog()->reset()->enable();

        $owners = $this->_em->getRepository(BatchLoadOwner::class)
            ->createQueryBuilder('o')
            ->preload(['children'])
            ->getQuery()
            ->getResult();

        // Five for the owners, one for all four collections.
        $this->assertQueryCount(6);

        foreach ($owners as $owner) {
            self::assertTrue($owner->children->isInitialized());
        }

        $this->assertQueryCount(6);
    }

    public function testPreloadIsAppliedToASingleResult(): void
    {
        $this->_em->clear();

        $owner = $this->_em->createQuery(
            'SELECT o FROM ' . BatchLoadOwner::class . ' o WHERE o.name = :name',
        )->setParameter('name', 'owner 1')->preload(['children'])->getSingleResult();

        $this->getQueryLog()->reset()->enable();

        self::assertCount(2, $owner->children);

        $this->assertQueryCount(0);
    }

    public function testPreloadRejectsIteration(): void
    {
        $query = $this->_em->createQuery('SELECT o FROM ' . BatchLoadOwner::class . ' o')
            ->preload(['children']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Preloading is not possible with toIterable()');

        $query->toIterable();
    }

    public function testPreloadThroughTheRepository(): void
    {
        $owners = $this->owners();

        $this->getQueryLog()->reset()->enable();

        $this->_em->getRepository(BatchLoadOwner::class)->preload($owners, ['children']);

        $this->assertQueryCount(1, 'One query for all four collections.');

        foreach ($owners as $owner) {
            self::assertTrue($owner->children->isInitialized());
            self::assertCount(2, $owner->children);
        }

        $this->assertQueryCount(1);
    }

    public function testPreloadAManyToManyCollection(): void
    {
        $owners = $this->owners();

        $this->getQueryLog()->reset()->enable();

        $this->_em->preload($owners, ['tags']);

        // One query for the join table, one for the tags themselves.
        $this->assertQueryCount(2);

        foreach ($owners as $owner) {
            self::assertSame(
                ['alpha', 'beta', 'own ' . $this->indexOf($owner)],
                array_map(static fn (BatchLoadTag $t): string => $t->name, $owner->tags->toArray()),
            );
        }
    }

    public function testPreloadAToOneAssociation(): void
    {
        $children = $this->_em->createQuery(
            'SELECT c FROM ' . BatchLoadChild::class . ' c WHERE c.owner IS NOT NULL',
        )->getResult();

        self::assertCount(8, $children);

        foreach ($children as $child) {
            self::assertTrue($this->_em->isUninitializedObject($child->owner));
        }

        $this->getQueryLog()->reset()->enable();

        $this->_em->preload($children, ['owner']);

        // Eight children, four distinct owners, one query.
        $this->assertQueryCount(1);

        foreach ($children as $child) {
            self::assertFalse($this->_em->isUninitializedObject($child->owner));
            self::assertStringStartsWith('owner ', $child->owner->name);
        }
    }

    public function testPreloadingAnInverseToOneIsANoOp(): void
    {
        // The inverse side of a to-one association is not lazy: the ORM loads it
        // while hydrating the owner. Preloading it has nothing left to do.
        $owners = $this->owners();

        foreach ($owners as $owner) {
            self::assertNotNull($owner->profile);
        }

        $this->getQueryLog()->reset()->enable();

        $this->_em->preload($owners, ['profile']);

        $this->assertQueryCount(0);
    }

    public function testPreloadWalksThroughAnInverseToOne(): void
    {
        $owners = $this->owners();

        $this->getQueryLog()->reset()->enable();

        // profile is already there; owner is a reference on the other side of it.
        $this->_em->preload($owners, ['profile.owner']);

        $this->assertQueryCount(0, 'Both sides are in the identity map already.');

        foreach ($owners as $owner) {
            self::assertSame($owner, $owner->profile->owner);
        }
    }

    public function testPreloadANestedPath(): void
    {
        $owners = $this->owners();

        $this->getQueryLog()->reset()->enable();

        $this->_em->preload($owners, ['children.owner']);

        // One query for the children of all owners, and the children's owner is
        // already in the identity map, so nothing more.
        $this->assertQueryCount(1);

        foreach ($owners as $owner) {
            foreach ($owner->children as $child) {
                self::assertSame($owner, $child->owner);
            }
        }
    }

    public function testPreloadSeveralPaths(): void
    {
        $owners = $this->owners();

        $this->getQueryLog()->reset()->enable();

        $this->_em->preload($owners, ['children', 'tags', 'profile']);

        // children (1) + tags (2); the inverse one-to-one is already loaded.
        $this->assertQueryCount(3);

        foreach ($owners as $owner) {
            self::assertCount(2, $owner->children);
            self::assertCount(3, $owner->tags);
            self::assertNotNull($owner->profile);
        }
    }

    public function testPreloadWithoutAPathInitializesReferences(): void
    {
        $references = [];

        foreach ($this->_em->createQuery('SELECT o.id FROM ' . BatchLoadOwner::class . ' o')->getScalarResult() as $row) {
            $references[] = $this->_em->getReference(BatchLoadOwner::class, $row['id']);
        }

        self::assertCount(4, $references);

        $this->getQueryLog()->reset()->enable();

        $this->_em->preload($references);

        $this->assertQueryCount(1, 'All four references are initialized by one query.');

        foreach ($references as $reference) {
            self::assertFalse($this->_em->isUninitializedObject($reference));
        }
    }

    public function testPreloadInitializesReferencesBeforeReadingTheirAssociations(): void
    {
        $references = [];

        foreach ($this->_em->createQuery('SELECT o.id FROM ' . BatchLoadOwner::class . ' o')->getScalarResult() as $row) {
            $references[] = $this->_em->getReference(BatchLoadOwner::class, $row['id']);
        }

        $this->getQueryLog()->reset()->enable();

        $this->_em->preload($references, ['children']);

        // One query to initialize the references, one for their collections -
        // instead of one query per reference.
        $this->assertQueryCount(2);

        foreach ($references as $reference) {
            self::assertCount(2, $reference->children);
        }
    }

    public function testPreloadingTwiceQueriesOnce(): void
    {
        $owners = $this->owners();

        $this->_em->preload($owners, ['children']);

        $this->getQueryLog()->reset()->enable();

        $this->_em->preload($owners, ['children']);

        $this->assertQueryCount(0, 'Initialized collections are skipped.');
    }

    public function testPreloadSchedulesNoWrites(): void
    {
        $owners = $this->owners();

        $this->_em->preload($owners, ['children', 'tags']);

        $this->getQueryLog()->reset()->enable();

        $this->_em->flush();

        $this->assertQueryCount(0, 'Preloading must leave a correct snapshot behind.');
    }

    public function testPreloadSkipsDirtyCollections(): void
    {
        $owners = $this->owners();

        $child        = new BatchLoadChild();
        $child->code  = 'new';
        $child->owner = $owners[0];
        $owners[0]->children->add($child);

        self::assertTrue($owners[0]->children->isDirty());

        $this->_em->preload($owners, ['children']);

        // The dirty collection was loaded on its own and kept the new element.
        self::assertCount(3, $owners[0]->children);
        self::assertCount(2, $owners[1]->children);
    }

    public function testPreloadIsNotAStrictLoadingViolation(): void
    {
        $owners        = $this->owners();
        $strictLoading = $this->_em->getConfiguration()->getStrictLoading();
        $strictLoading->setMode(StrictLoadingMode::All);

        try {
            $this->_em->preload($owners, ['children', 'tags', 'profile']);

            foreach ($owners as $owner) {
                self::assertCount(2, $owner->children);
                self::assertCount(3, $owner->tags);
                self::assertNotNull($owner->profile);
            }
        } finally {
            $strictLoading->setMode(StrictLoadingMode::Disabled);
        }
    }

    public function testStrictLoadingStillRejectsWhatWasNotPreloaded(): void
    {
        $owners        = $this->owners();
        $strictLoading = $this->_em->getConfiguration()->getStrictLoading();

        $this->_em->preload($owners, ['children']);
        $strictLoading->setMode(StrictLoadingMode::All);

        try {
            $this->expectException(StrictLoadingViolation::class);

            $owners[0]->tags->toArray();
        } finally {
            $strictLoading->setMode(StrictLoadingMode::Disabled);
        }
    }

    public function testPreloadingAnUnknownAssociationFails(): void
    {
        $this->expectException(ORMInvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Cannot preload "editor" of ' . BatchLoadChild::class
            . ': there is no such association. It was reached through the path "children.editor".',
        );

        $this->_em->preload($this->owners(), ['children.editor']);
    }

    public function testPreloadingNothingIsHarmless(): void
    {
        $this->getQueryLog()->reset()->enable();

        $this->_em->preload([], ['children']);
        $this->_em->preload([new BatchLoadOwner()], ['children']);

        $this->assertQueryCount(0, 'New and detached entities have nothing to preload.');
    }

    public function testPreloadAcceptsAnyIterable(): void
    {
        $owners = $this->owners();

        $this->_em->preload(new ArrayIterator($owners), ['children']);

        self::assertTrue($owners[0]->children->isInitialized());
        self::assertSame(4, count($owners));
    }
}
