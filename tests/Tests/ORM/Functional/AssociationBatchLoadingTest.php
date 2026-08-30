<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\Models\BatchLoading\BatchLoadChild;
use Doctrine\Tests\Models\BatchLoading\BatchLoadOwner;
use Doctrine\Tests\Models\BatchLoading\BatchLoadTag;

use function array_keys;
use function array_map;

/**
 * Batched loading of associations, as used by the eager fetch modes.
 */
class AssociationBatchLoadingTest extends BatchLoadingTestCase
{
    /** @return list<BatchLoadOwner> */
    private function loadOwnersWithEager(string $field): array
    {
        $this->_em->clear();

        return $this->_em->createQuery('SELECT o FROM ' . BatchLoadOwner::class . ' o ORDER BY o.id')
            ->setFetchMode(BatchLoadOwner::class, $field, ClassMetadata::FETCH_EAGER)
            ->getResult();
    }

    public function testOneToManyIsBatchedEvenWhenTheOwnerHasAnInverseToOne(): void
    {
        $this->getQueryLog()->reset()->enable();

        $owners = $this->loadOwnersWithEager('children');

        // 1 root query + 4 inverse one-to-one loads + 1 batched collection load.
        $this->assertQueryCount(6);

        foreach ($owners as $owner) {
            $index = $this->indexOf($owner);

            self::assertTrue($owner->children->isInitialized());
            self::assertSame(
                [$index . 'a', $index . 'b'],
                array_map(static fn (BatchLoadChild $c): string => $c->code, $owner->children->toArray()),
            );
        }

        // Reading the collections adds no queries.
        $this->assertQueryCount(6);
    }

    public function testIndexedOneToManyIsBatched(): void
    {
        $this->getQueryLog()->reset()->enable();

        $owners = $this->loadOwnersWithEager('indexedChildren');

        $this->assertQueryCount(6);

        foreach ($owners as $owner) {
            $index = $this->indexOf($owner);

            self::assertSame(['i' . $index . 'a', 'i' . $index . 'b'], array_keys($owner->indexedChildren->toArray()));
            self::assertSame('i' . $index . 'a', $owner->indexedChildren->get('i' . $index . 'a')->code);
        }
    }

    public function testManyToManyIsBatched(): void
    {
        $this->getQueryLog()->reset()->enable();

        $owners = $this->loadOwnersWithEager('tags');

        // 1 root query + 4 inverse one-to-one loads + 1 join table query + 1 target query.
        $this->assertQueryCount(7);

        foreach ($owners as $owner) {
            self::assertTrue($owner->tags->isInitialized());
            self::assertSame(
                ['alpha', 'beta', 'own ' . $this->indexOf($owner)],
                array_map(static fn (BatchLoadTag $t): string => $t->name, $owner->tags->toArray()),
            );
        }

        $this->assertQueryCount(7);
    }

    public function testManyToManySharesTargetInstancesBetweenCollections(): void
    {
        $owners = $this->loadOwnersWithEager('tags');

        self::assertSame($owners[0]->tags->get(0), $owners[1]->tags->get(0), 'The shared tag is one instance.');
    }

    public function testManyToManyRespectsTheAssociationOrdering(): void
    {
        // tags is ordered by name; "own N" sorts after "alpha" and "beta".
        foreach ($this->loadOwnersWithEager('tags') as $owner) {
            self::assertSame(
                ['alpha', 'beta', 'own ' . $this->indexOf($owner)],
                array_map(static fn (BatchLoadTag $t): string => $t->name, $owner->tags->toArray()),
            );
        }
    }

    public function testManyToManyIsBatchedFromTheInverseSide(): void
    {
        $this->_em->clear();
        $this->getQueryLog()->reset()->enable();

        $tags = $this->_em->createQuery('SELECT t FROM ' . BatchLoadTag::class . ' t WHERE t.name IN (:names) ORDER BY t.name')
            ->setParameter('names', ['alpha', 'beta'])
            ->setFetchMode(BatchLoadTag::class, 'owners', ClassMetadata::FETCH_EAGER)
            ->getResult();

        // 1 root query + 1 join table query + 1 target query. Tags have no
        // inverse one-to-one, so nothing interrupts the batch.
        $this->assertQueryCount(3);

        self::assertCount(2, $tags);

        foreach ($tags as $tag) {
            self::assertCount(4, $tag->owners, 'Both shared tags belong to all four owners.');
        }
    }

    public function testBatchLoadingSchedulesNoWrites(): void
    {
        $owners = $this->loadOwnersWithEager('tags');

        foreach ($owners as $owner) {
            self::assertFalse($owner->tags->isDirty(), 'A batch loaded collection is not dirty.');
        }

        $this->getQueryLog()->reset()->enable();

        $this->_em->flush();

        $this->assertQueryCount(0, 'Batch loading must leave a correct snapshot behind.');
    }

    public function testEmptyCollectionsAreStillMarkedInitialized(): void
    {
        $lonely       = new BatchLoadOwner();
        $lonely->name = 'lonely';
        $this->_em->persist($lonely);
        $this->_em->flush();
        $this->_em->clear();

        $owners = $this->loadOwnersWithEager('tags');

        self::assertCount(5, $owners);
        self::assertCount(0, $owners[4]->tags);
        self::assertTrue($owners[4]->tags->isInitialized());
    }

    public function testIterationStillLoadsCollectionsOneByOne(): void
    {
        $this->_em->clear();
        $this->getQueryLog()->reset()->enable();

        $query = $this->_em->createQuery('SELECT o FROM ' . BatchLoadOwner::class . ' o')
            ->setFetchMode(BatchLoadOwner::class, 'children', ClassMetadata::FETCH_EAGER);

        $seen = 0;

        foreach ($query->toIterable() as $owner) {
            self::assertCount(2, $owner->children);
            ++$seen;
        }

        self::assertSame(4, $seen);

        // Row-by-row iteration cannot batch across rows: 1 root + 4 profiles + 4 collections.
        $this->assertQueryCount(9);
    }

    public function testLazyLoadingIsUnchanged(): void
    {
        $this->_em->clear();
        $this->getQueryLog()->reset()->enable();

        $owners = $this->_em->createQuery('SELECT o FROM ' . BatchLoadOwner::class . ' o ORDER BY o.id')->getResult();

        foreach ($owners as $owner) {
            self::assertSame(
                ['alpha', 'beta', 'own ' . $this->indexOf($owner)],
                array_map(static fn (BatchLoadTag $t): string => $t->name, $owner->tags->toArray()),
            );
        }

        // 1 root + 4 profiles + one lazy load per collection.
        $this->assertQueryCount(9);
    }
}
