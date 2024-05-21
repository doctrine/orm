<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH11163Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH11163Bucket::class,
            GH11163BucketItem::class,
        ]);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $conn = static::$sharedConn;
        $conn->executeStatement('DELETE FROM GH11163BucketItem');
        $conn->executeStatement('DELETE FROM GH11163Bucket');
    }

    public function testFetchEagerModeWithOrderBy(): void
    {
        // Load entities into database
        $this->_em->persist($bucket = new GH11163Bucket(11163));
        $this->_em->persist(new GH11163BucketItem(1, $bucket, 2));
        $this->_em->persist(new GH11163BucketItem(2, $bucket, 3));
        $this->_em->persist(new GH11163BucketItem(3, $bucket, 1));
        $this->_em->flush();
        $this->_em->clear();

        // Fetch entity from database
        $dql    = 'SELECT bucket FROM ' . GH11163Bucket::class . ' bucket WHERE bucket.id = :id';
        $bucket = $this->_em->createQuery($dql)
            ->setParameter('id', 11163)
            ->getSingleResult();

        // Assert associated entity is loaded eagerly
        static::assertInstanceOf(GH11163Bucket::class, $bucket);
        static::assertInstanceOf(PersistentCollection::class, $bucket->items);
        static::assertTrue($bucket->items->isInitialized());

        static::assertCount(3, $bucket->items);

        // Assert order of entities
        static::assertSame(1, $bucket->items[0]->position);
        static::assertSame(3, $bucket->items[0]->id);

        static::assertSame(2, $bucket->items[1]->position);
        static::assertSame(1, $bucket->items[1]->id);

        static::assertSame(3, $bucket->items[2]->position);
        static::assertSame(2, $bucket->items[2]->id);
    }
}

#[ORM\Entity]
class GH11163Bucket
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private int $id;

    /** @var Collection<int, GH11163BucketItem> */
    #[ORM\OneToMany(
        targetEntity: GH11163BucketItem::class,
        mappedBy: 'bucket',
        fetch: 'EAGER',
    )]
    #[ORM\OrderBy(['position' => 'ASC'])]
    public Collection $items;

    public function __construct(int $id)
    {
        $this->id    = $id;
        $this->items = new ArrayCollection();
    }
}

#[ORM\Entity]
class GH11163BucketItem
{
    #[ORM\ManyToOne(targetEntity: GH11163Bucket::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private GH11163Bucket $bucket;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\Column(type: 'integer')]
    public int $position;

    public function __construct(int $id, GH11163Bucket $bucket, int $position)
    {
        $this->id       = $id;
        $this->bucket   = $bucket;
        $this->position = $position;
    }
}
