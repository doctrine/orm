<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH10334;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;

#[Entity]
class GH10334FooCollection
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    protected int $id;

    /** @var Collection<GH10334Foo> $foos */
    #[OneToMany(targetEntity: 'GH10334Foo', mappedBy: 'collection', cascade: ['persist', 'remove'])]
    private Collection $foos;

    public function __construct()
    {
        $this->foos = new ArrayCollection();
    }

    /** @return Collection<GH10334Foo> */
    public function getFoos(): Collection
    {
        return $this->foos;
    }
}
