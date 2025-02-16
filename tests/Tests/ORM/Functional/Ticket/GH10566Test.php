<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;

use function is_a;

class GH10566Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            GH10566A::class,
            GH10566B::class,
            GH10566C::class,
        );
    }

    #[DataProvider('provideEntityClasses')]
    public function testInsertion(string $startEntityClass): void
    {
        $a = new GH10566A();
        $b = new GH10566B();
        $c = new GH10566C();

        $a->other = $b;
        $b->other = $c;
        $c->other = $a;

        foreach ([$a, $b, $c] as $candidate) {
            if (is_a($candidate, $startEntityClass)) {
                $this->_em->persist($candidate);
            }
        }

        // Since all associations are nullable, the ORM has no problem finding an insert order,
        // it can always schedule "deferred updates" to fill missing foreign key values.
        $this->_em->flush();

        self::assertNotNull($a->id);
        self::assertNotNull($b->id);
        self::assertNotNull($c->id);
    }

    #[DataProvider('provideEntityClasses')]
    public function testRemoval(string $startEntityClass): void
    {
        $a = new GH10566A();
        $b = new GH10566B();
        $c = new GH10566C();

        $a->other = $b;
        $b->other = $c;
        $c->other = $a;

        $this->_em->persist($a);
        $this->_em->flush();

        $aId = $a->id;
        $bId = $b->id;
        $cId = $c->id;

        // In the removal case, the ORM currently does not schedule "extra updates"
        // to break association cycles before entities are removed. So, we must not
        // look at "nullable" for associations to find a delete commit order.
        //
        // To make it work, the user needs to have a database-level "ON DELETE SET NULL"
        // on an association. That's where the cycle can be broken. Commit order computation
        // for the removal case needs to look at this property.
        //
        // In this example, only A -> B can be used to break the cycle. So, regardless which
        // entity we start with, the ORM-level cascade will always remove all three entities,
        // and the order of database deletes always has to be (can only be) from B, then C, then A.

        foreach ([$a, $b, $c] as $candidate) {
            if (is_a($candidate, $startEntityClass)) {
                $this->_em->remove($candidate);
            }
        }

        $this->_em->flush();

        self::assertFalse($this->_em->getConnection()->fetchOne('SELECT id FROM gh10566_a WHERE id = ?', [$aId]));
        self::assertFalse($this->_em->getConnection()->fetchOne('SELECT id FROM gh10566_b WHERE id = ?', [$bId]));
        self::assertFalse($this->_em->getConnection()->fetchOne('SELECT id FROM gh10566_c WHERE id = ?', [$cId]));
    }

    /** @return Generator<array{class-string}> */
    public static function provideEntityClasses(): Generator
    {
        yield [GH10566A::class];
        yield [GH10566B::class];
        yield [GH10566C::class];
    }
}

#[ORM\Entity]
#[ORM\Table(name: 'gh10566_a')]
class GH10566A
{
    /** @var int */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public $id;

    /** @var GH10566B */
    #[ORM\OneToOne(targetEntity: GH10566B::class, cascade: ['all'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    public $other;
}

#[ORM\Entity]
#[ORM\Table(name: 'gh10566_b')]
class GH10566B
{
    /** @var int */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public $id;

    /** @var GH10566C */
    #[ORM\OneToOne(targetEntity: GH10566C::class, cascade: ['all'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    public $other;
}

#[ORM\Entity]
#[ORM\Table(name: 'gh10566_c')]
class GH10566C
{
    /** @var int */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public $id;

    /** @var GH10566A */
    #[ORM\OneToOne(targetEntity: GH10566A::class, cascade: ['all'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    public $other;
}
