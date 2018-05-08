<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH7212;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;

/** @Entity */
class GH7212Parent
{
    /**
     * @Column(type="integer")
     * @Id
     * @var int
     */
    private $id;

    /**
     * @OneToMany(targetEntity=GH7212Child::class, mappedBy="parent", indexBy="id")
     * @var Collection<int, GH7212Child>
     */
    private $children;

    public function __construct(int $id)
    {
        $this->id       = $id;
        $this->children = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function addChild(GH7212Child $child): void
    {
        $this->children->set($child->getId(), $child);
    }

    public function removeChild(GH7212Child $child): void
    {
        $this->children->remove($child->getId());
    }

    /**
     * @return Collection<int, GH7212Child>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }
}
