<?php

declare(strict_types=1);

namespace Doctrine\Tests_PHP81\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Tests\Models\Enums\Suit;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH10132Test extends OrmFunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            Complex::class,
            ComplexChild::class
        );
    }

    public function testQueryBackedEnumInCompositeKeyJoin(): void
    {
        $complex = new Complex();
        $complex->setType(Suit::Clubs);

        $complexChild = new ComplexChild();
        $complexChild->setComplex($complex);

        $this->_em->persist($complex);
        $this->_em->persist($complexChild);
        $this->_em->flush();
        $this->_em->clear();

        $qb = $this->_em->createQueryBuilder();
        $qb->select('s')
            ->from(ComplexChild::class, 's')
            ->where('s.complexType = :complexType');

        $qb->setParameter('complexType', Suit::Clubs);

        self::assertNotNull($qb->getQuery()->getOneOrNullResult());
    }
}

/** @Entity */
class Complex
{
    /**
     * @Id
     * @Column(type = "string", enumType = Suit::class)
     */
    protected Suit $type;

    /** @OneToMany(targetEntity = ComplexChild::class, mappedBy = "complex", cascade = {"persist"}) */
    protected Collection $complexChildren;

    public function __construct()
    {
        $this->complexChildren = new ArrayCollection();
    }

    public function getType(): Suit
    {
        return $this->type;
    }

    public function setType(Suit $type): void
    {
        $this->type = $type;
    }

    public function getComplexChildren(): Collection
    {
        return $this->complexChildren;
    }

    public function addComplexChild(ComplexChild $complexChild): void
    {
        $this->complexChildren->add($complexChild);
    }
}

/** @Entity */
class ComplexChild
{
    /**
     * @ManyToOne(targetEntity = Complex::class, inversedBy = "complexChildren")
     * @JoinColumn(name = "complexType", referencedColumnName = "type", nullable = false)
     */
    protected Complex $complex;

    /**
     * @Id
     * @Column(type = "string", enumType = Suit::class)
     */
    protected Suit $complexType;

    public function setComplex(Complex $complex): void
    {
        $complex->addComplexChild($this);
        $this->complexType = $complex->getType();
        $this->complex     = $complex;
    }

    public function getComplexType(): Suit
    {
        return $this->complexType;
    }

    public function getComplex(): Complex
    {
        return $this->complex;
    }
}
