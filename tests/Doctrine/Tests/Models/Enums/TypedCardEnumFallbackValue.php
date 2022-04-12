<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[Entity]
class TypedCardEnumFallbackValue
{
    #[Id, GeneratedValue, Column]
    public int $id;

    #[Column(enumType: Suit::class, enumFallbackValue: Suit::Spades)]
    public Suit $suit;

    #[Column(enumType: Suit::class, enumFallbackValue: null)]
    public ?Suit $suitDefaultNull;

    #[Column(nullable: true, enumType: Suit::class, enumFallbackValue: null)]
    public ?Suit $suitDefaultNullNullable;

    #[Column(nullable: true, enumType: Suit::class, enumFallbackValue: Suit::Spades)]
    public ?Suit $suitDefaultNotNullNullable;
}
