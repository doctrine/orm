<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH9192Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(GH9192A::class, GH9192B::class, GH9192C::class);
    }

    public function testIssue(): void
    {
        $a = new GH9192A();

        $b    = new GH9192B();
        $b->a = $a;
        $a->bs->add($b);

        $c    = new GH9192C();
        $c->b = $b;
        $b->cs->add($c);

        $a->c = $c;

        $this->_em->persist($a);
        $this->_em->persist($b);
        $this->_em->persist($c);
        $this->_em->flush();

        $this->expectNotToPerformAssertions();

        $this->_em->remove($a);
        $this->_em->flush();
    }
}

#[ORM\Entity]
class GH9192A
{
    /** @var int */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public $id;

    /** @var Collection<GH9192B> */
    #[ORM\OneToMany(mappedBy: 'a', targetEntity: GH9192B::class, cascade: ['remove'])]
    public $bs;

    /** @var GH9192C */
    #[ORM\OneToOne(targetEntity: GH9192C::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    public $c;

    public function __construct()
    {
        $this->bs = new ArrayCollection();
    }
}

#[ORM\Entity]
class GH9192B
{
    /** @var int */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public $id;

    /** @var Collection<GH9192C> */
    #[ORM\OneToMany(mappedBy: 'b', targetEntity: GH9192C::class, cascade: ['remove'])]
    public $cs;

    /** @var GH9192A */
    #[ORM\ManyToOne(inversedBy: 'bs', targetEntity: GH9192A::class)]
    public $a;

    public function __construct()
    {
        $this->cs = new ArrayCollection();
    }
}

#[ORM\Entity]
class GH9192C
{
    /** @var int */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public $id;

    /** @var GH9192B */
    #[ORM\ManyToOne(inversedBy: 'cs', targetEntity: GH9192B::class)]
    public $b;
}
