<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH10348;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="gh10348_parent_entities")
 */
#[ORM\Entity]
#[ORM\Table(name: 'gh10348_parent_entities')]
class GH10348ParentEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    /**
     * @ORM\OneToMany(targetEntity="GH10348ChildEntity", mappedBy="parent", cascade={"persist", "remove"})
     */
    #[ORM\OneToMany(targetEntity: GH10348ChildEntity::class, mappedBy: 'parent', cascade: ['persist', 'remove'])]
    private Collection $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function addChild(GH10348ChildEntity $childEntity): self
    {
        $childEntity->setParent($this);
        $this->children->add($childEntity);

        return $this;
    }
}
