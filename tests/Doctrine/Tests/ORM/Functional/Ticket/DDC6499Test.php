<?php

namespace Doctrine\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group #6499
 *
 *
 * Specifically, DDC6499B has a dependency on DDC6499A, and DDC6499A
 * has a dependency on DDC6499B. Since DDC6499A#b is not nullable,
 * the DDC6499B should be inserted first.
 */
class DDC6499Test extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->_schemaTool->createSchema([
            $this->_em->getClassMetadata(DDC6499A::class),
            $this->_em->getClassMetadata(DDC6499B::class),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown() : void
    {
        parent::tearDown();

        $this->_schemaTool->dropSchema([
            $this->_em->getClassMetadata(DDC6499A::class),
            $this->_em->getClassMetadata(DDC6499B::class),
        ]);
    }

    public function testIssue() : void
    {
        $b = new DDC6499B();
        $a = new DDC6499A($b);

        $this->_em->persist($a);
        $this->_em->persist($b);

        $this->_em->flush();

        self::assertInternalType('integer', $a->id);
        self::assertInternalType('integer', $b->id);
    }

    public function testIssueReversed() : void
    {
        $b = new DDC6499B();
        $a = new DDC6499A($b);

        $this->_em->persist($b);
        $this->_em->persist($a);

        $this->_em->flush();

        self::assertInternalType('integer', $a->id);
        self::assertInternalType('integer', $b->id);
    }
}

/** @Entity */
class DDC6499A
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @JoinColumn(nullable=false) @OneToOne(targetEntity=DDC6499B::class) */
    public $b;

    public function __construct(DDC6499B $b)
    {
        $this->b = $b;
    }
}

/** @Entity */
class DDC6499B
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @ManyToOne(targetEntity="DDC6499A") */
    private $a;
}
