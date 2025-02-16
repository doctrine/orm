<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[Entity]
class CardNativeEnum
{
    /** @var int|null */
    #[Id]
    #[GeneratedValue]
    #[Column(type: Types::INTEGER)]
    public $id;

    /** @var Suit */
    #[Column(type: Types::ENUM, enumType: Suit::class, options: ['values' => ['H', 'D', 'C', 'S', 'Z']])]
    public $suit;
}
