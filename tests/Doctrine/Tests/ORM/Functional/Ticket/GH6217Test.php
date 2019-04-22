<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use function uniqid;

/**
 * @group #6217
 */
final class GH6217Test extends OrmFunctionalTestCase
{
    public function setUp() : void
    {
        $this->enableSecondLevelCache();

        parent::setUp();

        $this->schemaTool->createSchema([
            $this->em->getClassMetadata(GH6217AssociatedEntity::class),
            $this->em->getClassMetadata(GH6217FetchedEntity::class),
        ]);
    }

    public function testLoadingOfSecondLevelCacheOnEagerAssociations() : void
    {
        $lazy    = new GH6217AssociatedEntity();
        $eager   = new GH6217AssociatedEntity();
        $fetched = new GH6217FetchedEntity($lazy, $eager);

        $this->em->persist($eager);
        $this->em->persist($lazy);
        $this->em->persist($fetched);
        $this->em->flush();
        $this->em->clear();

        $repository = $this->em->getRepository(GH6217FetchedEntity::class);
        $filters    = ['eager' => $eager->id];

        self::assertCount(1, $repository->findBy($filters));
        $queryCount = $this->getCurrentQueryCount();

        /** @var GH6217FetchedEntity[] $found */
        $found = $repository->findBy($filters);

        self::assertCount(1, $found);
        self::assertInstanceOf(GH6217FetchedEntity::class, $found[0]);
        self::assertSame($lazy->id, $found[0]->lazy->id);
        self::assertSame($eager->id, $found[0]->eager->id);
        self::assertEquals($queryCount, $this->getCurrentQueryCount(), 'No queries were executed in `findBy`');
    }
}

/** @ORM\Entity @ORM\Cache(usage="NONSTRICT_READ_WRITE") */
class GH6217AssociatedEntity
{
    /** @ORM\Id @ORM\Column(type="string") @ORM\GeneratedValue(strategy="NONE") */
    public $id;

    public function __construct()
    {
        $this->id = uniqid(self::class, true);
    }
}

/** @ORM\Entity @ORM\Cache(usage="NONSTRICT_READ_WRITE") */
class GH6217FetchedEntity
{
    /** @ORM\Id @ORM\Cache("NONSTRICT_READ_WRITE") @ORM\ManyToOne(targetEntity=GH6217AssociatedEntity::class) */
    public $lazy;

    /** @ORM\Id @ORM\Cache("NONSTRICT_READ_WRITE") @ORM\ManyToOne(targetEntity=GH6217AssociatedEntity::class, fetch="EAGER") */
    public $eager;

    public function __construct(GH6217AssociatedEntity $lazy, GH6217AssociatedEntity $eager)
    {
        $this->lazy  = $lazy;
        $this->eager = $eager;
    }
}
