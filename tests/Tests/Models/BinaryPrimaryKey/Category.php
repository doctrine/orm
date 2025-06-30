<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\BinaryPrimaryKey;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ReadableCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;

#[Entity]
class Category
{
    #[Id]
    #[Column(type: BinaryIdType::NAME, nullable: false)]
    private BinaryId $id;

    #[Column]
    private string $name;

    #[ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    private self|null $parent;

    /** @var Collection<int, Category> */
    #[OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $children;

    public function __construct(
        string $name,
        self|null $parent = null,
    ) {
        $this->id       = BinaryId::new();
        $this->name     = $name;
        $this->parent   = $parent;
        $this->children = new ArrayCollection();

        $parent?->addChild($this);
    }

    public function getId(): BinaryId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParent(): self|null
    {
        return $this->parent;
    }

    /** @return ReadableCollection<int, Category> */
    public function getChildren(): ReadableCollection
    {
        return $this->children;
    }

    /** @internal */
    public function addChild(self $category): void
    {
        if (! $this->children->contains($category)) {
            $this->children->add($category);
        }
    }
}
