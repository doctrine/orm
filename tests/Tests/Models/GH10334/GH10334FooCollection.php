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

/**
 * @Entity
 */
class GH10334FooCollection
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @OneToMany(targetEntity="GH10334Foo", mappedBy="collection", cascade={"persist", "remove"})
     * @var Collection<GH10334Foo> $foos
     */
    private $foos;

    public function __construct()
    {
        $this->foos = new ArrayCollection();
    }

    /**
     * @return Collection<GH10334Foo>
     */
    public function getFoos(): Collection
    {
        return $this->foos;
    }
}
