<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\EagerFetchedCompositeOneToMany;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class SecondLevelWithoutCompositePrimaryKey
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false)
     *
     * @var int|null
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=RootEntity::class, inversedBy="anotherSecondLevel")
     * @ORM\JoinColumns({
     *      @ORM\JoinColumn(name="root_id", referencedColumnName="id"),
     *      @ORM\JoinColumn(name="root_other_key", referencedColumnName="other_key")
     *  })
     *
     * @var RootEntity
     */
    private $root;

    public function __construct(RootEntity $upper)
    {
        $this->root = $upper;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
