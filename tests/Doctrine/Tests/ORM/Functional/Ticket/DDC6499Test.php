<?php

namespace Doctrine\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-6499
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

        $this->_schemaTool->dropSchema(
            [
                $this->_em->getClassMetadata(DDC6499A::class),
                $this->_em->getClassMetadata(DDC6499B::class),
            ]
        );
    }

    /**
     * Test for the bug described in issue #6499.
     */
    public function testIssue() : void
    {
        $a = new DDC6499A();
        $this->_em->persist($a);

        $b = new DDC6499B();
        $a->b = $b;
        $this->_em->persist($b);

        $this->_em->flush();
        $this->_em->clear();

        self::assertEquals($this->_em->find(DDC6499A::class, $a->id)->b->id, $b->id, "Issue #6499 will result in a Integrity constraint violation before reaching this point.");
    }

    /**
     * Test for the bug described in issue #6499 (reversed order).
     */
    public function testIssueReversed() : void
    {
        $a = new DDC6499A();

        $b = new DDC6499B();
        $a->b = $b;

        $this->_em->persist($b);
        $this->_em->persist($a);

        $this->_em->flush();
        $this->_em->clear();

        self::assertEquals($this->_em->find(DDC6499A::class, $a->id)->b->id, $b->id, "Issue #6499 will result in a Integrity constraint violation before reaching this point.");
    }
}

/** @Entity */
class DDC6499A
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     */
    public $id;

    /**
     * @OneToOne(targetEntity="DDC6499B")
     * @JoinColumn(nullable=false)
     */
    public $b;
}

/** @Entity */
class DDC6499B
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     */
    public $id;

    /**
     * @ManyToOne(targetEntity="DDC6499A", inversedBy="bs")
     */
    public $a;
}