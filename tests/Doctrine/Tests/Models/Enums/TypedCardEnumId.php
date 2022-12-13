<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;

#[Entity]
class TypedCardEnumId
{
    #[Id]
    #[Column(type: 'string', enumType: Suit::class)]
    public Suit $suit;
}
