<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH10132;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\Tests\Models\Enums\Suit;

#[Entity]
class ComplexChild
{
    #[ManyToOne(inversedBy: 'complexChildren')]
    #[JoinColumn(name: 'complexType', referencedColumnName: 'type', nullable: false)]
    protected Complex $complex;

    #[Id]
    #[Column(type: 'string', enumType: Suit::class)]
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
