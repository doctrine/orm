<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Tests\OrmFunctionalTestCase;

use function count;
use function iterator_to_array;

class EagerFetchCollectionTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(EagerFetchOwner::class, EagerFetchChild::class);

        // Ensure tables are empty
        $this->_em->getRepository(EagerFetchChild::class)->createQueryBuilder('o')->delete()->getQuery()->execute();
        $this->_em->getRepository(EagerFetchOwner::class)->createQueryBuilder('o')->delete()->getQuery()->execute();
    }

    public function testEagerFetchMode(): void
    {
        $owner  = $this->createOwnerWithChildren(2);
        $owner2 = $this->createOwnerWithChildren(3);

        $this->_em->flush();
        $this->_em->clear();

        $owner = $this->_em->find(EagerFetchOwner::class, $owner->id);

        $afterQueryCount = count($this->getQueryLog()->queries);
        $this->assertCount(2, $owner->children);

        $this->assertQueryCount($afterQueryCount, 'The $owner->children collection should already be initialized by find EagerFetchOwner before.');

        $this->assertCount(3, $this->_em->find(EagerFetchOwner::class, $owner2->id)->children);

        $this->_em->clear();

        $beforeQueryCount = count($this->getQueryLog()->queries);
        $owners           = $this->_em->getRepository(EagerFetchOwner::class)->findAll();

        $this->assertQueryCount($beforeQueryCount + 2, 'the findAll() + 1 subselect loading both collections of the two returned $owners');

        $this->assertCount(2, $owners[0]->children);
        $this->assertCount(3, $owners[1]->children);

        $this->assertQueryCount($beforeQueryCount + 2, 'both collections are already initialized and counting them does not make a difference in total query count');
    }

    public function testEagerFetchModeWithDQL(): void
    {
        $owner  = $this->createOwnerWithChildren(2);
        $owner2 = $this->createOwnerWithChildren(3);

        $this->_em->flush();
        $this->_em->clear();

        $query = $this->_em->createQuery('SELECT o FROM ' . EagerFetchOwner::class . ' o');
        $query->setFetchMode(EagerFetchOwner::class, 'children', ORM\ClassMetadata::FETCH_EAGER);

        $beforeQueryCount = count($this->getQueryLog()->queries);
        $owners           = $query->getResult();
        $afterQueryCount  = count($this->getQueryLog()->queries);

        $this->assertEquals($beforeQueryCount + 2, $afterQueryCount);

        $owners[0]->children->count();
        $owners[1]->children->count();

        $anotherQueryCount = count($this->getQueryLog()->queries);

        $this->assertEquals($anotherQueryCount, $afterQueryCount);
    }

    public function testSubselectFetchJoinWithNotAllowed(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Associations with fetch-mode=EAGER may not be using WITH conditions');

        $query = $this->_em->createQuery('SELECT o, c FROM ' . EagerFetchOwner::class . ' o JOIN o.children c WITH c.id = 1');
        $query->getResult();
    }

    public function testSubselectFetchJoinWithAllowedWhenOverriddenNotEager(): void
    {
        $query = $this->_em->createQuery('SELECT o, c FROM ' . EagerFetchOwner::class . ' o JOIN o.children c WITH c.id = 1');
        $query->setFetchMode(EagerFetchChild::class, 'owner', ORM\ClassMetadata::FETCH_LAZY);

        $this->assertIsString($query->getSql());
    }

    public function testSubselectFetchJoinWithAllowedWhenOverriddenNotEagerPaginator(): void
    {
        $query = $this->_em->createQuery('SELECT o, c FROM ' . EagerFetchOwner::class . ' o JOIN o.children c WITH c.id = 1');
        $query->setMaxResults(1);
        $query->setFetchMode(EagerFetchChild::class, 'owner', ORM\ClassMetadata::FETCH_LAZY);

        $paginator = new Paginator($query, true);
        $this->assertIsArray(iterator_to_array($paginator));
    }

    public function testEagerFetchWithIterable(): void
    {
        $this->createOwnerWithChildren(2);
        $this->_em->flush();
        $this->_em->clear();

        $iterable = $this->_em->getRepository(EagerFetchOwner::class)->createQueryBuilder('o')->getQuery()->toIterable();

        // There is only a single record, but use a foreach to ensure the iterator is marked as finished and the table lock is released
        foreach ($iterable as $owner) {
            $this->assertCount(2, $owner->children);
        }
    }

    protected function createOwnerWithChildren(int $children): EagerFetchOwner
    {
        $owner = new EagerFetchOwner();
        $this->_em->persist($owner);

        for ($i = 0; $i < $children; $i++) {
            $child        = new EagerFetchChild();
            $child->owner = $owner;

            $owner->children->add($child);

            $this->_em->persist($child);
        }

        return $owner;
    }
}

#[ORM\Entity]
class EagerFetchOwner
{
    /** @var int */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    public $id;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: EagerFetchChild::class, fetch: 'EAGER')]
    public Collection $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }
}

#[ORM\Entity]
class EagerFetchChild
{
    /** @var int */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    public $id;

    /** @var EagerFetchOwner */
    #[ORM\ManyToOne(targetEntity: EagerFetchOwner::class, inversedBy: 'children')]
    public $owner;
}
