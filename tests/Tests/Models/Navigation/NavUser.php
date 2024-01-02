<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Navigation;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'navigation_users')]
#[Entity]
class NavUser
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    private int $id;

    public function __construct(
        #[Column(type: 'string', length: 255)]
        private string $name,
    ) {
    }
}
