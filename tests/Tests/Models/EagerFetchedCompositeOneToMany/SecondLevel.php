<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\EagerFetchedCompositeOneToMany;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="eager_composite_join_second_level")
 */
class SecondLevel
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
     * @ORM\Column(type="integer", nullable=false)
     *
     * @var int|null
     */
    private $upperId;

    /**
     * @ORM\Id
     * @ORM\Column(type="string", nullable=false, name="other_key")
     *
     * @var string
     */
    private $otherKey;

    /**
     * @ORM\ManyToOne(targetEntity=RootEntity::class, inversedBy="secondLevel")
     * @ORM\JoinColumns({
     *      @ORM\JoinColumn(name="other_key", referencedColumnName="other_key"),
     *      @ORM\JoinColumn(name="upper_id", referencedColumnName="id")
     *  })
     *
     * @var RootEntity
     */
    private $root;

    public function __construct(int $id, RootEntity $upper)
    {
        $this->id       = $id;
        $this->upperId  = $upper->getId();
        $this->otherKey = $upper->getOtherKey();
        $this->root     = $upper;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
