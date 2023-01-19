<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH10132;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\Tests\Models\Enums\Suit;

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
