<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group GH6499
 *
 * Specifically, GH6499B has a dependency on GH6499A, and GH6499A
 * has a dependency on GH6499B. Since GH6499A#b is not nullable,
 * the database row for GH6499B should be inserted first.
 */
class GH6499Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(GH6499A::class, GH6499B::class);
    }

    public function testIssue(): void
    {
        $b = new GH6499B();
        $a = new GH6499A();

        $this->_em->persist($a);

        $a->b = $b;

        $this->_em->persist($b);

        $this->_em->flush();

        self::assertIsInt($a->id);
        self::assertIsInt($b->id);
    }

    public function testIssueReversed(): void
    {
        $b = new GH6499B();
        $a = new GH6499A();

        $a->b = $b;

        $this->_em->persist($b);
        $this->_em->persist($a);

        $this->_em->flush();

        self::assertIsInt($a->id);
        self::assertIsInt($b->id);
    }
}

/**
 * @ORM\Entity
 */
class GH6499A
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\OneToOne(targetEntity=GH6499B::class)
     *
     * @var GH6499B
     */
    public $b;
}

/**
 * @ORM\Entity
 */
class GH6499B
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity=GH6499A::class)
     *
     * @var GH6499A
     */
    private $a;
}
