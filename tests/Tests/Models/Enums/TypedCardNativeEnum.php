<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[Entity]
class TypedCardNativeEnum
{
    #[Id]
    #[GeneratedValue]
    #[Column]
    public int $id;

    #[Column(type: Types::ENUM)]
    public Suit $suit;
}
