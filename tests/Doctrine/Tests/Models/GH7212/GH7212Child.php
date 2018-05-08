<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH7212;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;

/** @Entity */
class GH7212Child
{
    /**
     * @Column(type="integer")
     * @Id
     * @var int
     */
    private $id;

    /**
     * @ManyToOne(targetEntity=GH7212Parent::class, inversedBy="children")
     * @var GH7212Parent|null
     */
    private $parent;

    public function __construct(int $id, ?GH7212Parent $parent = null)
    {
        $this->id = $id;

        $this->setParent($parent);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getParent(): ?GH7212Parent
    {
        return $this->parent;
    }

    public function setParent(?GH7212Parent $parent): void
    {
        $this->parent = $parent;
    }
}
