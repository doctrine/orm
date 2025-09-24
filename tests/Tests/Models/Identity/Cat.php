<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Identity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[Entity]
class Cat
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'IDENTITY')]
    public int $id;

    #[Column(type: 'string')]
    public string $name;
}
