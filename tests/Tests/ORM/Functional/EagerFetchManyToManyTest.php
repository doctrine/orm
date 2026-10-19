<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use SortDirection;

use function array_map;

class EagerFetchManyToManyTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(BatchManyToManyOwner::class, BatchManyToManyTarget::class);
        $this->_em->getConnection()->executeStatement('DELETE FROM batch_many_to_many_links');
        $this->_em->getConnection()->executeStatement('DELETE FROM batch_many_to_many_related');
        $this->_em->createQuery('DELETE FROM ' . BatchManyToManyOwner::class)->execute();
        $this->_em->createQuery('DELETE FROM ' . BatchManyToManyTarget::class)->execute();
    }

    /** @return iterable<string, array{bool, int}> */
    public static function fetchModes(): iterable
    {
        yield 'owning side' => [false, 100];
        yield 'inverse side' => [true, 100];
        yield 'owning side in chunks' => [false, 2];
        yield 'inverse side in chunks' => [true, 2];
    }

    #[DataProvider('fetchModes')]
    public function testBatchFetch(bool $inverse, int $batchSize): void
    {
        $this->createFixture();
        $this->_em->getConfiguration()->setEagerFetchBatchSize($batchSize);

        $class = $inverse ? BatchManyToManyTarget::class : BatchManyToManyOwner::class;
        $field = $inverse ? 'owners' : 'targets';
        $query = $this->_em->createQuery('SELECT e FROM ' . $class . ' e ORDER BY e.id');
        $query->setFetchMode($class, $field, ORM\ClassMetadata::FETCH_EAGER);

        $this->getQueryLog()->reset()->enable();
        $entities = $query->getResult();

        $this->assertQueryCount($batchSize === 2 ? 3 : 2);
        self::assertCount(3, $entities);

        foreach ($entities as $entity) {
            $collection = $entity->$field;
            self::assertInstanceOf(PersistentCollection::class, $collection);
            self::assertTrue($collection->isInitialized());
            self::assertFalse($collection->isDirty());
            self::assertSame($collection->toArray(), $collection->getSnapshot());
        }

        self::assertSame(['alpha', 'shared'], array_map(static fn ($entity) => $entity->name, $entities[0]->$field->toArray()));
        self::assertSame([$inverse ? 'alpha' : 'shared'], array_map(static fn ($entity) => $entity->name, $entities[1]->$field->toArray()));
        self::assertCount(0, $entities[2]->$field);
        self::assertSame($inverse ? $entities[0]->$field->first() : $entities[0]->$field->last(), $entities[1]->$field->first());
        $this->assertQueryCount($batchSize === 2 ? 3 : 2);

        // Merely loading collections must not schedule any changes for flush.
        $this->getQueryLog()->reset()->enable();
        $this->_em->flush();
        $this->assertQueryCount(0);
    }

    public function testMappingFetchMode(): void
    {
        $this->createFixture();
        $metadata                                        = $this->_em->getClassMetadata(BatchManyToManyOwner::class);
        $metadata->associationMappings['targets']->fetch = ORM\ClassMetadata::FETCH_EAGER;

        $this->getQueryLog()->reset()->enable();
        $owners = $this->_em->getRepository(BatchManyToManyOwner::class)->findBy([], ['id' => SortDirection::Ascending]);

        $this->assertQueryCount(2);
        self::assertCount(2, $owners[0]->targets);
        self::assertCount(1, $owners[1]->targets);
        self::assertCount(0, $owners[2]->targets);
        $this->assertQueryCount(2);
    }

    public function testPagination(): void
    {
        $this->createFixture();
        $query = $this->_em->createQuery('SELECT e FROM ' . BatchManyToManyOwner::class . ' e ORDER BY e.id');
        $query->setFetchMode(BatchManyToManyOwner::class, 'targets', ORM\ClassMetadata::FETCH_EAGER);
        $query->setMaxResults(1);

        $this->getQueryLog()->reset()->enable();
        $owners = $query->getResult();

        self::assertCount(1, $owners);
        self::assertCount(2, $owners[0]->targets);
        $this->assertQueryCount(2);
    }

    public function testChangesAfterBatchFetchArePersisted(): void
    {
        $this->createFixture();
        $query = $this->_em->createQuery('SELECT e FROM ' . BatchManyToManyOwner::class . ' e ORDER BY e.id');
        $query->setFetchMode(BatchManyToManyOwner::class, 'targets', ORM\ClassMetadata::FETCH_EAGER);
        $owners = $query->getResult();

        $shared = $owners[1]->targets->first();
        $owners[0]->targets->removeElement($shared);
        $owners[2]->targets->add($shared);
        $this->_em->flush();
        $this->_em->clear();

        $owners = $query->getResult();
        self::assertSame(['alpha'], array_map(static fn ($target) => $target->name, $owners[0]->targets->toArray()));
        self::assertSame(['shared'], array_map(static fn ($target) => $target->name, $owners[1]->targets->toArray()));
        self::assertSame($owners[1]->targets->first(), $owners[2]->targets->first());
    }

    public function testBothSidesEager(): void
    {
        $this->createFixture();
        $this->_em->getClassMetadata(BatchManyToManyOwner::class)->associationMappings['targets']->fetch = ORM\ClassMetadata::FETCH_EAGER;
        $this->_em->getClassMetadata(BatchManyToManyTarget::class)->associationMappings['owners']->fetch = ORM\ClassMetadata::FETCH_EAGER;

        $this->getQueryLog()->reset()->enable();
        $owners = $this->_em->getRepository(BatchManyToManyOwner::class)->findBy([], ['id' => SortDirection::Ascending]);

        self::assertCount(2, $owners[0]->targets);
        self::assertCount(1, $owners[1]->targets);
        self::assertCount(0, $owners[2]->targets);
        self::assertSame([$owners[0], $owners[1]], $owners[1]->targets->first()->owners->toArray());
        $this->assertQueryCount(3);
    }

    public function testSelfReferentialUnidirectionalAssociation(): void
    {
        $this->createFixture();
        $owners = $this->_em->getRepository(BatchManyToManyOwner::class)->findBy([], ['id' => SortDirection::Ascending]);
        $owners[0]->related->add($owners[1]);
        $owners[1]->related->add($owners[0]);
        $owners[1]->related->add($owners[2]);
        $this->_em->flush();
        $this->_em->clear();

        $query = $this->_em->createQuery('SELECT e FROM ' . BatchManyToManyOwner::class . ' e ORDER BY e.id');
        $query->setFetchMode(BatchManyToManyOwner::class, 'related', ORM\ClassMetadata::FETCH_EAGER);
        $this->getQueryLog()->reset()->enable();
        $owners = $query->getResult();

        self::assertSame([$owners[1]], $owners[0]->related->toArray());
        self::assertSame([$owners[0], $owners[2]], $owners[1]->related->toArray());
        self::assertCount(0, $owners[2]->related);
        $this->assertQueryCount(2);
    }

    public function testTargetFilter(): void
    {
        $this->createFixture();
        $this->_em->getConfiguration()->addFilter('batch_target', BatchManyToManyTargetFilter::class);
        $this->_em->getFilters()->enable('batch_target');
        $query = $this->_em->createQuery('SELECT e FROM ' . BatchManyToManyOwner::class . ' e ORDER BY e.id');
        $query->setFetchMode(BatchManyToManyOwner::class, 'targets', ORM\ClassMetadata::FETCH_EAGER);

        $this->getQueryLog()->reset()->enable();
        $owners = $query->getResult();

        self::assertSame(['alpha'], array_map(static fn ($target) => $target->name, $owners[0]->targets->toArray()));
        self::assertCount(0, $owners[1]->targets);
        self::assertCount(0, $owners[2]->targets);
        $this->assertQueryCount(2);
    }

    public function testIndexedAssociationUsesPerOwnerLoading(): void
    {
        $this->createFixture();
        $mapping          = $this->_em->getClassMetadata(BatchManyToManyOwner::class)->associationMappings['targets'];
        $mapping->indexBy = 'name';
        $mapping->fetch   = ORM\ClassMetadata::FETCH_EAGER;

        $this->getQueryLog()->reset()->enable();
        $owners = $this->_em->getRepository(BatchManyToManyOwner::class)->findBy([], ['id' => SortDirection::Ascending]);

        self::assertSame('alpha', $owners[0]->targets->get('alpha')->name);
        self::assertSame($owners[0]->targets->get('shared'), $owners[1]->targets->get('shared'));
        self::assertCount(0, $owners[2]->targets);
        $this->assertQueryCount(4);
    }

    public function testIterationUsesPerOwnerLoading(): void
    {
        $this->createFixture();
        $query = $this->_em->createQuery('SELECT e FROM ' . BatchManyToManyOwner::class . ' e ORDER BY e.id');
        $query->setFetchMode(BatchManyToManyOwner::class, 'targets', ORM\ClassMetadata::FETCH_EAGER);

        $this->getQueryLog()->reset()->enable();
        $counts = [];
        foreach ($query->toIterable() as $owner) {
            self::assertTrue($owner->targets->isInitialized());
            $counts[] = $owner->targets->count();
        }

        self::assertSame([2, 1, 0], $counts);
        $this->assertQueryCount(4);
    }

    private function createFixture(): void
    {
        $owners  = [new BatchManyToManyOwner('alpha'), new BatchManyToManyOwner('shared'), new BatchManyToManyOwner('empty')];
        $targets = [new BatchManyToManyTarget('shared'), new BatchManyToManyTarget('alpha'), new BatchManyToManyTarget('empty')];

        $owners[0]->targets->add($targets[0]);
        $owners[0]->targets->add($targets[1]);
        $owners[1]->targets->add($targets[0]);

        foreach ([...$owners, ...$targets] as $entity) {
            $this->_em->persist($entity);
        }

        $this->_em->flush();
        $this->_em->clear();
    }
}

