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
    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(
            [
            $this->_em->getClassMetadata(DDC6499A::class),
            $this->_em->getClassMetadata(DDC6499B::class),
            ]
        );
    }

    /**
     * Test for the bug described in issue #6499.
     */
    public function testIssue()
    {
        $a = new DDC6499A();
        $this->_em->persist($a);

        $b = new DDC6499B();
        $a->setB($b);
        $this->_em->persist($b);

        $this->_em->flush();

        // Issue #6499 will result in a Integrity constraint violation before reaching this point
        $this->assertEquals(true, true);
    }
}

/** @Entity */
class DDC6499A
{
    /**
     * @Id()
     * @GeneratedValue(strategy="AUTO")
     * @Column(name="id", type="integer")
     */
    private $id;

    /**
     * @OneToMany(targetEntity="DDC6499B", mappedBy="a", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $bs;

    /**
     * @OneToOne(targetEntity="DDC6499B", cascade={"persist"})
     * @JoinColumn(nullable=false)
     */
    private $b;

    /**
     * DDC6499A constructor.
     */
    public function __construct()
    {
        $this->bs = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return DDC6499B[]|ArrayCollection
     */
    public function getBs()
    {
        return $this->bs;
    }

    /**
     * @param DDC6499B $b
     */
    public function addB(DDC6499B $b)
    {
        if ($this->bs->contains($b)) return;

        $this->bs->add($b);

        // Update owning side
        $b->setA($this);
    }

    /**
     * @param DDC6499B $b
     */
    public function removeB(DDC6499B $b)
    {
        if (!$this->bs->contains($b)) return;

        $this->bs->removeElement($b);

        // Not updating owning side due to orphan removal
    }

    /**
     * @return DDC6499B
     */
    public function getB()
    {
        return $this->b;
    }

    /**
     * @param DDC6499B $b
     */
    public function setB(DDC6499B $b)
    {
        $this->b = $b;
    }
}

/** @Entity */
class DDC6499B
{
    /**
     * @Id()
     * @GeneratedValue(strategy="AUTO")
     * @Column(name="id", type="integer")
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="DDC6499A", inversedBy="bs", cascade={"persist"})
     */
    private $a;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return DDC6499A
     */
    public function getA()
    {
        return $this->a;
    }

    /**
     * @param DDC6499A $a
     */
    public function setA(DDC6499A $a)
    {
        $this->a = $a;
    }
}