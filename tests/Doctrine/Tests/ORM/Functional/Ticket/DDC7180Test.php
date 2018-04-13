<?php

namespace Doctrine\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group 7180
 */
class DDC7180Test extends OrmFunctionalTestCase
{
    private static $createdSchema = false;

    /**
     * {@inheritDoc}
     */
    protected function setUp() : void
    {
        parent::setUp();

        if (self::$createdSchema) {
            return;
        }

        $this->_schemaTool->createSchema([
            $this->_em->getClassMetadata(DDC7180A::class),
            $this->_em->getClassMetadata(DDC7180B::class),
            $this->_em->getClassMetadata(DDC7180C::class),
            $this->_em->getClassMetadata(DDC7180D::class),
            $this->_em->getClassMetadata(DDC7180E::class),
            $this->_em->getClassMetadata(DDC7180F::class),
            $this->_em->getClassMetadata(DDC7180G::class),
        ]);

        self::$createdSchema = true;
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

    public function testIssue3NodeCycle() : void
    {
        $d = new DDC7180D();
        $e = new DDC7180E();
        $f = new DDC7180F();
        $g = new DDC7180G();

        $d->e = $e;
        $e->f = $f;
        $f->d = $d;
        $g->d = $d;

        $this->_em->persist($d);
        $this->_em->persist($e);
        $this->_em->persist($f);
        $this->_em->persist($g);

        $this->_em->flush();

        self::assertInternalType('integer', $d->id);
        self::assertInternalType('integer', $e->id);
        self::assertInternalType('integer', $f->id);
        self::assertInternalType('integer', $g->id);
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

/**
 * @Entity
 */
class DDC7180D
{
    /**
     * @GeneratedValue()
     * @Id @Column(type="integer")
     */
    public $id;
    /**
     * @OneToOne(targetEntity=DDC7180E::class)
     * @JoinColumn(nullable=false)
     */
    public $e;
}

/**
 * @Entity
 */
class DDC7180E
{
    /**
     * @GeneratedValue()
     * @Id @Column(type="integer")
     */
    public $id;
    /**
     * @OneToOne(targetEntity=DDC7180F::class)
     * @JoinColumn(nullable=false)
     */
    public $f;
}

/**
 * @Entity
 */
class DDC7180F
{
    /**
     * @GeneratedValue()
     * @Id @Column(type="integer")
     */
    public $id;
    /**
     * @ManyToOne(targetEntity=DDC7180D::class)
     * @JoinColumn(nullable=true)
     */
    public $d;
}

/**
 * @Entity
 */
class DDC7180G
{
    /**
     * @GeneratedValue()
     * @Id @Column(type="integer")
     */
    public $id;
    /**
     * @ManyToOne(targetEntity=DDC7180D::class)
     * @JoinColumn(nullable=false)
     */
    public $d;
}
