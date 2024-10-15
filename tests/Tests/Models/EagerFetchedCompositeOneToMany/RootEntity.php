<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\EagerFetchedCompositeOneToMany;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="eager_composite_join_root")
 */
class RootEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false)
     *
     * @var int|null
     */
    private $id = null;

    /**
     * @ORM\Id
     * @ORM\Column(type="string", nullable=false, name="other_key")
     *
     * @var string
     */
    private $otherKey;

    /**
     * @ORM\OneToMany(mappedBy="root", targetEntity=SecondLevel::class, fetch="EAGER")
     *
     * @var Collection<int, SecondLevel>
     */
    private $secondLevel;

    /**
     * @ORM\OneToMany(mappedBy="root", targetEntity=SecondLevelWithoutCompositePrimaryKey::class, fetch="EAGER")
     *
     * @var Collection<int, SecondLevelWithoutCompositePrimaryKey>
     */
    private $anotherSecondLevel;

    public function __construct(int $id, string $other)
    {
        $this->otherKey           = $other;
        $this->secondLevel        = new ArrayCollection();
        $this->anotherSecondLevel = new ArrayCollection();
        $this->id                 = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOtherKey(): string
    {
        return $this->otherKey;
    }
}
