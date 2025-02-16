<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\EagerFetchedCompositeOneToMany;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'eager_composite_join_root')]
class RootEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', nullable: false)]
    private int|null $id = null;

    #[ORM\Id]
    #[ORM\Column(type: 'string', nullable: false, name: 'other_key', length: 42)]
    private string $otherKey;

    /** @var Collection<int, SecondLevel> */
    #[ORM\OneToMany(mappedBy: 'root', targetEntity: SecondLevel::class, fetch: 'EAGER')]
    private Collection $secondLevel;

    public function __construct(int $id, string $other)
    {
        $this->otherKey    = $other;
        $this->secondLevel = new ArrayCollection();
        $this->id          = $id;
    }

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getOtherKey(): string
    {
        return $this->otherKey;
    }
}
