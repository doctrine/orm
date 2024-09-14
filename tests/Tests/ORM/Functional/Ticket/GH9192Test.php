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

/**
 * @ORM\Entity
 */
class GH9192A
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\OneToMany(targetEntity="GH9192B", mappedBy="a", cascade={"remove"})
     *
     * @var Collection<GH9192B>
     */
    public $bs;

    /**
     * @ORM\OneToOne(targetEntity="GH9192C")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     *
     * @var GH9192C
     */
    public $c;

    public function __construct()
    {
        $this->bs = new ArrayCollection();
    }
}

/**
 * @ORM\Entity
 */
class GH9192B
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\OneToMany(targetEntity="GH9192C", mappedBy="b", cascade={"remove"})
     *
     * @var Collection<GH9192C>
     */
    public $cs;

    /**
     * @ORM\ManyToOne(targetEntity="GH9192A", inversedBy="bs")
     *
     * @var GH9192A
     */
    public $a;

    public function __construct()
    {
        $this->cs = new ArrayCollection();
    }
}

/**
 * @ORM\Entity
 */
class GH9192C
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="GH9192B", inversedBy="cs")
     *
     * @var GH9192B
     */
    public $b;
}
