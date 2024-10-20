<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH10049;

use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class ReadOnlyPropertyOwner
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'integer')]
        public readonly int $id,
    ) {
    }
}
