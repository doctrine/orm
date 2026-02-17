<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Issue12379;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Node
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int|null $id = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    public self|null $parent = null;

    /** @var Collection<int, Node> */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    public $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }
}
