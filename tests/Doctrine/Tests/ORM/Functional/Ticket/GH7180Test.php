<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests suggested in https://github.com/doctrine/orm/pull/7180#issuecomment-380841413 and
 * https://github.com/doctrine/orm/pull/7180#issuecomment-381067448.
 */
#[Group('GH7180')]
final class GH7180Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([GH7180A::class, GH7180B::class, GH7180C::class, GH7180D::class, GH7180E::class, GH7180F::class, GH7180G::class]);
    }

    public function testIssue(): void
    {
        $a = new GH7180A();
        $b = new GH7180B();
        $c = new GH7180C();

        $a->b = $b;
        $b->a = $a;
        $c->a = $a;

        $this->_em->persist($a);
        $this->_em->persist($b);
        $this->_em->persist($c);

        $this->_em->flush();

        self::assertIsInt($a->id);
        self::assertIsInt($b->id);
        self::assertIsInt($c->id);
    }

    public function testIssue3NodeCycle(): void
    {
        $d = new GH7180D();
        $e = new GH7180E();
        $f = new GH7180F();
        $g = new GH7180G();

        $d->e = $e;
        $e->f = $f;
        $f->d = $d;
        $g->d = $d;

        $this->_em->persist($d);
        $this->_em->persist($e);
        $this->_em->persist($f);
        $this->_em->persist($g);

        $this->_em->flush();

        self::assertIsInt($d->id);
        self::assertIsInt($e->id);
        self::assertIsInt($f->id);
        self::assertIsInt($g->id);
    }
}

#[Entity]
class GH7180A
{
    /** @var int */
    #[GeneratedValue]
    #[Id]
    #[Column(type: 'integer')]
    public $id;

    /** @var GH7180B */
    #[OneToOne(targetEntity: GH7180B::class, inversedBy: 'a')]
    #[JoinColumn(nullable: false)]
    public $b;
}

#[Entity]
class GH7180B
{
    /** @var int */
    #[GeneratedValue]
    #[Id]
    #[Column(type: 'integer')]
    public $id;

    /** @var GH7180A */
    #[OneToOne(targetEntity: GH7180A::class, mappedBy: 'b')]
    public $a;
}

#[Entity]
class GH7180C
{
    /** @var int */
    #[GeneratedValue]
    #[Id]
    #[Column(type: 'integer')]
    public $id;

    /** @var GH7180A */
    #[ManyToOne(targetEntity: GH7180A::class)]
    #[JoinColumn(nullable: false)]
    public $a;
}

#[Entity]
class GH7180D
{
    /** @var int */
    #[GeneratedValue]
    #[Id]
    #[Column(type: 'integer')]
    public $id;

    /** @var GH7180E */
    #[OneToOne(targetEntity: GH7180E::class)]
    #[JoinColumn(nullable: false)]
    public $e;
}

#[Entity]
class GH7180E
{
    /** @var int */
    #[GeneratedValue]
    #[Id]
    #[Column(type: 'integer')]
    public $id;

    /** @var GH7180F */
    #[OneToOne(targetEntity: GH7180F::class)]
    #[JoinColumn(nullable: false)]
    public $f;
}

#[Entity]
class GH7180F
{
    /** @var int */
    #[GeneratedValue]
    #[Id]
    #[Column(type: 'integer')]
    public $id;

    /** @var GH7180D */
    #[ManyToOne(targetEntity: GH7180D::class)]
    #[JoinColumn(nullable: true)]
    public $d;
}

#[Entity]
class GH7180G
{
    /** @var int */
    #[GeneratedValue]
    #[Id]
    #[Column(type: 'integer')]
    public $id;

    /** @var GH7180D */
    #[ManyToOne(targetEntity: GH7180D::class)]
    #[JoinColumn(nullable: false)]
    public $d;
}
