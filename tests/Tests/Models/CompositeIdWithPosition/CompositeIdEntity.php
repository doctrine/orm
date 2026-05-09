<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CompositeIdWithPosition;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class CompositeIdEntity extends CompositeIdMappedSuperclass
{
    #[ORM\Id(position: 3)]
    #[ORM\Column(type: 'string')]
    public string $third = '';
}
