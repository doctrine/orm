<?php

namespace Doctrine\Tests\Models\Issue12379;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Page
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\OneToOne(targetEntity: Node::class)]
    public ?Node $node = null;
}
