<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CompositeIdWithPosition;

use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
class CompositeIdMappedSuperclass
{
    #[ORM\Id(position: 2)]
    #[ORM\Column(type: 'string')]
    public string $second = '';

    #[ORM\Id(position: 1)]
    #[ORM\Column(type: 'integer')]
    public int $first = 0;
}
