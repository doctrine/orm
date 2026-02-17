<?php

namespace Doctrine\Tests\Models\Issue12379;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
class Node
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Node::class, inversedBy: 'children')]
    public ?Node $parent = null;

    /** @var Collection<int, Node> */
    #[ORM\OneToMany(targetEntity: Node::class, mappedBy: 'parent')]
    public Collection $children;
}
