<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH10348;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="gh10348_child_entities")
 */
#[ORM\Entity]
#[ORM\Table(name: 'gh10348_child_entities')]
class GH10348ChildEntity
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
     * @ORM\ManyToOne(targetEntity="GH10348ParentEntity", inversedBy="children")
     */
    #[ORM\ManyToOne(targetEntity: GH10348ParentEntity::class, inversedBy: 'children')]
    private ?GH10348ParentEntity $parent = null;

    /**
     * @ORM\ManyToOne(targetEntity="GH10348ChildEntity", cascade={"remove"})
     */
    #[ORM\ManyToOne(targetEntity: self::class, cascade: ['remove'])]
    private ?GH10348ChildEntity $origin = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getParent(): ?GH10348ParentEntity
    {
        return $this->parent;
    }

    public function setParent(?GH10348ParentEntity $parent): void
    {
        $this->parent = $parent;
    }

    public function getOrigin(): ?GH10348ChildEntity
    {
        return $this->origin;
    }

    public function setOrigin(?GH10348ChildEntity $origin): void
    {
        $this->origin = $origin;
    }
}
