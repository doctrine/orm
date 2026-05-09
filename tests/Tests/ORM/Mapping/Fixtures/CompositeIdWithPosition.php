<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping\Fixtures;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class CompositeIdWithPosition
{
    #[ORM\Id(position: 2)]
    #[ORM\Column(type: 'string')]
    public string $second = '';

    #[ORM\Id(position: 1)]
    #[ORM\Column(type: 'integer')]
    public int $first = 0;
}
