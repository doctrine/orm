<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('DDC-2575')]
class DDC2575Test extends OrmFunctionalTestCase
{
    /** @phpstan-var list<DDC2575Root> */
    private array $rootsEntities = [];

    /** @phpstan-var list<DDC2575A> */
    private array $aEntities = [];

    /** @phpstan-var list<DDC2575B> */
    private array $bEntities = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            DDC2575Root::class,
            DDC2575A::class,
            DDC2575B::class,
        );

        $entityRoot1 = new DDC2575Root(1);
        $entityB1    = new DDC2575B(2);
        $entityA1    = new DDC2575A($entityRoot1, $entityB1);

        $this->_em->persist($entityRoot1);
        $this->_em->persist($entityA1);
        $this->_em->persist($entityB1);

        $entityRoot2 = new DDC2575Root(3);
        $entityB2    = new DDC2575B(4);
        $entityA2    = new DDC2575A($entityRoot2, $entityB2);

        $this->_em->persist($entityRoot2);
        $this->_em->persist($entityA2);
        $this->_em->persist($entityB2);

        $this->_em->flush();

        $this->rootsEntities[] = $entityRoot1;
        $this->rootsEntities[] = $entityRoot2;

        $this->aEntities[] = $entityA1;
        $this->aEntities[] = $entityA2;

        $this->bEntities[] = $entityB1;
        $this->bEntities[] = $entityB2;

        $this->_em->clear();
    }

    public function testHydrationIssue(): void
    {
        $repository = $this->_em->getRepository(DDC2575Root::class);
        $qb         = $repository->createQueryBuilder('r')
            ->select('r, a, b')
            ->leftJoin('r.aRelation', 'a')
            ->leftJoin('a.bRelation', 'b');

        $query  = $qb->getQuery();
        $result = $query->getResult();

        self::assertCount(2, $result);

        $row = $result[0];
        self::assertNotNull($row->aRelation);
        self::assertEquals(1, $row->id);
        self::assertNotNull($row->aRelation->rootRelation);
        self::assertSame($row, $row->aRelation->rootRelation);
        self::assertNotNull($row->aRelation->bRelation);
        self::assertEquals(2, $row->aRelation->bRelation->id);

        $row = $result[1];
        self::assertNotNull($row->aRelation);
        self::assertEquals(3, $row->id);
        self::assertNotNull($row->aRelation->rootRelation);
        self::assertSame($row, $row->aRelation->rootRelation);
        self::assertNotNull($row->aRelation->bRelation);
        self::assertEquals(4, $row->aRelation->bRelation->id);
    }
}

#[Entity]
class DDC2575Root
{
    /** @var DDC2575A */
    #[OneToOne(targetEntity: 'DDC2575A', mappedBy: 'rootRelation')]
    public $aRelation;

    public function __construct(
        #[Id]
        #[Column(type: 'integer')]
        public int $id,
        #[Column(type: 'integer')]
        public int $sampleField = 0,
    ) {
    }
}

#[Entity]
class DDC2575A
{
    public function __construct(
        #[Id]
        #[OneToOne(targetEntity: 'DDC2575Root', inversedBy: 'aRelation')]
        #[JoinColumn(name: 'root_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
        public DDC2575Root $rootRelation,
        #[ManyToOne(targetEntity: 'DDC2575B')]
        #[JoinColumn(name: 'b_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
        public DDC2575B $bRelation,
    ) {
    }
}

#[Entity]
class DDC2575B
{
    public function __construct(
        #[Id]
        #[Column(type: 'integer')]
        public int $id,
        #[Column(type: 'integer')]
        public int $sampleField = 0,
    ) {
    }
}
