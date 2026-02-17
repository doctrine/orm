<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Issue12379;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Page
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int|null $id = null;

    #[ORM\OneToOne(targetEntity: Node::class)]
    public Node|null $node = null;
}
