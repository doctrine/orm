<?php

namespace Doctrine\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group 7180
 */
class DDC7180Test extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->_schemaTool->createSchema([
            $this->_em->getClassMetadata(DDC7180A::class),
            $this->_em->getClassMetadata(DDC7180B::class),
            $this->_em->getClassMetadata(DDC7180C::class),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown() : void
    {
        parent::tearDown();

        $this->_schemaTool->dropSchema([
            $this->_em->getClassMetadata(DDC7180A::class),
            $this->_em->getClassMetadata(DDC7180B::class),
            $this->_em->getClassMetadata(DDC7180C::class),
        ]);
    }

    public function testIssue() : void
    {
        $a = new DDC7180A();
        $b = new DDC7180B();
        $c = new DDC7180C();

        $a->b = $b;
        $b->a = $a;
        $c->a = $a;

        $this->_em->persist($a);
        $this->_em->persist($b);
        $this->_em->persist($c);

        $this->_em->flush();

        self::assertInternalType('integer', $a->id);
        self::assertInternalType('integer', $b->id);
        self::assertInternalType('integer', $c->id);
    }
}

/**
 * @Entity
 */
class DDC7180A
{
    /**
     * @GeneratedValue()
     * @Id @Column(type="integer")
     */
    public $id;
    /**
     * @OneToOne(targetEntity=DDC7180B::class, inversedBy="a")
     * @JoinColumn(nullable=false)
     */
    public $b;
}
/**
 * @Entity
 */
class DDC7180B
{
    /**
     * @GeneratedValue()
     * @Id @Column(type="integer")
     */
    public $id;
    /**
     * @OneToOne(targetEntity=DDC7180A::class, mappedBy="b")
     * @JoinColumn(nullable=true)
     */
    public $a;
}
/**
 * @Entity
 */
class DDC7180C
{
    /**
     * @GeneratedValue()
     * @Id @Column(type="integer")
     */
    public $id;
    /**
     * @ManyToOne(targetEntity=DDC7180A::class)
     * @JoinColumn(nullable=false)
     */
    public $a;
}
