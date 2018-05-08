<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH7212;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class GH7212Child
{
    /** @var int */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    private $id;

    /** @var GH7212Parent|null */
    #[ORM\ManyToOne(targetEntity: GH7212Parent::class, inversedBy: 'children')]
    private $parent;

    public function __construct(int $id, GH7212Parent|null $parent = null)
    {
        $this->id = $id;

        $this->setParent($parent);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getParent(): GH7212Parent|null
    {
        return $this->parent;
    }

    public function setParent(GH7212Parent|null $parent): void
    {
        $this->parent = $parent;
    }
}