#[ORM\Entity]
class BatchManyToManyOwner
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    /** @var Collection<int, BatchManyToManyTarget> */
    #[ORM\ManyToMany(targetEntity: BatchManyToManyTarget::class, inversedBy: 'owners')]
    #[ORM\JoinTable(name: 'batch_many_to_many_links')]
    #[ORM\OrderBy(['name' => SortDirection::Ascending])]
    public Collection $targets;

    /** @var Collection<int, BatchManyToManyOwner> */
    #[ORM\ManyToMany(targetEntity: self::class)]
    #[ORM\JoinTable(name: 'batch_many_to_many_related')]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'related_id', referencedColumnName: 'id')]
    #[ORM\OrderBy(['name' => SortDirection::Ascending])]
    public Collection $related;

    public function __construct(#[ORM\Column]
    public string $name,)
    {
        $this->targets = new ArrayCollection();
        $this->related = new ArrayCollection();
    }
}

class BatchManyToManyTargetFilter extends SQLFilter
{
    public function addFilterConstraint(ORM\ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        return $targetEntity->name === BatchManyToManyTarget::class
            ? $targetTableAlias . ".name <> 'shared'"
            : '';
    }
}

#[ORM\Entity]
class BatchManyToManyTarget
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    /** @var Collection<int, BatchManyToManyOwner> */
    #[ORM\ManyToMany(targetEntity: BatchManyToManyOwner::class, mappedBy: 'targets')]
    #[ORM\OrderBy(['name' => SortDirection::Ascending])]
    public Collection $owners;

    public function __construct(#[ORM\Column]
    public string $name,)
    {
        $this->owners = new ArrayCollection();
    }
}
