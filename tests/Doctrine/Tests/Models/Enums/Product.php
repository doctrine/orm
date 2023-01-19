<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embedded;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[Entity]
class Product
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    public int $id;

    #[Embedded(class: Quantity::class)]
    public Quantity $quantity;
}
